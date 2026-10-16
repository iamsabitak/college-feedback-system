<?php
require 'includes/auth.php';
require 'includes/db.php';

$pageTitle = 'Status Reports';
$active = 'status-reports';
$search = trim((string) ($_GET['search'] ?? ''));
$source = trim((string) ($_GET['source'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$allowedSources = ['public', 'portal'];
$allowedStatuses = ['New', 'Reviewed', 'Resolved'];
if (!in_array($source, $allowedSources, true)) {
  $source = '';
}
if (!in_array($status, $allowedStatuses, true)) {
  $status = '';
}

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$searchSql = <<<'SQL'
  SELECT f.id, 'Public feedback' AS source, 'public' AS source_key,
       f.submit_date AS created_at,
       COALESCE(NULLIF(f.submitter_name, ''), 'Anonymous') AS submitted_by,
      'Public' AS audience, f.category, f.message AS description,
       f.status, f.rating
  FROM feedback f
  UNION ALL
  SELECT ps.id, 'Student/staff portal' AS source, 'portal' AS source_key,
       ps.created_at, pu.full_name AS submitted_by,
       CASE pu.role WHEN 'student' THEN 'Student' ELSE 'Teacher & Staff' END AS audience,
       ps.category, CONCAT(ps.title, ': ', ps.description) AS description,
       CASE ps.status
         WHEN 'Submitted' THEN 'New'
         WHEN 'Under Review' THEN 'Reviewed'
         WHEN 'Completed' THEN 'Resolved'
         ELSE ps.status
       END AS status,
       NULL AS rating
  FROM portal_submissions ps
  INNER JOIN portal_users pu ON pu.id = ps.user_id
SQL;
$where = [];
$params = [];
if ($source !== '') {
  $where[] = 'reports.source_key = :source';
  $params[':source'] = $source;
}
if ($status !== '') {
  $where[] = 'reports.status = :status';
  $params[':status'] = $status;
}
if ($search !== '') {
  $where[] = '(LOWER(reports.submitted_by) LIKE :search_name
    OR LOWER(reports.audience) LIKE :search_audience
    OR LOWER(reports.category) LIKE :search_category
    OR LOWER(reports.description) LIKE :search_description
    OR LOWER(reports.source) LIKE :search_source
    OR LOWER(reports.status) LIKE :search_status
    OR CAST(reports.id AS CHAR) LIKE :search_id
    OR CAST(reports.rating AS CHAR) LIKE :search_rating)';
  $searchTerm = '%' . strtolower($search) . '%';
  $params[':search_name'] = $searchTerm;
  $params[':search_audience'] = $searchTerm;
  $params[':search_category'] = $searchTerm;
  $params[':search_description'] = $searchTerm;
  $params[':search_source'] = $searchTerm;
  $params[':search_status'] = $searchTerm;
  $params[':search_id'] = $searchTerm;
  $params[':search_rating'] = $searchTerm;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$reportsFromSql = ' FROM (' . $searchSql . ') reports' . $whereSql;

$summaryStmt = $pdo->prepare(
  'SELECT COUNT(*) AS total,
      COALESCE(SUM(reports.status IN (\'Resolved\', \'Completed\')), 0) AS resolved,
      COALESCE(SUM(reports.status NOT IN (\'Resolved\', \'Completed\')), 0) AS actionable'
  . $reportsFromSql
);
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();
$total = (int) ($summary['total'] ?? 0);
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$reportsStmt = $pdo->prepare('SELECT reports.*' . $reportsFromSql . ' ORDER BY reports.created_at DESC LIMIT :limit OFFSET :offset');
foreach ($params as $key => $value) {
  $reportsStmt->bindValue($key, $value);
}
$reportsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$reportsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$reportsStmt->execute();
$reportRows = $reportsStmt->fetchAll();

include 'includes/header.php';
?>

<section class="page-head">
  <div>
    <span class="page-kicker">Administration</span>
    <h1>Status review reports</h1>
    <p>Review public feedback and student/staff submissions together, including resolved and actionable totals.</p>
  </div>
</section>

<form class="filter-bar" method="GET">
  <div class="filter-group search">
    <label for="report-search">Search</label>
    <input id="report-search" name="search" type="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name, category or submission details">
  </div>
  <div class="filter-group">
    <label for="report-source">Source</label>
    <select id="report-source" name="source">
      <option value="">All submissions</option>
      <option value="public" <?php echo $source === 'public' ? 'selected' : ''; ?>>Public feedback</option>
      <option value="portal" <?php echo $source === 'portal' ? 'selected' : ''; ?>>Student/staff portal</option>
    </select>
  </div>
  <div class="filter-group">
    <label for="report-status">Status</label>
    <select id="report-status" name="status">
      <option value="">All statuses</option>
      <?php foreach ($allowedStatuses as $statusOption): ?>
        <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $status === $statusOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn btn-primary">Apply</button>
    <a class="btn btn-outline" href="status_reports.php">Clear</a>
  </div>
</form>

<div class="table-card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Source</th>
          <th>Submitted by</th>
          <th>Audience</th>
          <th>Category</th>
          <th>Submission</th>
          <th>Status</th>
          <th>Rating</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$reportRows): ?>
          <tr><td colspan="8"><div class="empty-state"><h3>No submissions found</h3><p>Try changing the search or filters.</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($reportRows as $row): ?>
            <?php $statusClass = in_array($row['status'], ['Resolved', 'Completed'], true) ? 'badge-resolved' : (in_array($row['status'], ['Reviewed', 'Under Review'], true) ? 'badge-reviewed' : 'badge-new'); ?>
            <tr>
              <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($row['source'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($row['submitted_by'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($row['audience'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="cell-msg" title="<?php echo htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td><?php echo $row['rating'] === null ? '—' : (int) $row['rating'] . ' / 5'; ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="8" style="padding:14px 18px; border-top:2px solid var(--line); color:var(--ink-soft); font-size:12.5px;">
            Filtered submissions: <strong><?php echo $total; ?></strong>
            <span style="margin-left:16px;">Resolved: <strong><?php echo (int) ($summary['resolved'] ?? 0); ?></strong></span>
            <span style="margin-left:16px;">Can still be resolved: <strong><?php echo (int) ($summary['actionable'] ?? 0); ?></strong></span>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
  <div class="table-toolbar" style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 18px; border-top:1px solid var(--line); flex-wrap:wrap;">
    <span>Page <?php echo $page; ?> of <?php echo $pages; ?> · 10 rows per page</span>
    <div style="display:flex; gap:8px; align-items:center;">
      <button class="icon-btn" type="button" aria-label="First page" data-report-page="1" <?php echo $page <= 1 ? 'disabled' : ''; ?>>«</button>
      <button class="icon-btn" type="button" aria-label="Previous page" data-report-page="<?php echo max(1, $page - 1); ?>" <?php echo $page <= 1 ? 'disabled' : ''; ?>>‹</button>
      <button class="icon-btn" type="button" aria-label="Next page" data-report-page="<?php echo min($pages, $page + 1); ?>" <?php echo $page >= $pages ? 'disabled' : ''; ?>>›</button>
      <button class="icon-btn" type="button" aria-label="Last page" data-report-page="<?php echo $pages; ?>" <?php echo $page >= $pages ? 'disabled' : ''; ?>>»</button>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('[data-report-page]').forEach((button) => {
  button.addEventListener('click', () => {
    const url = new URL(window.location.href);
    url.searchParams.set('page', button.dataset.reportPage);
    window.location.href = url.toString();
  });
});
</script>

<?php include 'includes/footer.php'; ?>
