<?php
/**
 * Shared administrator layout, sidebar, navigation, and page metadata.
 *
 * Application-owned logic for includes/header.php.
 */
if (!isset($active)) { $active = ''; }
$headerAdminEmail = '';
if (isset($pdo) && !empty($_SESSION['admin_id'])) {
  $headerEmailStmt = $pdo->prepare('SELECT email FROM admin WHERE id = :id LIMIT 1');
  $headerEmailStmt->execute([':id' => $_SESSION['admin_id']]);
  $headerAdminEmail = (string) ($headerEmailStmt->fetchColumn() ?: '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · ' : ''; ?>Admin Portal &middot; College Feedback System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,600;0,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="../assets/logo.svg">
<link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body">

<div class="admin-shell">
  <aside class="sidebar">
    <div class="sb-brand">
      <span class="brand-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span>
      <div><strong>Admin Portal</strong><br><small>Patan Nistha Campus</small></div>
    </div>
    <nav class="sb-nav">
      <a href="dashboard.php" class="sb-link <?php echo $active === 'dashboard' ? 'active' : ''; ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
        Dashboard
      </a>
      <a href="feedback_list.php" class="sb-link <?php echo $active === 'feedback-list' ? 'active' : ''; ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
        Feedback List
      </a>
      <a href="../public-portal/index.php" class="sb-link" target="_blank">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>
        View Public Site
      </a>
    </nav>
    <div class="sb-footer account-menu" id="accountMenu">
      <button type="button" class="sb-account-trigger" id="accountMenuToggle" aria-expanded="false" aria-controls="accountPopover">
      <div class="sb-admin">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?></div>
        <div>
          <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong><br>
          <small><?php echo htmlspecialchars($headerAdminEmail ?: 'Administrator', ENT_QUOTES, 'UTF-8'); ?></small>
        </div>
      </div>
        <svg class="account-chevron" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 10 5 5 5-5"/></svg>
      </button>
      <section class="account-popover" id="accountPopover" aria-label="Admin account menu" hidden>
        <div class="account-popover-head">
          <div class="avatar account-avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="account-identity"><strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($headerAdminEmail ?: 'Email not set', ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
        <div class="account-popover-divider"></div>
        <a href="profile.php" class="account-menu-item <?php echo $active === 'profile' ? 'active' : ''; ?>"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>Account Details</a>
        <a href="logout.php" class="account-menu-item account-logout"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>Log out</a>
      </section>
    </div>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <nav class="admin-breadcrumb" aria-label="Breadcrumb"><a href="dashboard.php">College Feedback</a><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg><span><?php echo htmlspecialchars($pageTitle ?? 'Dashboard', ENT_QUOTES, 'UTF-8'); ?></span></nav>
      <div class="notification-wrap" id="feedbackNotifications" data-notification-scope="<?php echo htmlspecialchars($_SESSION['notification_scope'] ?? ('admin-' . (int) $_SESSION['admin_id']), ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="notification-toggle" id="notificationToggle" aria-expanded="false" aria-controls="notificationPanel" aria-label="Notifications">
          <span class="notification-bell-icon"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg><span class="notification-count" id="notificationCount" hidden>0</span></span>
        </button>
        <section class="notification-panel" id="notificationPanel" aria-label="Recent feedback notifications" hidden>
          <div class="notification-panel-head"><div><strong>Recent feedback</strong><span data-notification-total>All feedback updates</span></div><button type="button" class="notification-read" data-notification-read>Mark all as read</button></div>
          <div class="notification-tabs" role="tablist" aria-label="Notification status">
            <button type="button" class="notification-tab is-active" role="tab" aria-selected="true" data-notification-filter="all">All</button>
            <button type="button" class="notification-tab" role="tab" aria-selected="false" data-notification-filter="read">Read</button>
            <button type="button" class="notification-tab" role="tab" aria-selected="false" data-notification-filter="unread">Unread</button>
          </div>
          <div class="notification-list" data-notification-list><p class="notification-empty">Loading recent feedback…</p></div>
        </section>
      </div>
    </div>
    <?php if (!in_array($pageTitle ?? '', ['Dashboard', 'Feedback List'], true)): ?>
    <div class="modal-overlay" id="feedbackModal" role="dialog" aria-modal="true" aria-labelledby="feedbackModalTitle">
      <div class="modal-box feedback-modal-box">
        <div class="feedback-modal-header"><div><div class="detail-meta"><span class="badge" id="feedbackModalType"></span><span class="badge badge-anon" id="feedbackModalAnonymous" hidden>Anonymous</span><span class="badge" id="feedbackModalStatus"></span></div><h3 id="feedbackModalTitle"></h3><p class="detail-date" id="feedbackModalDate"></p></div><button type="button" class="modal-close" aria-label="Close feedback details" data-feedback-modal-close>&times;</button></div>
        <div class="detail-block"><label>Category</label><div class="value" id="feedbackModalCategory"></div></div>
        <div class="detail-block"><label>Rating</label><div class="value" id="feedbackModalRating"></div></div>
        <div class="detail-block"><label>Comment</label><div class="value feedback-message" id="feedbackModalMessage"></div></div>
        <div class="detail-block"><label>Anonymity</label><div class="value" id="feedbackModalAnonymity"></div></div>
        <details class="modal-status-disclosure"><summary>Manage Status</summary><div class="detail-block status-control"><form action="status_update.php" method="POST" data-table-status-form><input type="hidden" name="id" id="feedbackModalId"><select id="feedbackModalStatusSelect" name="status"><option value="New">New</option><option value="Reviewed">Reviewed</option><option value="Resolved">Resolved</option></select><button type="submit" class="btn btn-primary btn-sm">Save status</button></form></div></details>
        <div class="modal-actions"><button type="button" class="btn btn-outline" data-feedback-modal-close>Close</button></div>
      </div>
    </div>
    <?php endif; ?>
