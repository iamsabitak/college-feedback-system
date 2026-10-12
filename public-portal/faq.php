<?php
/**
 * Displays frequently asked questions about feedback and the portal.
 *
 * Application-owned logic for faq.php.
 */
$pageTitle = 'Frequently Asked Questions';
$active = 'faq';
include 'includes/header.php';
?>

<main class="faq-page">
  <div class="faq-orb faq-orb-one" aria-hidden="true"></div><div class="faq-orb faq-orb-two" aria-hidden="true"></div><div class="faq-orb faq-orb-three" aria-hidden="true"></div>
  <section class="faq-hero container">
    <span class="eyebrow">Feedback help</span>
    <h1>Questions, answered plainly.</h1>
    <p>Everything you need to know before sharing feedback with Patan Nistha Campus.</p>
  </section>

  <div class="faq-content container">
    <section class="faq-group" data-faq-group>
      <div class="faq-group-head"><span class="faq-group-icon" aria-hidden="true">?</span><div><span class="section-tag">Your information</span><h2>Anonymity &amp; privacy</h2></div></div>
      <div class="faq-list">
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-a1"><span>Is my feedback really anonymous, or just hidden from view?</span><span class="faq-toggle" aria-hidden="true"></span></button><div class="faq-answer" id="faq-a1" hidden><div><p>When you choose anonymous submission, the name field is disabled in the form and the server does not read it from the request. No name reaches or is stored in the feedback record; it is not simply hidden in the dashboard.</p><div class="helpful"><span>Was this helpful?</span><button type="button" data-helpful="yes" aria-label="Yes, this was helpful">👍</button><button type="button" data-helpful="no" aria-label="No, this was not helpful">👎</button><span class="helpful-note" aria-live="polite"></span></div></div></div></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-a2"><span>Who can see my submitted feedback?</span><span class="faq-toggle" aria-hidden="true"></span></button><div class="faq-answer" id="faq-a2" hidden><div><p>Submitted feedback is available through the private, password-protected administration dashboard. The public cannot browse submissions, and the system is designed around a single authenticated administrator account.</p><div class="helpful"><span>Was this helpful?</span><button type="button" data-helpful="yes" aria-label="Yes, this was helpful">👍</button><button type="button" data-helpful="no" aria-label="No, this was not helpful">👎</button><span class="helpful-note" aria-live="polite"></span></div></div></div></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-a3"><span>Do I need an account or login to submit feedback?</span><span class="faq-toggle" aria-hidden="true"></span></button><div class="faq-answer" id="faq-a3" hidden><div><p>No. Students, teachers and staff, and visitors use the same public form. The portal does not create public accounts or keep account-based feedback histories.</p><div class="helpful"><span>Was this helpful?</span><button type="button" data-helpful="yes" aria-label="Yes, this was helpful">👍</button><button type="button" data-helpful="no" aria-label="No, this was not helpful">👎</button><span class="helpful-note" aria-live="polite"></span></div></div></div></article>
      </div>
    </section>

    <section class="faq-group" data-faq-group>
      <div class="faq-group-head"><span class="faq-group-icon" aria-hidden="true">✦</span><div><span class="section-tag">Using the form</span><h2>Submitting feedback</h2></div></div>
      <div class="faq-list">
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-a4"><span>Can I edit or delete feedback after submitting?</span><span class="faq-toggle" aria-hidden="true"></span></button><div class="faq-answer" id="faq-a4" hidden><div><p>No. Once the form is submitted, this public portal has no edit or delete feature. Please review your rating and comment before sending them.</p><div class="helpful"><span>Was this helpful?</span><button type="button" data-helpful="yes" aria-label="Yes, this was helpful">👍</button><button type="button" data-helpful="no" aria-label="No, this was not helpful">👎</button><span class="helpful-note" aria-live="polite"></span></div></div></div></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-a5"><span>Can I submit feedback about a specific teacher by name?</span><span class="faq-toggle" aria-hidden="true"></span></button><div class="faq-answer" id="faq-a5" hidden><div><p>Yes. Students can choose the Teachers category and describe their experience in the comment. Please keep feedback specific, respectful, and focused on the learning experience.</p><div class="helpful"><span>Was this helpful?</span><button type="button" data-helpful="yes" aria-label="Yes, this was helpful">👍</button><button type="button" data-helpful="no" aria-label="No, this was not helpful">👎</button><span class="helpful-note" aria-live="polite"></span></div></div></div></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-a6"><span>Can I submit more than once?</span><span class="faq-toggle" aria-hidden="true"></span></button><div class="faq-answer" id="faq-a6" hidden><div><p>The form does not require an account or impose a public submission history, so you can send feedback again when you have a separate experience to report. Repeated entries about the same issue are best combined into one clear comment.</p><div class="helpful"><span>Was this helpful?</span><button type="button" data-helpful="yes" aria-label="Yes, this was helpful">👍</button><button type="button" data-helpful="no" aria-label="No, this was not helpful">👎</button><span class="helpful-note" aria-live="polite"></span></div></div></div></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-a7"><span>What categories of feedback can I submit?</span><span class="faq-toggle" aria-hidden="true"></span></button><div class="faq-answer" id="faq-a7" hidden><div><p>Categories change with your role. Students can comment on teachers, courses, library, canteen, laboratories, or other topics; teachers and staff can choose facilities, administration, internal processes, or other; visitors can choose overall impression, admissions, campus, events, or other.</p><div class="helpful"><span>Was this helpful?</span><button type="button" data-helpful="yes" aria-label="Yes, this was helpful">👍</button><button type="button" data-helpful="no" aria-label="No, this was not helpful">👎</button><span class="helpful-note" aria-live="polite"></span></div></div></div></article>
      </div>
    </section>

    <section class="faq-group" data-faq-group>
      <div class="faq-group-head"><span class="faq-group-icon" aria-hidden="true">→</span><div><span class="section-tag">After you send</span><h2>What happens next</h2></div></div>
      <div class="faq-list">
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-a8"><span>How long does it take for the administration to act on feedback?</span><span class="faq-toggle" aria-hidden="true"></span></button><div class="faq-answer" id="faq-a8" hidden><div><p>The system records feedback for administrative review, but it does not set an automatic response or action deadline. Timing depends on the issue and the college's review process.</p><div class="helpful"><span>Was this helpful?</span><button type="button" data-helpful="yes" aria-label="Yes, this was helpful">👍</button><button type="button" data-helpful="no" aria-label="No, this was not helpful">👎</button><span class="helpful-note" aria-live="polite"></span></div></div></div></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-a9"><span>What happens to feedback I submit — is it read by everyone or just admins?</span><span class="faq-toggle" aria-hidden="true"></span></button><div class="faq-answer" id="faq-a9" hidden><div><p>It is recorded in the system and reviewed in the private administration dashboard, not published for everyone to read. Ratings and categories help the administrator identify patterns alongside individual comments.</p><div class="helpful"><span>Was this helpful?</span><button type="button" data-helpful="yes" aria-label="Yes, this was helpful">👍</button><button type="button" data-helpful="no" aria-label="No, this was not helpful">👎</button><span class="helpful-note" aria-live="polite"></span></div></div></div></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-a10"><span>Is there a way to follow up on feedback I've given?</span><span class="faq-toggle" aria-hidden="true"></span></button><div class="faq-answer" id="faq-a10" hidden><div><p>The portal has no ticket number, email notification, or account-based follow-up feature. For a matter that needs a direct reply, contact the college at <a href="mailto:info@patannistha.edu.np">info@patannistha.edu.np</a> or +977-01-5186497 rather than relying on an anonymous submission.</p><div class="helpful"><span>Was this helpful?</span><button type="button" data-helpful="yes" aria-label="Yes, this was helpful">👍</button><button type="button" data-helpful="no" aria-label="No, this was not helpful">👎</button><span class="helpful-note" aria-live="polite"></span></div></div></div></article>
      </div>
    </section>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
