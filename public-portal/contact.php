<?php
/**
 * Displays the contact form and campus contact information.
 *
 * Application-owned logic for contact.php.
 */
session_start();
$contactErrors = $_SESSION['contact_errors'] ?? [];
$contactOld = $_SESSION['contact_old'] ?? [];
$contactSuccess = $_SESSION['contact_success'] ?? '';
unset($_SESSION['contact_errors'], $_SESSION['contact_old'], $_SESSION['contact_success']);
if (empty($_SESSION['contact_csrf_token'])) {
  $_SESSION['contact_csrf_token'] = bin2hex(random_bytes(32));
}
$contactCsrfToken = $_SESSION['contact_csrf_token'];
$contactFieldError = static fn(string $key): string => htmlspecialchars((string) ($contactErrors[$key] ?? ''), ENT_QUOTES, 'UTF-8');
$contactFieldValue = static fn(string $key): string => htmlspecialchars((string) ($contactOld[$key] ?? ''), ENT_QUOTES, 'UTF-8');
$pageTitle = 'Contact';
$active = 'contact';
include 'includes/header.php';
?>

<section class="section contact-page">
  <div class="container">
    <div class="section-head">
      <span class="section-tag">Get in touch</span>
      <h2>Reach Patan Nistha Campus</h2>
      <p>For questions about the feedback system or the campus, contact the college through its official details.</p>
    </div>

    <div class="contact-layout">
      <div class="contact-info-column">
        <div class="campus-location">
          <div class="map-frame">
            <iframe title="Map pin for National Institute of Science (Patan Nistha Campus), Gwarko Ringroad, Lalitpur" src="https://www.google.com/maps?q=National+Institute+of+Science+NIS,+Gwarko+Ringroad,+Lalitpur,+Nepal&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
          </div>
          <div class="visiting-note">
            <span class="section-tag">Getting here</span>
            <p>The map is pinned to National Institute of Science (Patan Nistha Campus) on Gwarko Ringroad, Balkumari, Lalitpur. <a href="https://www.google.com/maps/search/?api=1&amp;query=National+Institute+of+Science+NIS,+Gwarko+Ringroad,+Lalitpur,+Nepal" target="_blank" rel="noopener">Open the exact pin in Google Maps</a>.</p>
          </div>
        </div>

        <div class="hours-card">
          <div class="hours-content">
            <h3>Office Hours</h3>
            <div class="hours-row"><span>Sunday &ndash; Friday</span><span>6:30 AM &ndash; 5:00 PM</span></div>
            <div class="hours-row"><span>Saturday</span><span>Closed</span></div>
          </div>
          <div class="hours-card-actions">
            <div class="contact-social" aria-label="Patan Nistha Campus social media">
              <a href="https://www.facebook.com/lalitpurnist" target="_blank" rel="noopener" aria-label="Patan Nistha Campus on Facebook"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M14 8h3V4h-3c-3.1 0-5 1.9-5 5v3H6v4h3v6h4v-6h3.1l.9-4H13V9c0-.7.3-1 1-1Z" fill="currentColor"/></svg></a>
              <a href="https://www.instagram.com/nistnss/" target="_blank" rel="noopener" aria-label="Patan Nistha Campus on Instagram"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.4" cy="6.6" r=".8" fill="currentColor" stroke="none"/></svg></a>
              <a href="https://www.youtube.com/@nationalcollege5800/featured" target="_blank" rel="noopener" aria-label="Patan Nistha Campus on YouTube"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M21.6 7.2a2.8 2.8 0 0 0-2-2C17.8 4.7 12 4.7 12 4.7s-5.8 0-7.6.5a2.8 2.8 0 0 0-2 2C2 9 2 12 2 12s0 3 .4 4.8a2.8 2.8 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.8 2.8 0 0 0 2-2C22 15 22 12 22 12s0-3-.4-4.8ZM10 15.5v-7l6 3.5-6 3.5Z" fill="currentColor"/></svg></a>
            </div>
            <a href="feedback.php" class="btn btn-marigold contact-feedback-cta"><i class="fas fa-pen-to-square"></i>Leave Feedback</a>
          </div>
        </div>

      </div>

      <section class="contact-form-card" id="contact-form" aria-labelledby="contactFormTitle">
        <div class="contact-form-heading">
          <h3 id="contactFormTitle">Send us a message</h3>
          <p>Use this form for questions about the campus or feedback system.</p>
        </div>
        <?php if (!empty($contactSuccess)): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($contactSuccess, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if (!empty($contactErrors['_form'])): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($contactErrors['_form'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form id="contactForm" action="submit_contact.php" method="POST" novalidate data-contact-form>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($contactCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
          <div class="field<?php echo isset($contactErrors['name']) ? ' has-error' : ''; ?>" data-contact-field="name">
            <label for="contactName">Name <span class="hint">(required)</span></label>
            <input type="text" id="contactName" name="name" autocomplete="name" maxlength="100" value="<?php echo $contactFieldValue('name'); ?>" required>
            <div class="error-text" aria-live="polite"><?php echo $contactFieldError('name'); ?></div>
          </div>
          <div class="field<?php echo isset($contactErrors['email']) ? ' has-error' : ''; ?>" data-contact-field="email">
            <label for="contactEmail">Email <span class="hint">(required)</span></label>
            <input type="email" id="contactEmail" name="email" autocomplete="email" maxlength="254" value="<?php echo $contactFieldValue('email'); ?>" required>
            <div class="error-text" aria-live="polite"><?php echo $contactFieldError('email'); ?></div>
          </div>
          <div class="field<?php echo isset($contactErrors['subject']) ? ' has-error' : ''; ?>" data-contact-field="subject">
            <label for="contactSubject">Subject <span class="hint">(required)</span></label>
            <input type="text" id="contactSubject" name="subject" maxlength="150" value="<?php echo $contactFieldValue('subject'); ?>" required>
            <div class="error-text" aria-live="polite"><?php echo $contactFieldError('subject'); ?></div>
          </div>
          <div class="field<?php echo isset($contactErrors['message']) ? ' has-error' : ''; ?>" data-contact-field="message">
            <label for="contactMessage">Message <span class="hint">(required)</span></label>
            <textarea id="contactMessage" name="message" rows="6" maxlength="5000" required><?php echo $contactFieldValue('message'); ?></textarea>
            <div class="error-text" aria-live="polite"><?php echo $contactFieldError('message'); ?></div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Send Message</button>
          </div>
        </form>
      </section>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
