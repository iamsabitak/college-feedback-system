<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/../portal_notifications.php';

$sidebarStaffStmt = $pdo->prepare('SELECT username, full_name, email FROM portal_users WHERE id = :id AND role = \'staff\' LIMIT 1');
$sidebarStaffStmt->execute([':id' => (int) $_SESSION['staff_id']]);
$sidebarStaffUser = $sidebarStaffStmt->fetch() ?: ['username' => $_SESSION['staff_username'] ?? 'staff', 'full_name' => $_SESSION['staff_name'] ?? 'Staff Member', 'email' => ''];
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
      notifyAllAdministrators($pdo, 'student_approval', 'Student account awaiting approval', $fullName . ' has been created by a staff member and needs approval.');
      $pdo->commit();
      $message = 'Student account sent for administrator approval. The student can sign in after approval.';
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      error_log('Staff-created student account failed: ' . $e->getMessage());
      $error = 'Unable to create the student account. Check that the details are unique and try again.';
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
$studentCount = count($users);
$pendingApprovalCount = count(array_filter($users, static function ($user) {
  return ($user['approval_status'] ?? 'approved') === 'pending';
}));
$approvedStudentCount = count(array_filter($users, static function ($user) {
  return ($user['approval_status'] ?? 'approved') === 'approved';
}));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Student Accounts | Staff Portal</title>
  <link rel="icon" type="image/svg+xml" href="../assets/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/staff.css">
  <link rel="stylesheet" href="../portal_sidebar.css">
  <link rel="stylesheet" href="../list_ui.css">
</head>
<body class="dashboard-body">
  <?php
  $portalSidebarRole = 'Staff';
  $portalSidebarUser = $sidebarStaffUser;
  $portalActiveSection = 'manage-students';
  $portalCurrentPage = 'manage-users';
  $portalCanManageStudents = true;
  include __DIR__ . '/../portal_sidebar.php';
  ?>

  <main class="dashboard-main">
    <header class="dashboard-header">
      <div>
        <span class="page-kicker">Staff tools</span>
        <h1>Create Student Accounts</h1>
      </div>
      <div class="user-chip"><?php echo htmlspecialchars($sidebarStaffUser['username'] ?? 'staff', ENT_QUOTES, 'UTF-8'); ?></div>
    </header>

    <section class="content-card manage-users-intro lower-space">
      <div class="manage-users-intro-copy">
        <span class="page-kicker">Student access</span>
        <h2>Register a student</h2>
        <p>Create a portal account and send it for administrator approval. Students can sign in after approval.</p>
      </div>
      <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <div class="manage-users-actions"><div class="account-summary-grid"><div><span>Total students</span><strong><?php echo $studentCount; ?></strong></div><div><span>Awaiting approval</span><strong><?php echo $pendingApprovalCount; ?></strong></div><div><span>Approved</span><strong><?php echo $approvedStudentCount; ?></strong></div></div><button type="button" class="btn btn-primary" data-open-student-dialog>Create student account</button></div>
    </section>

    <dialog class="student-identity-dialog" id="studentIdentityDialog" aria-labelledby="studentIdentityTitle">
      <div class="dialog-heading">
        <div><span class="page-kicker">Student access</span><h2 id="studentIdentityTitle">Create student account</h2><p>The account will be sent for administrator approval.</p></div>
        <button type="button" class="dialog-close" data-close-student-dialog aria-label="Close dialog">&times;</button>
      </div>
      <?php if ($error): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <form method="POST" class="submission-form two-column-form" data-confirm data-confirm-title="Create student account?" data-confirm-message="The new student account will be sent to an administrator for approval." data-confirm-label="Create account">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="field">
          <label for="student_id">Student ID</label>
          <input type="text" id="student_id" name="student_id" maxlength="30" value="<?php echo htmlspecialchars($_POST['student_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="field">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" maxlength="100" value="<?php echo htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" maxlength="60" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" maxlength="100" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" minlength="8" required>
        </div>
        <div class="dialog-actions">
          <button type="button" class="btn btn-outline" data-close-student-dialog>Cancel</button>
          <button type="submit" class="btn btn-primary">Create and send for approval</button>
        </div>
      </form>
    </dialog>

    <section class="content-card student-directory-heading">
      <div class="student-table-heading"><div><span class="page-kicker">Directory</span><h2>Student accounts</h2></div><label class="student-search"><span class="sr-only">Search student accounts</span><input type="search" placeholder="Search students" data-student-filter></label></div>
    </section>
    <div class="list-table-card pagination-contained">
      <div class="table-wrap list-table-wrap">
        <table class="data-table list-table">
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
              <tr data-student-row>
                <td><?php echo htmlspecialchars((string) ($user['student_id'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="student-name-cell"><span class="student-initials" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($user['full_name'], 0, 1) . substr(strstr($user['full_name'], ' ') ?: '', 1, 1)), ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong></span></td>
                <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                <?php $approval = (string) ($user['approval_status'] ?? 'approved'); ?><td><span class="student-approval student-approval-<?php echo htmlspecialchars($approval, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst($approval), ENT_QUOTES, 'UTF-8'); ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="pagination-bar" data-student-pagination>
        <div class="pagination-left-group">
          <p class="pagination-summary" data-student-pagination-summary></p>
          <label class="pagination-size-form"><span>Rows per page:</span><select data-student-page-size><option value="10">10</option><option value="20">20</option><option value="50">50</option></select></label>
        </div>
        <nav class="pagination-nav" aria-label="Student account pages">
          <button class="pagination-page-btn" type="button" data-student-page-nav="first" aria-label="First page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3 4 8l4 5M12 3 8 8l4 5"/></svg></button>
          <button class="pagination-page-btn" type="button" data-student-page-nav="prev" aria-label="Previous page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m10 3-5 5 5 5"/></svg></button>
          <span class="pagination-page-label" data-student-pagination-page></span>
          <button class="pagination-page-btn" type="button" data-student-page-nav="next" aria-label="Next page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m6 3 5 5-5 5"/></svg></button>
          <button class="pagination-page-btn" type="button" data-student-page-nav="last" aria-label="Last page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3l4 5-4 5M4 3l4 5-4 5"/></svg></button>
        </nav>
      </div>
    </div>
  </main>
<script>
const studentIdentityDialog = document.getElementById('studentIdentityDialog');
<?php if ($error): ?>studentIdentityDialog?.showModal();<?php endif; ?>
document.querySelector('[data-open-student-dialog]')?.addEventListener('click', () => studentIdentityDialog.showModal());
document.querySelectorAll('[data-close-student-dialog]').forEach((button) => {
  button.addEventListener('click', () => studentIdentityDialog.close());
});
studentIdentityDialog?.addEventListener('click', (event) => {
  if (event.target === studentIdentityDialog) studentIdentityDialog.close();
});
const studentFilter = document.querySelector('[data-student-filter]');
const studentRows = Array.from(document.querySelectorAll('[data-student-row]'));
const studentPageSize = document.querySelector('[data-student-page-size]');
const studentPageSummary = document.querySelector('[data-student-pagination-summary]');
const studentPageLabel = document.querySelector('[data-student-pagination-page]');
const studentPageButtons = Array.from(document.querySelectorAll('[data-student-page-nav]'));
let studentCurrentPage = 1;
let studentRowsPerPage = Number(studentPageSize?.value || 10);

function renderStudentAccounts() {
  const query = studentFilter?.value.trim().toLowerCase() || '';
  const filteredRows = studentRows.filter((row) => row.textContent.toLowerCase().includes(query));
  const totalPages = Math.max(1, Math.ceil(filteredRows.length / studentRowsPerPage));
  studentCurrentPage = Math.min(studentCurrentPage, totalPages);
  const firstVisible = filteredRows.length ? (studentCurrentPage - 1) * studentRowsPerPage : 0;
  const lastVisible = Math.min(firstVisible + studentRowsPerPage, filteredRows.length);
  studentRows.forEach((row) => { row.hidden = true; });
  filteredRows.slice(firstVisible, lastVisible).forEach((row) => { row.hidden = false; });
  if (studentPageSummary) studentPageSummary.textContent = `Showing ${filteredRows.length ? firstVisible + 1 : 0} to ${lastVisible} of ${filteredRows.length} results`;
  if (studentPageLabel) studentPageLabel.textContent = `Page ${studentCurrentPage} of ${totalPages}`;
  studentPageButtons.forEach((button) => {
    const nav = button.dataset.studentPageNav;
    button.disabled = (nav === 'first' || nav === 'prev') ? studentCurrentPage === 1 : studentCurrentPage === totalPages;
  });
}

studentFilter?.addEventListener('input', () => { studentCurrentPage = 1; renderStudentAccounts(); });
studentPageSize?.addEventListener('change', () => { studentRowsPerPage = Number(studentPageSize.value); studentCurrentPage = 1; renderStudentAccounts(); });
studentPageButtons.forEach((button) => button.addEventListener('click', () => {
  const query = studentFilter?.value.trim().toLowerCase() || '';
  const totalPages = Math.max(1, Math.ceil(studentRows.filter((row) => row.textContent.toLowerCase().includes(query)).length / studentRowsPerPage));
  if (button.dataset.studentPageNav === 'first') studentCurrentPage = 1;
  if (button.dataset.studentPageNav === 'prev') studentCurrentPage = Math.max(1, studentCurrentPage - 1);
  if (button.dataset.studentPageNav === 'next') studentCurrentPage = Math.min(totalPages, studentCurrentPage + 1);
  if (button.dataset.studentPageNav === 'last') studentCurrentPage = totalPages;
  renderStudentAccounts();
}));
renderStudentAccounts();
</script>
</body>
</html>
