<?php
/**
 * Creates OTP tokens, validates them, and securely changes administrator passwords.
 *
 * Application-owned logic for includes/otp.php.
 */
require_once __DIR__ . '/mailer.php';

function otp_csrf_token(): string
{
    if (empty($_SESSION['otp_csrf_token'])) {
        $_SESSION['otp_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['otp_csrf_token'];
}

function otp_valid_csrf_token(string $token): bool
{
    return !empty($_SESSION['otp_csrf_token'])
        && hash_equals($_SESSION['otp_csrf_token'], $token);
}

function otp_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function otp_latest(PDO $pdo, int $adminId, string $purpose): ?array
{
    $stmt = $pdo->prepare(
        'SELECT UNIX_TIMESTAMP(created_at) AS created_epoch,
                UNIX_TIMESTAMP(expires_at) AS expires_epoch,
                is_used
         FROM password_otp
         WHERE admin_id = :admin_id AND purpose = :purpose
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([':admin_id' => $adminId, ':purpose' => $purpose]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function otp_issue
// This function creates a one-time OTP, stores it with its expiry, and sends it by email.
(PDO $pdo, int $adminId, string $email, string $purpose): array
{
    if (!in_array($purpose, ['forgot_password', 'change_password'], true)) {
        throw new InvalidArgumentException('Unsupported OTP purpose.');
    }
    if ($email === '') {
        return ['status' => 'missing_email'];
    }
    if (!otp_mailer_ready()) {
        error_log('OTP email not sent: install PHPMailer and configure OTP_SMTP_APP_PASSWORD.');
        return ['status' => 'mail_error'];
    }

    try {
        $pdo->beginTransaction();
        $adminLock = $pdo->prepare('SELECT id FROM admin WHERE id = :id FOR UPDATE');
        $adminLock->execute([':id' => $adminId]);
        if (!$adminLock->fetch()) {
            $pdo->commit();
            return ['status' => 'unavailable'];
        }

        $latest = otp_latest($pdo, $adminId, $purpose);
        if ($latest && (int) $latest['created_epoch'] > time() - 60) {
            $pdo->commit();
            return ['status' => 'cooldown', 'latest' => $latest];
        }

        $invalidate = $pdo->prepare(
            'UPDATE password_otp SET is_used = 1
             WHERE admin_id = :admin_id AND purpose = :purpose AND is_used = 0'
        );
        $invalidate->execute([':admin_id' => $adminId, ':purpose' => $purpose]);

        $code = (string) random_int(100000, 999999);
        $insert = $pdo->prepare(
            'INSERT INTO password_otp (admin_id, otp_code, purpose, expires_at)
             VALUES (:admin_id, :otp_code, :purpose, DATE_ADD(NOW(), INTERVAL 60 SECOND))'
        );
        $insert->execute([
            ':admin_id' => $adminId,
            ':otp_code' => $code,
            ':purpose' => $purpose,
        ]);
        $otpId = (int) $pdo->lastInsertId();
        $latest = otp_latest($pdo, $adminId, $purpose);

        if (!send_otp_email($email, $code, $purpose)) {
            $used = $pdo->prepare('UPDATE password_otp SET is_used = 1 WHERE id = :id');
            $used->execute([':id' => $otpId]);
            $pdo->commit();
            return ['status' => 'mail_error', 'latest' => $latest];
        }

        $pdo->commit();
        return ['status' => 'sent', 'latest' => $latest];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('OTP request failed: ' . $e->getMessage());
        return ['status' => 'unavailable'];
    }
}

function otp_change_password(PDO $pdo, int $adminId, string $purpose, string $code, string $newPassword): string
{
    if (!in_array($purpose, ['forgot_password', 'change_password'], true)) {
        throw new InvalidArgumentException('Unsupported OTP purpose.');
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'SELECT id, otp_code, expires_at >= NOW() AS is_valid
             FROM password_otp
             WHERE admin_id = :admin_id AND purpose = :purpose AND is_used = 0
             ORDER BY id DESC LIMIT 1 FOR UPDATE'
        );
        $stmt->execute([':admin_id' => $adminId, ':purpose' => $purpose]);
        $otp = $stmt->fetch();

        if (!$otp) {
            $pdo->commit();
            return 'invalid';
        }
        if (!(int) $otp['is_valid']) {
            $consume = $pdo->prepare('UPDATE password_otp SET is_used = 1 WHERE id = :id AND is_used = 0');
            $consume->execute([':id' => $otp['id']]);
            $pdo->commit();
            return 'expired';
        }
        if (!hash_equals((string) $otp['otp_code'], $code)) {
            $pdo->commit();
            return 'invalid';
        }

        $update = $pdo->prepare('UPDATE admin SET password_hash = :password_hash WHERE id = :id');
        $update->execute([
            ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id' => $adminId,
        ]);
        $consume = $pdo->prepare('UPDATE password_otp SET is_used = 1 WHERE id = :id AND is_used = 0');
        $consume->execute([':id' => $otp['id']]);
        if ($consume->rowCount() !== 1) {
            throw new RuntimeException('OTP was already consumed.');
        }
        $pdo->commit();
        return 'success';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('OTP password update failed: ' . $e->getMessage());
        return 'unavailable';
    }
}