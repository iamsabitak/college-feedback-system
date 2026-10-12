<?php
/**
 * Validates and sends public contact messages through PHPMailer.
 *
 * Application-owned logic for submit_contact.php.
 */
session_start();
require_once __DIR__ . '/../admin-portal/includes/mailer.php';

function contact_post_value(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function contact_value_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function contact_redirect_with_errors(array $errors, array $values): never
{
    $_SESSION['contact_errors'] = $errors;
    $_SESSION['contact_old'] = $values;
    header('Location: contact.php#contact-form');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

// Only POST requests are allowed; GET requests return users to the contact page.
    header('Location: contact.php');
    exit;
}

$values = [
    'name' => trim(strip_tags(contact_post_value('name'))),
    'email' => trim(strip_tags(contact_post_value('email'))),
    'subject' => trim(preg_replace('/[\r\n]+/', ' ', strip_tags(contact_post_value('subject'))) ?? ''),
    'message' => trim(strip_tags(contact_post_value('message'))),
];

$errors = [];
$submittedToken = contact_post_value('csrf_token');
$sessionToken = $_SESSION['contact_csrf_token'] ?? '';
if (!is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    $errors['_form'] = 'Your form session expired. Please review your details and try again.';
}

if ($values['name'] === '') {
    $errors['name'] = 'Please enter your name.';
} elseif (contact_value_length($values['name']) > 100) {
    $errors['name'] = 'Name must be 100 characters or fewer.';
}

if ($values['email'] === '') {
    $errors['email'] = 'Please enter your email address.';
} elseif (contact_value_length($values['email']) > 254 || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
    $errors['email'] = 'Please enter a valid email address.';
}

if ($values['subject'] === '') {
    $errors['subject'] = 'Please enter a subject.';
} elseif (contact_value_length($values['subject']) > 150) {
    $errors['subject'] = 'Subject must be 150 characters or fewer.';
}

if ($values['message'] === '') {
    $errors['message'] = 'Please enter a message.';
} elseif (contact_value_length($values['message']) > 5000) {
    $errors['message'] = 'Message must be 5,000 characters or fewer.';
}

if ($errors) {
    contact_redirect_with_errors($errors, $values);
}

if (!send_contact_email// PHPMailer sends the validated message through the configured Gmail SMTP account.
($values['name'], $values['email'], $values['subject'], $values['message'])) {
    contact_redirect_with_errors([
        '_form' => 'We could not send your message right now. Please try again later or contact the campus by phone.',
    ], $values);
}

unset($_SESSION['contact_old'], $_SESSION['contact_errors'], $_SESSION['contact_csrf_token']);
$_SESSION['contact_success'] = 'Your message has been sent. Thank you for contacting us.';
header('Location: contact.php#contact-form');
exit;
