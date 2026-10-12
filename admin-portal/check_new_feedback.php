<?php
/**
 * Checks whether new feedback exists for the signed-in administrator.
 *
 * Application-owned logic for check_new_feedback.php.
 */
require 'includes/auth.php';

// The response is limited to unread feedback for the currently signed-in administrator.
require 'includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$since = isset($_GET['since']) && ctype_digit((string) $_GET['since']) ? (int) $_GET['since'] : null;
$adminId = (int) $_SESSION['admin_id'];

$latestStmt = $pdo->prepare('SELECT COALESCE(MAX(id), 0) FROM notifications WHERE admin_id = :admin_id');
$latestStmt->execute([':admin_id' => $adminId]);
$latestId = (int) $latestStmt->fetchColumn();

$count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE admin_id = :admin_id AND is_read = 0');
$count->execute([':admin_id' => $adminId]);
$unreadCount = (int) $count->fetchColumn();

$selectSql =
    'SELECT n.id AS notification_id, n.feedback_id, n.is_read,
            f.submitter_type, f.category, f.rating, f.submit_date, f.is_anonymous,
            CASE WHEN f.is_anonymous = 0 THEN f.submitter_name ELSE NULL END AS submitter_name,
            f.message
     FROM notifications n
     INNER JOIN feedback f ON f.id = n.feedback_id
     WHERE n.admin_id = :admin_id';

$recentStmt = $pdo->prepare($selectSql . ' ORDER BY n.id DESC');
$recentStmt->execute([':admin_id' => $adminId]);
$recent = $recentStmt->fetchAll();
$entries = [];
foreach ($recent as $row) {
    $entry = [
        'id' => (int) $row['feedback_id'],
        'feedback_id' => (int) $row['feedback_id'],
        'notification_id' => (int) $row['notification_id'],
        'is_read' => (bool) $row['is_read'],
        'submitter_type' => $row['submitter_type'],
        'category' => $row['category'],
        'rating' => (int) $row['rating'],
        'submit_date' => $row['submit_date'],
        'is_anonymous' => (bool) $row['is_anonymous'],
        'message' => $row['message'],
        'display_name' => $row['is_anonymous'] ? 'Anonymous Submission' : ($row['submitter_name'] ?: 'Unnamed'),
    ];
    if (!$row['is_anonymous'] && $row['submitter_name'] !== null) {
        $entry['submitter_name'] = $row['submitter_name'];
    }
    $entries[] = $entry;
}

$newEntries = [];
if ($since !== null && $latestId > $since) {
    $newStmt = $pdo->prepare($selectSql . ' AND n.id > :since ORDER BY n.id DESC');
    $newStmt->execute([':admin_id' => $adminId, ':since' => $since]);
    foreach ($newStmt->fetchAll() as $row) {
        $entry = [
            'id' => (int) $row['feedback_id'],
            'feedback_id' => (int) $row['feedback_id'],
            'notification_id' => (int) $row['notification_id'],
            'is_read' => (bool) $row['is_read'],
            'submitter_type' => $row['submitter_type'],
            'category' => $row['category'],
            'rating' => (int) $row['rating'],
            'submit_date' => $row['submit_date'],
            'is_anonymous' => (bool) $row['is_anonymous'],
            'message' => $row['message'],
            'display_name' => $row['is_anonymous'] ? 'Anonymous Submission' : ($row['submitter_name'] ?: 'Unnamed'),
        ];
        if (!$row['is_anonymous'] && $row['submitter_name'] !== null) {
            $entry['submitter_name'] = $row['submitter_name'];
        }
        $newEntries[] = $entry;
    }
}

echo json_encode([
    'latest_id' => $latestId,
    'unread_count' => $unreadCount,
    'entries' => $entries,
    'new_entries' => $newEntries,
]);
