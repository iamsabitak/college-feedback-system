<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../portal_feedback_options.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
require __DIR__ . '/includes/db.php';
echo json_encode(feedbackFormOptions($pdo, 'staff'));
