<?php
session_start();
require __DIR__ . '/includes/db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($token === '') {
    $error = 'Reset token is missing.';
} else {
    $stmt = $pdo->prepare('SELECT rt.user_id, rt.expires_at, u.email FROM portal_reset_tokens rt INNER JOIN portal_users u ON u.id = rt.user_id WHERE rt.token = :token AND rt.used_at IS NULL AND rt.expires_at > NOW() LIMIT 1');
    $stmt->execute([':token' => $token]);
    $record = $stmt->fetch();

    if (!$record) {
        $error = 'This password reset link has expired or is invalid.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['token'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($postToken === '') {
        $error = 'Reset token is missing.';
    } elseif ($newPassword === '' || $confirmPassword === '') {
        $error = 'Please enter and confirm your new password.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $verify = $pdo->prepare('SELECT rt.user_id, rt.expires_at FROM portal_reset_tokens rt WHERE rt.token = :token AND rt.used_at IS NULL AND rt.expires_at > NOW() LIMIT 1');
        $verify->execute([':token' => $postToken]);
        $valid = $verify->fetch();

        if (!$valid) {
            $error = 'This reset link is no longer valid.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateUser = $pdo->prepare('UPDATE portal_users SET password_hash = :password_hash WHERE id = :user_id LIMIT 1');
            $updateUser->execute([':password_hash' => $hash, ':user_id' => $valid['user_id']]);

            $markUsed = $pdo->prepare('UPDATE portal_reset_tokens SET used_at = NOW() WHERE token = :token LIMIT 1');
            $markUsed->execute([':token' => $postToken]);

            $success = 'Your password was updated successfully.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Student Password</title>
  <link rel="stylesheet" href="css/student.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="portal-body">
  <main class="portal-shell narrow-shell">
    <section class="portal-panel single-panel">
      <div class="portal-card">
        <div class="portal-brand">
          <span class="brand-mark"><img src="../assets/logo.svg" alt=""></span>
          <div>
            <strong>Set New Password</strong>
            <small>Student Portal</small>
          </div>
        </div>

        <div class="portal-header">
          <h2>Choose a new password</h2>
          <p>Use a secure password to protect your student account.</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <?php if (!$error || $success): ?>
          <?php if (!$success): ?>
            <form method="POST" class="portal-form">
              <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
              <div class="field">
                <label for="password">New password</label>
                <input type="password" id="password" name="password" minlength="8" required>
              </div>
              <div class="field">
                <label for="confirm_password">Confirm password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
              </div>
              <button type="submit" class="btn btn-primary btn-block">Update password</button>
            </form>
          <?php else: ?>
            <div class="portal-inline top-space">
              <a href="login.php">Return to sign in</a>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>
