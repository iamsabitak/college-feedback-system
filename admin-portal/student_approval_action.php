<?php
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/accounts.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['student_approval_csrf']) || !hash_equals($_SESSION['student_approval_csrf'], $token)) {
    http_response_code(403);
    exit('Invalid request token.');
}

$studentId = (int) ($_POST['student_id'] ?? 0);
$decision = (string) ($_POST['decision'] ?? '');
if ($studentId <= 0 || !in_array($decision, ['approve', 'reject'], true)) {
    http_response_code(400);
    exit('Invalid approval request.');
}

$approvalStatus = $decision === 'approve' ? 'approved' : 'rejected';
try {
    $pdo->beginTransaction();
    $studentStmt = $pdo->prepare(
        'SELECT id, email, full_name, username FROM users
         WHERE id = :id AND role = \'student\' AND created_by_staff_id IS NOT NULL
         FOR UPDATE'
    );
    $studentStmt->execute([':id' => $studentId]);
    $student = $studentStmt->fetch();
    if (!$student) {
        throw new RuntimeException('Staff-created student identity not found.');
    }

    $portalStmt = $pdo->prepare(
        'SELECT id FROM portal_users WHERE role = \'student\' AND LOWER(email) = LOWER(:email) LIMIT 1 FOR UPDATE'
    );
    $portalStmt->execute([':email' => $student['email']]);
    $portalId = $portalStmt->fetchColumn();
    if (!$portalId) {
        throw new RuntimeException('Student portal identity not found.');
    }

    $pendingStmt = $pdo->prepare('SELECT approval_status FROM users WHERE id = :id');
    $pendingStmt->execute([':id' => $studentId]);
    if ($pendingStmt->fetchColumn() !== 'pending') {
        throw new RuntimeException('This student identity has already been reviewed.');
    }

    $pdo->prepare('UPDATE users SET approval_status = :status, updated_at = NOW() WHERE id = :id')
        ->execute([':status' => $approvalStatus, ':id' => $studentId]);
    $pdo->prepare('UPDATE portal_users SET approval_status = :status WHERE id = :id')
        ->execute([':status' => $approvalStatus, ':id' => $portalId]);
    account_write_audit_log(
        $pdo,
        'admin',
        (int) $_SESSION['admin_id'],
        $decision === 'approve' ? 'approved_student' : 'rejected_student',
        $studentId,
        account_snap_label($student),
        'Staff-created student identity ' . $approvalStatus . '.'
    );
    $pdo->commit();
    $_SESSION['student_approval_notice'] = [
        'type' => 'success',
        'message' => 'Student identity ' . $approvalStatus . '.',
    ];
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Student approval action failed: ' . $e->getMessage());
    $_SESSION['student_approval_notice'] = [
        'type' => 'error',
        'message' => 'Unable to review this student identity. Refresh the list and try again.',
    ];
}

header('Location: student_approvals.php');
exit;