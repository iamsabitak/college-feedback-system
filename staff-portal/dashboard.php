<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/../portal_account.php';
require __DIR__ . '/../portal_charts.php';

$staffId = (int) $_SESSION['staff_id'];
$sections = [
  'overview' => 'Dashboard',
  'feedback-form' => 'Send Feedback',
  'feedback-history' => 'My Feedback',
  'account' => 'Account',
];
$section = (string) ($_GET['section'] ?? 'overview');
if (!isset($sections[$section])) {
  $section = 'overview';
}
$accountTab = (string) ($_GET['account_tab'] ?? 'details');
if (!in_array($accountTab, ['details', 'security'], true)) {
  $accountTab = 'details';
}
$feedbackCategories = ['Teachers', 'Courses', 'Library', 'Canteen', 'Laboratories', 'Facilities', 'Administration', 'Internal Processes', 'Overall Impression', 'Admissions', 'Campus', 'Events', 'Other'];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_feedback') {
  $category = trim((string) ($_POST['feedback_category'] ?? ''));
  $rating = (int) ($_POST['feedback_rating'] ?? 0);
  $message = trim((string) ($_POST['feedback_message'] ?? ''));
  $messageLength = function_exists('mb_strlen') ? mb_strlen($message, 'UTF-8') : strlen($message);

  if (!hash_equals($feedbackCsrf, (string) ($_POST['csrf_token'] ?? ''))) {
    $feedbackError = 'The form expired. Refresh the page and try again.';
  } elseif ($accountUserId < 1) {
    $feedbackError = 'Your portal identity could not be linked to a feedback account. Contact an administrator.';
  } elseif (!in_array($category, $feedbackCategories, true)) {
    $feedbackError = 'Choose a valid feedback topic.';
  } elseif ($rating < 1 || $rating > 5) {
    $feedbackError = 'Choose a rating from 1 to 5.';
  } elseif ($messageLength < 5 || $messageLength > 2000) {
    $feedbackError = 'Feedback must be between 5 and 2,000 characters.';
  } else {
    try {
      $pdo->beginTransaction();
      $feedbackStmt = $pdo->prepare(
        'INSERT INTO feedback (submitter_name, user_id, submitter_type, is_anonymous, category, rating, message, status, submit_date)
         VALUES (:name, :user_id, \'Teacher & Staff\', 0, :category, :rating, :message, \'New\', NOW())'
      );
      $feedbackStmt->execute([
        ':name' => $staff['full_name'],
        ':user_id' => $accountUserId,
        ':category' => $category,
        ':rating' => $rating,
        ':message' => strip_tags($message),
      ]);
      $feedbackId = (int) $pdo->lastInsertId();
      $pdo->prepare('INSERT INTO notifications (feedback_id, admin_id, is_read) SELECT :feedback_id, id, 0 FROM admin')
        ->execute([':feedback_id' => $feedbackId]);
      $pdo->commit();
      $_SESSION['portal_feedback_notice'] = ['type' => 'success', 'message' => 'Feedback submitted. You can track its status under My Feedback.'];
      header('Location: dashboard.php?section=feedback-form');
      exit;
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      error_log('Staff feedback submission failed: ' . $e->getMessage());
      $feedbackError = 'Unable to submit feedback right now. Please try again.';
    }
  }
}

$feedbackStmt = $pdo->prepare('SELECT id, category, rating, message, status, submit_date FROM feedback WHERE user_id = :user_id ORDER BY submit_date DESC');
$feedbackStmt->execute([':user_id' => $accountUserId]);
$feedbackRows = $feedbackStmt->fetchAll();
$feedbackCounts = ['New' => 0, 'Reviewed' => 0, 'Resolved' => 0];
foreach ($feedbackRows as $feedbackRow) {
  if (isset($feedbackCounts[$feedbackRow['status']])) {
    $feedbackCounts[$feedbackRow['status']]++;
  }
}

$ratingCounts = array_fill(1, 5, 0);
$feedbackCategoryCounts = [];
$monthlyActivity = [];
for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
  $month = new DateTime('first day of this month');
  $month->modify('-' . $monthOffset . ' months');
  $monthlyActivity[$month->format('Y-m')] = ['label' => $month->format('M'), 'count' => 0];
}
foreach ($feedbackRows as $feedbackRow) {
  $rating = (int) $feedbackRow['rating'];
  if (isset($ratingCounts[$rating])) {
    $ratingCounts[$rating]++;
  }
  $feedbackCategoryCounts[$feedbackRow['category']] = ($feedbackCategoryCounts[$feedbackRow['category']] ?? 0) + 1;
  $monthKey = date('Y-m', strtotime($feedbackRow['submit_date']));
  if (isset($monthlyActivity[$monthKey])) {
    $monthlyActivity[$monthKey]['count']++;
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
$activityCounts = [];
foreach ($monthlyActivity as $monthData) {
  $activityCounts[$monthData['label']] = $monthData['count'];
}
$activityColors = [];
foreach (array_keys($activityCounts) as $monthIndex => $monthLabel) {
  $activityColors[$monthLabel] = $chartPalette[$monthIndex % count($chartPalette)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard</title>
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
      <?php foreach ($sections as $sectionKey => $sectionLabel): ?>
        <a class="<?php echo $section === $sectionKey ? 'active' : ''; ?>" href="dashboard.php?section=<?php echo urlencode($sectionKey); ?>"><?php echo htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8'); ?></a>
      <?php endforeach; ?>
      <a href="manage_users.php">Manage users</a>
      <a href="logout.php">Log out</a>
    </nav>
  </aside>

  <main class="dashboard-main">
    <header class="dashboard-header">
      <div>
        <span class="page-kicker"><?php echo $section === 'overview' ? 'Welcome back' : 'Staff Portal'; ?></span>
        <h1><?php echo $section === 'overview' ? htmlspecialchars($staff['full_name'] ?? 'Staff Member', ENT_QUOTES, 'UTF-8') : htmlspecialchars($sections[$section], ENT_QUOTES, 'UTF-8'); ?></h1>
      </div>
      <div class="user-chip"><?php echo htmlspecialchars($staff['username'] ?? 'staff', ENT_QUOTES, 'UTF-8'); ?></div>
    </header>

    <?php if ($section === 'overview'): ?>
      <section class="stats-grid">
        <article class="stat-card"><span class="stat-label">Feedback sent</span><strong><?php echo $totalFeedback; ?></strong></article>
        <article class="stat-card"><span class="stat-label">New feedback</span><strong><?php echo $feedbackCounts['New']; ?></strong></article>
        <article class="stat-card"><span class="stat-label">Resolved feedback</span><strong><?php echo $feedbackCounts['Resolved']; ?></strong></article>
        <article class="stat-card"><span class="stat-label">Average rating</span><strong><?php echo number_format($averageRating, 1); ?><small> / 5</small></strong></article>
      </section>
      <section class="chart-grid" aria-label="Feedback charts">
        <?php renderPortalPieChart('Feedback ratings', $ratingChartCounts, $ratingChartColors, 'ratings'); ?>
        <?php renderPortalPieChart('Feedback topics', $feedbackCategoryCounts, $feedbackCategoryColors, 'feedback'); ?>
        <?php renderPortalPieChart('Feedback status', $feedbackCounts, $feedbackStatusColors, 'feedback'); ?>
        <?php renderPortalPieChart('Feedback activity, last six months', $activityCounts, $activityColors, 'entries'); ?>
      </section>
    <?php elseif ($section === 'feedback-form'): ?>
      <section class="content-card form-card">
        <div class="card-header"><h2>Send feedback</h2></div>
        <?php if ($feedbackNotice): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($feedbackNotice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($feedbackError): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($feedbackError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form method="POST" action="dashboard.php?section=feedback-form" class="submission-form">
          <input type="hidden" name="action" value="submit_feedback"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($feedbackCsrf, ENT_QUOTES, 'UTF-8'); ?>">
          <div class="field"><label for="feedback_category">Topic</label><select id="feedback_category" name="feedback_category" required><option value="">Choose a topic</option><?php foreach ($feedbackCategories as $feedbackCategory): ?><option value="<?php echo htmlspecialchars($feedbackCategory, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($_POST['feedback_category'] ?? '') === $feedbackCategory ? 'selected' : ''; ?>><?php echo htmlspecialchars($feedbackCategory, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
          <div class="field"><label for="feedback_rating">Rating</label><select id="feedback_rating" name="feedback_rating" required><option value="">Choose a rating</option><?php for ($ratingOption = 5; $ratingOption >= 1; $ratingOption--): ?><option value="<?php echo $ratingOption; ?>" <?php echo (int) ($_POST['feedback_rating'] ?? 0) === $ratingOption ? 'selected' : ''; ?>><?php echo $ratingOption; ?> / 5</option><?php endfor; ?></select></div>
          <div class="field"><label for="feedback_message">Comment</label><textarea id="feedback_message" name="feedback_message" rows="6" maxlength="2000" required><?php echo htmlspecialchars($_POST['feedback_message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
          <button type="submit" class="btn btn-primary">Submit feedback</button>
        </form>
      </section>
    <?php elseif ($section === 'feedback-history'): ?>
      <section class="content-card">
        <div class="card-header"><h2>My feedback status</h2></div>
        <div class="feedback-status-summary" aria-label="Feedback status totals"><?php foreach ($feedbackCounts as $feedbackStatus => $feedbackCount): ?><span><?php echo htmlspecialchars($feedbackStatus, ENT_QUOTES, 'UTF-8'); ?> <strong><?php echo $feedbackCount; ?></strong></span><?php endforeach; ?></div>
        <?php if (!$feedbackRows): ?><div class="empty-state">No feedback submitted yet.</div><?php else: ?>
          <div class="table-wrap"><table class="data-table"><thead><tr><th>Topic</th><th>Comment</th><th>Rating</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
            <?php foreach ($feedbackRows as $feedbackRow): ?><tr><td><?php echo htmlspecialchars($feedbackRow['category'], ENT_QUOTES, 'UTF-8'); ?></td><td class="portal-feedback-message"><?php echo htmlspecialchars($feedbackRow['message'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $feedbackRow['rating']; ?> / 5</td><td><span class="status-badge feedback-status-<?php echo strtolower($feedbackRow['status']); ?>"><?php echo htmlspecialchars($feedbackRow['status'], ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars(date('d M Y', strtotime($feedbackRow['submit_date'])), ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?>
          </tbody></table></div>
        <?php endif; ?>
      </section>
    <?php elseif ($section === 'account'): ?>
      <nav class="account-tabs" aria-label="Account settings">
        <a class="<?php echo $accountTab === 'details' ? 'active' : ''; ?>" href="dashboard.php?section=account&account_tab=details">Profile details</a>
        <a class="<?php echo $accountTab === 'security' ? 'active' : ''; ?>" href="dashboard.php?section=account&account_tab=security">Security</a>
      </nav>
      <?php if ($accountError): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($accountError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <?php if ($accountNotice): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($accountNotice, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <?php if ($accountTab === 'details'): ?>
        <section class="content-card account-card">
          <div class="card-header"><h2>Profile information</h2><p>Update your name and username. Email changes require administrator verification.</p></div>
          <form method="POST" action="dashboard.php?section=account&account_tab=details" class="submission-form account-form">
            <input type="hidden" name="action" value="update_profile"><input type="hidden" name="account_csrf_token" value="<?php echo htmlspecialchars($accountCsrf, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="100" value="<?php echo htmlspecialchars($_POST['full_name'] ?? $staff['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field"><label for="username">Username</label><input id="username" name="username" minlength="3" maxlength="60" pattern="[A-Za-z0-9._-]{3,60}" value="<?php echo htmlspecialchars($_POST['username'] ?? $staff['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field"><label for="email">Email address</label><input id="email" type="email" value="<?php echo htmlspecialchars($staff['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
            <button type="submit" class="btn btn-primary">Save profile</button>
          </form>
        </section>
      <?php else: ?>
        <section class="content-card account-card">
          <div class="card-header"><h2>Reset password</h2><p>Verify your current password before choosing a new one.</p></div>
          <form method="POST" action="dashboard.php?section=account&account_tab=security" class="submission-form account-form">
            <input type="hidden" name="action" value="change_password"><input type="hidden" name="account_csrf_token" value="<?php echo htmlspecialchars($accountCsrf, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
            <div class="field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="8" autocomplete="new-password" required></div>
            <div class="field"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required></div>
            <button type="submit" class="btn btn-primary">Update password</button>
          </form>
          <p class="account-help">If you no longer know your current password, contact the campus administrator.</p>
        </section>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</body>
</html>
