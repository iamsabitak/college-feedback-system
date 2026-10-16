<?php
session_start();
require __DIR__ . '/includes/db.php';

if (!empty($_SESSION['student_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both your username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, full_name, email, password_hash, status, approval_status FROM portal_users WHERE role = 'student' AND username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid username or password.';
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
  <title>Student Portal Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="../assets/logo.svg">
  <link rel="stylesheet" href="css/student.css">
</head>
<body class="portal-body">
  <main class="portal-shell">
    <section class="portal-showcase">
      <div class="portal-badge">Student Portal</div>
      <h1>Track your progress.</h1>
      <p>Review your submissions, monitor status updates, and resolve account access quickly with a secure student dashboard.</p>
      <ul>
        <li>Submission tracking</li>
        <li>Password reset support</li>
        <li>Account security</li>
      </ul>
    </section>

    <section class="portal-panel">
      <div class="portal-card">
        <div class="portal-brand">
          <span class="brand-mark"><img src="../assets/logo.svg" alt=""></span>
          <div>
            <strong>Student Portal</strong>
            <small>Patan Nistha Campus</small>
          </div>
        </div>

        <div class="portal-header">
          <h2>Welcome back</h2>
          <p>Sign in to review your academic and campus submissions.</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" class="portal-form">
          <div class="field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? 'student01', ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" value="student123" required>
          </div>
          <div class="portal-inline">
            <a href="forgot_password.php">Forgot password?</a>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Sign in</button>
        </form>

        <p class="demo-note">Demo account: <strong>student01</strong> / <strong>student123</strong></p>
      </div>
    </section>
  </main>
</body>
</html>
