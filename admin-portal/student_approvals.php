<?php
require 'includes/auth.php';
require 'includes/db.php';

$pageTitle = 'Student approvals';
$active = 'student-approvals';
$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
if (!in_array($status, ['', 'pending', 'approved', 'rejected'], true)) {
    $status = '';
}
$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$where = ['u.role = \'student\'', 'u.created_by_staff_id IS NOT NULL'];
$params = [];
if ($status !== '') {
    $where[] = 'u.approval_status = :status';
    $params[':status'] = $status;
}
if ($search !== '') {
    $where[] = '(LOWER(u.full_name) LIKE :search_name OR LOWER(u.username) LIKE :search_username OR LOWER(u.email) LIKE :search_email OR LOWER(u.student_id) LIKE :search_student_id OR LOWER(staff.full_name) LIKE :search_staff_name)';
    $searchTerm = '%' . strtolower($search) . '%';
    $params[':search_name'] = $searchTerm;
    $params[':search_username'] = $searchTerm;
    $params[':search_email'] = $searchTerm;
    $params[':search_student_id'] = $searchTerm;
    $params[':search_staff_name'] = $searchTerm;
}
$whereSql = implode(' AND ', $where);
$fromSql = ' FROM users u LEFT JOIN portal_users staff ON staff.id = u.created_by_staff_id AND staff.role = \'staff\' WHERE ' . $whereSql;
$countStmt = $pdo->prepare('SELECT COUNT(*)' . $fromSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$rowsStmt = $pdo->prepare(
    'SELECT u.id, u.full_name, u.username, u.email, u.student_id, u.approval_status,
            u.created_at, staff.full_name AS staff_name
     ' . $fromSql . '
     ORDER BY FIELD(u.approval_status, \'pending\', \'rejected\', \'approved\'), u.created_at DESC
     LIMIT :limit OFFSET :offset'
);
foreach ($params as $key => $value) {
    $rowsStmt->bindValue($key, $value);
}
$rowsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$rowsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$rowsStmt->execute();
$students = $rowsStmt->fetchAll();
$csrfToken = $_SESSION['student_approval_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['student_approval_csrf'] = $csrfToken;
$notice = $_SESSION['student_approval_notice'] ?? null;
unset($_SESSION['student_approval_notice']);
$queryParams = array_filter(['search' => $search, 'status' => $status], static fn($value) => $value !== '');

include 'includes/header.php';
?>

<section class="page-head">
  <div>
    <span class="page-kicker">Administration</span>
    <h1>Student identity approvals</h1>
    <p>Review student identities created by staff and approve or reject portal access.</p>
  </div>
</section>

<?php if ($notice): ?>
  <div class="alert <?php echo $notice['type'] === 'success' ? 'alert-success' : 'alert-error'; ?>" role="status"><?php echo htmlspecialchars($notice['message'], ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<form class="filter-bar" method="GET">
  <div class="filter-group search">
    <label for="approval-search">Search</label>
    <input id="approval-search" name="search" type="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Student, ID, email or staff member">
  </div>
  <div class="filter-group">
    <label for="approval-status">Approval status</label>
    <select id="approval-status" name="status">
      <option value="">All statuses</option>
      <?php foreach (['pending', 'approved', 'rejected'] as $statusOption): ?>
        <option value="<?php echo $statusOption; ?>" <?php echo $status === $statusOption ? 'selected' : ''; ?>><?php echo ucfirst($statusOption); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn btn-primary">Apply</button>
    <a class="btn btn-outline" href="student_approvals.php">Clear</a>
  </div>
</form>

<div class="table-card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Student ID</th><th>Name</th><th>Username</th><th>Email</th><th>Created by staff</th><th>Created</th><th>Approval</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php if (!$students): ?>
          <tr><td colspan="8"><div class="empty-state"><h3>No student identities found</h3><p>Try another search or approval filter.</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($students as $student): ?>
            <tr>
              <td><?php echo htmlspecialchars((string) ($student['student_id'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($student['username'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars((string) ($student['staff_name'] ?? 'Staff account'), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($student['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><span class="badge <?php echo $student['approval_status'] === 'approved' ? 'badge-student' : ($student['approval_status'] === 'pending' ? 'badge-visitor' : 'badge-danger'); ?>"><?php echo htmlspecialchars(ucfirst($student['approval_status']), ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td>
                <?php if ($student['approval_status'] === 'pending'): ?>
                  <div class="row-actions">
                    <?php foreach (['approve' => 'Approve', 'reject' => 'Reject'] as $decision => $label): ?>
                      <form method="POST" action="student_approval_action.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="student_id" value="<?php echo (int) $student['id']; ?>">
                        <input type="hidden" name="decision" value="<?php echo $decision; ?>">
                        <button type="submit" class="btn <?php echo $decision === 'approve' ? 'btn-primary' : 'btn-outline'; ?> btn-sm"><?php echo $label; ?></button>
                      </form>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span>-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-toolbar" style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 18px; border-top:1px solid var(--line); flex-wrap:wrap;">
    <span><?php echo $total; ?> staff-created students · Page <?php echo $page; ?> of <?php echo $pages; ?> · 10 per page</span>
    <div style="display:flex; gap:8px; align-items:center;">
      <?php foreach (['First' => 1, 'Previous' => max(1, $page - 1), 'Next' => min($pages, $page + 1), 'Last' => $pages] as $label => $targetPage): ?>
        <?php $disabled = in_array($label, ['First', 'Previous'], true) ? $page <= 1 : $page >= $pages; ?>
        <a class="icon-btn" aria-label="<?php echo $label; ?> page" href="<?php echo htmlspecialchars('student_approvals.php?' . http_build_query($queryParams + ['page' => $targetPage]), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $disabled ? 'aria-disabled="true" tabindex="-1"' : ''; ?>><?php echo ['First' => '«', 'Previous' => '‹', 'Next' => '›', 'Last' => '»'][$label]; ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>