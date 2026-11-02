<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/../portal_account.php';
require __DIR__ . '/../portal_charts.php';
require __DIR__ . '/../portal_feedback.php';
require_once __DIR__ . '/../portal_feedback_options.php';
require_once __DIR__ . '/../portal_notifications.php';
require __DIR__ . '/../portal_pagination.php';

$staffId = (int) $_SESSION['staff_id'];
$sections = [
  'overview' => 'Dashboard',
  'feedback-form' => 'Send Feedback',
  'feedback-history' => 'My Feedback',
  'account' => 'Profile Settings',
];
$section = (string) ($_GET['section'] ?? 'overview');
if (!isset($sections[$section])) {
  $section = 'overview';
}
$accountTab = (string) ($_GET['account_tab'] ?? 'details');
if (!in_array($accountTab, ['details', 'security'], true)) {
  $accountTab = 'details';
}
$staffFormOptions = feedbackFormOptions($pdo, 'staff');
$staffDepartments = $staffFormOptions['departments']; $staffRoles = $staffFormOptions['roles']; $staffFeedbackCategories = $staffFormOptions['categories'];
$urgencyLevels = ['Low', 'Medium', 'High'];
$ratingLabels = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very Good', 5 => 'Excellent'];
$feedbackCsrf = $_SESSION['staff_feedback_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['staff_feedback_csrf'] = $feedbackCsrf;
$accountCsrf = $_SESSION['portal_account_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['portal_account_csrf'] = $accountCsrf;
$feedbackNotice = $_SESSION['portal_feedback_notice'] ?? null;
unset($_SESSION['portal_feedback_notice']);

$staffStmt = $pdo->prepare(
  'SELECT p.id, p.full_name, p.username, p.email, u.id AS account_user_id
   FROM portal_users p
   LEFT JOIN users u ON LOWER(u.email) = LOWER(p.email) AND u.role = \'staff\'
   WHERE p.id = :id LIMIT 1'
);
$staffStmt->execute([':id' => $staffId]);
$staff = $staffStmt->fetch();
$accountUserId = (int) ($staff['account_user_id'] ?? 0);
$accountError = '';
$accountNotice = '';
if ($section === 'account') {
  [$accountError, $accountNotice] = processPortalAccountPost($pdo, $staffId, $accountUserId, 'staff', $staff, $accountCsrf);
}
$feedbackError = '';
$editingFeedback = null;
$feedbackFormValues = [];
if ($section === 'feedback-form' && isset($_GET['edit'])) {
  $editStmt = $pdo->prepare('SELECT * FROM feedback WHERE id = :id AND user_id = :user_id AND status = \'New\' LIMIT 1');
  $editStmt->execute([':id' => (int) $_GET['edit'], ':user_id' => $accountUserId]);
  $editingFeedback = $editStmt->fetch() ?: null;
  if (!$editingFeedback) {
    $_SESSION['portal_feedback_notice'] = ['type' => 'error', 'message' => 'Only new feedback can be edited.'];
    header('Location: dashboard.php?section=feedback-history'); exit;
  }
  $feedbackFormValues = ['feedback_title' => $editingFeedback['feedback_title'], 'staff_department' => $editingFeedback['staff_department'], 'staff_role' => $editingFeedback['staff_role'], 'feedback_category' => $editingFeedback['category'], 'urgency_level' => $editingFeedback['urgency_level'], 'feedback_message' => $editingFeedback['message'], 'feedback_rating' => $editingFeedback['rating'], 'overall_satisfaction' => $editingFeedback['overall_satisfaction'], 'preferred_solution' => $editingFeedback['preferred_solution'], 'feedback_visibility' => $editingFeedback['is_anonymous'] ? 'anonymous' : 'identified', 'follow_up_required' => (string) $editingFeedback['follow_up_required']];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') $feedbackFormValues = array_merge($feedbackFormValues, $_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_feedback') {
  $deleteId = (int) ($_POST['feedback_id'] ?? 0);
  if (!hash_equals($feedbackCsrf, (string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['portal_feedback_notice'] = ['type' => 'error', 'message' => 'The request expired. Please try again.'];
  } else {
    $attachmentStmt = $pdo->prepare('SELECT attachment_path FROM feedback WHERE id = :id AND user_id = :user_id AND status = \'New\' LIMIT 1');
    $attachmentStmt->execute([':id' => $deleteId, ':user_id' => $accountUserId]);
    $attachment = $attachmentStmt->fetchColumn();
    if ($attachment === false) {
      $_SESSION['portal_feedback_notice'] = ['type' => 'error', 'message' => 'Only new feedback can be deleted.'];
    } else {
      $deleteStmt = $pdo->prepare('DELETE FROM feedback WHERE id = :id AND user_id = :user_id AND status = \'New\'');
      $deleteStmt->execute([':id' => $deleteId, ':user_id' => $accountUserId]);
      deletePortalFeedbackAttachment((string) $attachment);
      $_SESSION['portal_feedback_notice'] = ['type' => 'success', 'message' => 'Feedback deleted.'];
    }
  }
  header('Location: dashboard.php?section=feedback-history');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['submit_feedback', 'update_feedback'], true)) {
  $isFeedbackUpdate = ($_POST['action'] ?? '') === 'update_feedback';
  $editingId = (int) ($_POST['feedback_id'] ?? 0);
  $category = trim((string) ($_POST['feedback_category'] ?? ''));
  $title = trim((string) ($_POST['feedback_title'] ?? ''));
  $department = trim((string) ($_POST['staff_department'] ?? ''));
  $staffRole = trim((string) ($_POST['staff_role'] ?? ''));
  $urgency = trim((string) ($_POST['urgency_level'] ?? ''));
  $rating = (int) ($_POST['feedback_rating'] ?? 0);
  $overallSatisfaction = (int) ($_POST['overall_satisfaction'] ?? 0);
  $message = trim((string) ($_POST['feedback_message'] ?? ''));
  $preferredSolution = trim((string) ($_POST['preferred_solution'] ?? ''));
  $visibility = (string) ($_POST['feedback_visibility'] ?? 'identified');
  $followUpRequired = (string) ($_POST['follow_up_required'] ?? '0');
  $messageLength = function_exists('mb_strlen') ? mb_strlen($message, 'UTF-8') : strlen($message);
  $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
  $solutionLength = function_exists('mb_strlen') ? mb_strlen($preferredSolution, 'UTF-8') : strlen($preferredSolution);

  if (!hash_equals($feedbackCsrf, (string) ($_POST['csrf_token'] ?? ''))) {
    $feedbackError = 'The form expired. Refresh the page and try again.';
  } elseif ($accountUserId < 1) {
    $feedbackError = 'Your portal identity could not be linked to a feedback account. Contact an administrator.';
  } elseif ($isFeedbackUpdate && !$editingId) {
    $feedbackError = 'The feedback entry could not be found.';
  } elseif (!in_array($category, $staffFeedbackCategories, true)) {
    $feedbackError = 'Choose a valid feedback category.';
  } elseif ($title === '' || $titleLength > 200) {
    $feedbackError = 'Enter a feedback title of 200 characters or fewer.';
  } elseif (!in_array($department, $staffDepartments, true)) {
    $feedbackError = 'Choose your staff department.';
  } elseif (!in_array($staffRole, $staffRoles, true)) {
    $feedbackError = 'Choose your staff role.';
  } elseif (!in_array($urgency, $urgencyLevels, true)) {
    $feedbackError = 'Choose a valid urgency level.';
  } elseif ($rating < 1 || $rating > 5) {
    $feedbackError = 'Choose a rating from 1 to 5.';
  } elseif ($overallSatisfaction < 1 || $overallSatisfaction > 5) {
    $feedbackError = 'Choose your overall workplace satisfaction rating.';
  } elseif ($messageLength < 5 || $messageLength > 2000) {
    $feedbackError = 'Feedback must be between 5 and 2,000 characters.';
  } elseif ($solutionLength > 2000) {
    $feedbackError = 'Preferred solution must be 2,000 characters or fewer.';
  } elseif (!in_array($visibility, ['identified', 'anonymous'], true)) {
    $feedbackError = 'Choose whether your feedback is identified or anonymous.';
  } elseif (!in_array($followUpRequired, ['0', '1'], true)) {
    $feedbackError = 'Choose whether you would like a response.';
  } else {
    $attachment = storePortalFeedbackAttachment($_FILES['feedback_attachment'] ?? []);
    if ($attachment['error'] !== '') {
      $feedbackError = $attachment['error'];
    } else {
      try {
        $pdo->beginTransaction();
        if ($isFeedbackUpdate) {
          $existingStmt = $pdo->prepare('SELECT attachment_path, attachment_name FROM feedback WHERE id = :id AND user_id = :user_id AND status = \'New\' FOR UPDATE');
          $existingStmt->execute([':id' => $editingId, ':user_id' => $accountUserId]);
          $existing = $existingStmt->fetch();
          if (!$existing) throw new RuntimeException('This feedback can no longer be edited.');
          $updateStmt = $pdo->prepare('UPDATE feedback SET submitter_name = :name, is_anonymous = :is_anonymous, category = :category, feedback_title = :title, urgency_level = :urgency, rating = :rating, overall_satisfaction = :overall_satisfaction, message = :message, preferred_solution = :preferred_solution, follow_up_required = :follow_up_required, staff_department = :department, staff_role = :role, attachment_path = :attachment_path, attachment_name = :attachment_name WHERE id = :id AND user_id = :user_id AND status = \'New\'');
          $updateStmt->execute([':name' => $visibility === 'anonymous' ? null : $staff['full_name'], ':is_anonymous' => $visibility === 'anonymous' ? 1 : 0, ':category' => $category, ':title' => strip_tags($title), ':urgency' => $urgency, ':rating' => $rating, ':overall_satisfaction' => $overallSatisfaction, ':message' => strip_tags($message), ':preferred_solution' => $preferredSolution === '' ? null : strip_tags($preferredSolution), ':follow_up_required' => (int) $followUpRequired, ':department' => $department, ':role' => $staffRole, ':attachment_path' => $attachment['path'] ?? $existing['attachment_path'], ':attachment_name' => $attachment['name'] ?? $existing['attachment_name'], ':id' => $editingId, ':user_id' => $accountUserId]);
          if ($attachment['path'] && $existing['attachment_path']) deletePortalFeedbackAttachment((string) $existing['attachment_path']);
          $pdo->commit(); $_SESSION['portal_feedback_notice'] = ['type' => 'success', 'message' => 'Feedback updated.']; header('Location: dashboard.php?section=feedback-history' . (isset($_GET['embed']) ? '&embed=1&saved=1' : '')); exit;
        }
        $feedbackStmt = $pdo->prepare(
          'INSERT INTO feedback (submitter_name, user_id, submitter_type, is_anonymous, category, feedback_title, urgency_level, rating, overall_satisfaction, message, preferred_solution, follow_up_required, staff_department, staff_role, attachment_path, attachment_name, status, submit_date)
           VALUES (:name, :user_id, \'Teacher & Staff\', :is_anonymous, :category, :title, :urgency, :rating, :overall_satisfaction, :message, :preferred_solution, :follow_up_required, :staff_department, :staff_role, :attachment_path, :attachment_name, \'New\', NOW())'
        );
        $feedbackStmt->execute([
          ':name' => $visibility === 'anonymous' ? null : $staff['full_name'],
          ':user_id' => $accountUserId,
          ':is_anonymous' => $visibility === 'anonymous' ? 1 : 0,
          ':category' => $category,
          ':title' => strip_tags($title),
          ':urgency' => $urgency,
          ':rating' => $rating,
          ':overall_satisfaction' => $overallSatisfaction,
          ':message' => strip_tags($message),
          ':preferred_solution' => $preferredSolution === '' ? null : strip_tags($preferredSolution),
          ':follow_up_required' => (int) $followUpRequired,
          ':staff_department' => $department,
          ':staff_role' => $staffRole,
          ':attachment_path' => $attachment['path'],
          ':attachment_name' => $attachment['name'],
        ]);
        $feedbackId = (int) $pdo->lastInsertId();
        notifyAllAdministrators($pdo, 'feedback', 'New staff feedback', 'A staff member submitted feedback for review.', $feedbackId);
        $pdo->commit();
        $_SESSION['portal_feedback_notice'] = ['type' => 'success', 'message' => 'Feedback submitted. You can track its status under My Feedback.'];
        header('Location: dashboard.php?section=feedback-history' . (isset($_GET['embed']) ? '&embed=1&saved=1' : ''));
        exit;
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        deletePortalFeedbackAttachment($attachment['path']);
        error_log('Staff feedback submission failed: ' . $e->getMessage());
        $feedbackError = 'Unable to submit feedback right now. Please try again.';
      }
    }
  }
}

$feedbackStmt = $pdo->prepare('SELECT id, category, feedback_title, urgency_level, staff_department, staff_role, rating, overall_satisfaction, is_anonymous, message, status, submit_date FROM feedback WHERE user_id = :user_id ORDER BY submit_date DESC');
$feedbackStmt->execute([':user_id' => $accountUserId]);
$feedbackRows = $feedbackStmt->fetchAll();
$feedbackSearch = trim((string) ($_GET['q'] ?? ''));
$feedbackDate = (string) ($_GET['date'] ?? '');
$feedbackStatusFilter = (string) ($_GET['status'] ?? '');
$feedbackVisibilityFilter = (string) ($_GET['visibility'] ?? '');
if (!in_array($feedbackStatusFilter, ['', 'New', 'Reviewed', 'Resolved'], true)) $feedbackStatusFilter = '';
if (!in_array($feedbackVisibilityFilter, ['', 'identified', 'anonymous'], true)) $feedbackVisibilityFilter = '';
if ($feedbackDate !== '' && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $feedbackDate)) $feedbackDate = '';
$filteredFeedbackRows = array_values(array_filter($feedbackRows, static function (array $row) use ($feedbackSearch, $feedbackDate, $feedbackStatusFilter, $feedbackVisibilityFilter): bool {
  $haystack = strtolower(implode(' ', [$row['feedback_title'] ?? '', $row['category'] ?? '', $row['message'] ?? '', $row['staff_department'] ?? '', $row['staff_role'] ?? '']));
  return ($feedbackSearch === '' || str_contains($haystack, strtolower($feedbackSearch))) && ($feedbackDate === '' || substr((string) $row['submit_date'], 0, 10) === $feedbackDate) && ($feedbackStatusFilter === '' || $row['status'] === $feedbackStatusFilter) && ($feedbackVisibilityFilter === '' || ($feedbackVisibilityFilter === 'anonymous') === (bool) $row['is_anonymous']);
}));
$feedbackHistoryTotal = count($filteredFeedbackRows);
$allowedFeedbackPageSizes = [10, 20, 50];
$requestedFeedbackPageSize = $_GET['per_page'] ?? '10';
$feedbackHistoryPerPage = is_string($requestedFeedbackPageSize) ? (int) $requestedFeedbackPageSize : 10;
if (!in_array($feedbackHistoryPerPage, $allowedFeedbackPageSizes, true)) {
  $feedbackHistoryPerPage = 10;
}
$requestedFeedbackPage = $_GET['page'] ?? '1';
$feedbackHistoryPage = is_string($requestedFeedbackPage) ? max(1, (int) $requestedFeedbackPage) : 1;
$feedbackHistoryPages = max(1, (int) ceil($feedbackHistoryTotal / $feedbackHistoryPerPage));
$feedbackHistoryPage = min($feedbackHistoryPage, $feedbackHistoryPages);
$feedbackHistoryRows = array_slice($filteredFeedbackRows, ($feedbackHistoryPage - 1) * $feedbackHistoryPerPage, $feedbackHistoryPerPage);
$feedbackCounts = ['New' => 0, 'Reviewed' => 0, 'Resolved' => 0];
foreach ($feedbackRows as $feedbackRow) {
  if (isset($feedbackCounts[$feedbackRow['status']])) {
    $feedbackCounts[$feedbackRow['status']]++;
  }
}

$ratingCounts = array_fill(1, 5, 0);
$feedbackCategoryCounts = [];
$weeklyStart = new DateTimeImmutable('today -6 days');
$weeklyActivity = [];
for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
  $day = $weeklyStart->modify('+' . $dayOffset . ' days');
  $weeklyActivity[$day->format('Y-m-d')] = 0;
}
foreach ($feedbackRows as $feedbackRow) {
  $rating = (int) $feedbackRow['rating'];
  if (isset($ratingCounts[$rating])) {
    $ratingCounts[$rating]++;
  }
  $feedbackCategoryCounts[$feedbackRow['category']] = ($feedbackCategoryCounts[$feedbackRow['category']] ?? 0) + 1;
  $dayKey = date('Y-m-d', strtotime($feedbackRow['submit_date']));
  if (array_key_exists($dayKey, $weeklyActivity)) {
    $weeklyActivity[$dayKey]++;
  }
}
arsort($feedbackCategoryCounts);
$totalFeedback = count($feedbackRows);
$averageRating = $totalFeedback ? array_sum(array_column($feedbackRows, 'rating')) / $totalFeedback : 0;
$feedbackStatusColors = ['New' => '#e0942c', 'Reviewed' => '#4c7890', 'Resolved' => '#4a6b57'];
$ratingColors = [1 => '#b85c4b', 2 => '#d98248', 3 => '#e0b64b', 4 => '#79a27a', 5 => '#4a6b57'];
$ratingChartCounts = [];
$ratingChartColors = [];
foreach ($ratingCounts as $rating => $ratingCount) {
  $ratingLabel = $rating . ' star' . ($rating === 1 ? '' : 's');
  $ratingChartCounts[$ratingLabel] = $ratingCount;
  $ratingChartColors[$ratingLabel] = $ratingColors[$rating];
}
$chartPalette = ['#e0942c', '#4c7890', '#4a6b57', '#b85c4b', '#7f8c55', '#d98248', '#526d82'];
$feedbackCategoryColors = [];
foreach (array_keys($feedbackCategoryCounts) as $categoryIndex => $categoryName) {
  $feedbackCategoryColors[$categoryName] = $chartPalette[$categoryIndex % count($chartPalette)];
}
$weeklyTotal = array_sum($weeklyActivity);
$weeklyPeakDay = $weeklyTotal > 0 ? array_search(max($weeklyActivity), $weeklyActivity, true) : false;
$weeklyMax = max($weeklyActivity) ?: 1;
$weeklyCoordinates = [];
foreach (array_values($weeklyActivity) as $dayIndex => $dayCount) {
  $weeklyX = 8 + ($dayIndex * 164 / 6);
  $weeklyY = 31 - ($dayCount / $weeklyMax * 23);
  $weeklyCoordinates[] = round($weeklyX, 1) . ',' . round($weeklyY, 1);
}
$weeklyPoints = implode(' ', $weeklyCoordinates);
$weeklyPath = 'M' . str_replace(' ', ' L', $weeklyPoints);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard</title>
  <link rel="icon" type="image/svg+xml" href="../assets/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/staff.css">
  <link rel="stylesheet" href="../portal_sidebar.css">
  <link rel="stylesheet" href="../list_ui.css">
</head>
<body class="dashboard-body<?php echo $section === 'feedback-form' && isset($_GET['embed']) ? ' feedback-embed' : ''; ?>">
  <?php
  $portalSidebarRole = 'Staff';
  $portalSidebarUser = $staff;
  $portalActiveSection = $section;
  $portalCanManageStudents = true;
  include __DIR__ . '/../portal_sidebar.php';
  ?>

  <main class="dashboard-main">
    <div class="portal-topbar">
      <nav class="portal-breadcrumb" aria-label="Breadcrumb"><a href="dashboard.php?section=overview">College Feedback</a><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg><span><?php echo htmlspecialchars($sections[$section], ENT_QUOTES, 'UTF-8'); ?></span></nav>
    </div>
    <header class="dashboard-header">
      <div>
        <span class="page-kicker"><?php echo $section === 'overview' ? 'Welcome back' : 'Staff Portal'; ?></span>
        <h1><?php echo $section === 'overview' ? htmlspecialchars($staff['full_name'] ?? 'Staff Member', ENT_QUOTES, 'UTF-8') : htmlspecialchars($sections[$section], ENT_QUOTES, 'UTF-8'); ?></h1>
      </div>
      <div class="user-chip"><?php echo htmlspecialchars($staff['username'] ?? 'staff', ENT_QUOTES, 'UTF-8'); ?></div>
    </header>

    <?php if ($section === 'overview'): ?>
      <section class="stats-grid">
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Feedback sent</span><span class="stat-icon stat-icon-navy"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5M4 19h17"/><path d="m7 15 4-4 3 2 6-7"/></svg></span></div><strong class="stat-number"><?php echo $totalFeedback; ?></strong></article>
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">New feedback</span><span class="stat-icon stat-icon-marigold"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/></svg></span></div><strong class="stat-number"><?php echo $feedbackCounts['New']; ?></strong></article>
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Resolved feedback</span><span class="stat-icon stat-icon-moss"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m9 11 3 3L22 4"/></svg></span></div><strong class="stat-number"><?php echo $feedbackCounts['Resolved']; ?></strong></article>
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Average rating</span><span class="stat-icon stat-icon-slate"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/></svg></span></div><strong class="stat-number"><?php echo number_format($averageRating, 1); ?><small> / 5</small></strong></article>
      </section>
      <section class="chart-grid" aria-label="Feedback charts">
        <?php renderPortalPieChart('Feedback ratings', $ratingChartCounts, $ratingChartColors, 'ratings'); ?>
        <?php renderPortalPieChart('Feedback topics', $feedbackCategoryCounts, $feedbackCategoryColors, 'feedback'); ?>
        <?php renderPortalPieChart('Feedback status', $feedbackCounts, $feedbackStatusColors, 'feedback'); ?>
        <section class="dashboard-chart-panel weekly-panel portal-weekly-card">
          <div class="dashboard-panel-heading"><div><span class="chart-kicker">Seven-day volume</span><h2>Weekly Trend</h2><p><?php echo $weeklyTotal; ?> submissions over the last seven days.</p></div></div>
          <div class="weekly-trend-summary">
            <svg class="weekly-sparkline" viewBox="0 0 180 50" role="img" aria-label="Daily feedback trend for the last seven days">
              <defs><linearGradient id="weeklyArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4a6b57" stop-opacity=".25"/><stop offset="1" stop-color="#4a6b57" stop-opacity="0"/></linearGradient></defs>
              <path class="weekly-area" d="M8 43 L<?php echo $weeklyPoints; ?> L172 43 Z"/>
              <path class="weekly-line" d="<?php echo htmlspecialchars($weeklyPath, ENT_QUOTES, 'UTF-8'); ?>"/>
              <?php foreach (array_values($weeklyActivity) as $dayIndex => $dayCount): $weeklyX = 8 + ($dayIndex * 164 / 6); $weeklyY = 31 - ($dayCount / $weeklyMax * 23); ?><circle class="weekly-point<?php echo array_keys($weeklyActivity)[$dayIndex] === date('Y-m-d') ? ' is-today' : ''; ?>" cx="<?php echo round($weeklyX, 1); ?>" cy="<?php echo round($weeklyY, 1); ?>" r="3"/><?php endforeach; ?>
            </svg>
            <div class="weekly-trend-value"><?php if ($weeklyPeakDay === false): ?><span>No feedback yet</span><?php else: ?><strong><?php echo $weeklyActivity[$weeklyPeakDay]; ?></strong><span>peak on <?php echo date('D', strtotime($weeklyPeakDay)); ?></span><?php endif; ?></div>
          </div>
          <div class="weekly-labels" aria-hidden="true"><?php foreach (array_keys($weeklyActivity) as $day): ?><span><?php echo date('D', strtotime($day)); ?></span><?php endforeach; ?></div>
        </section>
      </section>
      <div class="portal-dashboard-widgets">
        <section class="content-card portal-widget-panel" aria-labelledby="recentActivityTitle">
          <div class="dashboard-panel-heading"><div><span class="chart-kicker">Latest updates</span><h2 id="recentActivityTitle">Recent Activity</h2><p>Your latest feedback entries.</p></div><a href="dashboard.php?section=feedback-history">View all</a></div>
          <div class="portal-recent-feedback-list">
            <?php if (!$feedbackRows): ?><p class="widget-empty">You have not submitted any feedback yet.</p><?php else: foreach (array_slice($feedbackRows, 0, 5) as $recentFeedback): ?>
              <div class="portal-recent-feedback-item"><span class="activity-icon <?php echo (int) $recentFeedback['rating'] >= 4 ? 'activity-success' : 'activity-warning'; ?>" aria-hidden="true"><?php echo (int) $recentFeedback['rating']; ?></span><span class="recent-feedback-copy"><strong><?php echo htmlspecialchars($recentFeedback['feedback_title'] ?: $recentFeedback['category'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($recentFeedback['category'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars(date('d M Y', strtotime($recentFeedback['submit_date'])), ENT_QUOTES, 'UTF-8'); ?></small></span><span class="rating-badge"><?php echo htmlspecialchars($recentFeedback['status'], ENT_QUOTES, 'UTF-8'); ?></span></div>
            <?php endforeach; endif; ?>
          </div>
        </section>
        <section class="content-card portal-widget-panel" aria-labelledby="quickActionsTitle">
          <div class="dashboard-panel-heading"><div><span class="chart-kicker">Workspace</span><h2 id="quickActionsTitle">Quick Actions</h2><p>Go directly to common tasks.</p></div></div>
          <div class="portal-quick-actions-grid">
            <button class="portal-quick-action" type="button" data-send-feedback><span class="quick-action-icon" aria-hidden="true">＋</span><span><strong>Send Feedback</strong><small>Share a new comment or concern</small></span></button>
            <a class="portal-quick-action" href="dashboard.php?section=feedback-history"><span class="quick-action-icon" aria-hidden="true">≡</span><span><strong>My Feedback</strong><small>Review your submissions</small></span></a>
            <a class="portal-quick-action" href="dashboard.php?section=account"><span class="quick-action-icon" aria-hidden="true">◇</span><span><strong>Profile Settings</strong><small>Update your account details</small></span></a>
          </div>
        </section>
      </div>
    <?php elseif ($section === 'feedback-form'): ?>
      <section class="content-card form-card feedback-form-card">
        <div class="card-header"><h2><?php echo $editingFeedback ? 'Edit feedback' : 'Send feedback'; ?></h2></div>
        <?php if ($feedbackNotice): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($feedbackNotice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($feedbackError): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($feedbackError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($editingFeedback): ?><script>(function(){function populateFeedbackForm(){var values=<?php echo json_encode($feedbackFormValues, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;Object.keys(values).forEach(function(name){var control=document.querySelector('[name="'+name+'"]');if(!control)return;var radios=document.querySelectorAll('[name="'+name+'"][type="radio"]');if(radios.length){radios.forEach(function(radio){radio.checked=String(radio.value)===String(values[name]);});}else{control.value=values[name]??'';}});}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',populateFeedbackForm,{once:true});}else{populateFeedbackForm();}})();</script><?php endif; ?>
        <form method="POST" action="dashboard.php?section=feedback-form<?php echo $editingFeedback ? '&amp;edit=' . (int) $editingFeedback['id'] : ''; ?><?php echo isset($_GET['embed']) ? '&amp;embed=1' : ''; ?>" class="submission-form feedback-details-form staff-feedback-form" enctype="multipart/form-data" data-feedback-form data-confirm data-confirm-title="<?php echo $editingFeedback ? 'Save feedback changes?' : 'Submit feedback?'; ?>" data-confirm-message="<?php echo $editingFeedback ? 'Your changes will be sent for review.' : 'Submit this feedback to the college?'; ?>" data-confirm-label="<?php echo $editingFeedback ? 'Save changes' : 'Submit feedback'; ?>">
          <input type="hidden" name="action" value="<?php echo $editingFeedback ? 'update_feedback' : 'submit_feedback'; ?>"><?php if ($editingFeedback): ?><input type="hidden" name="feedback_id" value="<?php echo (int) $editingFeedback['id']; ?>"><?php endif; ?><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($feedbackCsrf, ENT_QUOTES, 'UTF-8'); ?>">
          <section class="feedback-form-section">
            <div class="feedback-form-section-heading"><span>1</span><div><h3>About you</h3><p>Tell us your department and role.</p></div></div>
            <div class="feedback-form-fields">
              <div class="field full-width"><label for="feedback_title">Feedback title</label><input type="text" id="feedback_title" name="feedback_title" maxlength="200" placeholder="Enter a short title for your feedback" value="<?php echo htmlspecialchars($_POST['feedback_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
              <div class="field"><label for="staff_department">Staff department</label><select id="staff_department" name="staff_department" data-feedback-options="departments" data-selected="<?php echo htmlspecialchars($feedbackFormValues['staff_department'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required disabled><option>Loading departments…</option></select></div>
              <div class="field"><label for="staff_role">Staff role</label><select id="staff_role" name="staff_role" data-feedback-options="roles" data-selected="<?php echo htmlspecialchars($feedbackFormValues['staff_role'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required disabled><option>Loading roles…</option></select></div>
            </div>
          </section>
          <section class="feedback-form-section">
            <div class="feedback-form-section-heading"><span>2</span><div><h3>Your feedback</h3><p>Describe the issue and how urgent it is.</p></div></div>
            <div class="feedback-form-fields">
              <div class="field"><label for="feedback_category">Feedback category</label><select id="feedback_category" name="feedback_category" data-feedback-options="categories" data-selected="<?php echo htmlspecialchars($feedbackFormValues['feedback_category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required disabled><option>Loading categories…</option></select></div>
              <div class="field"><label for="urgency_level">Urgency level</label><select id="urgency_level" name="urgency_level" required><option value="">Choose urgency</option><?php foreach ($urgencyLevels as $urgencyLevel): ?><option value="<?php echo htmlspecialchars($urgencyLevel, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($_POST['urgency_level'] ?? '') === $urgencyLevel ? 'selected' : ''; ?>><?php echo htmlspecialchars($urgencyLevel, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
              <div class="field full-width"><label for="feedback_message">Comment / Description</label><textarea id="feedback_message" name="feedback_message" rows="5" maxlength="2000" placeholder="Describe your feedback, concern, or experience." required><?php echo htmlspecialchars($_POST['feedback_message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            </div>
          </section>
          <section class="feedback-form-section">
            <div class="feedback-form-section-heading"><span>3</span><div><h3>Ratings</h3><p>Rate the topic and your overall workplace experience.</p></div></div>
            <div class="staff-rating-grid">
              <fieldset class="star-rating-fieldset staff-rating-card" data-star-group><legend>Topic rating <span class="required-indicator" aria-hidden="true">*</span></legend><p>How would you rate this feedback topic?</p><div class="star-rating-options"><?php foreach ($ratingLabels as $ratingOption => $ratingLabel): ?><label class="star-rating-option" title="<?php echo $ratingOption . ' — ' . htmlspecialchars($ratingLabel, ENT_QUOTES, 'UTF-8'); ?>"><input type="radio" name="feedback_rating" value="<?php echo $ratingOption; ?>" aria-label="<?php echo $ratingOption . ' — ' . htmlspecialchars($ratingLabel, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int) ($_POST['feedback_rating'] ?? 0) === $ratingOption ? 'checked' : ''; ?> required><span aria-hidden="true">★</span><span class="sr-only"><?php echo $ratingOption . ' — ' . htmlspecialchars($ratingLabel, ENT_QUOTES, 'UTF-8'); ?></span></label><?php endforeach; ?></div><small class="rating-scale">1 Poor · 2 Fair · 3 Good · 4 Very Good · 5 Excellent</small></fieldset>
              <fieldset class="star-rating-fieldset staff-rating-card" data-star-group><legend>Overall workplace satisfaction <span class="required-indicator" aria-hidden="true">*</span></legend><p>Overall, how satisfied are you working here?</p><div class="star-rating-options"><?php foreach ($ratingLabels as $ratingOption => $ratingLabel): ?><label class="star-rating-option" title="<?php echo $ratingOption . ' — ' . htmlspecialchars($ratingLabel, ENT_QUOTES, 'UTF-8'); ?>"><input type="radio" name="overall_satisfaction" value="<?php echo $ratingOption; ?>" aria-label="<?php echo $ratingOption . ' — ' . htmlspecialchars($ratingLabel, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int) ($_POST['overall_satisfaction'] ?? 0) === $ratingOption ? 'checked' : ''; ?> required><span aria-hidden="true">★</span><span class="sr-only"><?php echo $ratingOption . ' — ' . htmlspecialchars($ratingLabel, ENT_QUOTES, 'UTF-8'); ?></span></label><?php endforeach; ?></div><small class="rating-scale">1 Poor · 2 Fair · 3 Good · 4 Very Good · 5 Excellent</small></fieldset>
            </div>
          </section>
          <section class="feedback-form-section">
            <div class="feedback-form-section-heading"><span>4</span><div><h3>Ideas &amp; solutions</h3><p>Share suggestions that could help improve the workplace.</p></div></div>
            <div class="feedback-form-fields">
              <div class="field full-width"><label for="preferred_solution">Preferred solution <span class="optional-label">Optional</span></label><textarea id="preferred_solution" name="preferred_solution" rows="3" maxlength="2000" placeholder="What action or solution would you suggest?"><?php echo htmlspecialchars($_POST['preferred_solution'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            </div>
          </section>
          <section class="feedback-form-section">
            <div class="feedback-form-section-heading"><span>5</span><div><h3>Privacy &amp; follow-up</h3><p>Choose who can see your name and whether you need a reply.</p></div></div>
            <div class="feedback-form-fields staff-privacy-grid">
              <fieldset class="feedback-choice-fieldset"><legend>Feedback visibility</legend><div class="feedback-choice-options"><label><input type="radio" name="feedback_visibility" data-feedback-visibility value="identified" <?php echo ($_POST['feedback_visibility'] ?? 'identified') === 'identified' ? 'checked' : ''; ?>><span>Identified</span></label><label><input type="radio" name="feedback_visibility" data-feedback-visibility value="anonymous" <?php echo ($_POST['feedback_visibility'] ?? '') === 'anonymous' ? 'checked' : ''; ?>><span>Anonymous</span></label></div><p class="feedback-privacy-note">Anonymous feedback hides your name in standard administrator details, but your account remains linked for tracking. Your department, role, or comment may still identify you. Authorized database administrators may access account links.</p></fieldset>
              <fieldset class="feedback-choice-fieldset"><legend>Would you like a response from the college?</legend><div class="feedback-choice-options"><label><input type="radio" name="follow_up_required" data-feedback-follow-up value="0" <?php echo ($_POST['follow_up_required'] ?? '0') === '0' ? 'checked' : ''; ?>><span>No</span></label><label><input type="radio" name="follow_up_required" data-feedback-follow-up value="1" <?php echo ($_POST['follow_up_required'] ?? '') === '1' ? 'checked' : ''; ?>><span>Yes</span></label></div><p class="feedback-privacy-note" data-feedback-privacy-note hidden>If you request a response, authorized staff may use the contact details on your account. Anonymous feedback remains anonymous in ordinary feedback details.</p></fieldset>
            </div>
          </section>
          <section class="feedback-form-section">
            <div class="feedback-form-section-heading"><span>6</span><div><h3>Attachment <small>Optional</small></h3><p>Add a document or image that helps explain your feedback.</p></div></div>
            <div class="feedback-form-fields">
              <div class="field full-width"><label for="feedback_attachment">Choose a file <span class="optional-label">Optional</span></label><input type="file" id="feedback_attachment" name="feedback_attachment" data-feedback-attachment accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"><small class="field-help" data-feedback-attachment-status>JPG, JPEG, PNG, or PDF. Maximum size: 5 MB.</small></div>
            </div>
          </section>
          <div class="feedback-form-actions"><button type="submit" class="btn btn-primary"><?php echo $editingFeedback ? 'Save changes' : 'Submit feedback'; ?></button></div>
        </form>
      </section>
    <?php elseif ($section === 'feedback-history'): ?>
      <section class="content-card feedback-history-summary">
        <div class="card-header"><h2>My feedback status</h2><button type="button" class="send-feedback-button" data-send-feedback><span aria-hidden="true">＋</span> Send Feedback</button></div>
        <div class="feedback-status-summary" aria-label="Feedback status totals"><?php foreach ($feedbackCounts as $feedbackStatus => $feedbackCount): ?><article class="feedback-status-card feedback-status-card-<?php echo strtolower($feedbackStatus); ?>"><span><?php echo htmlspecialchars($feedbackStatus, ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo $feedbackCount; ?></strong><small><?php echo $feedbackStatus === 'New' ? 'Waiting for review' : ($feedbackStatus === 'Reviewed' ? 'Seen by a reviewer' : 'Closed with action taken'); ?></small></article><?php endforeach; ?></div>
      </section>
      <?php if (!$feedbackRows): ?><section class="content-card"><div class="empty-state">No feedback submitted yet.</div></section><?php else: ?>
        <?php if ($feedbackNotice): ?><div class="alert alert-<?php echo $feedbackNotice['type'] === 'error' ? 'error' : 'success'; ?>" role="status"><?php echo htmlspecialchars($feedbackNotice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form method="GET" class="portal-feedback-filters" aria-label="Filter feedback"><input type="hidden" name="section" value="feedback-history"><label><span class="sr-only">Search feedback</span><input type="search" name="q" value="<?php echo htmlspecialchars($feedbackSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search feedback"></label><label><span>Date</span><input type="date" name="date" value="<?php echo htmlspecialchars($feedbackDate, ENT_QUOTES, 'UTF-8'); ?>"></label><label><span>Status</span><select name="status"><option value="">All statuses</option><?php foreach (['New','Reviewed','Resolved'] as $filterStatus): ?><option value="<?php echo $filterStatus; ?>" <?php echo $feedbackStatusFilter === $filterStatus ? 'selected' : ''; ?>><?php echo $filterStatus; ?></option><?php endforeach; ?></select></label><label><span>Visibility</span><select name="visibility"><option value="">All visibility</option><option value="identified" <?php echo $feedbackVisibilityFilter === 'identified' ? 'selected' : ''; ?>>Identified</option><option value="anonymous" <?php echo $feedbackVisibilityFilter === 'anonymous' ? 'selected' : ''; ?>>Anonymous</option></select></label><button class="btn btn-primary" type="submit">Apply</button><a class="btn btn-outline" href="dashboard.php?section=feedback-history">Clear</a></form>
        <div class="list-table-card">
          <div class="list-table-wrap"><table class="list-table"><thead><tr><th>Title</th><th>Category</th><th>Department</th><th>Role</th><th>Comment</th><th>Rating</th><th>Satisfaction</th><th>Urgency</th><th>Visibility</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($feedbackHistoryRows as $feedbackRow): $isNew = $feedbackRow['status'] === 'New'; ?><tr><td><?php echo htmlspecialchars($feedbackRow['feedback_title'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($feedbackRow['category'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($feedbackRow['staff_department'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($feedbackRow['staff_role'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td><td class="portal-feedback-message"><?php echo htmlspecialchars($feedbackRow['message'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $feedbackRow['rating']; ?> / 5</td><td><?php echo $feedbackRow['overall_satisfaction'] ? (int) $feedbackRow['overall_satisfaction'] . ' / 5' : '—'; ?></td><td><?php echo htmlspecialchars($feedbackRow['urgency_level'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo $feedbackRow['is_anonymous'] ? 'Anonymous' : 'Identified'; ?></td><td><span class="status-badge feedback-status-<?php echo strtolower($feedbackRow['status']); ?>"><?php echo htmlspecialchars($feedbackRow['status'], ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars(date('d M Y', strtotime($feedbackRow['submit_date'])), ENT_QUOTES, 'UTF-8'); ?></td><td><div class="feedback-row-actions"><?php if ($isNew): ?><button type="button" class="feedback-action" aria-label="Edit feedback" title="Edit feedback" data-send-feedback data-feedback-form-url="dashboard.php?section=feedback-form&amp;embed=1&amp;edit=<?php echo (int) $feedbackRow['id']; ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m4 16.5-.8 4.3 4.3-.8L19 8.5 15.5 5 4 16.5Z"/><path d="m14.5 6 3.5 3.5"/></svg></button><form method="POST" class="feedback-delete-form" data-confirm data-confirm-title="Delete feedback?" data-confirm-message="This feedback entry will be permanently removed." data-confirm-label="Delete" data-confirm-danger="true"><input type="hidden" name="action" value="delete_feedback"><input type="hidden" name="feedback_id" value="<?php echo (int) $feedbackRow['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($feedbackCsrf, ENT_QUOTES, 'UTF-8'); ?>"><button class="feedback-action danger" type="submit" aria-label="Delete feedback" title="Delete feedback"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m5 5v6m4-6v6"/></svg></button></form><?php else: ?><button type="button" class="feedback-action is-locked" aria-label="Edit feedback" title="Only new feedback can be edited" data-feedback-locked><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m4 16.5-.8 4.3 4.3-.8L19 8.5 15.5 5 4 16.5Z"/><path d="m14.5 6 3.5 3.5"/></svg></button><button type="button" class="feedback-action danger is-locked" aria-label="Delete feedback" title="Only new feedback can be deleted" data-feedback-locked><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m5 5v6m4-6v6"/></svg></button><?php endif; ?></div></td></tr><?php endforeach; ?>
          </tbody></table></div>
        </div>
        <?php renderPortalPagination($feedbackHistoryTotal, $feedbackHistoryPage, $feedbackHistoryPages, $feedbackHistoryPerPage, $allowedFeedbackPageSizes, ['section' => 'feedback-history', 'q' => $feedbackSearch, 'date' => $feedbackDate, 'status' => $feedbackStatusFilter, 'visibility' => $feedbackVisibilityFilter]); ?>
      <?php endif; ?>
    <?php elseif ($section === 'account'): ?>
      <div class="profile-settings portal-profile-settings">
      <nav class="profile-tabs" role="tablist" aria-label="Profile settings">
        <a class="profile-tab <?php echo $accountTab === 'details' ? 'active' : ''; ?>" id="profileDetailsTab" role="tab" aria-controls="profileDetailsPanel" aria-selected="<?php echo $accountTab === 'details' ? 'true' : 'false'; ?>" href="dashboard.php?section=account&amp;account_tab=details">Profile Details</a>
        <a class="profile-tab <?php echo $accountTab === 'security' ? 'active' : ''; ?>" id="profileSecurityTab" role="tab" aria-controls="profileSecurityPanel" aria-selected="<?php echo $accountTab === 'security' ? 'true' : 'false'; ?>" href="dashboard.php?section=account&amp;account_tab=security">Security</a>
      </nav>
      <?php if ($accountError): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($accountError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <?php if ($accountNotice): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($accountNotice, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <?php if ($accountTab === 'details'): ?>
        <section class="profile-tab-panel active" role="tabpanel" id="profileDetailsPanel" aria-labelledby="profileDetailsTab">
        <div class="profile-section-heading"><div><h2>Profile Information</h2><p>Update your name and username. Email changes require administrator verification.</p></div></div>
        <section class="profile-card portal-profile-card" aria-label="Profile information">
          <form method="POST" action="dashboard.php?section=account&account_tab=details" class="submission-form account-form">
            <input type="hidden" name="action" value="update_profile"><input type="hidden" name="account_csrf_token" value="<?php echo htmlspecialchars($accountCsrf, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="100" value="<?php echo htmlspecialchars($_POST['full_name'] ?? $staff['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field"><label for="username">Username</label><input id="username" name="username" minlength="3" maxlength="60" pattern="[A-Za-z0-9._-]{3,60}" value="<?php echo htmlspecialchars($_POST['username'] ?? $staff['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field"><label for="email">Email address</label><input id="email" type="email" value="<?php echo htmlspecialchars($staff['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
            <button type="submit" class="btn btn-primary">Save profile</button>
          </form>
        </section>
        </section>
      <?php else: ?>
        <section class="profile-tab-panel active" role="tabpanel" id="profileSecurityPanel" aria-labelledby="profileSecurityTab">
        <div class="profile-section-heading"><div><h2>Security</h2><p>Update your password to keep your account secure.</p></div></div>
        <section class="profile-card portal-profile-card">
          <div class="profile-card-heading"><h3>Reset Password</h3><p>Enter your current password and choose a new secure password.</p></div>
          <form method="POST" action="dashboard.php?section=account&account_tab=security" class="submission-form account-form">
            <input type="hidden" name="action" value="change_password"><input type="hidden" name="account_csrf_token" value="<?php echo htmlspecialchars($accountCsrf, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
            <div class="field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="8" autocomplete="new-password" required></div>
            <div class="field"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required></div>
            <button type="submit" class="btn btn-primary">Update password</button>
          </form>
          <p class="account-help">If you no longer know your current password, contact the campus administrator.</p>
        </section>
        </section>
      <?php endif; ?>
      </div>
    <?php endif; ?>
    <footer class="portal-site-footer"><span>&copy; <?php echo date('Y'); ?> Patan Nistha Campus. All Rights Reserved. | College Online Feedback System</span></footer>
  </main>
  <script src="../portal_feedback_form.js"></script>
  <?php if (isset($_GET['embed'], $_GET['saved'])): ?><script>window.parent.postMessage({type:'portal-feedback-saved'}, window.location.origin);</script><?php endif; ?>
</body>
</html>
