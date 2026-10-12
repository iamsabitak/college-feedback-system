<?php
/**
 * Public portal home page and primary navigation entry point.
 *
 * Application-owned logic for index.php.
 */
$pageTitle = 'Home';
$active = 'home';
include 'includes/header.php';
?>

<section class="hero">
  <div class="container">
    <div class="hero-layout">
      <div class="hero-inner">
        <span class="eyebrow">Patan Nistha Campus &middot; Feedback Portal</span>
        <h1 data-hero-item>Tell us how we're really doing.</h1>
        <p class="lead" data-hero-item>One shared form for students, teachers &amp; staff, and visitors — with a genuine
        anonymous option, so honest feedback about people, classes, and facilities actually reaches the
        people who can act on it.</p>
        <div class="hero-actions" data-hero-item>
          <a href="feedback.php" class="btn btn-primary">Give Feedback</a>
          <a href="about.php" class="btn btn-outline">How it works</a>
        </div>
      </div>

      <div class="home-feedback-panel" aria-label="Animated feedback experience preview">
        <div class="home-stage" aria-hidden="true">
          <div class="home-ring home-ring-one">
            <span class="home-node home-node-student" style="--home-angle:0deg">
              <svg viewBox="0 0 24 24"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/></svg>
            </span>
            <span class="home-node home-node-staff" style="--home-angle:120deg">
              <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <span class="home-node home-node-visitor" style="--home-angle:240deg">
              <svg viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </span>
          </div>
          <div class="home-ring home-ring-two">
            <span class="home-node home-node-small" style="--home-angle:60deg"></span>
            <span class="home-node home-node-small" style="--home-angle:240deg"></span>
          </div>

          <span class="home-halo home-halo-one"></span>
          <span class="home-halo home-halo-two"></span>
          <svg class="home-crest" viewBox="0 0 120 140" role="img" aria-label="Patan Nistha Campus logo">
            <path d="M60 6 108 22v48c0 34-48 64-48 64S12 104 12 70V22Z" fill="#1b2a41" stroke="#e0942c" stroke-width="4"/>
            <path d="M60 16 98 28v40c0 27-38 52-38 52S22 95 22 68V28Z" fill="#faf8f4"/>
            <path d="M60 68 32 74v26l28-6Z" fill="#1b2a41"/>
            <path d="M60 68l28 6v26l-28-6Z" fill="#24395a"/>
            <polygon points="60,30 63.3,39.5 73.3,39.7 65.3,45.7 68.2,55.3 60,49.6 51.8,55.3 54.7,45.7 46.7,39.7 56.7,39.5" fill="#e0942c"/>
          </svg>

          <div class="home-feedback-bubble home-feedback-bubble-one">
            <span class="home-bubble-icon">
              <svg viewBox="0 0 24 24"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/></svg>
            </span>
            <div><strong>Library hours could be longer</strong><small>Student · ★★★★★</small></div>
          </div>
          <div class="home-feedback-bubble home-feedback-bubble-two">
            <span class="home-bubble-icon home-bubble-icon-gold">
              <svg viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            </span>
            <div><strong>Anonymous · Canteen</strong><small>typing<span class="home-typing-dots"><i></i><i></i><i></i></span></small></div>
          </div>

          <span class="home-spark home-spark-one">✦</span>
          <span class="home-spark home-spark-two">✦</span>
          <span class="home-spark home-spark-three">✦</span>
          <div class="home-rating-stars" aria-hidden="true">
            <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
          </div>
        </div>
      </div>
    </div>

    <div class="stat-strip">
      <div class="stat"><div class="stat-num" data-count="3">3</div><div class="stat-label">Feedback Groups</div></div>
      <div class="stat"><div class="stat-num">1&ndash;5</div><div class="stat-label">Star Rating</div></div>
      <div class="stat"><div class="stat-num" data-count="100" data-suffix="%">100%</div><div class="stat-label">Anonymous Option</div></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="section-tag">Who can submit</span>
      <h2>A single form, built for three kinds of voices</h2>
      <p>Choose your role and the form adapts — showing the categories that actually apply to you.</p>
    </div>

    <div class="role-grid">
      <div class="role-card">
        <div class="role-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/></svg>
        </div>
        <h3>Students</h3>
        <p>Share feedback about teachers, courses, the library, canteen, or laboratories.</p>
      </div>
      <div class="role-card">
        <div class="role-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <h3>Teachers &amp; Staff</h3>
        <p>Report on facilities, administration, or internal processes that affect your work.</p>
      </div>
      <div class="role-card">
        <div class="role-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <h3>Visitors &amp; Outsiders</h3>
        <p>Tell us about your overall impression, admissions experience, campus, or events.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0;">
  <div class="container">
    <div class="section-head">
      <span class="section-tag">How it works</span>
      <h2>Three steps, less than two minutes</h2>
    </div>
    <div class="steps">
      <div class="step">
        <div class="step-num">1</div>
        <h4>Choose your role</h4>
        <p>Tell us if you're a student, staff member, or visitor — the category list adjusts to match.</p>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <h4>Rate &amp; write</h4>
        <p>Give a 1&ndash;5 star rating and add a written comment with as much detail as you'd like.</p>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <h4>Submit — with or without your name</h4>
        <p>Flip the anonymous toggle any time, and your name is never stored at all.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="anon-banner">
      <div>
        <h3>Genuinely anonymous, not just hidden.</h3>
        <p>When you submit anonymously, the name field is removed before the form is even sent — the
        database never receives it. Not "hidden from view." Not stored at all.</p>
      </div>
      <a href="feedback.php" class="btn btn-marigold">Try it now</a>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
