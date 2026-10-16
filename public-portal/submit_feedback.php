<?php
/**
 * Handles POST from feedback.php.
 * Implements Section 6.1 (server-side validation), Section 6.3 (anonymous handling),
 * and Section 13 security considerations (prepared statements, sanitisation).
 *
 * Validates and stores public feedback, including anonymous submissions.
 */
session_start();
require 'includes/db.php';

// Keep this false in normal use: successful submissions redirect to the
// styled confirmation page and never expose submitted values.
$debugFeedbackSubmission = false;

function feedbackDebugOutput(string $title, array $values): void
{
    global $debugFeedbackSubmission;

    if (!$debugFeedbackSubmission) {
        return;
    }

    echo '<!doctype html><html><head><meta charset="utf-8"><title>Feedback debug</title>';
    echo '<style>body{font:16px/1.5 monospace;margin:2rem}pre{padding:1rem;background:#f4f4f4;overflow:auto}</style>';
    echo '</head><body><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><pre>';
    echo htmlspecialchars(print_r($values, true), ENT_QUOTES, 'UTF-8');
    echo '</pre></body></html>';
}

function feedbackStringLength(string $value): int
{
    // mbstring is not enabled on every PHP installation. Without this fallback,
    // calling mb_strlen() causes an uncaught fatal error (HTTP 500).
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: feedback.php');
    exit;
}

if (!isset($pdo)) {
    $_SESSION['fb_errors'] = ['message' => 'Database is unavailable. Please try again in a moment.'];
    header('Location: feedback.php');
    exit;
}

$allowedCategories = ['Teachers', 'Courses', 'Library', 'Canteen', 'Laboratories', 'Facilities', 'Administration', 'Internal Processes', 'Overall Impression', 'Admissions', 'Campus', 'Events', 'Other'];
$submitter_type = 'Public';
$category       = trim($_POST['category'] ?? '');
$rating         = (int) ($_POST['rating'] ?? 0);
$message        = trim($_POST['message'] ?? '');
$is_anonymous   = isset($_POST['is_anonymous']) && $_POST['is_anonymous'] === '1';

// If anonymous, the name is discarded outright — it is never read from $_POST at all
// beyond this point, so there is no path by which it could reach the database.
$submitter_name = $is_anonymous ? null : trim($_POST['submitter_name'] ?? '');
if ($submitter_name === '') {
    $submitter_name = null; // name is optional even when not anonymous
}

$errors = [];

if (!in_array($category, $allowedCategories, true)) {
    $errors['category'] = 'Please choose a valid category.';
}
if ($rating < 1 || $rating > 5) {
    $errors['rating'] = 'Rating must be between 1 and 5 stars.';
}
if (!$is_anonymous && $submitter_name === null) {
    $errors['submitter_name'] = 'Please enter your name or submit anonymously.';
}
if (feedbackStringLength($message) < 5) {
    $errors['message'] = 'Please write a comment of at least 5 characters.';
} elseif (feedbackStringLength($message) > 2000) {
    $errors['message'] = 'Comment is too long (2000 characters max).';
}

if (!empty($errors)) {
    feedbackDebugOutput('Feedback validation failed', [
        'received_POST' => $_POST,
        'processed_values' => [
            'category' => $category,
            'rating' => $rating,
            'message' => $message,
            'is_anonymous' => $is_anonymous,
            'submitter_name' => $submitter_name,
        ],
        'validation_errors' => $errors,
    ]);
    if ($debugFeedbackSubmission) {
        exit;
    }
    $_SESSION['fb_errors'] = $errors;
    $_SESSION['fb_old'] = [
        'category'       => $category,
        'rating'         => $rating,
        'message'        => $message,
        'is_anonymous'   => $is_anonymous ? 1 : 0,
        'submitter_name' => $submitter_name,
    ];
    header('Location: feedback.php');
    exit;
}

// Sanitisation: strip_tags prevents any stored script/markup injection (Section 13).
// The database receives only sanitized values; anonymous names are never stored.
$category = strip_tags($category);
$message  = strip_tags($message);
if ($submitter_name !== null) {
    $submitter_name = strip_tags($submitter_name);
}

try {
    // Store the submission and notify every administrator in one transaction.
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'INSERT INTO feedback (submitter_name, submitter_type, is_anonymous, category, rating, message, submit_date)
         VALUES (:name, :type, :anon, :category, :rating, :message, NOW())'
    );
    $stmt->execute([
        ':name'     => $submitter_name,           // NULL when anonymous — never stored
        ':type'     => $submitter_type,
        ':anon'     => $is_anonymous ? 1 : 0,
        ':category' => $category,
        ':rating'   => $rating,
        ':message'  => $message,
    ]);
    $feedbackId = (int) $pdo->lastInsertId();
    $notifyAdmins = $pdo->prepare(
        'INSERT INTO notifications (feedback_id, admin_id, is_read)
         SELECT :feedback_id, id, 0 FROM admin'
    );
    $notifyAdmins->execute([':feedback_id' => $feedbackId]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    feedbackDebugOutput('Feedback database save failed', [
        'received_POST' => $_POST,
        'processed_values' => compact('submitter_type', 'category', 'rating', 'message', 'is_anonymous', 'submitter_name'),
        'exception_class' => get_class($e),
        'exception_code' => $e->getCode(),
        'exception_message' => $e->getMessage(),
    ]);
    if ($debugFeedbackSubmission) {
        exit;
    }
    $_SESSION['fb_errors'] = ['message' => 'Something went wrong saving your feedback. Please try again.'];
    $_SESSION['fb_old'] = $_POST;
    header('Location: feedback.php');
    exit;
}

feedbackDebugOutput('Feedback saved successfully', [
    'received_POST' => $_POST,
    'processed_values' => compact('submitter_type', 'category', 'rating', 'message', 'is_anonymous', 'submitter_name'),
    'inserted_feedback_id' => $feedbackId,
]);
if ($debugFeedbackSubmission) {
    exit;
}

header('Location: confirmation.php');
exit;
