<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

function renderStudentPieChart(string $title, array $values, array $colors, string $totalLabel): void
{
  $total = array_sum($values);
  $segments = [];
  $start = 0;
  $index = 0;
  $valueCount = count($values);

  if ($total > 0) {
    foreach ($values as $label => $value) {
      $end = ++$index === $valueCount ? 100 : $start + ((int) $value / $total * 100);
      $color = $colors[$label] ?? '#4a6b57';
      $segments[] = $color . ' ' . number_format($start, 3, '.', '') . '% ' . number_format($end, 3, '.', '') . '%';
      $start = $end;
    }
  }

  $gradient = $segments ? 'conic-gradient(' . implode(', ', $segments) . ')' : '#e8ece8';
  $chartDescription = [];
  foreach ($values as $label => $value) {
    $chartDescription[] = $label . ': ' . $value;
  }
  ?>
  <article class="content-card chart-card">
    <div class="card-header"><h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2></div>
    <div class="pie-chart-layout">
      <div class="pie-chart" role="img" aria-label="<?php echo htmlspecialchars($title . ' breakdown. ' . implode(', ', $chartDescription), ENT_QUOTES, 'UTF-8'); ?>" style="background: <?php echo htmlspecialchars($gradient, ENT_QUOTES, 'UTF-8'); ?>">
        <span class="pie-hole"><strong><?php echo $total; ?></strong><small><?php echo htmlspecialchars($totalLabel, ENT_QUOTES, 'UTF-8'); ?></small></span>
      </div>
      <ul class="pie-legend">
        <?php foreach ($values as $label => $value): ?>
          <li>
            <span class="pie-swatch" style="--slice-color: <?php echo htmlspecialchars($colors[$label] ?? '#4a6b57', ENT_QUOTES, 'UTF-8'); ?>"></span>
            <span class="pie-status"><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></span>
            <strong><?php echo (int) $value; ?></strong>
            <small><?php echo $total ? number_format($value / $total * 100, 1) : '0.0'; ?>%</small>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </article>
  <?php
}

$userId = (int) $_SESSION['student_id'];
$sections = [
  'overview' => 'Dashboard',
  'feedback-form' => 'Send Feedback',
  'feedback-history' => 'My Feedback',
  'submission-form' => 'Submit Request',
  'submission-history' => 'My Requests',
  'account' => 'Account',
];
$section = (string) ($_GET['section'] ?? 'overview');
if (!isset($sections[$section])) {
  $section = 'overview';
}
$feedbackCategories = ['Teachers', 'Courses', 'Library', 'Canteen', 'Laboratories', 'Facilities', 'Administration', 'Internal Processes', 'Overall Impression', 'Admissions', 'Campus', 'Events', 'Other'];
$feedbackCsrf = $_SESSION['student_feedback_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['student_feedback_csrf'] = $feedbackCsrf;
$feedbackNotice = $_SESSION['portal_feedback_notice'] ?? null;
unset($_SESSION['portal_feedback_notice']);

$userStmt = $pdo->prepare(
  'SELECT p.id, p.full_name, p.username, p.email, u.id AS account_user_id
   FROM portal_users p
   LEFT JOIN users u ON LOWER(u.email) = LOWER(p.email) AND u.role = \'student\'
   WHERE p.id = :id LIMIT 1'
);
$userStmt->execute([':id' => $userId]);
$user = $userStmt->fetch();
$accountUserId = (int) ($user['account_user_id'] ?? 0);
$feedbackError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title'])) {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);

    if ($title !== '' && $category !== '' && $description !== '') {
        $stmt = $pdo->prepare('INSERT INTO portal_submissions (user_id, title, category, status, description) VALUES (:user_id, :title, :category, :status, :description)');
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':category' => $category,
            ':status' => 'Submitted',
            ':description' => $description,
        ]);
    }
}

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
           VALUES (:name, :user_id, \'Student\', 0, :category, :rating, :message, \'New\', NOW())'
        );
        $feedbackStmt->execute([
          ':name' => $user['full_name'],
          ':user_id' => $accountUserId,
          ':category' => $category,
          ':rating' => $rating,
          ':message' => strip_tags($message),
        ]);
        $feedbackId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO notifications (feedback_id, admin_id, is_read) SELECT :feedback_id, id, 0 FROM admin')
          ->execute([':feedback_id' => $feedbackId]);
        $pdo->commit();
        $_SESSION['portal_feedback_notice'] = ['type' => 'success', 'message' => 'Feedback submitted. You can track its status below.'];
        header('Location: dashboard.php?section=feedback-form');
        exit;
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        error_log('Student feedback submission failed: ' . $e->getMessage());
        $feedbackError = 'Unable to submit feedback right now. Please try again.';
      }
    }
  }

$submissionsStmt = $pdo->prepare('SELECT * FROM portal_submissions WHERE user_id = :user_id ORDER BY created_at DESC');
$submissionsStmt->execute([':user_id' => $userId]);
$submissions = $submissionsStmt->fetchAll();

$feedbackStmt = $pdo->prepare('SELECT id, category, rating, message, status, submit_date FROM feedback WHERE user_id = :user_id ORDER BY submit_date DESC');
$feedbackStmt->execute([':user_id' => $accountUserId]);
$feedbackRows = $feedbackStmt->fetchAll();
$feedbackCounts = ['New' => 0, 'Reviewed' => 0, 'Resolved' => 0];
foreach ($feedbackRows as $feedbackRow) {
  if (isset($feedbackCounts[$feedbackRow['status']])) {
    $feedbackCounts[$feedbackRow['status']]++;
  }
}

$statusCounts = ['Submitted' => 0, 'Under Review' => 0, 'Completed' => 0];
foreach ($submissions as $submission) {
    $statusCounts[$submission['status']] = ($statusCounts[$submission['status']] ?? 0) + 1;
}

$ratingCounts = array_fill(1, 5, 0);
$feedbackCategoryCounts = [];
foreach ($feedbackRows as $feedbackRow) {
  $rating = (int) $feedbackRow['rating'];
  if (isset($ratingCounts[$rating])) {
    $ratingCounts[$rating]++;
  }
  $feedbackCategoryCounts[$feedbackRow['category']] = ($feedbackCategoryCounts[$feedbackRow['category']] ?? 0) + 1;
}
arsort($feedbackCategoryCounts);
$averageRating = $feedbackRows ? array_sum(array_column($feedbackRows, 'rating')) / count($feedbackRows) : 0;

$monthlyActivity = [];
for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
  $month = new DateTime('first day of this month');
  $month->modify('-' . $monthOffset . ' months');
  $monthKey = $month->format('Y-m');
  $monthlyActivity[$monthKey] = ['label' => $month->format('M'), 'count' => 0];
}
foreach ($feedbackRows as $feedbackRow) {
  $monthKey = date('Y-m', strtotime($feedbackRow['submit_date']));
  if (isset($monthlyActivity[$monthKey])) {
    $monthlyActivity[$monthKey]['count']++;
  }
}
foreach ($submissions as $submission) {
  $monthKey = date('Y-m', strtotime($submission['created_at']));
  if (isset($monthlyActivity[$monthKey])) {
    $monthlyActivity[$monthKey]['count']++;
  }
}
$totalRequests = count($submissions);
$activeRequests = $statusCounts['Submitted'] + $statusCounts['Under Review'];
$totalFeedback = count($feedbackRows);
$requestStatusColors = ['Submitted' => '#e0942c', 'Under Review' => '#4c7890', 'Completed' => '#4a6b57'];
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
  <title><?php echo htmlspecialchars($sections[$section], ENT_QUOTES, 'UTF-8'); ?> | Student Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/student.css">
</head>
<body class="dashboard-body">
  <aside class="dashboard-sidebar">
    <div class="sidebar-brand">
      <span class="brand-mark"><img src="../assets/logo.svg" alt=""></span>
      <div>
        <strong>Student Portal</strong>
        <small>Patan Nistha Campus</small>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($sections as $sectionKey => $sectionLabel): ?>
        <a class="<?php echo $section === $sectionKey ? 'active' : ''; ?>" href="dashboard.php?section=<?php echo urlencode($sectionKey); ?>"><?php echo htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8'); ?></a>
      <?php endforeach; ?>
      <a href="logout.php">Log out</a>
    </nav>
  </aside>

  <main class="dashboard-main">
    <header class="dashboard-header">
      <div>
        <span class="page-kicker"><?php echo $section === 'overview' ? 'Welcome back' : 'Student Portal'; ?></span>
        <h1><?php echo $section === 'overview' ? htmlspecialchars($user['full_name'] ?? 'Student', ENT_QUOTES, 'UTF-8') : htmlspecialchars($sections[$section], ENT_QUOTES, 'UTF-8'); ?></h1>
      </div>
      <div class="user-chip"><?php echo htmlspecialchars($user['username'] ?? 'student', ENT_QUOTES, 'UTF-8'); ?></div>
    </header>

    <?php if ($section === 'overview'): ?>
      <section class="stats-grid">
        <article class="stat-card"><span class="stat-label">Total requests</span><strong><?php echo $totalRequests; ?></strong></article>
        <article class="stat-card"><span class="stat-label">Active requests</span><strong><?php echo $activeRequests; ?></strong></article>
        <article class="stat-card"><span class="stat-label">Feedback sent</span><strong><?php echo $totalFeedback; ?></strong></article>
        <article class="stat-card"><span class="stat-label">Average rating</span><strong><?php echo number_format($averageRating, 1); ?><small> / 5</small></strong></article>
      </section>

      <section class="chart-grid" aria-label="Activity charts">
        <?php renderStudentPieChart('Request status', $statusCounts, $requestStatusColors, 'requests'); ?>
        <?php renderStudentPieChart('Feedback ratings', $ratingChartCounts, $ratingChartColors, 'ratings'); ?>
        <?php renderStudentPieChart('Feedback topics', $feedbackCategoryCounts, $feedbackCategoryColors, 'feedback'); ?>
        <?php renderStudentPieChart('Feedback status', $feedbackCounts, $feedbackStatusColors, 'feedback'); ?>
        <?php renderStudentPieChart('Activity, last six months', $activityCounts, $activityColors, 'events'); ?>
      </section>
    <?php elseif ($section === 'feedback-form'): ?>
      <section class="content-card form-card">
        <div class="card-header"><h2>Send feedback</h2></div>
        <?php if ($feedbackNotice): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($feedbackNotice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($feedbackError): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($feedbackError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form method="POST" class="submission-form">
          <input type="hidden" name="action" value="submit_feedback">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($feedbackCsrf, ENT_QUOTES, 'UTF-8'); ?>">
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
    <?php elseif ($section === 'submission-form'): ?>
      <section class="content-card form-card">
        <div class="card-header"><h2>Submit a request</h2></div>
        <form method="POST" class="submission-form">
          <div class="field"><label for="title">Title</label><input type="text" id="title" name="title" required></div>
          <div class="field"><label for="category">Category</label><input type="text" id="category" name="category" required></div>
          <div class="field"><label for="description">Details</label><textarea id="description" name="description" rows="6" required></textarea></div>
          <button type="submit" class="btn btn-primary">Submit request</button>
        </form>
      </section>
    <?php elseif ($section === 'submission-history'): ?>
      <section class="content-card">
        <div class="card-header"><h2>My requests</h2></div>
        <?php if (!$submissions): ?><div class="empty-state">No submissions yet.</div><?php else: ?>
          <div class="table-wrap"><table class="data-table"><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
            <?php foreach ($submissions as $submission): ?><tr><td><?php echo htmlspecialchars($submission['title'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($submission['category'], ENT_QUOTES, 'UTF-8'); ?></td><td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $submission['status'])); ?>"><?php echo htmlspecialchars($submission['status'], ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars(date('d M Y', strtotime($submission['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?>
          </tbody></table></div>
        <?php endif; ?>
      </section>
    <?php elseif ($section === 'account'): ?>
      <section class="content-card account-card">
        <div class="card-header"><h2>Account details</h2></div>
        <ul class="info-list"><li><span>Full name</span><strong><?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></li><li><span>Email</span><strong><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></li><li><span>Username</span><strong><?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></li></ul>
        <a href="forgot_password.php" class="btn btn-outline">Reset password</a>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
