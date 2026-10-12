<?php
/**
 * Marks one notification as read.
 *
 * Application-owned logic for mark_notification_read.php.
 */
require 'includes/auth.php';

// The notification ID is checked against the administrator scope before it is updated.
require 'includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use a POST request.']);
    exit;
}

$notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$notificationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid notification is required.']);
    exit;
}

$adminId = (int) $_SESSION['admin_id'];
$markRead = $pdo->prepare(
    'UPDATE notifications
     SET is_read = 1, read_at = COALESCE(read_at, NOW())
     WHERE id = :notification_id AND admin_id = :admin_id'
);
$markRead->execute([':notification_id' => $notificationId, ':admin_id' => $adminId]);

$exists = $pdo->prepare('SELECT id FROM notifications WHERE id = :notification_id AND admin_id = :admin_id LIMIT 1');
$exists->execute([':notification_id' => $notificationId, ':admin_id' => $adminId]);
if (!$exists->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Notification not found.']);
    exit;
}

$count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE admin_id = :admin_id AND is_read = 0');
$count->execute([':admin_id' => $adminId]);
echo json_encode(['success' => true, 'is_read' => true, 'unread_count' => (int) $count->fetchColumn()]);
