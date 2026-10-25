<?php
$portalSidebarRole = $portalSidebarRole ?? 'Student';
$portalSidebarUser = $portalSidebarUser ?? [];
$portalActiveSection = $portalActiveSection ?? 'overview';
$portalCurrentPage = $portalCurrentPage ?? 'dashboard';
$portalCanManageStudents = $portalCanManageStudents ?? false;
$portalUsername = (string) ($portalSidebarUser['username'] ?? strtolower($portalSidebarRole));
$portalFullName = (string) ($portalSidebarUser['full_name'] ?? $portalUsername);
$portalEmail = (string) ($portalSidebarUser['email'] ?? '');
$portalInitial = strtoupper(substr($portalUsername, 0, 1));
?>
<aside class="dashboard-sidebar">
  <div class="sidebar-brand">
    <span class="brand-mark"><img src="../assets/logo.svg" alt=""></span>
    <div><strong><?php echo htmlspecialchars($portalSidebarRole, ENT_QUOTES, 'UTF-8'); ?> Portal</strong><small>Patan Nistha Campus</small></div>
  </div>

  <nav class="sidebar-nav" aria-label="Main navigation">
    <a class="<?php echo $portalCurrentPage === 'dashboard' && $portalActiveSection === 'overview' ? 'active' : ''; ?>" href="dashboard.php?section=overview"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg><span>Dashboard</span></a>
    <a class="<?php echo $portalCurrentPage === 'dashboard' && $portalActiveSection === 'feedback-form' ? 'active' : ''; ?>" href="dashboard.php?section=feedback-form"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg><span>Send Feedback</span></a>
    <a class="<?php echo $portalCurrentPage === 'dashboard' && $portalActiveSection === 'feedback-history' ? 'active' : ''; ?>" href="dashboard.php?section=feedback-history"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg><span>My Feedback</span></a>
    <?php if ($portalCanManageStudents): ?>
      <a class="<?php echo $portalCurrentPage === 'manage-users' ? 'active' : ''; ?>" href="manage_users.php"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="4"/><path d="M3 21v-2a6 6 0 0 1 12 0v2"/><path d="M19 8v6M16 11h6"/></svg><span>Create Student Account</span></a>
    <?php endif; ?>
  </nav>

  <div class="portal-account-menu" id="portalAccountMenu">
    <button type="button" class="portal-account-trigger" id="portalAccountToggle" aria-expanded="false" aria-controls="portalAccountPopover">
      <span class="portal-avatar" aria-hidden="true"><?php echo htmlspecialchars($portalInitial, ENT_QUOTES, 'UTF-8'); ?></span>
      <span class="portal-account-trigger-identity"><strong><?php echo htmlspecialchars($portalUsername, ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($portalEmail ?: $portalSidebarRole . ' account', ENT_QUOTES, 'UTF-8'); ?></small></span>
      <svg class="portal-account-chevron" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 10 5 5 5-5"/></svg>
    </button>
    <section class="portal-account-popover" id="portalAccountPopover" aria-label="Account menu" hidden>
      <div class="portal-account-popover-head">
        <span class="portal-avatar portal-account-avatar" aria-hidden="true"><?php echo htmlspecialchars($portalInitial, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="portal-account-identity"><strong><?php echo htmlspecialchars($portalFullName, ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($portalEmail ?: $portalUsername, ENT_QUOTES, 'UTF-8'); ?></small></span>
      </div>
      <div class="portal-account-divider"></div>
      <a class="portal-account-item <?php echo $portalCurrentPage === 'dashboard' && $portalActiveSection === 'account' ? 'active' : ''; ?>" href="dashboard.php?section=account&amp;account_tab=details"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>Account Details</a>
      <a class="portal-account-item portal-account-logout" href="logout.php"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>Log out</a>
    </section>
  </div>
</aside>
<script>
(function () {
  const menu = document.getElementById('portalAccountMenu');
  const toggle = document.getElementById('portalAccountToggle');
  const popover = document.getElementById('portalAccountPopover');
  if (!menu || !toggle || !popover) return;
  let closeTimer = 0;

  function setOpen(open, restoreFocus) {
    window.clearTimeout(closeTimer);
    toggle.setAttribute('aria-expanded', String(open));
    if (open) {
      popover.hidden = false;
      requestAnimationFrame(function () { popover.classList.add('open'); });
      popover.querySelector('a').focus();
    } else {
      popover.classList.remove('open');
      closeTimer = window.setTimeout(function () { popover.hidden = true; }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 180);
      if (restoreFocus) toggle.focus();
    }
  }

  toggle.addEventListener('click', function () {
    setOpen(toggle.getAttribute('aria-expanded') !== 'true', false);
  });
  document.addEventListener('click', function (event) {
    if (!menu.contains(event.target)) setOpen(false, false);
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') setOpen(false, true);
  });
}());
</script>