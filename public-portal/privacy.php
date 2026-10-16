<?php
/**
 * Displays the privacy and data-handling policy.
 *
 * Application-owned logic for privacy.php.
 */
$pageTitle = 'Privacy';
$active = 'privacy';
include 'includes/header.php';
?>

<div class="reading-progress" aria-hidden="true"><span id="readingProgressBar"></span></div>
<main class="privacy-page">
  <section class="privacy-hero container"><span class="eyebrow">Privacy policy</span><h1>Your feedback, handled with care.</h1><p>This page explains, in plain language, what the College Online Feedback System records and how it handles anonymous submissions.</p><p class="privacy-effective">Applies to the public feedback portal for Patan Nistha Campus, Balkumari, Gwarko-7, Lalitpur.</p></section>
  <div class="privacy-content container">
    <aside class="privacy-aside"><strong>At a glance</strong><span>Anonymous means no name is read or stored.</span><span>Feedback is reviewed in one authenticated admin dashboard.</span></aside>
    <div class="privacy-policy">
      <section class="privacy-section" data-policy-section><div class="policy-heading"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><h2>What we collect</h2></div><p>The public form does not ask for or record your student/staff/visitor role. Each entry records its selected topic, 1–5 rating, written comment, and submission timestamp. A name is recorded only when you do not choose anonymous submission; it is required for named feedback.</p></section>
      <section class="privacy-section privacy-guarantee" data-policy-section><div class="policy-heading"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v5c0 5.1 3.4 8.5 8 10 4.6-1.5 8-4.9 8-10V6l-8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg><h2>Anonymous submissions</h2></div><p><strong>Your name is never stored when you submit anonymously.</strong> When the anonymous toggle is on, the name field is disabled before submission. On the server, the name is not read from the request at all, so it cannot be inserted into the database.</p></section>
      <section class="privacy-section" data-policy-section><div class="policy-heading"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg><h2>Who has access</h2></div><p>Feedback is not published on the public portal. It is available to a single authenticated administrator through a password-protected administration dashboard, where entries can be reviewed, searched, filtered, and deleted.</p></section>
      <section class="privacy-section" data-policy-section><div class="policy-heading"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M8 3v4m8-4v4M5 5h14v16H5z"/><path d="M8 11h8M8 15h5"/></svg><h2>Retention and deletion</h2></div><p>The current database design does not apply an automatic deletion schedule. Feedback is kept indefinitely unless an administrator deletes an entry from the administration dashboard.</p></section>
      <section class="privacy-section" data-policy-section><div class="policy-heading"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path d="M9 9h.01M15 9h.01M8.5 15c1-.8 2.2-1.2 3.5-1.2s2.5.4 3.5 1.2"/></svg><h2>What we do not collect</h2></div><p>This portal does not create public accounts, log IP addresses, or use cookies or tracking for analytics or advertising. PHP sessions are used only to support form validation, such as returning you to the form with an error message if a submission needs correction.</p></section>
      <section class="privacy-section" data-policy-section><div class="policy-heading"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="m5 6 7 6 7-6"/></svg><h2>Privacy concerns</h2></div><p>If you have a question or concern about privacy in this feedback portal, contact Patan Nistha Campus at <a href="mailto:info@patannistha.edu.np">info@patannistha.edu.np</a> or <a href="tel:+977015186497">+977-01-5186497</a>. For a direct response about an issue, please use these contact details instead of an anonymous feedback entry.</p></section>
    </div>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
