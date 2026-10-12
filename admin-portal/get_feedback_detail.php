<?php
/**
 * Returns one feedback record as JSON for the detail modal.
 *
 * Application-owned logic for get_feedback_detail.php.
 */
require 'includes/auth.php';
require 'includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid feedback ID is required.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, submitter_name, submitter_type, is_anonymous, category, rating, message, submit_date, status FROM feedback WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$feedback = $stmt->fetch();

if (!$feedback) {
    http_response_code(404);
    echo json_encode(['error' => 'Feedback entry not found.']);
    exit;
}

$feedback['id'] = (int) $feedback['id'];
$feedback['rating'] = (int) $feedback['rating'];
$feedback['is_anonymous'] = (bool) $feedback['is_anonymous'];
$feedback['display_name'] = $feedback['is_anonymous'] ? 'Anonymous Submission' : ($feedback['submitter_name'] ?: 'Unnamed');
echo json_encode(['feedback' => $feedback]);
