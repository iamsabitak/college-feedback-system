<?php
/**
 * Redirects to the profile update route.
 *
 * Application-owned logic for profile.php.
 */
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/otp.php';

$stmt = $pdo->prepare('SELECT username, email, phone, updated_at FROM admin WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['admin_id']]);
$admin = $stmt->fetch();
if (!$admin) {
    header('Location: logout.php');
    exit;
}
$csrfToken = otp_csrf_token();
$pageTitle = 'Profile Settings';
$active = 'profile';
include 'includes/header.php';
?>
<div class="page-head">
  <div><h1>Profile Settings</h1></div>
</div>
<div class="profile-settings" data-profile-tabs>
  <div class="profile-tabs" role="tablist" aria-label="Profile settings">
    <button type="button" role="tab" id="profileDetailsTab" aria-controls="profileDetailsPanel" aria-selected="true" tabindex="0" class="profile-tab active" data-profile-tab="details">Profile Details</button>
    <button type="button" role="tab" id="profileSecurityTab" aria-controls="profileSecurityPanel" aria-selected="false" tabindex="-1" class="profile-tab" data-profile-tab="security">Security</button>
  </div>

  <section class="profile-tab-panel active" role="tabpanel" id="profileDetailsPanel" aria-labelledby="profileDetailsTab" data-profile-panel="details">
    <div class="profile-section-heading"><div><h2>Profile Information</h2><p>Update your name and contact details.</p></div><div class="profile-updated">Last updated <time data-profile-updated><?php echo htmlspecialchars($admin['updated_at'] ?: 'Not recorded', ENT_QUOTES, 'UTF-8'); ?></time></div></div>
    <section class="profile-card detail-card" aria-label="Profile information">
      <div class="profile-fields" data-profile data-csrf-token="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ([
            'username' => ['Username', 'text'],
            'email' => ['Email address', 'email'],
            'phone' => ['Phone number', 'tel'],
        ] as $field => [$label, $type]): ?>
          <section class="profile-row" data-profile-row="<?php echo $field; ?>">
            <div class="profile-field-label"><?php echo $label; ?></div>
            <div class="profile-display" data-profile-display>
              <span class="profile-value" data-profile-value><?php if (!empty($admin[$field])): ?><?php echo htmlspecialchars($admin[$field], ENT_QUOTES, 'UTF-8'); ?><?php else: ?><span class="profile-empty">Not set</span><?php endif; ?></span>
              <button type="button" class="icon-btn profile-edit" data-profile-edit aria-label="Edit <?php echo strtolower($label); ?>" title="Edit <?php echo strtolower($label); ?>"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 4 4 4L9 19l-5 1 1-5Z"/><path d="m14 6 4 4"/></svg></button>
            </div>
            <div class="profile-editor" data-profile-editor hidden>
              <div class="profile-edit-controls"><input type="<?php echo $type; ?>" value="<?php echo htmlspecialchars($admin[$field] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-profile-input autocomplete="<?php echo $field === 'username' ? 'username' : ($field === 'email' ? 'email' : 'tel'); ?>"><div class="profile-edit-actions"><button type="button" class="btn btn-primary btn-sm" data-profile-save>Save</button><button type="button" class="btn btn-outline btn-sm" data-profile-cancel>Cancel</button></div></div>
              <div class="profile-inline-error" data-profile-error role="alert"></div>
            </div>
            <?php if ($field === 'email'): ?>
              <div class="profile-email-verification" data-profile-email-verification hidden>
                <p>A verification code will be sent to the new email before it is saved.</p>
                <div class="profile-edit-controls"><input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code" data-profile-email-code><div class="profile-edit-actions"><button type="button" class="btn btn-primary btn-sm" data-profile-email-verify>Verify email</button><button type="button" class="btn btn-outline btn-sm" data-profile-email-cancel>Cancel</button></div></div>
                <div class="profile-edit-actions profile-resend"><button type="button" class="btn btn-outline btn-sm" data-profile-email-resend disabled>Resend code</button><span data-profile-resend-countdown></span></div>
                <div class="profile-inline-error" data-profile-email-error role="alert"></div>
              </div>
            <?php endif; ?>
          </section>
        <?php endforeach; ?>
      </div>
    </section>
  </section>

  <section class="profile-tab-panel" role="tabpanel" id="profileSecurityPanel" aria-labelledby="profileSecurityTab" data-profile-panel="security" hidden>
    <details class="security-disclosure" open>
      <summary><div><strong>Reset Password</strong><small>Enter your current password and choose a new secure password.</small></div><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 15-6-6-6 6"/></svg></summary>
      <div class="security-panel-body">
        <div class="security-info-banner">At least 8 characters are required. Uppercase, lowercase, and a number are optional recommendations.</div>
        <div class="security-password-flow" data-security-password-flow data-loaded="false"><p class="security-loading">Loading secure password controls…</p></div>
      </div>
    </details>
  </section>
</div>
<?php include 'includes/footer.php'; ?>
