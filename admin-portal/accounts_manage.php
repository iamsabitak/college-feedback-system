<?php
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/accounts.php';

$tab = $_GET['tab'] ?? 'accounts';
if (in_array($tab, ['students', 'staff'], true)) {
  $_GET['category'] = $tab === 'students' ? 'student' : 'staff';
  $tab = 'accounts';
}
if (!in_array($tab, ['accounts', 'audit'], true)) {
  $tab = 'accounts';
}
$pageTitle = $tab === 'audit' ? 'Account audit log' : 'Staff & students';
$active = $tab === 'audit' ? 'account-audit' : 'accounts';
$category = $_GET['category'] ?? 'all';
if (!in_array($category, ['all', 'student', 'staff'], true)) {
  $category = 'all';
}

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
if (!in_array($perPage, [10, 20, 50], true)) {
    $perPage = 10;
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$roleFilter = $tab === 'accounts' && $category !== 'all' ? $category : null;

if ($tab === 'audit') {
    $auditFilters = [
        'action' => trim((string) ($_GET['action'] ?? '')),
      'search' => $search,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ];
    $auditActions = $pdo->query('SELECT DISTINCT action FROM account_audit_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
    $auditTotal = account_count_audit_log($pdo, $auditFilters);
    $auditPages = max(1, (int) ceil($auditTotal / $perPage));
    $page = min($page, $auditPages);
    $offset = ($page - 1) * $perPage;
    $auditRows = account_select_audit_log($pdo, $auditFilters, $perPage, $offset);
} else {
    $userFilters = [
        'role' => $roleFilter,
        'search' => $search,
        'status' => $statusFilter,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ];
    $userTotal = account_count_users($pdo, $userFilters);
    $userPages = max(1, (int) ceil($userTotal / $perPage));
    $page = min($page, $userPages);
    $offset = ($page - 1) * $perPage;
    $userRows = account_select_users($pdo, $userFilters, $perPage, $offset);
}

include 'includes/header.php';
?>

<section class="page-head">
  <div>
    <span class="page-kicker">Administration</span>
    <h1><?php echo $tab === 'audit' ? 'Account audit log' : 'Staff &amp; students'; ?></h1>
    <p><?php echo $tab === 'audit' ? 'Review account creation, updates, access changes, and other administrative actions.' : 'Manage student and staff accounts and reset account access.'; ?></p>
  </div>
</section>

<?php if ($tab === 'accounts'): ?>
<div class="table-card" style="margin-bottom:18px;">
  <div class="filter-bar">
    <div class="filter-group search" style="flex:1; min-width:220px;">
      <label for="account-search" style="display:block; margin-bottom:6px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-soft);">Search</label>
      <input type="search" id="account-search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name, username, email or student ID">
    </div>
    <div class="filter-actions">
      <?php if ($tab === 'accounts'): ?>
      <label for="account-category" style="display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:var(--ink-soft);">
        Category
        <select id="account-category" style="min-width:130px;">
          <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All accounts</option>
          <option value="student" <?php echo $category === 'student' ? 'selected' : ''; ?>>Students</option>
          <option value="staff" <?php echo $category === 'staff' ? 'selected' : ''; ?>>Staff</option>
        </select>
      </label>
      <button type="button" class="btn btn-outline" data-open-filters>Filters</button>
      <button type="button" class="btn btn-primary" data-open-account-form>+ Add account</button>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($tab === 'audit'): ?>
  <form class="filter-bar" method="GET">
    <input type="hidden" name="tab" value="audit">
    <div class="filter-group search">
      <label for="audit-search">Search</label>
      <input id="audit-search" name="search" type="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Admin, action, target or details">
    </div>
    <div class="filter-group">
      <label for="audit-action">Action</label>
      <select id="audit-action" name="action">
        <option value="">All actions</option>
        <?php foreach ($auditActions as $auditAction): ?>
          <option value="<?php echo htmlspecialchars($auditAction, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($auditFilters['action'] ?? '') === $auditAction ? 'selected' : ''; ?>><?php echo htmlspecialchars($auditAction, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label for="audit-date-from">From</label>
      <input id="audit-date-from" name="date_from" type="date" value="<?php echo htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="filter-group">
      <label for="audit-date-to">To</label>
      <input id="audit-date-to" name="date_to" type="date" value="<?php echo htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="filter-actions">
      <button type="submit" class="btn btn-primary">Apply</button>
      <a class="btn btn-outline" href="accounts_manage.php?tab=audit">Clear</a>
    </div>
  </form>
  <div class="table-card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Time</th>
            <th>Admin</th>
            <th>Action</th>
            <th>Target</th>
            <th>Changes / details</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($auditRows)): ?>
            <tr><td colspan="5"><div class="empty-state"><h3>No audit entries</h3><p>No admin actions have been recorded yet.</p></div></td></tr>
          <?php else: ?>
            <?php foreach ($auditRows as $row): ?>
              <tr>
                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['actor_username'] ?? 'Admin'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="badge badge-staff"><?php echo htmlspecialchars($row['action'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td><?php echo htmlspecialchars((string) ($row['target_label'] ?: '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['details'] ?: '-'), ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="table-toolbar" style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 18px; border-top:1px solid var(--line); flex-wrap:wrap;">
      <div style="display:flex; gap:8px; align-items:center;">
        <button class="icon-btn" type="button" aria-label="First page" data-page-nav="first" <?php echo $page <= 1 ? 'disabled' : ''; ?>>«</button>
        <button class="icon-btn" type="button" aria-label="Previous page" data-page-nav="prev" <?php echo $page <= 1 ? 'disabled' : ''; ?>>‹</button>
        <span>Page <?php echo (int) $page; ?> of <?php echo (int) $auditPages; ?></span>
        <button class="icon-btn" type="button" aria-label="Next page" data-page-nav="next" <?php echo $page >= $auditPages ? 'disabled' : ''; ?>>›</button>
        <button class="icon-btn" type="button" aria-label="Last page" data-page-nav="last" <?php echo $page >= $auditPages ? 'disabled' : ''; ?>>»</button>
      </div>
      <label style="display:flex; align-items:center; gap:8px; color:var(--ink-soft); font-size:12px;">
        Rows per page
        <select id="rows-per-page" style="min-width:80px;">
          <option value="10" <?php echo $perPage === 10 ? 'selected' : ''; ?>>10</option>
          <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20</option>
          <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50</option>
        </select>
      </label>
    </div>
  </div>
<?php else: ?>
  <div class="table-card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Username</th>
            <th>ID</th>
            <th>Status</th>
            <th>Last login</th>
            <th>Named submissions</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($userRows)): ?>
            <tr><td colspan="8"><div class="empty-state"><h3>No accounts found</h3><p>Try another category or create a new account.</p></div></td></tr>
          <?php else: ?>
            <?php foreach ($userRows as $user): ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                  <span class="badge <?php echo $user['role'] === 'student' ? 'badge-student' : 'badge-staff'; ?>"><?php echo ucfirst(htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8')); ?></span>
                </td>
                <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) ($user['role'] === 'student' ? ($user['student_id'] ?? '-') : ($user['staff_id'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="badge <?php echo $user['status'] === 'active' ? 'badge-student' : 'badge-danger'; ?>"><?php echo htmlspecialchars($user['status'] === 'active' ? 'Active' : 'Disabled', ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td><?php echo htmlspecialchars($user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) ($user['named_submissions'] ?? 0); ?></td>
                <td>
                  <div class="account-menu-wrap" style="position:relative; display:inline-block;">
                    <button type="button" class="icon-btn" data-menu-toggle="account-menu-<?php echo (int) $user['id']; ?>" aria-label="Open actions">⋯</button>
                    <div id="account-menu-<?php echo (int) $user['id']; ?>" class="row-dropdown" hidden>
                      <button type="button" data-account-action="edit" data-account-id="<?php echo (int) $user['id']; ?>">Edit</button>
                      <button type="button" data-account-action="reset_password" data-account-id="<?php echo (int) $user['id']; ?>">Reset password</button>
                      <button type="button" data-account-action="toggle_status" data-account-id="<?php echo (int) $user['id']; ?>"><?php echo $user['status'] === 'active' ? 'Disable' : 'Enable'; ?></button>
                      <button type="button" class="danger" data-account-action="delete" data-account-id="<?php echo (int) $user['id']; ?>">Delete</button>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="table-toolbar" style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 18px; border-top:1px solid var(--line); flex-wrap:wrap;">
      <div style="display:flex; gap:8px; align-items:center;">
        <button class="icon-btn" type="button" aria-label="First page" data-page-nav="first" <?php echo $page <= 1 ? 'disabled' : ''; ?>>«</button>
        <button class="icon-btn" type="button" aria-label="Previous page" data-page-nav="prev" <?php echo $page <= 1 ? 'disabled' : ''; ?>>‹</button>
        <span>Page <?php echo (int) $page; ?> of <?php echo (int) $userPages; ?></span>
        <button class="icon-btn" type="button" aria-label="Next page" data-page-nav="next" <?php echo $page >= $userPages ? 'disabled' : ''; ?>>›</button>
        <button class="icon-btn" type="button" aria-label="Last page" data-page-nav="last" <?php echo $page >= $userPages ? 'disabled' : ''; ?>>»</button>
      </div>
      <label style="display:flex; align-items:center; gap:8px; color:var(--ink-soft); font-size:12px;">
        Rows per page
        <select id="rows-per-page" style="min-width:80px;">
          <option value="10" <?php echo $perPage === 10 ? 'selected' : ''; ?>>10</option>
          <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20</option>
          <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50</option>
        </select>
      </label>
    </div>
  </div>
<?php endif; ?>

<div id="accountFiltersModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="accountFiltersTitle">
  <div class="modal-box" style="max-width:520px;">
    <div class="feedback-modal-header" style="margin-bottom:20px;">
      <div><h3 id="accountFiltersTitle">Filters</h3><p>Refine the current account list</p></div>
      <button type="button" class="modal-close" data-close-filter-modal aria-label="Close filters">&times;</button>
    </div>
    <form method="GET" id="accountFilterForm">
      <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
      <div class="field">
        <label for="filter_status">Status</label>
        <select id="filter_status" name="status">
          <option value="">All statuses</option>
          <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
          <option value="disabled" <?php echo $statusFilter === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
        </select>
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="field">
          <label for="filter_date_from">Last login from</label>
          <input id="filter_date_from" name="date_from" type="date" value="<?php echo htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="field">
          <label for="filter_date_to">Last login to</label>
          <input id="filter_date_to" name="date_to" type="date" value="<?php echo htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" data-close-filter-modal>Cancel</button>
        <button type="submit" class="btn btn-primary">Apply filters</button>
      </div>
    </form>
  </div>
</div>

<div id="accountModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="accountModalTitle">
  <div class="modal-box" style="max-width:540px;">
    <div class="feedback-modal-header" style="margin-bottom:18px;">
      <div><h3 id="accountModalTitle">Add account</h3></div>
      <button type="button" class="modal-close" data-close-account-modal aria-label="Close account form">&times;</button>
    </div>
    <form id="accountForm">
      <input type="hidden" name="id" value="">
      <div class="field">
        <label for="account_role">Category</label>
        <select id="account_role" name="role" required>
          <option value="student">Student</option>
          <option value="staff">Staff</option>
        </select>
      </div>
      <div class="field">
        <label for="account_full_name">Full name</label>
        <input id="account_full_name" name="full_name" type="text" maxlength="100" required>
      </div>
      <div class="field">
        <label for="account_username">Username</label>
        <input id="account_username" name="username" type="text" maxlength="60" required>
      </div>
      <div class="field">
        <label for="account_email">Email</label>
        <input id="account_email" name="email" type="email" maxlength="100" required>
      </div>
      <div class="field">
        <label id="account_id_label" for="account_id">Student ID</label>
        <input id="account_id" name="account_id" type="text" maxlength="30" placeholder="Enter student ID" required>
      </div>
      <div id="accountFormErrors" class="alert alert-error" aria-live="polite" style="display:none;"></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" data-close-account-modal>Cancel</button>
        <button type="submit" class="btn btn-primary">Save account</button>
      </div>
    </form>
  </div>
</div>

<div id="tempPasswordModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="tempPasswordTitle">
  <div class="modal-box" style="max-width:460px;">
    <div class="feedback-modal-header" style="margin-bottom:18px;">
      <div><h3 id="tempPasswordTitle">Temporary password</h3></div>
      <button type="button" class="modal-close" data-close-temp-password aria-label="Close password dialog">&times;</button>
    </div>
    <p>The temporary password is shown once. Share it securely with the new user and ask them to change it immediately.</p>
    <div class="field">
      <label for="tempPasswordValue">Password</label>
      <input id="tempPasswordValue" type="text" readonly>
    </div>
    <div class="modal-actions">
      <button type="button" class="btn btn-outline" id="copyTempPassword">Copy</button>
      <button type="button" class="btn btn-primary" data-close-temp-password>Done</button>
    </div>
  </div>
</div>

<style>
  .badge-danger {
    background: #fbeceb;
    color: #b3261e;
  }
  .row-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    z-index: 30;
    min-width: 180px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px;
    box-shadow: 0 12px 24px rgba(16, 26, 43, 0.12);
    overflow: hidden;
  }
  .row-dropdown button {
    display: block;
    width: 100%;
    border: none;
    background: transparent;
    padding: 10px 14px;
    text-align: left;
    font: 13px/1.4 var(--sans);
    color: var(--ink);
    cursor: pointer;
  }
  .row-dropdown button:hover {
    background: #f5f7f4;
  }
  .row-dropdown button.danger {
    color: var(--danger);
  }
  .table-toolbar {
    background: #fff;
    border-top: 1px solid var(--line);
  }
</style>

<?php include 'includes/footer.php'; ?>

<script>
const tab = '<?php echo htmlspecialchars($tab, ENT_QUOTES, 'UTF-8'); ?>';
const filterModal = document.getElementById('accountFiltersModal');
const accountModal = document.getElementById('accountModal');
const tempModal = document.getElementById('tempPasswordModal');
const accountRoleInput = document.getElementById('account_role');
const accountForm = document.getElementById('accountForm');
const formErrors = document.getElementById('accountFormErrors');
let reloadAfterPassword = false;

function openModal(modal) {
  modal.classList.add('open');
}
function closeModal(modal) {
  modal.classList.remove('open');
  if (modal === tempModal && reloadAfterPassword) {
    reloadAfterPassword = false;
    window.location.reload();
  }
}

if (document.querySelector('[data-open-filters]')) {
  document.querySelector('[data-open-filters]').addEventListener('click', () => openModal(filterModal));
}
['data-close-filter-modal', 'data-close-account-modal', 'data-close-temp-password'].forEach((selector) => {
  document.querySelectorAll('[' + selector + ']').forEach((button) => {
    button.addEventListener('click', () => {
      const modal = button.closest('.modal-overlay');
      if (modal) closeModal(modal);
    });
  });
});

function setFormRole(role) {
  const roleName = role === 'student' ? 'student' : 'staff';
  accountRoleInput.value = roleName;
  document.getElementById('account_id_label').textContent = roleName === 'student' ? 'Student ID' : 'Staff ID';
  document.getElementById('account_id').placeholder = roleName === 'student' ? 'Enter student ID' : 'Enter staff ID';
}

document.querySelectorAll('[data-open-account-form]').forEach((button) => {
  button.addEventListener('click', () => {
    const role = ['student', 'staff'].includes(document.getElementById('account-category')?.value)
      ? document.getElementById('account-category').value
      : 'student';
    accountForm.reset();
    accountForm.querySelector('[name="id"]').value = '';
    setFormRole(role);
    document.getElementById('accountModalTitle').textContent = 'Add account';
    formErrors.style.display = 'none';
    openModal(accountModal);
  });
});

accountRoleInput.addEventListener('change', () => {
  document.getElementById('account_id').value = '';
  setFormRole(accountRoleInput.value);
});

document.getElementById('account-category')?.addEventListener('change', (event) => {
  const url = new URL(window.location.href);
  url.searchParams.set('tab', 'accounts');
  url.searchParams.set('category', event.target.value);
  url.searchParams.delete('page');
  window.location.href = url.toString();
});

const searchInput = document.getElementById('account-search');
if (searchInput) {
  searchInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      const val = searchInput.value.trim();
      const url = new URL(window.location.href);
      if (val) {
        url.searchParams.set('search', val);
      } else {
        url.searchParams.delete('search');
      }
      url.searchParams.set('tab', tab);
      url.searchParams.set('page', '1');
      window.location.href = url.toString();
    }
  });
}

accountForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  formErrors.style.display = 'none';
  const formData = new FormData(accountForm);
  const response = await fetch('account_save.php', {
    method: 'POST',
    body: formData,
  });
  const json = await response.json();
  if (!response.ok || !json.success) {
    const errText = json.errors ? Object.values(json.errors).join(' ') : (json.message || 'Please correct the highlighted fields.');
    formErrors.textContent = errText;
    formErrors.style.display = 'block';
    return;
  }
  closeModal(accountModal);
  if (json.password) {
    document.getElementById('tempPasswordValue').value = json.password;
    reloadAfterPassword = true;
    openModal(tempModal);
    return;
  }
  window.location.reload();
});

function handleAccountAction(action, id) {
  const formData = new FormData();
  formData.append('action', action);
  formData.append('id', String(id));
  fetch('account_action.php', { method: 'POST', body: formData })
    .then(async (response) => {
      const json = await response.json();
      if (!response.ok || !json.success) {
        alert(json.message || 'The action could not be completed.');
        return;
      }
      if (json.password) {
        document.getElementById('tempPasswordValue').value = json.password;
        reloadAfterPassword = true;
        openModal(tempModal);
        return;
      }
      window.location.reload();
    });
}

document.querySelectorAll('[data-account-action]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('.row-dropdown').forEach((menu) => menu.hidden = true);
    const action = button.dataset.accountAction;
    const id = Number(button.dataset.accountId);
    if (action === 'edit') {
      fetch('account_save.php?view=' + id, { method: 'GET' })
        .then(async (response) => {
          const json = await response.json();
          if (!json.success) {
            alert(json.message || 'Unable to load account.');
            return;
          }
          accountForm.querySelector('[name="id"]').value = json.user.id;
          document.getElementById('account_full_name').value = json.user.full_name || '';
          document.getElementById('account_username').value = json.user.username || '';
          document.getElementById('account_email').value = json.user.email || '';
          document.getElementById('account_id').value = json.user.role === 'student'
            ? (json.user.student_id || '')
            : (json.user.staff_id || '');
          setFormRole(json.user.role || 'student');
          document.getElementById('accountModalTitle').textContent = 'Edit account';
          formErrors.style.display = 'none';
          openModal(accountModal);
        });
      return;
    }
    if (action === 'delete' && !confirm('Delete this account? This action cannot be undone.')) {
      return;
    }
    if (action === 'toggle_status') {
      handleAccountAction(button.textContent.trim().toLowerCase() === 'disable' ? 'disable' : 'enable', id);
      return;
    }
    handleAccountAction(action, id);
  });
});

document.querySelectorAll('[data-menu-toggle]').forEach((button) => {
  button.addEventListener('click', (event) => {
    event.stopPropagation();
    const targetId = button.dataset.menuToggle;
    const panel = document.getElementById(targetId);
    document.querySelectorAll('.row-dropdown').forEach((menu) => {
      if (menu !== panel) menu.hidden = true;
    });
    if (!panel.hidden) {
      panel.hidden = true;
      return;
    }

    document.body.appendChild(panel);
    panel.hidden = false;
    const buttonRect = button.getBoundingClientRect();
    const menuRect = panel.getBoundingClientRect();
    const menuWidth = Math.max(menuRect.width, 180);
    const left = Math.max(8, Math.min(buttonRect.right - menuWidth, window.innerWidth - menuWidth - 8));
    const belowTop = buttonRect.bottom + 8;
    const top = belowTop + menuRect.height <= window.innerHeight - 8
      ? belowTop
      : Math.max(8, buttonRect.top - menuRect.height - 8);

    Object.assign(panel.style, {
      position: 'fixed',
      zIndex: '300',
      left: left + 'px',
      right: 'auto',
      top: top + 'px',
    });
  });
});

document.addEventListener('click', (event) => {
  if (!event.target.closest('.row-dropdown') && !event.target.closest('[data-menu-toggle]')) {
    document.querySelectorAll('.row-dropdown').forEach((menu) => menu.hidden = true);
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    document.querySelectorAll('.row-dropdown').forEach((menu) => menu.hidden = true);
    closeModal(filterModal);
    closeModal(accountModal);
    closeModal(tempModal);
  }
});

const rowsPerPage = document.getElementById('rows-per-page');
if (rowsPerPage) {
  rowsPerPage.addEventListener('change', () => {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', rowsPerPage.value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
  });
}

document.getElementById('copyTempPassword')?.addEventListener('click', async () => {
  const value = document.getElementById('tempPasswordValue').value;
  try {
    await navigator.clipboard.writeText(value);
    document.getElementById('copyTempPassword').textContent = 'Copied';
  } catch {
    document.getElementById('copyTempPassword').textContent = 'Copy failed';
  }
});

const pageNavButtons = document.querySelectorAll('[data-page-nav]');
pageNavButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const nav = button.dataset.pageNav;
    const url = new URL(window.location.href);
    const page = Number(url.searchParams.get('page') || '1');
    const totalPages = Number('<?php echo intval($tab === 'audit' ? $auditPages : $userPages); ?>');
    let nextPage = page;
    if (nav === 'first') nextPage = 1;
    if (nav === 'prev') nextPage = Math.max(1, page - 1);
    if (nav === 'next') nextPage = Math.min(totalPages, page + 1);
    if (nav === 'last') nextPage = totalPages;
    url.searchParams.set('page', String(nextPage));
    window.location.href = url.toString();
  });
});
</script>
