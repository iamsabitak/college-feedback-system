<?php
/**
 * Loads dashboard statistics, charts, recent activity, and searchable feedback data.
 *
 * Application-owned logic for dashboard.php.
 */
require 'includes/auth.php';
require 'includes/db.php';

// feedback_list.php reuses this data view while keeping Dashboard overview-only.
$isFeedbackList = !empty($feedbackListOnly);

/* ---------- Summary statistics (Module 4) ---------- */

// These queries calculate the dashboard totals, ratings, status counts, and trend data.
$total = $pdo->query('SELECT COUNT(*) FROM feedback')->fetchColumn();
$avgRating = $pdo->query('SELECT ROUND(AVG(rating),1) FROM feedback')->fetchColumn();
$todayCount = $pdo->query("SELECT COUNT(*) FROM feedback WHERE DATE(submit_date) = CURDATE()")->fetchColumn();
$yesterdayCount = $pdo->query('SELECT COUNT(*) FROM feedback WHERE submit_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND submit_date < CURDATE()')->fetchColumn();
$newTodayStmt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE status = :status AND submit_date >= CURDATE() AND submit_date < DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
$newTodayStmt->execute([':status' => 'New']);
$newTodayCount = (int) $newTodayStmt->fetchColumn();
$newYesterdayStmt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE status = :status AND submit_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND submit_date < CURDATE()");
$newYesterdayStmt->execute([':status' => 'New']);
$newYesterdayCount = (int) $newYesterdayStmt->fetchColumn();
$topCategoryRow = $pdo->query('SELECT category, COUNT(*) AS total FROM feedback GROUP BY category ORDER BY total DESC, category ASC LIMIT 1')->fetch();
$topCategory = $topCategoryRow['category'] ?? '—';
$topCategoryCount = (int) ($topCategoryRow['total'] ?? 0);
$newCountStmt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE status = :status");
$newCountStmt->execute([':status' => 'New']);
$newCount = $newCountStmt->fetchColumn();
$distributionStmt = $pdo->prepare('SELECT rating, COUNT(*) AS total FROM feedback GROUP BY rating');
$distributionStmt->execute();
$ratingCounts = array_fill(1, 5, 0);
foreach ($distributionStmt->fetchAll() as $distributionRow) {
    $ratingCounts[(int) $distributionRow['rating']] = (int) $distributionRow['total'];
}
$maxRatingCount = max($ratingCounts) ?: 1;
$ratingTotal = array_sum($ratingCounts);
$averageMarker = $avgRating === null ? null : max(10, min(90, (((float) $avgRating - 0.5) / 5) * 100));

// Overview-only widgets. The list page deliberately stays focused on the table.
$recentFeedback = [];
$categoryBreakdown = [];
$weeklyCounts = [];
$weeklyPoints = '';
$weeklyTotal = 0;
if (!$isFeedbackList) {
  $recentFeedback = $pdo->query("SELECT id, submitter_name, submitter_type, is_anonymous, category, rating, submit_date, COALESCE(status, 'New') AS status, message FROM feedback ORDER BY submit_date DESC LIMIT 5")->fetchAll();
  $categoryBreakdown = $pdo->query('SELECT category, COUNT(*) AS total FROM feedback GROUP BY category ORDER BY total DESC, category ASC')->fetchAll();
  $weeklyStart = new DateTimeImmutable('today -6 days');
  $weeklyEnd = new DateTimeImmutable('tomorrow');
  for ($day = 0; $day < 7; $day++) {
    $weeklyCounts[$weeklyStart->modify('+' . $day . ' days')->format('Y-m-d')] = 0;
  }
  $weeklyStmt = $pdo->prepare('SELECT DATE(submit_date) AS day, COUNT(*) AS total FROM feedback WHERE submit_date >= :start AND submit_date < :end GROUP BY DATE(submit_date)');
  $weeklyStmt->execute([':start' => $weeklyStart->format('Y-m-d 00:00:00'), ':end' => $weeklyEnd->format('Y-m-d 00:00:00')]);
  foreach ($weeklyStmt->fetchAll() as $weeklyRow) {
    if (array_key_exists($weeklyRow['day'], $weeklyCounts)) $weeklyCounts[$weeklyRow['day']] = (int) $weeklyRow['total'];
  }
  $weeklyTotal = array_sum($weeklyCounts);
  $weeklyMax = max($weeklyCounts) ?: 1;
  $weeklyCoordinates = [];
  foreach (array_values($weeklyCounts) as $index => $count) {
    $x = 8 + ($index * 164 / 6);
    $y = 31 - ((int) $count / $weeklyMax * 23);
    $weeklyCoordinates[] = round($x, 1) . ',' . round($y, 1);
  }
  $weeklyPoints = implode(' ', $weeklyCoordinates);
}
$maxCategoryCount = max(array_map(static fn($row) => (int) $row['total'], $categoryBreakdown) ?: [1]);
$visibleCategories = array_slice($categoryBreakdown, 0, 6);
$moreCategories = array_slice($categoryBreakdown, 6);
$trendText = static function (int $current, int $previous): string {
  $delta = $current - $previous;
  return $delta > 0 ? '+' . $delta . ' vs yesterday' : ($delta < 0 ? $delta . ' vs yesterday' : 'No change vs yesterday');
};

/* ---------- Search + filters (Module 5) ---------- */

// User-provided filters are validated before being used in prepared SQL statements.
$readQueryString = static function (string $key): string {
  $value = $_GET[$key] ?? '';
  return is_string($value) ? trim($value) : '';
};
$search   = $readQueryString('q');
$type     = $readQueryString('type');
$category = $readQueryString('category');
$rating   = $readQueryString('rating');
$dateFrom = $readQueryString('date_from');
$dateTo   = $readQueryString('date_to');
$status   = $readQueryString('status');
$perPage  = (int) $readQueryString('per_page');
$page     = max(1, (int) $readQueryString('page'));
$allowedPageSizes = [10, 20, 40, 50, 100];
if (!in_array($perPage, $allowedPageSizes, true)) $perPage = 10;
$isValidDate = static function (string $date): bool {
  if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts)) return false;
  return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
};
if (!$isValidDate($dateFrom)) $dateFrom = '';
if (!$isValidDate($dateTo)) $dateTo = '';
if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
  [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}

$where  = [];
$params = [];

if ($search !== '') {
  $where[] = '(message LIKE :q_message OR submitter_name LIKE :q_name OR category LIKE :q_category)';
  $params[':q_message'] = '%' . $search . '%';
  $params[':q_name'] = '%' . $search . '%';
  $params[':q_category'] = '%' . $search . '%';
}
if ($type !== '' && in_array($type, ['Student', 'Teacher & Staff', 'Visitor & Outsider'], true)) {
    $where[] = 'submitter_type = :type';
    $params[':type'] = $type;
}
if ($category !== '') {
    $where[] = 'category = :category';
    $params[':category'] = $category;
}
if ($rating !== '' && in_array($rating, ['1','2','3','4','5'], true)) {
    $where[] = 'rating = :rating';
    $params[':rating'] = (int) $rating;
}
if ($status !== '' && in_array($status, ['New', 'Reviewed', 'Resolved'], true)) {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}
if ($dateFrom !== '') {
  $where[] = 'submit_date >= :date_from';
  $params[':date_from'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
  $where[] = 'submit_date < :date_to';
  $params[':date_to'] = date('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00';
}

$whereSql = '';
if ($where) {
  $whereSql = ' WHERE ' . implode(' AND ', $where);
}
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM feedback' . $whereSql);
$countStmt->execute($params);
$totalResults = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalResults / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare('SELECT * FROM feedback' . $whereSql . ' ORDER BY submit_date DESC LIMIT :limit OFFSET :offset');
foreach ($params as $name => $value) {
  $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// Distinct categories for the filter dropdown

// The category list is loaded from the database so new categories appear automatically.
$categories = $pdo->query('SELECT DISTINCT category FROM feedback ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

function badgeClass($type) {
    return match ($type) {
        'Student' => 'badge-student',
        'Teacher & Staff' => 'badge-staff',
        'Visitor & Outsider' => 'badge-visitor',
        default => '',
    };
}
function starGlyphs($rating) {
    $rating = (int) $rating;
    return '<span class="stars-inline" aria-label="' . $rating . ' out of 5 stars">'
        . str_repeat('<span class="star-on" aria-hidden="true">★</span>', $rating)
        . str_repeat('<span class="star-off" aria-hidden="true">★</span>', 5 - $rating)
        . '</span>';
}
function statusBadgeClass($status) {
    return match ($status) {
        'Reviewed' => 'badge-reviewed',
        'Resolved' => 'badge-resolved',
        default => 'badge-new',
    };
}

$activeFilterCount = count(array_filter([$type, $category, $rating, $status, $dateFrom, $dateTo], static fn($value) => $value !== ''));
$paginationParams = array_filter([
  'q' => $search,
  'type' => $type,
  'category' => $category,
  'rating' => $rating,
  'status' => $status,
  'date_from' => $dateFrom,
  'date_to' => $dateTo,
], static fn($value) => $value !== '');
$paginationParams['per_page'] = $perPage;
$previousQuery = $paginationParams;
$previousQuery['page'] = max(1, $page - 1);
$nextQuery = $paginationParams;
$nextQuery['page'] = min($totalPages, $page + 1);
$firstResult = $totalResults ? $offset + 1 : 0;
$lastResult = min($offset + $perPage, $totalResults);

$pageTitle = $isFeedbackList ? 'Feedback List' : 'Dashboard';
$active = $isFeedbackList ? 'feedback-list' : 'dashboard';
include 'includes/header.php';
?>

<?php if (!$isFeedbackList): ?>
<?php
$statusBreakdownStmt = $pdo->prepare('SELECT status, COUNT(*) AS total FROM feedback GROUP BY status');
$statusBreakdownStmt->execute();
$statusBreakdown = $statusBreakdownStmt->fetchAll();
$submitterBreakdownStmt = $pdo->prepare('SELECT submitter_type, COUNT(*) AS total FROM feedback GROUP BY submitter_type');
$submitterBreakdownStmt->execute();
$submitterBreakdown = $submitterBreakdownStmt->fetchAll();
$ratingSplit = [
  'low' => array_sum(array_slice($ratingCounts, 1, 2)),
  'mid' => $ratingCounts[3],
  'high' => array_sum(array_slice($ratingCounts, 4, 2)),
];
$statusTotal = array_sum(array_map(static fn($row) => (int) $row['total'], $statusBreakdown));
$submitterTotal = array_sum(array_map(static fn($row) => (int) $row['total'], $submitterBreakdown));
$weeklyPeakDay = $weeklyTotal > 0 ? array_search(max($weeklyCounts), $weeklyCounts, true) : false;
$weeklyToday = date('Y-m-d');
$weeklyMax = max($weeklyCounts) ?: 1;
$weeklyPoints = implode(' ', array_map(static fn($index, $count) => round(8 + ($index * 164 / 6), 1) . ',' . round(31 - ((int) $count / $weeklyMax * 23), 1), array_keys($weeklyCounts), array_values($weeklyCounts)));
?>

<div class="page-head dashboard-page-head">
  <div>
    <h1>Feedback Dashboard</h1>
    <p>An overview of feedback from students, staff, and visitors.</p>
  </div>
  <div class="dashboard-freshness">Data as of <time datetime="<?php echo date(DATE_ATOM); ?>"><?php echo date('g:i A'); ?></time></div>
</div>

<div class="stat-cards" aria-label="Feedback summary">
  <div class="stat-card">
    <div class="stat-card-top"><span class="stat-label">Total Feedback</span><span class="stat-icon stat-icon-navy" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/></svg></span></div>
    <strong class="stat-number" data-count="<?php echo (int) $total; ?>"><?php echo (int) $total; ?></strong>
    <span class="stat-meta">All submissions received</span>
  </div>
  <div class="stat-card">
    <div class="stat-card-top"><span class="stat-label">Average Rating</span><span class="stat-icon stat-icon-marigold" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.3-6.2 3.3 1.2-6.8-5-4.9 6.9-1Z"/></svg></span></div>
    <strong class="stat-number" data-count="<?php echo $avgRating !== null ? (float) $avgRating : 0; ?>" data-suffix=" / 5"><?php echo $avgRating !== null ? number_format((float) $avgRating, 1) : '0.0'; ?></strong>
    <span class="stat-meta">Across all feedback</span>
  </div>
  <a class="stat-card stat-card-link" href="feedback_list.php?date_from=<?php echo urlencode(date('Y-m-d')); ?>&amp;date_to=<?php echo urlencode(date('Y-m-d')); ?>" aria-label="View today's <?php echo (int) $todayCount; ?> feedback submissions">
    <div class="stat-card-top"><span class="stat-label">Today's Feedback</span><span class="stat-icon stat-icon-moss" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span></div>
    <strong class="stat-number" data-count="<?php echo (int) $todayCount; ?>"><?php echo (int) $todayCount; ?></strong>
    <span class="stat-meta"><?php echo htmlspecialchars($trendText((int) $todayCount, (int) $yesterdayCount)); ?></span>
  </a>
  <a class="stat-card stat-card-link" href="feedback_list.php?status=New" aria-label="View <?php echo (int) $newCount; ?> new unreviewed feedback submissions">
    <div class="stat-card-top"><span class="stat-label">New / Unreviewed</span><span class="stat-icon stat-icon-marigold" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v9l6 3"/><circle cx="12" cy="12" r="9"/></svg></span></div>
    <strong class="stat-number" data-count="<?php echo (int) $newCount; ?>"><?php echo (int) $newCount; ?></strong>
    <span class="stat-meta"><?php echo htmlspecialchars($trendText($newTodayCount, $newYesterdayCount)); ?></span>
  </a>
</div>

<div class="dashboard-chart-grid" aria-label="Feedback analytics">
  <section class="dashboard-chart-panel" aria-labelledby="ratingsSplitTitle">
    <div class="dashboard-panel-heading"><div><span class="chart-kicker">Sentiment</span><h2 id="ratingsSplitTitle">Ratings Split</h2><p>Low, mid, and high ratings.</p></div></div>
    <div class="donut-layout">
      <div class="donut-wrap">
        <svg class="donut-chart" viewBox="0 0 120 120" role="img" aria-label="Ratings split: <?php echo $ratingSplit['low']; ?> low, <?php echo $ratingSplit['mid']; ?> mid, and <?php echo $ratingSplit['high']; ?> high">
          <circle class="donut-track" cx="60" cy="60" r="48"/>
          <?php $segmentColors = ['low' => '#b3261e', 'mid' => '#e0942c', 'high' => '#4a6b57']; $ratingOffset = 0; foreach (['low', 'mid', 'high'] as $segment): $segmentPercent = $ratingTotal > 0 ? round(($ratingSplit[$segment] / $ratingTotal) * 100) : 0; $segmentLength = $ratingTotal > 0 ? ($segmentPercent / 100) * 301.592894 : 0; ?><circle class="donut-segment" cx="60" cy="60" r="48" stroke="<?php echo $segmentColors[$segment]; ?>" stroke-dasharray="<?php echo $segmentLength; ?> 301.592894" stroke-dashoffset="<?php echo -$ratingOffset; ?>"/>
          <?php $ratingOffset += $segmentLength; endforeach; ?>
        </svg>
        <div class="donut-center"><strong><?php echo (int) $ratingTotal; ?></strong><span>responses</span></div>
      </div>
      <ul class="donut-legend">
        <li><span class="legend-dot legend-low"></span><span><strong><?php echo $ratingSplit['low']; ?></strong> Low (1–2★)</span></li>
        <li><span class="legend-dot legend-mid"></span><span><strong><?php echo $ratingSplit['mid']; ?></strong> Mid (3★)</span></li>
        <li><span class="legend-dot legend-high"></span><span><strong><?php echo $ratingSplit['high']; ?></strong> High (4–5★)</span></li>
      </ul>
    </div>
  </section>

  <section class="dashboard-chart-panel" aria-labelledby="statusSplitTitle">
    <div class="dashboard-panel-heading"><div><span class="chart-kicker">Review queue</span><h2 id="statusSplitTitle">Status</h2><p>Current feedback workflow.</p></div></div>
    <div class="donut-layout">
      <div class="donut-wrap">
        <svg class="donut-chart" viewBox="0 0 120 120" role="img" aria-label="Feedback status split">
          <circle class="donut-track" cx="60" cy="60" r="48"/>
          <?php $statusColors = ['New' => '#e0942c', 'Reviewed' => '#4a6b57', 'Resolved' => '#718ca0']; $statusOffset = 0; foreach ($statusBreakdown as $statusRow): $statusCount = (int) $statusRow['total']; $statusPercent = $statusTotal > 0 ? round(($statusCount / $statusTotal) * 100) : 0; $statusLength = $statusTotal > 0 ? ($statusPercent / 100) * 301.592894 : 0; ?><circle class="donut-segment" cx="60" cy="60" r="48" stroke="<?php echo $statusColors[$statusRow['status']] ?? '#718ca0'; ?>" stroke-dasharray="<?php echo $statusLength; ?> 301.592894" stroke-dashoffset="<?php echo -$statusOffset; ?>"/>
          <?php $statusOffset += $statusLength; endforeach; ?>
        </svg>
        <div class="donut-center"><strong><?php echo $statusTotal; ?></strong><span>entries</span></div>
      </div>
      <ul class="donut-legend">
        <?php foreach ($statusBreakdown as $statusRow): ?><li><span class="legend-dot" style="background:<?php echo $statusColors[$statusRow['status']] ?? '#718ca0'; ?>"></span><span><strong><?php echo (int) $statusRow['total']; ?></strong> <?php echo htmlspecialchars($statusRow['status']); ?></span></li><?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="dashboard-chart-panel" aria-labelledby="submitterMixTitle">
    <div class="dashboard-panel-heading"><div><span class="chart-kicker">Audience</span><h2 id="submitterMixTitle">Submitter Mix</h2><p>Who is sharing feedback.</p></div></div>
    <div class="donut-layout">
      <div class="donut-wrap">
        <svg class="donut-chart" viewBox="0 0 120 120" role="img" aria-label="Submitter type split">
          <circle class="donut-track" cx="60" cy="60" r="48"/>
          <?php $submitterColors = ['Student' => '#4a6b57', 'Teacher & Staff' => '#718ca0', 'Visitor & Outsider' => '#e0942c']; $submitterOffset = 0; foreach ($submitterBreakdown as $submitterRow): $submitterCount = (int) $submitterRow['total']; $submitterPercent = $submitterTotal > 0 ? round(($submitterCount / $submitterTotal) * 100) : 0; $submitterLength = $submitterTotal > 0 ? ($submitterPercent / 100) * 301.592894 : 0; ?><circle class="donut-segment" cx="60" cy="60" r="48" stroke="<?php echo $submitterColors[$submitterRow['submitter_type']] ?? '#e0942c'; ?>" stroke-dasharray="<?php echo $submitterLength; ?> 301.592894" stroke-dashoffset="<?php echo -$submitterOffset; ?>"/>
          <?php $submitterOffset += $submitterLength; endforeach; ?>
        </svg>
        <div class="donut-center"><strong><?php echo $submitterTotal; ?></strong><span>submitters</span></div>
      </div>
      <ul class="donut-legend">
        <?php foreach ($submitterBreakdown as $submitterRow): ?><li><span class="legend-dot" style="background:<?php echo $submitterColors[$submitterRow['submitter_type']] ?? '#e0942c'; ?>"></span><span><strong><?php echo (int) $submitterRow['total']; ?></strong> <?php echo htmlspecialchars($submitterRow['submitter_type']); ?></span></li><?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="dashboard-chart-panel weekly-panel" aria-labelledby="weeklyTrendTitle">
    <div class="dashboard-panel-heading"><div><span class="chart-kicker">Seven-day volume</span><h2 id="weeklyTrendTitle">Weekly Trend</h2><p><?php echo (int) $weeklyTotal; ?> submissions over the last seven days.</p></div></div>
    <div class="weekly-trend-summary">
      <svg class="weekly-sparkline" viewBox="0 0 180 50" role="img" aria-label="Daily feedback trend for the last seven days">
        <defs><linearGradient id="weeklyArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4a6b57" stop-opacity=".25"/><stop offset="1" stop-color="#4a6b57" stop-opacity="0"/></linearGradient></defs>
        <path class="weekly-area" d="M8 43 L<?php echo $weeklyPoints; ?> L172 43 Z"/>
        <polyline class="weekly-line" points="<?php echo $weeklyPoints; ?>"/>
        <?php foreach (array_values($weeklyCounts) as $index => $count): $x = 8 + ($index * 164 / 6); $y = 31 - ((int) $count / $weeklyMax * 23); ?><circle class="weekly-point<?php echo $weeklyToday === array_keys($weeklyCounts)[$index] ? ' is-today' : ''; ?>" cx="<?php echo round($x, 1); ?>" cy="<?php echo round($y, 1); ?>" r="3"/><?php endforeach; ?>
      </svg>
      <div class="weekly-trend-value">
        <?php if ($weeklyPeakDay === false): ?><span>No feedback yet</span><?php else: ?><strong><?php echo (int) $weeklyCounts[$weeklyPeakDay]; ?></strong><span>peak on <?php echo date('D', strtotime($weeklyPeakDay)); ?></span><?php endif; ?>
      </div>
    </div>
    <div class="weekly-labels" aria-hidden="true"><?php foreach (array_keys($weeklyCounts) as $day): ?><span><?php echo date('D', strtotime($day)); ?></span><?php endforeach; ?></div>
  </section>
</div>

<div class="dashboard-bottom-grid">
  <section class="dashboard-bottom-panel activity-panel" aria-labelledby="recentActivityTitle">
    <div class="dashboard-panel-heading">
      <div><span class="chart-kicker">Latest updates</span><h2 id="recentActivityTitle">Recent Activity</h2><p>Your latest feedback entries.</p></div>
      <a href="feedback_list.php">View all</a>
    </div>
    <div class="recent-feedback-list">
      <?php if (empty($recentFeedback)): ?>
        <p class="widget-empty">No feedback has been submitted yet.</p>
      <?php else: foreach ($recentFeedback as $feedback): ?>
        <button class="recent-feedback-item" type="button" data-feedback-view data-feedback-id="<?php echo (int) $feedback['id']; ?>" data-feedback-name="<?php echo htmlspecialchars($feedback['is_anonymous'] ? 'Anonymous Submission' : ($feedback['submitter_name'] ?: 'Unnamed'), ENT_QUOTES, 'UTF-8'); ?>" data-feedback-type="<?php echo htmlspecialchars($feedback['submitter_type'], ENT_QUOTES, 'UTF-8'); ?>" data-feedback-category="<?php echo htmlspecialchars($feedback['category'], ENT_QUOTES, 'UTF-8'); ?>" data-feedback-rating="<?php echo (int) $feedback['rating']; ?>" data-feedback-message="<?php echo htmlspecialchars($feedback['message'], ENT_QUOTES, 'UTF-8'); ?>" data-feedback-date="<?php echo htmlspecialchars(date('d M Y, g:i A', strtotime($feedback['submit_date'])), ENT_QUOTES, 'UTF-8'); ?>" data-feedback-anonymity="<?php echo htmlspecialchars($feedback['is_anonymous'] ? 'Submitted anonymously — no name was collected or stored.' : 'Submitted with name: ' . ($feedback['submitter_name'] ?: '—'), ENT_QUOTES, 'UTF-8'); ?>">
          <span class="activity-icon <?php echo (int) $feedback['rating'] >= 4 ? 'activity-success' : 'activity-warning'; ?>" aria-hidden="true"><?php echo (int) $feedback['rating']; ?></span>
          <span class="recent-feedback-copy"><strong><?php echo htmlspecialchars($feedback['is_anonymous'] ? 'Anonymous submission' : ($feedback['submitter_name'] ?: 'Unnamed submission')); ?></strong><small><?php echo htmlspecialchars($feedback['category']); ?> · <?php echo htmlspecialchars($feedback['submitter_type']); ?></small></span>
          <span class="rating-badge"><?php echo (int) $feedback['rating']; ?>★</span>
        </button>
      <?php endforeach; endif; ?>
    </div>
  </section>

  <section class="dashboard-bottom-panel quick-actions-panel" aria-labelledby="quickActionsTitle">
    <div class="dashboard-panel-heading"><div><span class="chart-kicker">Workspace</span><h2 id="quickActionsTitle">Quick Actions</h2><p>Go directly to common tasks.</p></div></div>
    <div class="quick-actions-grid">
      <a class="quick-action" href="feedback_list.php"><span class="quick-action-icon" aria-hidden="true">≡</span><span><strong>Feedback List</strong><small>Review and manage submissions</small></span></a>
      <a class="quick-action" href="../public-portal/index.php" target="_blank" rel="noopener noreferrer"><span class="quick-action-icon" aria-hidden="true">◎</span><span><strong>Public Portal</strong><small>Open the public feedback site</small></span></a>
      <a class="quick-action" href="profile.php"><span class="quick-action-icon" aria-hidden="true">◇</span><span><strong>My Profile</strong><small>Update account details</small></span></a>
      <!-- <a class="quick-action" href="change_password.php"><span class="quick-action-icon" aria-hidden="true">⌁</span><span><strong>Change Password</strong><small>Update your login password</small></span></a> -->
    </div>
  </section>
</div>

<?php endif; ?>

<?php if ($isFeedbackList): ?>
<div class="page-head feedback-list-page-head">
  <div>
    <span class="page-kicker">Submitted feedback</span>
    <h1>Feedback List</h1>
    <p>Search, review, filter, and manage all feedback received through the public portal.</p>
  </div>
</div>
<div class="dashboard-toolbar">
  <div class="list-toolbar-actions">
    <form method="GET" class="live-search-form" data-live-search>
      <?php foreach (['type' => $type, 'category' => $category, 'rating' => $rating, 'status' => $status, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'per_page' => $perPage] as $name => $value): if ($value !== ''): ?><input type="hidden" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; endforeach; ?>
      <label class="sr-only" for="feedbackSearch">Search feedback</label>
      <input type="search" id="feedbackSearch" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search name, comment, category" autocomplete="off">
    </form>
    <div class="filter-menu-wrap">
      <button type="button" class="btn btn-outline filter-open" data-filter-open aria-expanded="false" aria-controls="filterPanel">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10m-7 6h4"/><circle cx="8" cy="6" r="2" fill="currentColor"/><circle cx="15" cy="12" r="2" fill="currentColor"/></svg>
        Filters<?php if ($activeFilterCount): ?><span class="filter-count"><?php echo $activeFilterCount; ?></span><?php endif; ?>
      </button>
      <section class="filter-panel" id="filterPanel" aria-label="Feedback filters" hidden>
        <form method="GET" class="filter-panel-form">
          <div class="filter-panel-grid">
        <div class="filter-group"><label for="type">Submitter Type</label><select id="type" name="type"><option value="">All Types</option><option value="Student" <?php echo $type === 'Student' ? 'selected' : ''; ?>>Student</option><option value="Teacher & Staff" <?php echo $type === 'Teacher & Staff' ? 'selected' : ''; ?>>Teacher / Staff</option><option value="Visitor & Outsider" <?php echo $type === 'Visitor & Outsider' ? 'selected' : ''; ?>>Visitor / Outsider</option></select></div>
        <div class="filter-group"><label for="status">Status</label><select id="status" name="status"><option value="">All Statuses</option><?php foreach (['New', 'Reviewed', 'Resolved'] as $option): ?><option value="<?php echo $option; ?>" <?php echo $status === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label for="category">Category</label><select id="category" name="category"><option value="">All Categories</option><?php foreach ($categories as $c): ?><option value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $category === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
        <div class="filter-group"><label for="rating">Rating</label><select id="rating" name="rating"><option value="">Any Rating</option><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?php echo $i; ?>" <?php echo $rating === (string) $i ? 'selected' : ''; ?>><?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?></option><?php endfor; ?></select></div>
            <div class="filter-group"><label for="date_from">From</label><input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="filter-group"><label for="date_to">To</label><input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>"></div>
          </div>
          <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
          <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
          <div class="filter-actions"><a href="feedback_list.php" class="btn btn-outline btn-sm">Clear all</a><button type="submit" class="btn btn-primary btn-sm">Apply filters</button></div>
        </form>
      </section>
    </div>
  </div>
</div>

<div class="table-card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Submitter</th>
          <th>Type</th>
          <th>Category</th>
          <th>Rating</th>
          <th>Status</th>
          <th>Comment</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8">
            <div class="empty-state">
              <h3>No feedback found</h3>
              <p>Try adjusting your search or filters.</p>
            </div>
          </td></tr>
        <?php else: foreach ($rows as $row): ?>
          <tr data-feedback-row>
            <td>
              <?php if ($row['is_anonymous']): ?>
                <span class="badge badge-anon">Anonymous</span>
              <?php else: ?>
                <?php echo htmlspecialchars($row['submitter_name'] ?: '—'); ?>
              <?php endif; ?>
            </td>
            <td><span class="badge <?php echo badgeClass($row['submitter_type']); ?>"><?php echo htmlspecialchars($row['submitter_type']); ?></span></td>
            <td><?php echo htmlspecialchars($row['category']); ?></td>
            <td class="rating-cell"><?php echo starGlyphs($row['rating']); ?></td>
            <td data-status-cell><span class="badge <?php echo statusBadgeClass($row['status'] ?? 'New'); ?>"><?php echo htmlspecialchars($row['status'] ?? 'New'); ?></span></td>
            <td class="cell-msg" title="<?php echo htmlspecialchars($row['message']); ?>"><?php echo htmlspecialchars($row['message']); ?></td>
            <td><?php echo date('d M Y, g:i A', strtotime($row['submit_date'])); ?></td>
            <td>
              <div class="row-actions">
                <button type="button" class="icon-btn" title="View feedback" aria-label="View feedback"
                        data-feedback-view
                        data-feedback-id="<?php echo (int) $row['id']; ?>"
                        data-feedback-name="<?php echo htmlspecialchars($row['is_anonymous'] ? 'Anonymous Submission' : ($row['submitter_name'] ?: 'Unnamed'), ENT_QUOTES, 'UTF-8'); ?>"
                        data-feedback-type="<?php echo htmlspecialchars($row['submitter_type'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-feedback-category="<?php echo htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-feedback-rating="<?php echo (int) $row['rating']; ?>"
                        data-feedback-message="<?php echo htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-feedback-date="<?php echo htmlspecialchars(date('d M Y, g:i A', strtotime($row['submit_date'])), ENT_QUOTES, 'UTF-8'); ?>"
                        data-feedback-anonymity="<?php echo htmlspecialchars($row['is_anonymous'] ? 'Submitted anonymously — no name was collected or stored.' : 'Submitted with name: ' . ($row['submitter_name'] ?: '—'), ENT_QUOTES, 'UTF-8'); ?>">
                  <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <button type="button" class="icon-btn danger" title="Delete feedback" aria-label="Delete feedback"
                        data-delete-id="<?php echo (int) $row['id']; ?>"
                        data-delete-name="<?php echo htmlspecialchars($row['is_anonymous'] ? 'this anonymous entry' : ($row['submitter_name'] ?: 'this entry')); ?>">
                  <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="pagination-bar">
  <div class="pagination-left-group">
    <p class="pagination-summary">Showing <?php echo $firstResult; ?> to <?php echo $lastResult; ?> of <?php echo $totalResults; ?> results</p>
    <form method="GET" class="pagination-size-form">
      <?php foreach ($paginationParams as $name => $value): if ($name !== 'per_page'): ?><input type="hidden" name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; endforeach; ?>
      <input type="hidden" name="page" value="1">
      <label for="per_page">Rows per page:</label>
      <select id="per_page" name="per_page" data-page-size><?php foreach ($allowedPageSizes as $size): ?><option value="<?php echo $size; ?>" <?php echo $perPage === $size ? 'selected' : ''; ?>><?php echo $size; ?></option><?php endforeach; ?></select>
    </form>
  </div>
  <nav class="pagination-nav" aria-label="Feedback pages">
    <?php if ($page == 1): ?><span class="pagination-page-btn" aria-disabled="true"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3 4 8l4 5M12 3 8 8l4 5"/></svg></span><span class="pagination-page-btn" aria-disabled="true"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m10 3-5 5 5 5"/></svg></span><?php else: ?><a class="pagination-page-btn" href="?<?php echo htmlspecialchars(http_build_query(array_merge($paginationParams, ['page' => 1])), ENT_QUOTES, 'UTF-8'); ?>" aria-label="First page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3 4 8l4 5M12 3 8 8l4 5"/></svg></a><a class="pagination-page-btn" href="?<?php echo htmlspecialchars(http_build_query($previousQuery), ENT_QUOTES, 'UTF-8'); ?>" rel="prev" aria-label="Previous page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m10 3-5 5 5 5"/></svg></a><?php endif; ?>
    <span class="pagination-page-label">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
    <?php if ($page == $totalPages): ?><span class="pagination-page-btn" aria-disabled="true"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m6 3 5 5-5 5"/></svg></span><span class="pagination-page-btn" aria-disabled="true"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3l4 5-4 5M4 3l4 5-4 5"/></svg></span><?php else: ?><a class="pagination-page-btn" href="?<?php echo htmlspecialchars(http_build_query($nextQuery), ENT_QUOTES, 'UTF-8'); ?>" rel="next" aria-label="Next page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m6 3 5 5-5 5"/></svg></a><a class="pagination-page-btn" href="?<?php echo htmlspecialchars(http_build_query(array_merge($paginationParams, ['page' => $totalPages])), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Last page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3l4 5-4 5M4 3l4 5-4 5"/></svg></a><?php endif; ?>
  </nav>
</div>

<!-- Feedback detail modal -->
<div class="modal-overlay" id="feedbackModal" role="dialog" aria-modal="true" aria-labelledby="feedbackModalTitle">
  <div class="modal-box feedback-modal-box">
    <div class="feedback-modal-header">
      <div>
        <div class="detail-meta"><span class="badge" id="feedbackModalType"></span><span class="badge badge-anon" id="feedbackModalAnonymous" hidden>Anonymous</span><span class="badge" id="feedbackModalStatus"></span></div>
        <h3 id="feedbackModalTitle"></h3>
        <p class="detail-date" id="feedbackModalDate"></p>
      </div>
      <button type="button" class="modal-close" aria-label="Close feedback details" data-feedback-modal-close>&times;</button>
    </div>
    <div class="detail-block">
      <label>Category</label>
      <div class="value" id="feedbackModalCategory"></div>
    </div>
    <div class="detail-block">
      <label>Rating</label>
      <div class="value" id="feedbackModalRating"></div>
    </div>
    <div class="detail-block">
      <label>Comment</label>
      <div class="value feedback-message" id="feedbackModalMessage"></div>
    </div>
    <div class="detail-block">
      <label>Anonymity</label>
      <div class="value" id="feedbackModalAnonymity"></div>
    </div>
    <div class="detail-block status-control">
      <label for="feedbackModalStatusSelect">Status</label>
      <form action="status_update.php" method="POST" data-table-status-form>
        <input type="hidden" name="id" id="feedbackModalId">
        <select id="feedbackModalStatusSelect" name="status"><option value="New">New</option><option value="Reviewed">Reviewed</option><option value="Resolved">Resolved</option></select>
        <button type="submit" class="btn btn-primary btn-sm">Update status</button>
      </form>
    </div>
    <div class="modal-actions">
      <button type="button" class="btn btn-danger" id="feedbackModalDelete" data-delete-id="" data-delete-name="" title="Delete feedback" aria-label="Delete feedback">
        <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
        Delete
      </button>
      <button type="button" class="btn btn-outline" data-feedback-modal-close>Close</button>
    </div>
  </div>
</div>

<!-- Delete confirmation modal (Module 5 / Section 13) -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <h3>Delete this feedback entry?</h3>
    <p>You're about to permanently delete the entry from <strong id="deleteTargetName"></strong>. This cannot be undone.</p>
    <form id="deleteForm" method="POST" action="delete_feedback.php">
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-danger">Delete Permanently</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
