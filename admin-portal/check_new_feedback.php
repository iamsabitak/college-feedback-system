<?php
require 'includes/auth.php';
require 'includes/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$since = isset($_GET['since']) && ctype_digit((string) $_GET['since']) ? (int) $_GET['since'] : null;
$adminId = (int) $_SESSION['admin_id'];
$latest = $pdo->prepare('SELECT COALESCE(MAX(id), 0) FROM notifications WHERE admin_id = :admin_id'); $latest->execute([':admin_id' => $adminId]); $latestId = (int) $latest->fetchColumn();
$count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE admin_id = :admin_id AND is_read = 0'); $count->execute([':admin_id' => $adminId]);
$sql = "SELECT n.id notification_id,n.feedback_id,n.notification_type,n.title,n.message,n.is_read,n.created_at,f.submitter_type,f.category,f.rating,f.submit_date,f.is_anonymous,CASE WHEN f.is_anonymous=0 THEN f.submitter_name ELSE NULL END submitter_name FROM notifications n LEFT JOIN feedback f ON f.id=n.feedback_id WHERE n.admin_id=:admin_id";
$entry = static function (array $row): array { $feedback = !empty($row['feedback_id']); return ['id'=>(int)$row['notification_id'],'notification_id'=>(int)$row['notification_id'],'feedback_id'=>$feedback?(int)$row['feedback_id']:null,'is_read'=>(bool)$row['is_read'],'notification_type'=>$row['notification_type']?:'feedback','title'=>$row['title']?:($feedback?($row['category']?:'Feedback submission'):'Notification'),'message'=>$row['message']?:($feedback?($row['message']?:''):''),'submit_date'=>$row['submit_date']?:$row['created_at'],'submitter_type'=>$row['submitter_type']?:'System','category'=>$row['category']?:'','rating'=>(int)($row['rating']??0),'is_anonymous'=>(bool)($row['is_anonymous']??false),'display_name'=>!empty($row['is_anonymous'])?'Anonymous Submission':($row['submitter_name']?:'College Feedback'),'submitter_name'=>$row['submitter_name']]; };
$all = $pdo->prepare($sql . ' ORDER BY n.id DESC'); $all->execute([':admin_id'=>$adminId]); $entries = array_map($entry, $all->fetchAll());
$newEntries = [];
if ($since !== null && $latestId > $since) { $fresh = $pdo->prepare($sql . ' AND n.id > :since ORDER BY n.id DESC'); $fresh->execute([':admin_id'=>$adminId, ':since'=>$since]); $newEntries = array_map($entry, $fresh->fetchAll()); }
echo json_encode(['latest_id'=>$latestId,'unread_count'=>(int)$count->fetchColumn(),'entries'=>$entries,'new_entries'=>$newEntries]);
