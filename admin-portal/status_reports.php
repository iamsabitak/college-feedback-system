<?php
require 'includes/auth.php';
require 'includes/db.php';
require __DIR__ . '/../portal_pagination.php';

$pageTitle = 'Reports';
$active = 'status-reports';
$search = trim((string) ($_GET['search'] ?? ''));
$source = trim((string) ($_GET['source'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$rating = trim((string) ($_GET['rating'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$allowedSources = ['student', 'staff'];
$allowedStatuses = ['New', 'Reviewed', 'Resolved'];
if (!in_array($source, $allowedSources, true)) {
  $source = '';
}
if (!in_array($status, $allowedStatuses, true)) {
  $status = '';
}
if (!in_array($rating, ['', '1', '2', '3', '4', '5'], true)) {
  $rating = '';
}
foreach (['dateFrom', 'dateTo'] as $dateVariable) {
  if ($$dateVariable !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $$dateVariable)) {
    $$dateVariable = '';
  }
}

$allowedPageSizes = [10, 20, 50];
$perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
if (!in_array($perPage, $allowedPageSizes, true)) {
  $perPage = 10;
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$searchSql = <<<'SQL'
  SELECT f.id,
       CASE WHEN f.submitter_type = 'Student' THEN 'Student' ELSE 'Staff' END AS source,
       CASE WHEN f.submitter_type = 'Student' THEN 'student' ELSE 'staff' END AS source_key,
       f.submit_date AS created_at,
      CASE WHEN f.is_anonymous = 1 THEN 'Anonymous' ELSE COALESCE(NULLIF(f.submitter_name, ''), 'Unnamed') END AS submitted_by,
      CASE WHEN f.submitter_type = 'Student' THEN 'Student' ELSE 'Staff' END AS audience,
      f.category, f.message AS description,
       f.status, f.rating
  FROM feedback f
  UNION ALL
  SELECT ps.id,
       CASE WHEN pu.role = 'student' THEN 'Student' ELSE 'Staff' END AS source,
       CASE WHEN pu.role = 'student' THEN 'student' ELSE 'staff' END AS source_key,
       ps.created_at, pu.full_name AS submitted_by,
       CASE pu.role WHEN 'student' THEN 'Student' ELSE 'Staff' END AS audience,
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
if ($rating !== '') {
  $where[] = 'reports.rating = :rating';
  $params[':rating'] = (int) $rating;
}
if ($dateFrom !== '') {
  $where[] = 'DATE(reports.created_at) >= :date_from';
  $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
  $where[] = 'DATE(reports.created_at) <= :date_to';
  $params[':date_to'] = $dateTo;
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

if (($_GET['download'] ?? '') === 'csv') {
  $csvStmt = $pdo->prepare('SELECT reports.*' . $reportsFromSql . ' ORDER BY reports.created_at DESC');
  foreach ($params as $key => $value) {
    $csvStmt->bindValue($key, $value);
  }
  $csvStmt->execute();
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="reports-' . date('Y-m-d-His') . '.csv"');
  $output = fopen('php://output', 'wb');
  fputcsv($output, ['Date', 'Source', 'Submitted by', 'Audience', 'Category', 'Submission', 'Status', 'Rating']);
  while ($row = $csvStmt->fetch()) {
    fputcsv($output, [
      date('Y-m-d H:i', strtotime($row['created_at'])), $row['source'], $row['submitted_by'], $row['audience'],
      $row['category'], $row['description'], $row['status'], $row['rating'] === null ? '' : $row['rating'] . ' / 5',
    ]);
  }
  fclose($output);
  exit;
}

$activeFilterCount = count(array_filter([$source, $status, $rating, $dateFrom, $dateTo], static fn ($value) => $value !== ''));
$filteredCsvQuery = http_build_query(array_filter([
  'download' => 'csv', 'search' => $search, 'source' => $source, 'status' => $status,
  'rating' => $rating, 'date_from' => $dateFrom, 'date_to' => $dateTo,
], static fn ($value) => $value !== ''));

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
    <h1>Reports</h1>
    <p>Review student and staff submissions, including resolved and actionable totals.</p>
  </div>
</section>

<div class="dashboard-toolbar">
  <div class="list-toolbar-actions">
    <form method="GET" class="live-search-form" data-live-search>
      <?php foreach (['source' => $source, 'status' => $status, 'rating' => $rating, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'per_page' => $perPage] as $name => $value): if ($value !== ''): ?><input type="hidden" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; endforeach; ?>
      <label class="sr-only" for="report-search">Search reports</label>
      <input id="report-search" name="search" type="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search name, category or submission" autocomplete="off">
    </form>
    <div class="filter-menu-wrap">
      <button type="button" class="btn btn-outline filter-open" data-filter-open aria-expanded="false" aria-controls="filterPanel"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10m-7 6h4"/><circle cx="8" cy="6" r="2" fill="currentColor"/><circle cx="15" cy="12" r="2" fill="currentColor"/></svg>Filters<?php if ($activeFilterCount): ?><span class="filter-count"><?php echo $activeFilterCount; ?></span><?php endif; ?></button>
      <section class="filter-panel" id="filterPanel" aria-label="Report filters" hidden>
        <form method="GET" class="filter-panel-form">
          <div class="filter-panel-grid">
            <div class="filter-group"><label for="report-source">Source</label><select id="report-source" name="source"><option value="">All sources</option><option value="student" <?php echo $source === 'student' ? 'selected' : ''; ?>>Student</option><option value="staff" <?php echo $source === 'staff' ? 'selected' : ''; ?>>Staff</option></select></div>
            <div class="filter-group"><label for="report-status">Status</label><select id="report-status" name="status"><option value="">All statuses</option><?php foreach ($allowedStatuses as $statusOption): ?><option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $status === $statusOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
            <div class="filter-group"><label for="report-rating">Rating</label><select id="report-rating" name="rating"><option value="">Any rating</option><?php for ($star = 5; $star >= 1; $star--): ?><option value="<?php echo $star; ?>" <?php echo $rating === (string) $star ? 'selected' : ''; ?>><?php echo $star; ?> star<?php echo $star === 1 ? '' : 's'; ?></option><?php endfor; ?></select></div>
            <div class="filter-group"><label for="report-date-from">From</label><input id="report-date-from" name="date_from" type="date" value="<?php echo htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="filter-group"><label for="report-date-to">To</label><input id="report-date-to" name="date_to" type="date" value="<?php echo htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>"></div>
          </div>
          <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?><input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
          <div class="filter-actions"><a href="status_reports.php" class="btn btn-outline btn-sm">Clear all</a><button type="submit" class="btn btn-primary btn-sm">Apply filters</button></div>
        </form>
      </section>
    </div>
    <a class="btn btn-outline" href="status_reports.php?<?php echo htmlspecialchars($filteredCsvQuery, ENT_QUOTES, 'UTF-8'); ?>"><svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>Download CSV</a>
  </div>
</div>

<section class="report-summary-grid" aria-label="Filtered report totals">
  <article class="report-summary-card"><span>Filtered submissions</span><strong><?php echo $total; ?></strong></article>
  <article class="report-summary-card"><span>Resolved</span><strong><?php echo (int) ($summary['resolved'] ?? 0); ?></strong></article>
  <article class="report-summary-card"><span>Can still be resolved</span><strong><?php echo (int) ($summary['actionable'] ?? 0); ?></strong></article>
</section>

<div class="table-card list-table-card pagination-contained">
  <div class="table-wrap list-table-wrap">
    <table class="list-table">
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
    </table>
  </div>
  <?php renderPortalPagination($total, $page, $pages, $perPage, $allowedPageSizes, ['search' => $search, 'source' => $source, 'status' => $status, 'rating' => $rating, 'date_from' => $dateFrom, 'date_to' => $dateTo]); ?>
</div>

<?php include 'includes/footer.php'; ?>
