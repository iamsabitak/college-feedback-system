<?php
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/accounts.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$action = trim((string) ($_POST['action'] ?? ''));
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0 || !in_array($action, ['reset_password', 'disable', 'enable', 'delete'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$userStmt->execute([':id' => $id]);
$user = $userStmt->fetch();
if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

try {
    $portalStmt = $pdo->prepare('SELECT id FROM portal_users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
    $portalStmt->execute([':email' => $user['email']]);
    $portalUserId = $portalStmt->fetchColumn();

    switch ($action) {
        case 'reset_password':
            $tempPassword = account_temp_password();
            $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = :hash, must_change_password = 1, status = :status WHERE id = :id')
                ->execute([
                    ':hash' => $passwordHash,
                    ':status' => 'active',
                    ':id' => $id,
                ]);
            if ($portalUserId) {
                $pdo->prepare('UPDATE portal_users SET password_hash = :hash, status = :status WHERE id = :id')
                    ->execute([':hash' => $passwordHash, ':status' => 'active', ':id' => $portalUserId]);
            }
            account_write_audit_log($pdo, 'admin', (int) $_SESSION['admin_id'], 'reset_password', $id, account_snap_label($user), 'temporary password reset');
            echo json_encode(['success' => true, 'message' => 'Password reset successfully.', 'password' => $tempPassword]);
            break;

        case 'disable':
            $pdo->prepare('UPDATE users SET status = :status WHERE id = :id')->execute([':status' => 'disabled', ':id' => $id]);
            if ($portalUserId) {
                $pdo->prepare('UPDATE portal_users SET status = :status WHERE id = :id')->execute([':status' => 'disabled', ':id' => $portalUserId]);
            }
            account_write_audit_log($pdo, 'admin', (int) $_SESSION['admin_id'], 'disabled_user', $id, account_snap_label($user), 'account disabled');
            echo json_encode(['success' => true, 'message' => 'Account disabled.']);
            break;

        case 'enable':
            $pdo->prepare('UPDATE users SET status = :status WHERE id = :id')->execute([':status' => 'active', ':id' => $id]);
            if ($portalUserId) {
                $pdo->prepare('UPDATE portal_users SET status = :status WHERE id = :id')->execute([':status' => 'active', ':id' => $portalUserId]);
            }
            account_write_audit_log($pdo, 'admin', (int) $_SESSION['admin_id'], 'enabled_user', $id, account_snap_label($user), 'account enabled');
            echo json_encode(['success' => true, 'message' => 'Account enabled.']);
            break;

        case 'delete':
            account_write_audit_log($pdo, 'admin', (int) $_SESSION['admin_id'], 'deleted_user', $id, account_snap_label($user), account_audit_lifecycle_details('Deleted', $user));
            if ($portalUserId) {
                $pdo->prepare('DELETE FROM portal_users WHERE id = :id')->execute([':id' => $portalUserId]);
            }
            $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Account deleted.']);
            break;
    }
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Action could not be completed.']);
    error_log('Account action failed: ' . $e->getMessage());
    exit;
}
