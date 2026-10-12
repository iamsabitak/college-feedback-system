<?php
/**
 * Updates administrator profile fields and verifies email changes.
 *
 * Application-owned logic for update_profile.php.
 */
require 'includes/auth.php';

// Profile changes are stored only after CSRF and input validation checks succeed.
require 'includes/db.php';
require 'includes/otp.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function profileJson(bool $success, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    profileJson(false, 'Use a profile update request.');
}
if (!otp_valid_csrf_token(otp_post_string('csrf_token'))) {
    http_response_code(403);
    profileJson(false, 'Your session expired. Reload the profile and try again.');
}

$adminId = (int) $_SESSION['admin_id'];
$action = otp_post_string('action') ?: 'save';
$field = otp_post_string('field');
$value = trim(otp_post_string('value'));
$allowedFields = ['username', 'email', 'phone'];

function profileUpdatedAt(PDO $pdo, int $adminId): string
{
    $stmt = $pdo->prepare('SELECT updated_at FROM admin WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $adminId]);
    return (string) ($stmt->fetchColumn() ?: '');
}

function profileEmailInUse(PDO $pdo, string $email, int $adminId): bool
{
    $stmt = $pdo->prepare('SELECT id FROM admin WHERE email = :email AND id <> :id LIMIT 1');
    $stmt->execute([':email' => $email, ':id' => $adminId]);
    return (bool) $stmt->fetchColumn();
}

function sendProfileEmailCode(string $email, int $adminId): array
{
    if (!otp_mailer_ready()) {
        return ['success' => false, 'message' => 'Email verification is not configured. Add the Gmail App Password in includes/mailer.php.'];
    }
    $pending = $_SESSION['profile_email_otp'] ?? null;
    if ($pending && (int) $pending['admin_id'] === $adminId && (int) $pending['resend_at'] > time()) {
        return ['success' => false, 'message' => 'Please wait before requesting another code.', 'cooldown' => (int) $pending['resend_at'] - time()];
    }
    $code = (string) random_int(100000, 999999);
    if (!send_otp_email($email, $code, 'email_change')) {
        return ['success' => false, 'message' => 'We could not send the verification email. Check the SMTP configuration and try again.'];
    }
    $_SESSION['profile_email_otp'] = [
        'admin_id' => $adminId,
        'email' => $email,
        'code_hash' => hash('sha256', $code),
        'expires_at' => time() + 60,
        'resend_at' => time() + 60,
    ];
    return ['success' => true, 'message' => 'Verification code sent to ' . $email . '.', 'expires_in' => 60, 'resend_in' => 60];
}

if ($action === 'verify_email') {
    $pending = $_SESSION['profile_email_otp'] ?? null;
    $code = preg_replace('/\D/', '', otp_post_string('code'));
    if (!$pending || (int) $pending['admin_id'] !== $adminId) {
        profileJson(false, 'Request a new verification code first.');
    }
    if ((int) $pending['expires_at'] <= time()) {
        unset($_SESSION['profile_email_otp']);
        profileJson(false, 'This code has expired. Request a new one.', ['expired' => true]);
    }
    if (!preg_match('/^[0-9]{6}$/', $code) || !hash_equals($pending['code_hash'], hash('sha256', $code))) {
        profileJson(false, 'That verification code is not valid.');
    }
    if (profileEmailInUse($pdo, $pending['email'], $adminId)) {
        unset($_SESSION['profile_email_otp']);
        profileJson(false, 'That email address is already assigned to another admin.');
    }
    $update = $pdo->prepare('UPDATE admin SET email = :email WHERE id = :id');
    $update->execute([':email' => $pending['email'], ':id' => $adminId]);
    unset($_SESSION['profile_email_otp']);
    profileJson(true, 'Email verified and updated.', [
        'field' => 'email',
        'value' => $pending['email'],
        'updated_at' => profileUpdatedAt($pdo, $adminId),
    ]);
}

if ($action === 'resend_email') {
    $pending = $_SESSION['profile_email_otp'] ?? null;
    if (!$pending || (int) $pending['admin_id'] !== $adminId) {
        profileJson(false, 'Request an email change first.');
    }
    $result = sendProfileEmailCode($pending['email'], $adminId);
    if (!$result['success']) {
        profileJson(false, $result['message'], array_intersect_key($result, ['cooldown' => true]));
    }
    profileJson(true, $result['message'], ['resend_in' => $result['resend_in']]);
}

if ($action !== 'save' || !in_array($field, $allowedFields, true)) {
    http_response_code(400);
    profileJson(false, 'Choose a valid profile field.');
}

$currentStmt = $pdo->prepare('SELECT ' . $field . ' FROM admin WHERE id = :id LIMIT 1');
$currentStmt->execute([':id' => $adminId]);
$currentValue = (string) ($currentStmt->fetchColumn() ?? '');
if ($value === $currentValue) {
    profileJson(true, 'No changes to save.', ['field' => $field, 'value' => $currentValue, 'updated_at' => profileUpdatedAt($pdo, $adminId)]);
}

if ($field === 'username') {
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $value)) {
        profileJson(false, 'Use 3–50 letters, numbers, periods, underscores, or hyphens.');
    }
    $exists = $pdo->prepare('SELECT id FROM admin WHERE username = :username AND id <> :id LIMIT 1');
    $exists->execute([':username' => $value, ':id' => $adminId]);
    if ($exists->fetchColumn()) {
        profileJson(false, 'That username is already in use.');
    }
} elseif ($field === 'email') {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL) || strlen($value) > 100) {
        profileJson(false, 'Enter a valid email address (maximum 100 characters).');
    }
    if (profileEmailInUse($pdo, $value, $adminId)) {
        profileJson(false, 'That email address is already assigned to another admin.');
    }
    $result = sendProfileEmailCode($value, $adminId);
    if (!$result['success']) {
        profileJson(false, $result['message'], array_intersect_key($result, ['cooldown' => true]));
    }
    profileJson(true, $result['message'], [
        'requires_otp' => true,
        'resend_in' => $result['resend_in'],
    ]);
} elseif ($field === 'phone') {
    $digits = preg_replace('/\D/', '', $value);
    if ($value !== '' && (!preg_match('/^\+?[0-9][0-9 ()-]{5,18}$/', $value) || strlen($digits) < 7 || strlen($digits) > 15)) {
        profileJson(false, 'Enter a phone number with 7–15 digits; an optional leading + is allowed.');
    }
}

$update = $pdo->prepare('UPDATE admin SET ' . $field . ' = :value WHERE id = :id');
$update->execute([':value' => $field === 'phone' && $value === '' ? null : $value, ':id' => $adminId]);
if ($field === 'username') {
    $_SESSION['admin_username'] = $value;
}
profileJson(true, ucfirst($field) . ' updated.', [
    'field' => $field,
    'value' => $value,
    'updated_at' => profileUpdatedAt($pdo, $adminId),
]);
