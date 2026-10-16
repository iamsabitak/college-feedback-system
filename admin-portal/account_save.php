<?php
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/accounts.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['view']) ? (int) $_GET['view'] : (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User id is required.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    unset($user['password_hash']);
    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$payload = $_POST;
if (empty($payload)) {
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
}

$existingId = isset($payload['id']) && $payload['id'] !== '' ? (int) $payload['id'] : null;
$validation = account_validate_payload($pdo, $payload, $existingId);
if (!empty($validation['errors'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $validation['errors']]);
    exit;
}

$role = $validation['clean']['role'];
$fullName = $validation['clean']['full_name'];
$email = $validation['clean']['email'];
$username = $validation['clean']['username'];
$studentId = $validation['clean']['student_id'];
$staffId = $validation['clean']['staff_id'];
$now = date('Y-m-d H:i:s');

try {
    if ($existingId) {
        $pdo->beginTransaction();
        $currentStmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
        $currentStmt->execute([':id' => $existingId]);
        $currentUser = $currentStmt->fetch();
        if (!$currentUser) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE users SET full_name = :full_name, username = :username, email = :email, student_id = :student_id, staff_id = :staff_id, role = :role, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            ':full_name' => $fullName,
            ':username' => $username,
            ':email' => $email,
            ':student_id' => $studentId !== '' ? $studentId : null,
            ':staff_id' => $staffId !== '' ? $staffId : null,
            ':role' => $role,
            ':id' => $existingId,
        ]);

        $portalStmt = $pdo->prepare('SELECT id FROM portal_users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $portalStmt->execute([':email' => $currentUser['email']]);
        $portalUserId = $portalStmt->fetchColumn();
        if ($portalUserId) {
            $pdo->prepare('UPDATE portal_users SET role = :role, username = :username, full_name = :full_name, email = :email, status = :status WHERE id = :id')
                ->execute([
                    ':role' => $role,
                    ':username' => $username,
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':status' => $currentUser['status'],
                    ':id' => $portalUserId,
                ]);
        } else {
            $pdo->prepare('INSERT INTO portal_users (role, username, full_name, email, password_hash, status) VALUES (:role, :username, :full_name, :email, :password_hash, :status)')
                ->execute([
                    ':role' => $role,
                    ':username' => $username,
                    ':full_name' => $fullName,
                    ':email' => $email,
                    ':password_hash' => $currentUser['password_hash'],
                    ':status' => $currentUser['status'],
                ]);
        }

        $target = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $target->execute([':id' => $existingId]);
        $user = $target->fetch();
        account_write_audit_log($pdo, 'admin', (int) $_SESSION['admin_id'], 'updated_user', (int) $existingId, account_snap_label($user), account_audit_changes($currentUser, $user));
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Account updated successfully.', 'user' => $user]);
        exit;
    }

    $tempPassword = account_temp_password();
    $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, username, email, student_id, staff_id, role, password_hash, status, must_change_password, created_at, updated_at)
         VALUES (:full_name, :username, :email, :student_id, :staff_id, :role, :password_hash, :status, 1, :created_at, :updated_at)'
    );
    $stmt->execute([
        ':full_name' => $fullName,
        ':username' => $username,
        ':email' => $email,
        ':student_id' => $studentId !== '' ? $studentId : null,
        ':staff_id' => $staffId !== '' ? $staffId : null,
        ':role' => $role,
        ':password_hash' => $passwordHash,
        ':status' => 'active',
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);

    $newUserId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO portal_users (role, username, full_name, email, password_hash, status) VALUES (:role, :username, :full_name, :email, :password_hash, :status)')
        ->execute([
            ':role' => $role,
            ':username' => $username,
            ':full_name' => $fullName,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':status' => 'active',
        ]);
    $user = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $user->execute([':id' => $newUserId]);
    $row = $user->fetch();
    account_write_audit_log($pdo, 'admin', (int) $_SESSION['admin_id'], 'created_user', $newUserId, account_snap_label($row), account_audit_lifecycle_details('Created', $row));
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully.',
        'password' => $tempPassword,
        'user' => $row,
    ]);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to save the account right now.']);
    error_log('Account save failed: ' . $e->getMessage());
    exit;
}
