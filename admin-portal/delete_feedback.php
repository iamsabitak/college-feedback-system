<?php
/**
 * Permanently deletes a feedback entry after confirmation.
 *
 * Application-owned logic for delete_feedback.php.
 */
require 'includes/auth.php';

// The deletion endpoint is protected and receives the target ID from the confirmation form.
require 'includes/db.php';

// id can arrive either in the query string (dashboard row action) or POST (from the confirm modal form action)
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $attachmentStmt = $pdo->prepare('SELECT attachment_path FROM feedback WHERE id = :id LIMIT 1');
    $attachmentStmt->execute([':id' => $id]);
    $attachmentPath = $attachmentStmt->fetchColumn();
    $stmt = $pdo->prepare('DELETE FROM feedback WHERE id = :id');
    $stmt->execute([':id' => $id]);
    if ($stmt->rowCount() > 0 && $attachmentPath) {
        require_once dirname(__DIR__) . '/portal_feedback.php';
        deletePortalFeedbackAttachment((string) $attachmentPath);
    }

    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['deleted' => $stmt->rowCount() > 0]);
        exit;
    }
}

$redirect = $_POST['redirect'] ?? 'dashboard.php';
header('Location: ' . $redirect);
exit;
