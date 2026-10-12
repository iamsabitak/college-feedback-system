<?php
/**
 * Marks all notifications for the current administrator as read.
 *
 * Application-owned logic for mark_all_notifications_read.php.
 */
require 'includes/auth.php';

// Only the current administrator's notification records are marked as read.
require 'includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Use a POST request.']);
    exit;
}

$adminId = (int) $_SESSION['admin_id'];
$markAllRead = $pdo->prepare(
    'UPDATE notifications
     SET is_read = 1, read_at = COALESCE(read_at, NOW())
     WHERE admin_id = :admin_id AND is_read = 0'
);
$markAllRead->execute([':admin_id' => $adminId]);

$count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE admin_id = :admin_id AND is_read = 0');
$count->execute([':admin_id' => $adminId]);
echo json_encode(['success' => true, 'unread_count' => (int) $count->fetchColumn()]);
