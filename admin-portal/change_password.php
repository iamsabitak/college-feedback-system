<?php
/**
 * Verifies the current password and administrator email before changing the password.
 *
 * Application-owned logic for change_password.php.
 */
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/otp.php';

$error = '';
$success = $_SESSION['change_password_success'] ?? '';
unset($_SESSION['change_password_success']);
$pending = $_SESSION['change_otp'] ?? null;
if ($pending && (int) $pending['admin_id'] !== (int) $_SESSION['admin_id']) {
    unset($_SESSION['change_otp']);
    $pending = null;
}
$errorType = '';
$notice = '';

$prepareStep = static function (int $adminId, ?array $latest): array {
    $now = time();
    $created = $latest ? (int) $latest['created_epoch'] : $now;
    return [
        'admin_id' => $adminId,
        'expires_at' => $latest ? (int) $latest['expires_epoch'] : $now + 60,
        'resend_at' => $created + 60,
    ];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!otp_valid_csrf_token(otp_post_string('csrf_token'))) {
        $error = 'Your session expired. Reload this page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'request') {
            $adminStmt = $pdo->prepare('SELECT id, email FROM admin WHERE id = :id LIMIT 1');
            $adminStmt->execute([':id' => $_SESSION['admin_id']]);
            $admin = $adminStmt->fetch();

            if (!otp_mailer_ready()) {
                $error = 'Email delivery is not configured. Ask the system administrator to configure the Gmail App Password.';
            } elseif (!$admin || empty($admin['email'])) {
                $error = 'Your administrator account has no registered email address. Contact your system administrator.';
            } else {
                $result = otp_issue($pdo, (int) $admin['id'], $admin['email'], 'change_password');
                $latest = $result['latest'] ?? otp_latest($pdo, (int) $admin['id'], 'change_password');
                if ($result['status'] === 'sent' || $result['status'] === 'cooldown') {
                    $pending = $prepareStep((int) $admin['id'], $latest);
                    $_SESSION['change_otp'] = $pending;
                    $notice = $result['status'] === 'sent'
                        ? 'A verification code was sent to your administrator email.'
                        : 'A code was recently sent. Enter that code or wait before requesting another.';
                } elseif ($result['status'] === 'mail_error') {
                    $error = 'We could not send the verification email. Check the mail setup and try again.';
                } else {
                    $error = 'We could not request a verification code. Please try again.';
                }
            }
        } elseif ($action === 'resend' && $pending) {
            $adminStmt = $pdo->prepare('SELECT id, email FROM admin WHERE id = :id LIMIT 1');
            $adminStmt->execute([':id' => $_SESSION['admin_id']]);
            $admin = $adminStmt->fetch();
            if ($admin && !empty($admin['email'])) {
                $result = otp_issue($pdo, (int) $admin['id'], $admin['email'], 'change_password');
                $latest = $result['latest'] ?? otp_latest($pdo, (int) $admin['id'], 'change_password');
                $pending = $prepareStep((int) $admin['id'], $latest);
                $_SESSION['change_otp'] = $pending;
                if ($result['status'] === 'sent') {
                    $notice = 'A new verification code was sent to your administrator email.';
                } elseif ($result['status'] === 'cooldown') {
                    $error = 'Please wait for the current resend cooldown to finish.';
                } else {
                    $error = 'We could not send a new verification code. Please try again.';
                }
            } else {
                $error = 'We could not send a new verification code. Please try again.';
            }
        } elseif ($action === 'verify' && $pending) {
            $currentPassword = otp_post_string('current_password');
            $code = preg_replace('/\D/', '', otp_post_string('otp_code'));
            $newPassword = otp_post_string('new_password');
            $confirmPassword = otp_post_string('confirm_password');
            $adminStmt = $pdo->prepare('SELECT password_hash FROM admin WHERE id = :id LIMIT 1');
            $adminStmt->execute([':id' => $_SESSION['admin_id']]);
            $admin = $adminStmt->fetch();

            if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'Your new password must be at least 8 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New password and confirmation do not match.';
            } elseif (password_verify($newPassword, $admin['password_hash'])) {
                $error = 'Choose a password different from your current password.';
            } elseif (!preg_match('/^[0-9]{6}$/', $code)) {
                $error = 'That verification code is not valid.';
                $errorType = 'otp';
            } else {
                $result = otp_change_password($pdo, (int) $_SESSION['admin_id'], 'change_password', $code, $newPassword);
                if ($result === 'expired') {
                    $error = 'This code has expired. Request a new one.';
                    $errorType = 'expired';
                } elseif ($result === 'invalid') {
                    $error = 'That verification code is not valid.';
                    $errorType = 'otp';
                } elseif ($result === 'success') {
                    unset($_SESSION['change_otp']);
                    $pending = null;
                    $success = 'Password changed successfully. You are still signed in.';
                } else {
                    $error = 'We could not update your password. Please try again.';
                }
            }
        }
    }
}

$pending = $_SESSION['change_otp'] ?? null;
$csrfToken = otp_csrf_token();
$now = time();
$accountEmailStmt = $pdo->prepare('SELECT email FROM admin WHERE id = :id LIMIT 1');
$accountEmailStmt->execute([':id' => $_SESSION['admin_id']]);
$accountEmail = (string) ($accountEmailStmt->fetchColumn() ?: '');
$mailerReady = otp_mailer_ready();
$embedded = isset($_GET['embed']) && $_GET['embed'] === '1';
$pageTitle = 'Change Password';
$active = 'password';
$isExpired = $pending && (int) $pending['expires_at'] <= $now;

if (!$embedded) {
    include 'includes/header.php';
}
?>
<?php if (!$embedded): ?>
<div class="page-head">
  <div>
    <span class="page-kicker">Account security</span>
    <h1>Change Password</h1>
    <p>Verify your administrator email and update your sign-in details.</p>
  </div>
</div>
<?php endif; ?>

<div class="password-card otp-password-card">
  <?php if ($success): ?>
    <div class="otp-success" role="status"><span class="otp-check" aria-hidden="true">✓</span><span><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span></div>
  <?php endif; ?>
  <?php if ($notice): ?>
    <div class="otp-sent" role="status"><span class="otp-check" aria-hidden="true">✓</span><span><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></span></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if (!$mailerReady): ?>
    <div class="otp-config-notice" role="status">Email delivery is not configured. Set the Gmail App Password as <code>OTP_SMTP_APP_PASSWORD</code> before requesting a verification code.</div>
  <?php endif; ?>

  <?php if (!$pending): ?>
    <!-- Initial State before OTP is requested -->
    <div class="security-cards-grid">
      <!-- Card 1: Verify it's you -->
      <div class="security-step-card">
        <div class="security-step-head">
          <span class="security-step-num">1</span>
          <h3>Verify it's you</h3>
        </div>
        <div class="field">
          <label for="securityAccountEmail">Account Email</label>
          <input id="securityAccountEmail" type="email" value="<?php echo htmlspecialchars($accountEmail, ENT_QUOTES, 'UTF-8'); ?>" disabled>
        </div>
        <p class="security-step-desc">To protect your account, we’ll email a 6-digit verification code before changing your password.</p>
        <form method="POST" action="change_password.php<?php echo $embedded ? '?embed=1' : ''; ?>" data-password-request>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="request">
          <button class="btn btn-primary" type="submit"<?php echo !$mailerReady ? ' disabled title="Email delivery is not configured"' : ''; ?>>Send Verification Code</button>
        </form>
      </div>

      <!-- Card 2: Choose a new password (disabled placeholder) -->
      <div class="security-step-card is-pending-step">
        <div class="security-step-head">
          <span class="security-step-num">2</span>
          <h3>Choose a new password</h3>
        </div>
        <div class="field password-field">
          <label for="prep_current_password">Current Password</label>
          <input type="password" id="prep_current_password" disabled placeholder="Enter after verification code is sent">
        </div>
        <div class="field password-field">
          <label for="prep_new_password">New Password</label>
          <input type="password" id="prep_new_password" disabled placeholder="At least 8 characters">
        </div>
        <div class="field password-field">
          <label for="prep_confirm_password">Confirm New Password</label>
          <input type="password" id="prep_confirm_password" disabled placeholder="Confirm your new password">
        </div>
      </div>
    </div>
  <?php else: ?>
    <!-- Active Verification State matching Screenshot 2 -->
    <form method="POST" action="change_password.php<?php echo $embedded ? '?embed=1' : ''; ?>" class="security-cards-form" data-otp-form data-otp-expires="<?php echo (int) $pending['expires_at']; ?>" data-otp-invalid="<?php echo $errorType === 'otp' ? '1' : '0'; ?>" data-password-form>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="action" value="verify">

      <div class="security-cards-grid">
        <!-- Left Card: Step 1 Verify it's you -->
        <div class="security-step-card">
          <div class="security-step-head">
            <span class="security-step-num">1</span>
            <h3>Verify it's you</h3>
          </div>

          <div class="field">
            <label for="securityAccountEmail">Account Email</label>
            <input id="securityAccountEmail" type="email" value="<?php echo htmlspecialchars($accountEmail, ENT_QUOTES, 'UTF-8'); ?>" disabled>
          </div>

          <!-- Timer banner matching Screenshot 2 -->
          <div class="recovery-timer-banner<?php echo $isExpired || $errorType === 'expired' ? ' is-expired' : ' is-active'; ?>" data-otp-banner>
            <span>Code expires in <strong data-otp-countdown>1:00</strong></span>
            <span class="recovery-timer-status" data-otp-status-text><?php echo $isExpired || $errorType === 'expired' ? 'Code expired. Request a new one.' : 'Code is active'; ?></span>
          </div>

          <!-- 6-digit OTP boxes matching Screenshot 2 -->
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
            <small class="field-hint">Enter the 6-digit code sent to your administrator email.</small>
          </div>

          <div class="security-resend-row">
            <button type="button" class="btn btn-resend-outline" data-resend-trigger<?php echo (int) $pending['resend_at'] > $now ? ' disabled' : ''; ?>>
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7v5h-5"/><path d="M20 12a8 8 0 1 1-2.3-5.7L20 9"/></svg>
              Resend Code
            </button>
            <span class="resend-status-text" data-resend-countdown data-resend-at="<?php echo (int) $pending['resend_at']; ?>">
              <?php echo (int) $pending['resend_at'] > $now ? 'Resend available in ' . ((int) $pending['resend_at'] - $now) . 's' : 'You can request a new code.'; ?>
            </span>
          </div>
        </div>

        <!-- Right Card: Step 2 Choose a new password -->
        <div class="security-step-card">
          <div class="security-step-head">
            <span class="security-step-num">2</span>
            <h3>Choose a new password</h3>
          </div>

          <!-- Current Password (requested by user) -->
          <div class="field password-field">
            <label for="current_password">Current Password</label>
            <div class="password-input">
              <input type="password" id="current_password" name="current_password" autocomplete="off" required>
              <button type="button" class="icon-password-toggle" data-password-toggle="current_password" aria-label="Show current password">
                <svg class="eye-open" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                <svg class="eye-closed" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.8M6.1 6.1C3.5 7.9 2 12 2 12s3.5 6 10 6c1.3 0 2.5-.2 3.5-.6"/><circle cx="12" cy="12" r="2.5"/></svg>
              </button>
            </div>
            <div class="field-error" data-password-error="current_password"></div>
          </div>

          <!-- New Password with eye toggle, 4 strength bars, 3 rules checklist -->
          <div class="field password-field">
            <label for="new_password">New Password</label>
            <div class="password-input">
              <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
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
              <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required>
              <button type="button" class="icon-password-toggle" data-password-toggle="confirm_password" aria-label="Show confirmed password">
                <svg class="eye-open" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                <svg class="eye-closed" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.8M6.1 6.1C3.5 7.9 2 12 2 12s3.5 6 10 6c1.3 0 2.5-.2 3.5-.6"/><circle cx="12" cy="12" r="2.5"/></svg>
              </button>
            </div>
            <div class="field-error" data-password-error="confirm_password"></div>
          </div>
        </div>
      </div>

      <!-- Action buttons matching Screenshot 2: Clear & Verify & Update Password -->
      <div class="security-card-actions">
        <button class="btn btn-outline" type="reset">Clear</button>
        <button class="btn btn-primary" type="submit" data-otp-submit<?php echo $isExpired ? ' disabled' : ''; ?>>Verify &amp; Update Password</button>
      </div>
    </form>

    <form method="POST" action="change_password.php<?php echo $embedded ? '?embed=1' : ''; ?>" class="otp-resend hidden-resend-form" data-resend-form>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="action" value="resend">
    </form>
  <?php endif; ?>

  <?php if (!$embedded): ?>
    <p class="otp-back"><a href="dashboard.php">← Back to dashboard</a></p>
  <?php endif; ?>
</div>

<?php
if (!$embedded) {
    include 'includes/footer.php';
}
?>
