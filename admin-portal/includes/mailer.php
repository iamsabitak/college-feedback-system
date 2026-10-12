<?php
/**
 * Configures PHPMailer and sends public contact emails.
 *
 * Application-owned logic for includes/mailer.php.
 */
/**
 * Gmail SMTP configuration for OTP mail.
 * Enable 2-Step Verification for sabitakhadka091@gmail.com, create an App Password at
 * https://myaccount.google.com/apppasswords, then set the App Password as the
 * OTP_SMTP_APP_PASSWORD environment variable. Never use or commit the Gmail
 * account password in this repository.
 */
const OTP_SMTP_USERNAME = 'sabitakhadka091@gmail.com';
const OTP_SMTP_APP_PASSWORD = 'PASTE_GMAIL_APP_PASSWORD_HERE';

/**
 * Local credentials belong in mailer.local.php, which is ignored by Git.
 * Environment configuration takes precedence for production deployments.
 */
function otp_local_smtp_app_password(): string
{
    $localConfig = __DIR__ . '/mailer.local.php';
    if (!is_file($localConfig)) {
        return '';
    }
    $config = require $localConfig;
    return is_array($config) && isset($config['app_password']) && is_string($config['app_password'])
        ? trim($config['app_password'])
        : '';
}

function otp_smtp_app_password(): string
{
    $password = getenv('OTP_SMTP_APP_PASSWORD');
    if (is_string($password) && trim($password) !== '') {
        return trim($password);
    }
    $localPassword = otp_local_smtp_app_password();
    return $localPassword !== '' ? $localPassword : OTP_SMTP_APP_PASSWORD;
}

function otp_mailer_ready(): bool
{
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    return is_file($autoload) && otp_smtp_app_password() !== 'PASTE_GMAIL_APP_PASSWORD_HERE';
}

function send_otp_email(string $to, string $otpCode, string $purpose): bool
{
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!otp_mailer_ready()) {
        error_log('OTP email not sent: configure PHPMailer and OTP_SMTP_APP_PASSWORD.');
        return false;
    }
    require_once $autoload;
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = OTP_SMTP_USERNAME;
        $mail->Password = otp_smtp_app_password();
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom(OTP_SMTP_USERNAME, 'College Feedback System');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Your College Feedback System verification code';
        $action = match ($purpose) {
            'change_password' => 'confirm your password change',
            'email_change' => 'verify your new administrator email address',
            default => 'reset your password',
        };
        $mail->Body = '<p>College Feedback System</p><p>Use this verification code to ' . $action . ':</p><p style="font-size:28px;font-weight:bold;letter-spacing:6px">' . htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8') . '</p><p>This code expires in 60 seconds.</p>';
        $mail->AltBody = "College Feedback System\n\nYour verification code is: {$otpCode}\n\nThis code expires in 60 seconds.";
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('OTP email failed: ' . $e->getMessage());
        return false;
    }
}

function send_contact_email
// This helper creates the configured Gmail SMTP message and returns whether delivery succeeded.
(string $replyToName, string $replyToEmail, string $subject, string $message): bool
{
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!otp_mailer_ready()) {
        error_log('Contact email not sent: configure PHPMailer and OTP_SMTP_APP_PASSWORD.');
        return false;
    }
    require_once $autoload;
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = OTP_SMTP_USERNAME;
        $mail->Password = otp_smtp_app_password();
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(OTP_SMTP_USERNAME, 'College Feedback System Contact Form');
        $mail->addAddress(OTP_SMTP_USERNAME);
        $mail->addReplyTo($replyToEmail, $replyToName);
        $mail->isHTML(false);
        $mail->Subject = 'Contact form: ' . str_replace(["\r", "\n"], ' ', $subject);
        $mail->Body = "Name: {$replyToName}\r\nEmail: {$replyToEmail}\r\nSubject: {$subject}\r\n\r\nMessage:\r\n{$message}";
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Contact email failed: ' . $e->getMessage());
        return false;
    }
}
