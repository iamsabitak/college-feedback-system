<?php
/**
 * Displays information about the system and campus feedback purpose.
 *
 * Application-owned logic for about.php.
 */
$pageTitle = 'About';
$active = 'about';
include 'includes/header.php';
?>

<section class="about-hero">
  <div class="container">
    <span class="eyebrow">About the system</span>
    <p class="statement">"We built this to replace forms that get lost with feedback that gets read —
    and to make honesty possible, not just permitted."</p>

    <div class="principle-grid">
      <div class="principle-card">
        <div class="principle-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 3 7l9 5 9-5-9-5Z"/><path d="M3 12l9 5 9-5"/><path d="M3 17l9 5 9-5"/></svg>
        </div>
        <h3>Genuine anonymity</h3>
        <p>When you toggle anonymous, your name is dropped before the form is even sent — it's never received, let alone stored.</p>
      </div>
      <div class="principle-card">
        <div class="principle-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>
        </div>
        <h3>Structured, not just written</h3>
        <p>Every comment carries a 1–5 star rating and a category, so the administration can spot trends, not just read opinions one at a time.</p>
      </div>
      <div class="principle-card">
        <div class="principle-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        </div>
        <h3>Built for every voice</h3>
        <p>One shared form adapts to students, teachers &amp; staff, and visitors alike — no separate systems to maintain.</p>
      </div>
    </div>

    <div class="callout">
      "Feedback that never gets lost, never gets buried, and is honest enough to actually be useful."
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="mission-row">
      <div>
        <h2>Why we built this</h2>
        <p style="color:var(--ink-soft); font-size:14.5px;">
          Paper feedback forms get lost, are slow to read, and offer no way to calculate averages or spot
          trends. Feedback-givers also have to be physically present to submit one — and there was never
          a comfortable, verifiable way to stay anonymous. This system fixes all four problems at once.
        </p>
        <ul class="mission-list">
          <li>Nothing gets physically lost or damaged</li>
          <li>Ratings are calculated automatically, not counted by hand</li>
          <li>Feedback can be given from any device, any time</li>
          <li>Anonymity is enforced by the database, not just a form checkbox</li>
        </ul>
      </div>
      <div>
        <div class="anon-banner" style="background:var(--moss); color:var(--white);">
          <div>
            <h3 style="color:var(--white);">Who reviews this feedback?</h3>
            <p style="color:#e4ede8;">A single, password-protected administrator account manages every
            submission — searching, filtering, and reviewing entries through a private dashboard that
            the public never sees.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section campus-section">
  <div class="container">
    <div class="section-head">
      <span class="section-tag">About the campus</span>
      <h2>Built for practical, student-focused learning</h2>
      <p>Established in 1994, Patan Nistha Campus is based in Balkumari, Gwarko, Lalitpur and focuses on connecting academic knowledge with practical learning.</p>
    </div>
    <div class="campus-grid">
      <article class="campus-card"><span>Programs</span><h3>BCA, BBS &amp; MBS</h3><p>Academic pathways in computer applications, business studies, and management studies.</p></article>
      <article class="campus-card"><span>Learning</span><h3>Technology &amp; practice</h3><p>Computer-based and technology-enabled learning support the move from concepts to real applications.</p></article>
      <article class="campus-card"><span>Resources</span><h3>Library &amp; guidance</h3><p>Learning resources and academic guidance support assignments, projects, independent study, and future plans.</p></article>
    </div>
    <p class="official-link">For admissions, notices, and the latest campus updates, visit the <a href="https://patannistha.edu.np/" target="_blank" rel="noopener">official Patan Nistha Campus website</a>.</p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
