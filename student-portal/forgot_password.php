<?php
session_start();
require __DIR__ . '/includes/db.php';

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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/student.css">
</head>
<body class="portal-body">
  <main class="portal-shell narrow-shell">
    <section class="portal-panel single-panel">
      <div class="portal-card">
        <div class="portal-brand">
          <span class="brand-mark"><img src="../assets/logo.svg" alt=""></span>
          <div>
            <strong>Reset Password</strong>
            <small>Student Portal</small>
          </div>
        </div>

        <div class="portal-header">
          <h2>Forgot password?</h2>
          <p>Enter the email address tied to your student account and we will generate a secure reset link.</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($notice): ?><div class="alert alert-success"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if (!empty($_SESSION['student_reset_link'])): ?>
          <div class="reset-link-box">
            <strong>Reset link:</strong>
            <a href="<?php echo htmlspecialchars($_SESSION['student_reset_link'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($_SESSION['student_reset_link'], ENT_QUOTES, 'UTF-8'); ?></a>
          </div>
          <?php unset($_SESSION['student_reset_link']); ?>
        <?php endif; ?>

        <form method="POST" class="portal-form">
          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="student@example.com" required>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
        </form>

        <div class="portal-inline top-space">
          <a href="login.php">&larr; Back to sign in</a>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
