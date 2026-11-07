<?php
session_name('college_feedback_student');
session_start();
require __DIR__ . '/includes/db.php';

$portalRecoveryRole = 'student';
$portalRecoveryLabel = 'Student Portal';
require __DIR__ . '/../portal_recovery.php';
exit;

if (!empty($_SESSION['student_id'])) {
    header('Location: dashboard.php');
    exit;
}

$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Please enter your account email.';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, email FROM portal_users WHERE role = 'student' AND email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 1800);

            $tokenStmt = $pdo->prepare('INSERT INTO portal_reset_tokens (user_id, token, expires_at, used_at, created_at) VALUES (:user_id, :token, :expires_at, NULL, NOW())');
            $tokenStmt->execute([
                ':user_id' => $user['id'],
                ':token' => $token,
                ':expires_at' => $expiresAt,
            ]);

            $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/college-feedback-system/student-portal/reset_password.php?token=' . urlencode($token);
            $body = "Hello {$user['full_name']},\n\nUse this link to reset your password:\n{$resetLink}\n\nThis link expires in 30 minutes.";

            if (!mail($email, 'Student Portal Password Reset', $body)) {
                error_log('Student reset mail failed for ' . $email);
            }

            $notice = 'A reset link has been generated for this account. If email delivery is not available on this local environment, use the generated reset link shown below.';
            $_SESSION['student_reset_link'] = $resetLink;
        } else {
            $notice = 'If the email matches an active student account, a reset link will be sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Password Reset</title>
  <link rel="icon" type="image/svg+xml" href="../assets/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../admin-portal/css/admin.css">
</head>
<body class="login-body">
  <main class="login-shell">
    <section class="login-showcase" aria-label="College Feedback System"><div class="login-orb login-orb-one" aria-hidden="true"></div><div class="login-orb login-orb-two" aria-hidden="true"></div><div class="login-grid" aria-hidden="true"></div><div class="login-showcase-brand"><span class="brand-mark login-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span><span>College Feedback<small>Patan Nistha Campus</small></span></div><div class="login-showcase-copy"><span class="login-eyebrow">College Feedback System · Student Portal</span><h1>Make your feedback count.</h1><p>Share feedback about campus life, review responses, and manage your account from one secure student workspace.</p><div class="showcase-rule"></div><span class="showcase-note">A private workspace for better campus decisions.</span></div><p class="login-copyright">&copy; <?php echo date('Y'); ?> Patan Nistha Campus</p></section>
    <section class="login-panel"><div class="login-content recovery-content<?php echo $error ? ' has-error' : ''; ?>"><div class="login-brand"><span class="brand-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span><span><strong>Student Portal</strong><small>College Feedback System</small></span></div><div class="login-title recovery-title"><span class="page-kicker">ACCOUNT RECOVERY</span><h1>Reset your password</h1><p>Enter your student email to receive a secure password reset link.</p></div>

        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($notice): ?><div class="otp-sent" role="status"><span class="otp-check" aria-hidden="true">✓</span><span><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
        <?php if (!empty($_SESSION['student_reset_link'])): ?>
          <div class="otp-config-notice">
            <strong>Reset link:</strong>
            <a href="<?php echo htmlspecialchars($_SESSION['student_reset_link'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($_SESSION['student_reset_link'], ENT_QUOTES, 'UTF-8'); ?></a>
          </div>
          <?php unset($_SESSION['student_reset_link']); ?>
        <?php endif; ?>

        <form method="POST" class="recovery-request-form">
          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="student@example.com" required>
          </div>
          <button type="submit" class="btn btn-primary btn-block login-submit">Send reset link</button>
        </form>

        <p class="recovery-back"><a href="login.php">← Back to sign in</a></p>
      </div></section>
  </main>
</body>
</html>
