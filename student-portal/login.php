<?php
session_start();
require __DIR__ . '/includes/db.php';

if (!empty($_SESSION['student_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
      $error = 'Please enter both your email and password.';
    } else {
      $stmt = $pdo->prepare("SELECT id, username, full_name, email, password_hash, status, approval_status FROM portal_users WHERE role = 'student' AND LOWER(email) = LOWER(:email) LIMIT 1");
      $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } elseif ($user['approval_status'] === 'pending') {
          $error = 'Your student identity is waiting for administrator approval.';
        } elseif ($user['approval_status'] !== 'approved') {
          $error = 'Your student identity was not approved. Contact a staff member for help.';
        } else {
          $lastLogin = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE role = 'student' AND LOWER(email) = LOWER(:email)");
          $lastLogin->execute([':email' => $user['email']]);
            session_regenerate_id(true);
            $_SESSION['student_id'] = (int) $user['id'];
            $_SESSION['student_name'] = $user['full_name'];
            $_SESSION['student_username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Login | College Feedback System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,600;0,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="../assets/logo.svg">
  <link rel="stylesheet" href="../admin-portal/css/admin.css">
</head>
<body class="login-body">
  <main class="login-shell">
    <section class="login-showcase" aria-label="College Feedback System">
      <div class="login-orb login-orb-one" aria-hidden="true"></div><div class="login-orb login-orb-two" aria-hidden="true"></div><div class="login-grid" aria-hidden="true"></div>
      <div class="login-showcase-brand"><span class="brand-mark login-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span><span>College Feedback<small>Patan Nistha Campus</small></span></div>
      <div class="login-showcase-copy"><span class="login-eyebrow">College Feedback System · Student Portal</span><h1>Make your feedback count.</h1><p>Share feedback about campus life, review responses, and manage your account from one secure student workspace.</p><div class="showcase-rule"></div><span class="showcase-note">Your feedback helps improve campus life.</span></div>
      <p class="login-copyright">&copy; <?php echo date('Y'); ?> Patan Nistha Campus</p>
    </section>

    <section class="login-panel">
      <div class="login-content<?php echo $error ? ' has-error' : ''; ?>">
        <div class="login-brand"><span class="brand-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span><span><strong>Student Portal</strong><small>College Feedback System</small></span></div>
        <div class="login-title"><h1>Student Login</h1><p>Sign in to share feedback and review your feedback history.</p></div>
        <?php if ($error): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form method="POST" novalidate data-login-form>
          <div class="field"><label for="email">Email address</label><input type="email" id="email" name="email" autocomplete="username" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required autofocus></div>
          <div class="field"><label for="password">Password</label><div class="login-password-input"><input type="password" id="password" name="password" autocomplete="current-password" required><button class="login-password-toggle" type="button" data-login-password-toggle aria-label="Show password"><svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg><svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.8M6.1 6.1C3.5 7.9 2 12 2 12s3.5 6 10 6c1.3 0 2.5-.2 3.5-.6"/><circle cx="12" cy="12" r="2.5"/></svg></button></div><a class="forgot-link" href="forgot_password.php">Forgot password?</a></div>
          <button type="submit" class="btn btn-primary btn-block login-submit"><span class="login-submit-content"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Sign In</span><span class="login-spinner" aria-hidden="true"></span></button>
        </form>
        <p class="demo-hint">Demo credentials: <strong>student@example.com</strong> / <strong>student123</strong></p>
      </div>
    </section>
  </main>
<script src="../admin-portal/js/admin.js"></script>
</body>
</html>
