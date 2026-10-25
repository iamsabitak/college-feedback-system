<?php
/**
 * Displays a complete feedback record and its status details.
 *
 * Application-owned logic for view_feedback.php.
 */
require 'includes/auth.php';
require 'includes/db.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM feedback WHERE id = :id');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    header('Location: dashboard.php');
    exit;
}
if ($row['is_anonymous']) {
  $row['submitter_name'] = null;
}

function badgeClass($type) {
    return match ($type) {
        'Student' => 'badge-student',
        'Teacher & Staff' => 'badge-staff',
        'Visitor & Outsider' => 'badge-visitor',
        'Public' => 'badge-visitor',
        default => '',
    };
}
function starGlyphs($rating) {
    return str_repeat('★', (int) $rating) . str_repeat('☆', 5 - (int) $rating);
}
$currentStatus = $row['status'] ?? 'New';

$pageTitle = 'Feedback Detail';
$active = 'dashboard';
include 'includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Feedback Detail</h1>
    <p>Full submission record, entry #<?php echo (int) $row['id']; ?>.</p>
  </div>
  <a href="dashboard.php" class="btn btn-outline btn-sm">&larr; Back to Dashboard</a>
</div>

<div class="detail-card">
  <div class="detail-top">
    <div>
      <div class="detail-meta">
        <span class="badge <?php echo badgeClass($row['submitter_type']); ?>"><?php echo htmlspecialchars($row['submitter_type']); ?></span>
        <?php if ($row['is_anonymous']): ?><span class="badge badge-anon">Anonymous</span><?php endif; ?>
        <span class="badge badge-<?php echo strtolower($currentStatus); ?>"><?php echo htmlspecialchars($currentStatus); ?></span>
      </div>
      <h2><?php echo $row['is_anonymous'] ? 'Anonymous Submission' : htmlspecialchars($row['submitter_name'] ?: 'Unnamed'); ?></h2>
      <div class="detail-date">Submitted <?php echo date('d M Y, g:i A', strtotime($row['submit_date'])); ?></div>
    </div>
    <span class="stars-inline" style="font-size:20px;"><?php echo starGlyphs($row['rating']); ?></span>
  </div>

  <div class="detail-block">
    <label>Feedback title</label>
    <div class="value"><?php echo htmlspecialchars($row['feedback_title'] ?: 'Not provided'); ?></div>
  </div>

  <div class="detail-block">
    <label>Category</label>
    <div class="value"><?php echo htmlspecialchars($row['category']); ?></div>
  </div>

  <?php if ($row['submitter_type'] === 'Student'): ?>
    <div class="detail-block"><label>Department / Faculty</label><div class="value"><?php echo htmlspecialchars($row['department_faculty'] ?: 'Not provided'); ?><?php if ($row['semester_year']): ?> · <?php echo htmlspecialchars($row['semester_year']); ?><?php endif; ?></div></div>
  <?php elseif ($row['submitter_type'] === 'Teacher & Staff'): ?>
    <div class="detail-block"><label>Staff department and role</label><div class="value"><?php echo htmlspecialchars(trim(($row['staff_department'] ?? '') . ' · ' . ($row['staff_role'] ?? ''), ' ·') ?: 'Not provided'); ?></div></div>
  <?php endif; ?>

  <?php if ($row['urgency_level']): ?><div class="detail-block"><label>Urgency</label><div class="value"><?php echo htmlspecialchars($row['urgency_level']); ?></div></div><?php endif; ?>

  <div class="detail-block status-control">
    <label for="feedbackStatus">Status</label>
    <form method="POST" action="status_update.php" data-status-form>
      <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
      <select id="feedbackStatus" name="status" data-status-select>
        <?php foreach (['New', 'Reviewed', 'Resolved'] as $option): ?><option value="<?php echo $option; ?>" <?php echo $currentStatus === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
      <span class="status-toast" aria-live="polite"></span>
    </form>
  </div>

  <div class="detail-block">
    <label>Rating</label>
    <div class="value"><?php echo (int) $row['rating']; ?> out of 5 stars</div>
  </div>

  <?php if ($row['overall_satisfaction']): ?><div class="detail-block"><label><?php echo $row['submitter_type'] === 'Teacher & Staff' ? 'Overall workplace satisfaction' : 'Overall satisfaction'; ?></label><div class="value"><?php echo (int) $row['overall_satisfaction']; ?> out of 5</div></div><?php endif; ?>

  <div class="detail-block">
    <label>Comment</label>
    <div class="value"><?php echo nl2br(htmlspecialchars($row['message'])); ?></div>
  </div>

  <?php if ($row['preferred_solution']): ?><div class="detail-block"><label>Preferred solution</label><div class="value"><?php echo nl2br(htmlspecialchars($row['preferred_solution'])); ?></div></div><?php endif; ?>
  <?php if ($row['suggested_improvements']): ?><div class="detail-block"><label>Suggested improvements</label><div class="value"><?php echo nl2br(htmlspecialchars($row['suggested_improvements'])); ?></div></div><?php endif; ?>
  <div class="detail-block"><label>Response requested</label><div class="value"><?php echo !empty($row['follow_up_required']) ? 'Yes — use the contact details on the associated account where permitted.' : 'No'; ?></div></div>
  <?php if (!empty($row['attachment_name'])): ?><div class="detail-block"><label>Attachment</label><div class="value"><a href="feedback_attachment.php?id=<?php echo (int) $row['id']; ?>"><?php echo htmlspecialchars($row['attachment_name']); ?></a></div></div><?php endif; ?>

  <div class="detail-block">
    <label>Anonymity</label>
    <div class="value">
      <?php echo $row['is_anonymous']
        ? 'Identity is hidden in standard feedback details. Portal account links may be available to authorized database administrators for tracking.'
        : 'Submitted with name: ' . htmlspecialchars($row['submitter_name'] ?: '—'); ?>
    </div>
  </div>

  <div class="detail-actions">
    <button type="button" class="btn btn-danger" data-delete-id="<?php echo (int) $row['id']; ?>"
            data-delete-name="<?php echo htmlspecialchars($row['is_anonymous'] ? 'this anonymous entry' : ($row['submitter_name'] ?: 'this entry')); ?>">
      Delete Entry
    </button>
  </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <h3>Delete this feedback entry?</h3>
    <p>You're about to permanently delete the entry from <strong id="deleteTargetName"></strong>. This cannot be undone.</p>
    <form id="deleteForm" method="POST" action="delete_feedback.php">
      <input type="hidden" name="redirect" value="dashboard.php">
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-danger">Delete Permanently</button>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
