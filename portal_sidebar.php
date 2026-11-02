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
function initializePortalAccountMenu() {
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
}
initializePortalAccountMenu();

function initializeFeedbackModal() {
  const triggers = Array.from(document.querySelectorAll('[data-send-feedback]'));
  if (!triggers.length) return;
  const modal = document.createElement('section');
  modal.className = 'feedback-modal';
  modal.hidden = true;
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
  modal.setAttribute('aria-labelledby', 'feedbackModalTitle');
  modal.innerHTML = '<div class="feedback-modal-backdrop" data-feedback-modal-close></div><div class="feedback-modal-dialog"><div class="feedback-modal-head"><div><h2 id="feedbackModalTitle">Send feedback</h2><p>Tell us what is working and what could be better. It takes about two minutes.</p></div><button type="button" class="feedback-modal-close" data-feedback-modal-close aria-label="Close send feedback form">&times;</button></div><iframe class="feedback-modal-frame" title="Send feedback form"></iframe></div>';
  document.body.appendChild(modal);
  const frame = modal.querySelector('iframe');
  let opener = null;
  function closeModal() { modal.hidden = true; frame.removeAttribute('src'); if (opener) opener.focus(); }
  triggers.forEach(function (trigger) {
    trigger.addEventListener('click', function () {
      opener = trigger;
      frame.src = trigger.dataset.feedbackFormUrl || 'dashboard.php?section=feedback-form&embed=1';
      modal.hidden = false;
      modal.querySelector('.feedback-modal-close').focus();
    });
  });
  modal.addEventListener('click', function (event) { if (event.target.hasAttribute('data-feedback-modal-close')) closeModal(); });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
  window.addEventListener('message', function (event) { if (event.origin === window.location.origin && event.data && event.data.type === 'portal-feedback-saved') { closeModal(); window.location.reload(); } });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeFeedbackModal, { once: true });
} else {
  initializeFeedbackModal();
}

function enhanceFeedbackValueBadges() {
  document.querySelectorAll('.list-table').forEach(function (table) {
    const headers = Array.from(table.querySelectorAll('thead th')).map(function (header) { return header.textContent.trim().toLowerCase(); });
    const urgencyIndex = headers.indexOf('urgency');
    const visibilityIndex = headers.indexOf('visibility');
    table.querySelectorAll('tbody tr').forEach(function (row) {
      [[urgencyIndex, 'urgency'], [visibilityIndex, 'visibility']].forEach(function (value) {
        const index = value[0], kind = value[1]; const cell = index >= 0 ? row.cells[index] : null;
        if (!cell || cell.querySelector('.feedback-value-badge')) return;
        const label = cell.textContent.trim(); const badge = document.createElement('span');
        badge.className = 'feedback-value-badge ' + kind + '-' + label.toLowerCase().replace(/\s+/g, '-'); badge.textContent = label; cell.replaceChildren(badge);
      });
    });
  });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', enhanceFeedbackValueBadges, { once: true }); else enhanceFeedbackValueBadges();

function showPortalToast(message, type) {
  let toast = document.getElementById('portalActionToast');
  if (!toast) { toast = document.createElement('div'); toast.id = 'portalActionToast'; toast.className = 'portal-action-toast'; toast.setAttribute('role', 'status'); toast.setAttribute('aria-live', 'polite'); document.body.appendChild(toast); }
  toast.textContent = message; toast.className = 'portal-action-toast ' + (type || 'error') + ' visible';
  window.clearTimeout(toast._timer); toast._timer = window.setTimeout(function () { toast.classList.remove('visible'); }, 3600);
}
document.addEventListener('click', function (event) {
  const locked = event.target.closest('[data-feedback-locked]');
  if (locked) showPortalToast('Reviewed or resolved feedback cannot be edited or deleted.', 'error');
});
document.addEventListener('submit', function (event) {
  // Confirmation is handled by the shared action-confirm modal.
});
</script>
<?php include __DIR__ . '/confirmation_modal.php'; ?>
