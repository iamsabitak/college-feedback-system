<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

$staffId = (int) $_SESSION['staff_id'];
$feedbackCategories = ['Teachers', 'Courses', 'Library', 'Canteen', 'Laboratories', 'Facilities', 'Administration', 'Internal Processes', 'Overall Impression', 'Admissions', 'Campus', 'Events', 'Other'];
$feedbackCsrf = $_SESSION['staff_feedback_csrf'] ?? bin2hex(random_bytes(32));
$_SESSION['staff_feedback_csrf'] = $feedbackCsrf;
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
      $_SESSION['portal_feedback_notice'] = ['type' => 'success', 'message' => 'Feedback submitted. You can track its status below.'];
      header('Location: dashboard.php');
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

$submissionStmt = $pdo->query('SELECT s.*, u.full_name, u.username FROM portal_submissions s INNER JOIN portal_users u ON u.id = s.user_id ORDER BY s.created_at DESC');
$submissions = $submissionStmt->fetchAll();

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
foreach ($submissions as $row) {
    $statusCounts[$row['status']] = ($statusCounts[$row['status']] ?? 0) + 1;
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
      <a class="active" href="dashboard.php">Dashboard</a>
      <a href="manage_users.php">Manage users</a>
      <a href="logout.php">Log out</a>
    </nav>
  </aside>

  <main class="dashboard-main">
    <header class="dashboard-header">
      <div>
        <span class="page-kicker">Team overview</span>
        <h1><?php echo htmlspecialchars($staff['full_name'] ?? 'Staff Member', ENT_QUOTES, 'UTF-8'); ?></h1>
      </div>
      <div class="user-chip"><?php echo htmlspecialchars($staff['username'] ?? 'staff', ENT_QUOTES, 'UTF-8'); ?></div>
    </header>

    <section class="stats-grid">
      <article class="stat-card">
        <span class="stat-label">Submitted</span>
        <strong><?php echo $statusCounts['Submitted']; ?></strong>
      </article>
      <article class="stat-card">
        <span class="stat-label">Under Review</span>
        <strong><?php echo $statusCounts['Under Review']; ?></strong>
      </article>
      <article class="stat-card">
        <span class="stat-label">Completed</span>
        <strong><?php echo $statusCounts['Completed']; ?></strong>
      </article>
      <article class="stat-card">
        <span class="stat-label">Feedback resolved</span>
        <strong><?php echo $feedbackCounts['Resolved']; ?></strong>
      </article>
    </section>

    <section class="content-card lower-space">
      <div class="card-header">
        <h2>Send feedback</h2>
      </div>
      <?php if ($feedbackNotice): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($feedbackNotice['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <?php if ($feedbackError): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($feedbackError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <form method="POST" class="submission-form two-column-form">
        <input type="hidden" name="action" value="submit_feedback">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($feedbackCsrf, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="field">
          <label for="feedback_category">Topic</label>
          <select id="feedback_category" name="feedback_category" required>
            <option value="">Choose a topic</option>
            <?php foreach ($feedbackCategories as $feedbackCategory): ?>
              <option value="<?php echo htmlspecialchars($feedbackCategory, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($_POST['feedback_category'] ?? '') === $feedbackCategory ? 'selected' : ''; ?>><?php echo htmlspecialchars($feedbackCategory, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="feedback_rating">Rating</label>
          <select id="feedback_rating" name="feedback_rating" required>
            <option value="">Choose a rating</option>
            <?php for ($ratingOption = 5; $ratingOption >= 1; $ratingOption--): ?>
              <option value="<?php echo $ratingOption; ?>" <?php echo (int) ($_POST['feedback_rating'] ?? 0) === $ratingOption ? 'selected' : ''; ?>><?php echo $ratingOption; ?> / 5</option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="field full-width">
          <label for="feedback_message">Comment</label>
          <textarea id="feedback_message" name="feedback_message" rows="4" maxlength="2000" required><?php echo htmlspecialchars($_POST['feedback_message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit feedback</button>
      </form>
    </section>

    <section class="content-card">
      <div class="card-header">
        <h2>Submission queue</h2>
      </div>

      <?php if (!$submissions): ?>
        <div class="empty-state">No submissions available.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>User</th>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Submitted</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($submissions as $row): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>"><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                  <td><?php echo htmlspecialchars(date('d M Y', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section class="content-card">
      <div class="card-header">
        <h2>My feedback status</h2>
      </div>
      <div class="feedback-status-summary" aria-label="Feedback status totals">
        <?php foreach ($feedbackCounts as $feedbackStatus => $feedbackCount): ?>
          <span><?php echo htmlspecialchars($feedbackStatus, ENT_QUOTES, 'UTF-8'); ?> <strong><?php echo $feedbackCount; ?></strong></span>
        <?php endforeach; ?>
      </div>
      <?php if (!$feedbackRows): ?>
        <div class="empty-state">No feedback submitted yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Topic</th><th>Comment</th><th>Rating</th><th>Status</th><th>Submitted</th></tr></thead>
            <tbody>
              <?php foreach ($feedbackRows as $feedbackRow): ?>
                <tr>
                  <td><?php echo htmlspecialchars($feedbackRow['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="portal-feedback-message"><?php echo htmlspecialchars($feedbackRow['message'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo (int) $feedbackRow['rating']; ?> / 5</td>
                  <td><span class="status-badge feedback-status-<?php echo strtolower($feedbackRow['status']); ?>"><?php echo htmlspecialchars($feedbackRow['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                  <td><?php echo htmlspecialchars(date('d M Y', strtotime($feedbackRow['submit_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
