<?php
/**
 * Authenticates an administrator using password verification and starts a session.
 *
 * Application-owned logic for login.php.
 */
session_start();

// The PHP session stores the signed-in administrator ID for protected pages.
require 'includes/db.php';

// Already logged in? Go straight to the dashboard.
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = $_SESSION['login_notice'] ?? '';
unset($_SESSION['login_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both your username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admin WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $admin = $stmt->fetch();

        // Generic error message on failure — never reveal whether the username exists (Module 3).
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $error = 'Invalid username or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['notification_scope'] = bin2hex(random_bytes(12));
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
<title>Admin Login &middot; College Feedback System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,600;0,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="../assets/logo.svg">
<link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-body">

  <main class="login-shell">
    <section class="login-showcase" aria-label="College Feedback System">
      <div class="login-orb login-orb-one" aria-hidden="true"></div><div class="login-orb login-orb-two" aria-hidden="true"></div><div class="login-grid" aria-hidden="true"></div>
      <div class="login-showcase-brand"><span class="brand-mark login-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span><span>College Feedback<small>Patan Nistha Campus</small></span></div>
      <div class="login-showcase-copy"><span class="login-eyebrow">College Feedback System · Admin Portal</span><h1>Welcome back.</h1><p>Review, search, and manage every feedback submission from students, staff, and visitors at Patan Nistha Campus.</p><div class="showcase-rule"></div><span class="showcase-note">A private workspace for better campus decisions.</span></div>
      <p class="login-copyright">&copy; <?php echo date('Y'); ?> Patan Nistha Campus</p>
    </section>

    <section class="login-panel">
      <div class="login-content<?php echo $error ? ' has-error' : ''; ?>">
        <div class="login-brand"><span class="brand-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span><span><strong>Admin Portal</strong><small>College Feedback System</small></span></div>
        <div class="login-title"><h1>Admin Login</h1><p>Sign in to manage feedback submissions.</p></div>
        <?php if ($success): ?><div class="otp-success login-success" role="status"><span class="otp-check" aria-hidden="true">✓</span><span><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST" novalidate data-login-form>
          <div class="field"><label for="username">Username</label><input type="text" id="username" name="username" autocomplete="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus></div>
          <div class="field"><label for="password">Password</label><div class="login-password-input"><input type="password" id="password" name="password" autocomplete="current-password" required><button class="login-password-toggle" type="button" data-login-password-toggle aria-label="Show password"><svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg><svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.8M6.1 6.1C3.5 7.9 2 12 2 12s3.5 6 10 6c1.3 0 2.5-.2 3.5-.6"/><circle cx="12" cy="12" r="2.5"/></svg></button></div><a class="forgot-link" href="forgot_password.php">Forgot password?</a></div>
          <label class="remember-row"><input type="checkbox" name="remember" value="1"><span>Remember me on this device</span></label>
          <button type="submit" class="btn btn-primary btn-block login-submit"><span class="login-submit-content"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Sign In</span><span class="login-spinner" aria-hidden="true"></span></button>
        </form>
        <p class="demo-hint">Demo credentials: <strong>admin</strong> / <strong>admin123</strong></p>
      </div>
    </section>
  </main>

<script src="js/admin.js"></script>
</body>
</html>
