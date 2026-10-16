<?php
session_start();
require __DIR__ . '/includes/db.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, full_name, email, password_hash, status FROM portal_users WHERE role = 'staff' AND username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid username or password.';
        } else {
          $lastLogin = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE role = 'staff' AND LOWER(email) = LOWER(:email)");
          $lastLogin->execute([':email' => $user['email']]);
            session_regenerate_id(true);
            $_SESSION['staff_id'] = (int) $user['id'];
            $_SESSION['staff_name'] = $user['full_name'];
            $_SESSION['staff_username'] = $user['username'];
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
  <title>Staff Portal Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/staff.css">
</head>
<body class="portal-body">
  <main class="portal-shell">
    <section class="portal-showcase">
      <div class="portal-badge">Staff Portal</div>
      <h1>Manage submissions.</h1>
      <p>Review pending work, track active submissions, and manage user accounts from a single staff dashboard.</p>
      <ul>
        <li>Submission review</li>
        <li>User account management</li>
        <li>Operational workflows</li>
      </ul>
    </section>

    <section class="portal-panel">
      <div class="portal-card">
        <div class="portal-brand">
          <span class="brand-mark"><img src="../assets/logo.svg" alt=""></span>
          <div>
            <strong>Staff Portal</strong>
            <small>Patan Nistha Campus</small>
          </div>
        </div>

        <div class="portal-header">
          <h2>Staff access</h2>
          <p>Sign in to review and coordinate campus submissions.</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <form method="POST" class="portal-form">
          <div class="field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="staff01" required>
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" value="staff123" required>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Sign in</button>
        </form>

        <p class="demo-note">Demo account: <strong>staff01</strong> / <strong>staff123</strong></p>
      </div>
    </section>
  </main>
</body>
</html>
