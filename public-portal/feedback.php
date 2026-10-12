<?php
/**
 * Renders the public feedback form and restores validation errors.
 *
 * Application-owned logic for feedback.php.
 */
session_start();
// Re-populate values + show server-side errors if we bounced back from submit_feedback.php
$errors = $_SESSION['fb_errors'] ?? [];
$old = $_SESSION['fb_old'] ?? [];
unset($_SESSION['fb_errors'], $_SESSION['fb_old']);

$pageTitle = 'Give Feedback';
$active = 'feedback';
include 'includes/header.php';
?>

<section class="section feedback-page">
  <div class="container">
    <div class="section-head feedback-page-head">
      <span class="section-tag">Your voice matters</span>
      <h2>Share Your Experience</h2>
      <p>Tell us what worked well and what could be improved. Your response helps the campus make student life better.</p>
    </div>

    <div class="feedback-layout">
      <div class="feedback-form-column">
        <div class="form-card">
          <div class="feedback-form-heading">
            <span class="feedback-form-icon" aria-hidden="true">✦</span>
            <div>
              <h1>Share Your Feedback</h1>
              <p class="sub">Every field marked required is checked on both your browser and our server before saving.</p>
            </div>
          </div>

          <div class="form-progress" aria-label="Feedback form progress">
        <div class="progress-track" role="progressbar" aria-label="Completed form sections" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0">
          <span class="progress-fill"></span>
        </div>
        <ol class="progress-steps">
          <li data-progress="identity"><span class="progress-dot"></span><span>Who you are</span></li>
          <li data-progress="rating"><span class="progress-dot"></span><span>Your rating</span></li>
          <li data-progress="comment"><span class="progress-dot"></span><span>Your comment</span></li>
          <li data-progress="privacy"><span class="progress-dot"></span><span>Privacy</span></li>
        </ol>
        <span class="progress-summary" aria-live="polite">0 of 4 sections complete</span>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          Please fix the highlighted fields below and submit again.
        </div>
      <?php endif; ?>

      <form id="feedbackForm" action="submit_feedback.php" method="POST" novalidate>

        <section class="form-section" aria-labelledby="identityHeading">
          <div class="form-section-heading">
            <span class="section-index">01</span>
            <div><h2 id="identityHeading">Who you are</h2><p>Choose the perspective that best fits your feedback.</p></div>
          </div>
          <div class="field" data-field="submitter_type">
            <label>I am a... <span class="hint">(required)</span></label>
            <div class="role-select-group">
              <label class="role-pill <?php echo (($old['submitter_type'] ?? '') === 'Student') ? 'selected' : ''; ?>">
                <input type="radio" name="submitter_type" value="Student" <?php echo (($old['submitter_type'] ?? '') === 'Student') ? 'checked' : ''; ?>>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/></svg>
                <span>Student</span>
              </label>
              <label class="role-pill <?php echo (($old['submitter_type'] ?? '') === 'Teacher & Staff') ? 'selected' : ''; ?>">
                <input type="radio" name="submitter_type" value="Teacher & Staff" <?php echo (($old['submitter_type'] ?? '') === 'Teacher & Staff') ? 'checked' : ''; ?>>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Teacher / Staff</span>
              </label>
              <label class="role-pill <?php echo (($old['submitter_type'] ?? '') === 'Visitor & Outsider') ? 'selected' : ''; ?>">
                <input type="radio" name="submitter_type" value="Visitor & Outsider" <?php echo (($old['submitter_type'] ?? '') === 'Visitor & Outsider') ? 'checked' : ''; ?>>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <span>Visitor / Outsider</span>
              </label>
            </div>
            <div class="error-text"><?php echo htmlspecialchars($errors['submitter_type'] ?? ''); ?></div>
          </div>

          <div class="field" data-field="category">
            <label for="category">Category <span class="hint">(required — based on your role above)</span></label>
            <select id="category" name="category" disabled>
              <option value="" disabled selected>Select your role first</option>
            </select>
            <div class="error-text"><?php echo htmlspecialchars($errors['category'] ?? ''); ?></div>
          </div>
        </section>

        <section class="form-section" aria-labelledby="ratingHeading">
          <div class="form-section-heading">
            <span class="section-index">02</span>
            <div><h2 id="ratingHeading">Your rating</h2><p>How has your experience been?</p></div>
          </div>
          <div class="field" data-field="rating">
            <label>Rating <span class="hint">(1 to 5 stars, required)</span></label>
            <div class="rating-control">
              <div class="star-row">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <button type="button" class="star" data-value="<?php echo $i; ?>" aria-label="Rate <?php echo $i; ?> out of 5" aria-pressed="false">&#9733;</button>
                <?php endfor; ?>
              </div>
              <output class="rating-label" id="ratingLabel" aria-live="polite">Select a rating</output>
            </div>
            <input type="hidden" id="rating" name="rating" value="<?php echo htmlspecialchars($old['rating'] ?? ''); ?>">
            <div class="error-text"><?php echo htmlspecialchars($errors['rating'] ?? ''); ?></div>
          </div>
        </section>

        <section class="form-section" aria-labelledby="commentHeading">
          <div class="form-section-heading">
            <span class="section-index">03</span>
            <div><h2 id="commentHeading">Your comment</h2><p>Specific details help the campus understand what can improve.</p></div>
          </div>
          <div class="field" data-field="message">
            <label for="message">Your Comment <span class="hint">(required)</span></label>
            <div class="textarea-wrap">
              <textarea id="message" name="message" maxlength="2000" placeholder="Tell us what's working, and what isn't..."><?php echo htmlspecialchars($old['message'] ?? ''); ?></textarea>
              <span class="character-count" id="characterCount" aria-live="polite">0 / 2000</span>
            </div>
            <div class="error-text"><?php echo htmlspecialchars($errors['message'] ?? ''); ?></div>
          </div>
        </section>

        <section class="form-section feedback-privacy-section" aria-labelledby="privacyHeading">
          <div class="form-section-heading">
            <span class="section-index">04</span>
            <div class="privacy-heading">
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 14v3"/></svg>
              <div><h2 id="privacyHeading">Your privacy</h2><p>Choose whether to include your name with this response.</p></div>
            </div>
          </div>
          <div class="field">
            <div class="toggle-row">
              <div>
                <strong>Submit Anonymously</strong>
                <span>Your name will not be collected or stored at all.</span>
              </div>
              <label class="switch">
                <input type="checkbox" id="is_anonymous" name="is_anonymous" value="1" <?php echo !empty($old['is_anonymous']) ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>
          </div>

          <div class="field name-field" id="nameField" data-field="submitter_name">
            <label for="submitter_name">Your Name <span class="hint">(required unless anonymous)</span></label>
            <input type="text" id="submitter_name" name="submitter_name" placeholder="e.g. Sabita Khadka" value="<?php echo htmlspecialchars($old['submitter_name'] ?? ''); ?>" required>
            <div class="error-text"><?php echo htmlspecialchars($errors['submitter_name'] ?? ''); ?></div>
          </div>
        </section>

        <div class="form-actions">
          <a href="index.php" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-primary submit-button"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4Z"/></svg>Submit Feedback</button>
        </div>
      </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
