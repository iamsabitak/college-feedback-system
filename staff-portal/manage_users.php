<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

$message = '';
$error = '';
$csrfToken = $_SESSION['staff_student_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['staff_student_csrf'] = $csrfToken;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($csrfToken, (string) ($_POST['csrf_token'] ?? ''))) {
  $error = 'The form expired. Refresh the page and try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
  $studentId = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';

  if ($username === '' || $fullName === '' || $email === '' || $studentId === '' || $password === '') {
        $error = 'All fields are required.';
  } elseif (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
    $error = 'Username must be 3-60 characters using letters, numbers, dots, underscores, or hyphens.';
  } elseif ((function_exists('mb_strlen') ? mb_strlen($fullName) : strlen($fullName)) > 100
    || (function_exists('mb_strlen') ? mb_strlen($email) : strlen($email)) > 100
    || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Enter a valid name and email address.';
  } elseif (!preg_match('/^[A-Za-z0-9\/-]{3,30}$/', $studentId)) {
    $error = 'Student ID must be 3-30 characters using letters, numbers, /, or -.';
  } elseif (strlen($password) < 8) {
    $error = 'Password must be at least 8 characters long.';
    } else {
    $identityCheck = $pdo->prepare(
      'SELECT id FROM users WHERE LOWER(email) = LOWER(:user_email) OR LOWER(username) = LOWER(:user_username) OR LOWER(student_id) = LOWER(:student_id)
       UNION ALL
       SELECT id FROM portal_users WHERE LOWER(email) = LOWER(:portal_email) OR LOWER(username) = LOWER(:portal_username)
       LIMIT 1'
    );
    $identityCheck->execute([
      ':user_email' => $email,
      ':user_username' => $username,
      ':student_id' => $studentId,
      ':portal_email' => $email,
      ':portal_username' => $username,
    ]);
    if ($identityCheck->fetchColumn()) {
      $error = 'That student ID, username, or email already exists.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
      $pdo->beginTransaction();
      $userStmt = $pdo->prepare(
        'INSERT INTO users (full_name, username, email, student_id, role, password_hash, status, must_change_password, approval_status, created_by_staff_id)
         VALUES (:full_name, :username, :email, :student_id, \'student\', :password_hash, \'active\', 0, \'pending\', :staff_id)'
      );
      $userStmt->execute([
        ':full_name' => $fullName,
        ':username' => $username,
        ':email' => $email,
        ':student_id' => $studentId,
        ':password_hash' => $hash,
        ':staff_id' => (int) $_SESSION['staff_id'],
      ]);
      $portalStmt = $pdo->prepare(
        'INSERT INTO portal_users (role, username, full_name, email, password_hash, status, approval_status)
         VALUES (\'student\', :username, :full_name, :email, :password_hash, \'active\', \'pending\')'
      );
      $portalStmt->execute([
        ':username' => $username,
        ':full_name' => $fullName,
        ':email' => $email,
        ':password_hash' => $hash,
      ]);
      $pdo->commit();
      $message = 'Student identity sent to the administrator for approval. Login is enabled after approval.';
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      error_log('Staff-created student account failed: ' . $e->getMessage());
      $error = 'Unable to create the student identity. Check that the details are unique and try again.';
        }
    }
    }
}

$usersStmt = $pdo->query(
  'SELECT pu.id, pu.username, pu.full_name, pu.email, pu.status,
      u.student_id, u.approval_status
   FROM portal_users pu
   LEFT JOIN users u ON LOWER(u.email) = LOWER(pu.email) AND u.role = \'student\'
   WHERE pu.role = \'student\'
   ORDER BY pu.full_name'
);
$users = $usersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff User Management</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/staff.css">
</head>
<body class="dashboard-body">
  <aside class="dashboard-sidebar">
    <div class="sidebar-brand">
      <span class="brand-mark"><img src="../assets/logo.svg" alt=""></span>
      <div>
        <strong>Staff Portal</strong>
        <small>Patan Nistha Campus</small>
      </div>
    </div>

    <nav class="sidebar-nav">
      <a href="dashboard.php">Dashboard</a>
      <a class="active" href="manage_users.php">Manage users</a>
      <a href="logout.php">Log out</a>
    </nav>
  </aside>

  <main class="dashboard-main">
    <header class="dashboard-header">
      <div>
        <span class="page-kicker">User accounts</span>
        <h1>Account management</h1>
      </div>
    </header>

    <section class="content-card lower-space">
      <div class="card-header">
        <h2>Student identities</h2>
      </div>
      <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <p>New student identities require administrator approval before the student can sign in.</p>
      <button type="button" class="btn btn-primary" data-open-student-dialog>Create student identity</button>
    </section>

    <dialog class="student-identity-dialog" id="studentIdentityDialog" aria-labelledby="studentIdentityTitle">
      <div class="dialog-heading">
        <div><span class="page-kicker">Staff action</span><h2 id="studentIdentityTitle">Create student identity</h2><p>Admin approval is required before the student can sign in.</p></div>
        <button type="button" class="dialog-close" data-close-student-dialog aria-label="Close dialog">&times;</button>
      </div>
      <form method="POST" class="submission-form two-column-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="field">
          <label for="student_id">Student ID</label>
          <input type="text" id="student_id" name="student_id" maxlength="30" required>
        </div>
        <div class="field">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" maxlength="100" required>
        </div>
        <div class="field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" maxlength="60" required>
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" maxlength="100" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" minlength="8" required>
        </div>
        <div class="dialog-actions">
          <button type="button" class="btn btn-outline" data-close-student-dialog>Cancel</button>
          <button type="submit" class="btn btn-primary">Send for approval</button>
        </div>
      </form>
    </dialog>

    <section class="content-card">
      <div class="card-header">
        <h2>Current users</h2>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Student ID</th>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Approval</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?php echo htmlspecialchars((string) ($user['student_id'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(ucfirst((string) ($user['approval_status'] ?? 'approved')), ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
<script>
const studentIdentityDialog = document.getElementById('studentIdentityDialog');
document.querySelector('[data-open-student-dialog]')?.addEventListener('click', () => studentIdentityDialog.showModal());
document.querySelectorAll('[data-close-student-dialog]').forEach((button) => {
  button.addEventListener('click', () => studentIdentityDialog.close());
});
studentIdentityDialog?.addEventListener('click', (event) => {
  if (event.target === studentIdentityDialog) studentIdentityDialog.close();
});
<?php if ($error): ?>
studentIdentityDialog?.showModal();
<?php endif; ?>
</script>
</body>
</html>
