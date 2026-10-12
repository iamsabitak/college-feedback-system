<?php
/**
 * Updates a feedback entry status after administrator confirmation.
 *
 * Application-owned logic for status_update.php.
 */
require 'includes/auth.php';

// Only New, Reviewed, or Resolved values are accepted for the feedback status.
require 'includes/db.php';

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$allowedStatuses = ['New', 'Reviewed', 'Resolved'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id < 1 || !in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    exit('Invalid status update.');
}

$existsStmt = $pdo->prepare('SELECT id FROM feedback WHERE id = :id LIMIT 1');
$existsStmt->execute([':id' => $id]);
if (!$existsStmt->fetch()) {
    http_response_code(404);
    exit('Feedback entry not found.');
}
$stmt = $pdo->prepare('UPDATE feedback SET status = :status WHERE id = :id');
$stmt->execute([':status' => $status, ':id' => $id]);

if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    header('Content-Type: application/json');
    echo json_encode(['updated' => true, 'status' => $status]);
    exit;
}

header('Location: view_feedback.php?id=' . $id . '&status_updated=1');
exit;
