<?php
require 'includes/auth.php';
require 'includes/db.php';
require_once dirname(__DIR__) . '/portal_feedback.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('A valid feedback ID is required.');
}

$stmt = $pdo->prepare('SELECT attachment_path, attachment_name FROM feedback WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$attachment = $stmt->fetch();
$storedName = (string) ($attachment['attachment_path'] ?? '');
$path = portalFeedbackAttachmentFilePath($storedName);
if (!$attachment || !$path) {
    http_response_code(404);
    exit('Attachment not found.');
}

if (!is_file($path) || !function_exists('finfo_open')) {
    http_response_code(404);
    exit('Attachment not found.');
}

$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $fileInfo ? finfo_file($fileInfo, $path) : false;
if ($fileInfo) {
    finfo_close($fileInfo);
}
$allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
if (!in_array($mimeType, $allowedTypes, true)) {
    http_response_code(415);
    exit('Unsupported attachment type.');
}

$downloadName = (string) ($attachment['attachment_name'] ?: 'feedback-attachment');
$downloadName = str_replace(["\r", "\n", '"'], ['', '', ''], $downloadName);
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="attachment"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);