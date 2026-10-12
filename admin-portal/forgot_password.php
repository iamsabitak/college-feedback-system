<?php
/**
 * Handles administrator password-reset requests and OTP verification.
 *
 * Application-owned logic for forgot_password.php.
 */
session_start();
require 'includes/db.php';
require 'includes/otp.php';

$error = '';
$errorType = '';
$notice = '';
$pending = $_SESSION['forgot_otp'] ?? null;
$identity = '';
$requestStarted = null;
$mailerReady = otp_mailer_ready();

$prepareStep = static function (?int $adminId, string $identity, ?array $latest): array {
    $now = time();
    $created = $latest ? (int) $latest['created_epoch'] : $now;
    return [
        'admin_id' => $adminId,
        'identity' => $identity,
        'expires_at' => $latest ? (int) $latest['expires_epoch'] : $now + 60,
        'resend_at' => $created + 60,
    ];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestStarted = microtime(true);
    if (!otp_valid_csrf_token(otp_post_string('csrf_token'))) {
        $error = 'Your session expired. Reload this page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'request' || $action === 'resend') {
            $previousPending = $pending;
            $pending = null;
            unset($_SESSION['forgot_otp']);
            if ($action === 'request') {
                $identity = trim(otp_post_string('identity'));
            } else {
                $identity = (string) ($previousPending['identity'] ?? '');
            }

            $lookup = $pdo->prepare(
                'SELECT id, username, email FROM admin
                 WHERE username = :username OR email = :email LIMIT 1'
            );
            $lookup->execute([':username' => $identity, ':email' => $identity]);
            $admin = $lookup->fetch();
            $eligibleAdmin = $admin && !empty($admin['email']) ? $admin : null;
            $latest = null;

            if ($mailerReady && $eligibleAdmin) {
                $result = otp_issue($pdo, (int) $eligibleAdmin['id'], $eligibleAdmin['email'], 'forgot_password');
                $latest = $result['latest'] ?? otp_latest($pdo, (int) $eligibleAdmin['id'], 'forgot_password');
            }

            if ($mailerReady) {
                $pendingAdminId = $eligibleAdmin && in_array($result['status'] ?? '', ['sent', 'cooldown'], true)
                    ? (int) $eligibleAdmin['id']
                    : null;
                $pending = $prepareStep($pendingAdminId, $identity, $latest);
                $_SESSION['forgot_otp'] = $pending;
                $notice = 'If an account matches, an OTP has been sent to the associated email.';
            }
        } elseif ($action === 'verify' && $pending) {
            $code = preg_replace('/\D/', '', otp_post_string('otp_code'));
            $newPassword = otp_post_string('new_password');
            $confirmPassword = otp_post_string('confirm_password');

            if (strlen($newPassword) < 8) {
                $error = 'Your new password must be at least 8 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New password and confirmation do not match.';
            } elseif (!preg_match('/^[0-9]{6}$/', $code)) {
                $error = 'That verification code is not valid.';
                $errorType = 'otp';
            } elseif (empty($pending['admin_id'])) {
                $error = 'That verification code is not valid.';
                $errorType = 'otp';
            } else {
                $result = otp_change_password($pdo, (int) $pending['admin_id'], 'forgot_password', $code, $newPassword);
                if ($result === 'expired') {
                    $error = 'This code has expired. Request a new one.';
                    $errorType = 'expired';
                } elseif ($result === 'invalid') {
                    $error = 'That verification code is not valid.';
                    $errorType = 'otp';
                } elseif ($result === 'success') {
                    unset($_SESSION['forgot_otp']);
                    $_SESSION['login_notice'] = 'Your password was reset successfully. Sign in with your new password.';
                    header('Location: login.php');
                    exit;
                } else {
                    $error = 'We could not update your password. Please try again.';
                }
            }
        }
    }

    if ($requestStarted !== null && in_array($_POST['action'] ?? '', ['request', 'resend'], true)) {
        $remaining = 1.2 - (microtime(true) - $requestStarted);
        if ($remaining > 0) {
            usleep((int) ($remaining * 1000000));
        }
    }
}

$pending = $_SESSION['forgot_otp'] ?? null;
$csrfToken = otp_csrf_token();
$now = time();
$isExpired = $pending && (int) $pending['expires_at'] <= $now;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password · College Feedback System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,600;0,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="../assets/logo.svg">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-body">
  <main class="login-shell">
    <!-- Left content preserved from login page -->
    <section class="login-showcase" aria-label="College Feedback System">
      <div class="login-orb login-orb-one" aria-hidden="true"></div>
      <div class="login-orb login-orb-two" aria-hidden="true"></div>
      <div class="login-grid" aria-hidden="true"></div>
      <div class="login-showcase-brand">
        <span class="brand-mark login-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span>
        <span>College Feedback<small>Patan Nistha Campus</small></span>
      </div>
      <div class="login-showcase-copy">
        <span class="login-eyebrow">College Feedback System · Admin Portal</span>
        <h1>Welcome back.</h1>
        <p>Review, search, and manage every feedback submission from students, staff, and visitors at Patan Nistha Campus.</p>
        <div class="showcase-rule"></div>
        <span class="showcase-note">A private workspace for better campus decisions.</span>
      </div>
      <p class="login-copyright">&copy; <?php echo date('Y'); ?> Patan Nistha Campus</p>
    </section>

    <!-- Right panel with customized send OTP and setting password phases -->
    <section class="login-panel">
      <div class="login-content recovery-content<?php echo $error ? ' has-error' : ''; ?>">
        <div class="login-brand">
          <span class="brand-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span>
          <span><strong>Admin Portal</strong><small>College Feedback System</small></span>
        </div>

        <div class="login-title recovery-title">
          <span class="page-kicker">ACCOUNT RECOVERY</span>
          <h1>Reset your password</h1>
          <p><?php echo $pending ? 'We’ll email a 6-digit verification code to the administrator email address.' : 'Enter your username or email to receive a 6-digit verification code.'; ?></p>
        </div>

        <?php if ($notice): ?>
          <div class="otp-sent" role="status"><span class="otp-check" aria-hidden="true">✓</span><span><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></span></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if (!$mailerReady): ?>
          <div class="otp-config-notice" role="status">Email delivery is unavailable until a Gmail App Password is configured as <code>OTP_SMTP_APP_PASSWORD</code>. Ask the system administrator to configure it, then return here to request your code.</div>
        <?php endif; ?>

        <?php if (!$pending): ?>
          <!-- Phase 1: Send OTP Phase -->
          <form method="post" class="recovery-request-form" data-otp-request>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="request">
            <div class="field">
              <label for="identity">Username or Email</label>
              <input id="identity" name="identity" value="<?php echo htmlspecialchars($identity, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" placeholder="Enter username or email" required autofocus>
            </div>
            <button class="btn btn-primary btn-block login-submit" type="submit"<?php echo !$mailerReady ? ' disabled title="Email delivery is not configured"' : ''; ?>>
              <span class="login-submit-content">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                Send OTP
              </span>
            </button>
          </form>
        <?php else: ?>
          <!-- Phase 2: Setting Password Phase (Matching Screenshot 1) -->
          <form method="post" class="recovery-verify-form<?php echo $errorType === 'otp' ? ' otp-invalid' : ''; ?>" data-otp-form data-otp-expires="<?php echo (int) $pending['expires_at']; ?>" data-otp-invalid="<?php echo $errorType === 'otp' ? '1' : '0'; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="verify">

            <!-- Active/Expired Timer Banner -->
            <div class="recovery-timer-banner<?php echo $isExpired || $errorType === 'expired' ? ' is-expired' : ' is-active'; ?>" data-otp-banner>
              <span>Code expires in <strong data-otp-countdown>1:00</strong></span>
              <span class="recovery-timer-status" data-otp-status-text><?php echo $isExpired || $errorType === 'expired' ? 'Code expired. Request a new one.' : 'Code is active'; ?></span>
            </div>

            <!-- 6 Separate Digit Boxes -->
            <div class="field">
              <label>Verification Code</label>
              <div class="otp-boxes" data-otp-boxes>
                <input type="text" class="otp-box<?php echo $errorType === 'otp' ? ' is-invalid' : ''; ?>" data-otp-digit="0" inputmode="numeric" pattern="[0-9]" maxlength="1" autocomplete="one-time-code" aria-label="Digit 1"<?php echo $isExpired ? ' disabled' : ''; ?> required>
                <input type="text" class="otp-box<?php echo $errorType === 'otp' ? ' is-invalid' : ''; ?>" data-otp-digit="1" inputmode="numeric" pattern="[0-9]" maxlength="1" aria-label="Digit 2"<?php echo $isExpired ? ' disabled' : ''; ?> required>
                <input type="text" class="otp-box<?php echo $errorType === 'otp' ? ' is-invalid' : ''; ?>" data-otp-digit="2" inputmode="numeric" pattern="[0-9]" maxlength="1" aria-label="Digit 3"<?php echo $isExpired ? ' disabled' : ''; ?> required>
                <input type="text" class="otp-box<?php echo $errorType === 'otp' ? ' is-invalid' : ''; ?>" data-otp-digit="3" inputmode="numeric" pattern="[0-9]" maxlength="1" aria-label="Digit 4"<?php echo $isExpired ? ' disabled' : ''; ?> required>
                <input type="text" class="otp-box<?php echo $errorType === 'otp' ? ' is-invalid' : ''; ?>" data-otp-digit="4" inputmode="numeric" pattern="[0-9]" maxlength="1" aria-label="Digit 5"<?php echo $isExpired ? ' disabled' : ''; ?> required>
                <input type="text" class="otp-box<?php echo $errorType === 'otp' ? ' is-invalid' : ''; ?>" data-otp-digit="5" inputmode="numeric" pattern="[0-9]" maxlength="1" aria-label="Digit 6"<?php echo $isExpired ? ' disabled' : ''; ?> required>
              </div>
              <input type="hidden" name="otp_code" id="otp_code" class="otp-code">
              <small class="field-hint">Enter the 6-digit code sent to the associated email.</small>
            </div>

            <!-- New Password with eye toggle, 4 strength bars, 2x2 checklist -->
            <div class="field password-field">
              <label for="new_password">New Password</label>
              <div class="password-input">
                <input id="new_password" name="new_password" type="password" minlength="8" autocomplete="new-password" required>
                <button type="button" class="icon-password-toggle" data-password-toggle="new_password" aria-label="Show new password">
                  <svg class="eye-open" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                  <svg class="eye-closed" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.8M6.1 6.1C3.5 7.9 2 12 2 12s3.5 6 10 6c1.3 0 2.5-.2 3.5-.6"/><circle cx="12" cy="12" r="2.5"/></svg>
                </button>
              </div>

              <div class="field-error" data-password-error="new_password"></div>
            </div>

            <!-- Confirm New Password with eye toggle -->
            <div class="field password-field">
              <label for="confirm_password">Confirm New Password</label>
              <div class="password-input">
                <input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
                <button type="button" class="icon-password-toggle" data-password-toggle="confirm_password" aria-label="Show confirmed password">
                  <svg class="eye-open" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                  <svg class="eye-closed" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.8M6.1 6.1C3.5 7.9 2 12 2 12s3.5 6 10 6c1.3 0 2.5-.2 3.5-.6"/><circle cx="12" cy="12" r="2.5"/></svg>
                </button>
              </div>
              <div class="field-error" data-password-error="confirm_password"></div>
            </div>

            <button class="btn btn-primary btn-block btn-recovery-submit" type="submit" data-otp-submit<?php echo $isExpired ? ' disabled' : ''; ?>>Verify &amp; Reset</button>
          </form>

          <!-- Resend section below form -->
          <div class="recovery-resend-row">
            <form method="post" class="otp-resend">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="action" value="resend">
              <button type="submit" class="btn btn-resend-outline" data-otp-resend<?php echo (int) $pending['resend_at'] > $now ? ' disabled' : ''; ?>>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7v5h-5"/><path d="M20 12a8 8 0 1 1-2.3-5.7L20 9"/></svg>
                Resend Code
              </button>
            </form>
            <span class="resend-status-text" data-resend-countdown data-resend-at="<?php echo (int) $pending['resend_at']; ?>">
              <?php echo (int) $pending['resend_at'] > $now ? 'Resend available in ' . ((int) $pending['resend_at'] - $now) . 's' : 'You can request a new code.'; ?>
            </span>
          </div>
        <?php endif; ?>

        <p class="recovery-back"><a href="login.php">← Back to sign in</a></p>
      </div>
    </section>
  </main>
  <script src="js/admin.js"></script>
</body>
</html>
