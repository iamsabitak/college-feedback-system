<?php
session_name('college_feedback_staff'); session_start(); require __DIR__ . '/includes/db.php';
$portalRecoveryRole = 'staff';
$portalRecoveryLabel = 'Staff Portal';
require __DIR__ . '/../portal_recovery.php';
exit;
if (!empty($_SESSION['staff_id'])) { header('Location: dashboard.php'); exit; }
$error = ''; $notice = ''; $resetLink = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string) ($_POST['email'] ?? ''));
  if ($email === '') $error = 'Please enter your account email.';
  else {
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM portal_users WHERE role = 'staff' AND LOWER(email) = LOWER(:email) LIMIT 1"); $stmt->execute([':email' => $email]); $user = $stmt->fetch();
    if ($user) {
      $token = bin2hex(random_bytes(32)); $pdo->prepare('INSERT INTO portal_reset_tokens (user_id, token, expires_at, used_at, created_at) VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NULL, NOW())')->execute([':user_id' => $user['id'], ':token' => $token]);
      $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/college-feedback-system/staff-portal/reset_password.php?token=' . urlencode($token);
      @mail($user['email'], 'Staff Portal Password Reset', "Hello {$user['full_name']},\n\nReset your password: {$resetLink}\n\nThis link expires in 30 minutes.");
      $notice = 'If the email matches a staff account, a secure reset link has been sent.';
    } else $notice = 'If the email matches a staff account, a secure reset link has been sent.';
  }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset Password | Staff Portal</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../admin-portal/css/admin.css"></head><body class="login-body"><main class="login-shell"><section class="login-showcase"><div class="login-orb login-orb-one"></div><div class="login-orb login-orb-two"></div><div class="login-grid"></div><div class="login-showcase-brand"><span class="brand-mark login-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span><span>College Feedback<small>Patan Nistha Campus</small></span></div><div class="login-showcase-copy"><span class="login-eyebrow">College Feedback System · Staff Portal</span><h1>Support the campus.</h1><p>Review campus feedback and manage student access from one secure staff workspace.</p><div class="showcase-rule"></div><span class="showcase-note">A practical workspace for a better campus.</span></div></section><section class="login-panel"><div class="login-content recovery-content<?php echo $error ? ' has-error' : ''; ?>"><div class="login-brand"><span class="brand-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span><span><strong>Staff Portal</strong><small>College Feedback System</small></span></div><div class="login-title recovery-title"><span class="page-kicker">ACCOUNT RECOVERY</span><h1>Reset your password</h1><p>Enter your staff email to receive a secure password reset link.</p></div><?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><?php if ($notice): ?><div class="otp-sent"><span class="otp-check">✓</span><span><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?><?php if ($resetLink): ?><div class="otp-config-notice"><strong>Local reset link:</strong> <a href="<?php echo htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8'); ?>">Open reset page</a></div><?php endif; ?><form method="post" class="recovery-request-form"><div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" placeholder="staff@example.com" required autofocus></div><button class="btn btn-primary btn-block login-submit" type="submit">Send reset link</button></form><p class="recovery-back"><a href="login.php">← Back to sign in</a></p></div></section></main></body></html>
