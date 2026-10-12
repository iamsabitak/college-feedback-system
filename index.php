<?php
/**
 * Landing page that links to the public and administrator portals.
 *
 * Application-owned logic for index.php.
 */
// Root landing page: keep this independent from the two portal layouts.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="College Online Feedback System portal selection page.">
  <title>College Online Feedback System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,500;6..72,600;6..72,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/svg+xml" href="assets/logo.svg">
  <style>
    :root { --navy:#1b2a41; --navy-deep:#101a2b; --moss:#4a6b57; --moss-light:#e8efe9; --marigold:#e0942c; --paper:#faf8f4; --ink-soft:#5b6169; --line:#e3ddd0; --white:#fff; }
    * { box-sizing:border-box; }
    body { min-height:100vh; margin:0; font-family:Inter,Arial,sans-serif; color:var(--navy); background:linear-gradient(135deg,#f8fafb 0%,#f0f4f2 50%,#ede8e2 100%); }
    .shell { width:min(1200px,calc(100% - 48px)); margin:auto; }
    header { display:flex; align-items:center; justify-content:space-between; padding:28px 0; animation:slideDown .6s ease-out; }
    @keyframes slideDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
    .brand { display:flex; align-items:center; gap:12px; font-family:Newsreader,Georgia,serif; font-size:19px; font-weight:700; }
    .brand-mark { display:grid; width:45px; height:45px; place-items:center; overflow:hidden; border-radius:12px; background:linear-gradient(135deg,var(--navy) 0%,var(--navy-deep) 100%); color:var(--white); font-size:15px; box-shadow:0 8px 20px rgba(27,42,65,.25); transition:all .3s ease; }
    .brand-mark:hover { transform:scale(1.08) rotate(5deg); box-shadow:0 12px 28px rgba(27,42,65,.35); }
    .brand-logo { display:block; width:100%; height:100%; object-fit:cover; }
    .help-link { color:var(--ink-soft); font-size:14px; font-weight:600; text-decoration:none; transition:all .3s ease; position:relative; display:flex; align-items:center; gap:6px; }
    .help-link i { transition:transform .3s ease; }
    .help-link::after { content:''; position:absolute; bottom:-3px; left:0; width:0; height:2px; background:var(--moss); transition:width .3s ease; }
    .help-link:hover { color:var(--navy); }
    .help-link:hover i { transform:translateX(3px); }
    .help-link:hover::after { width:100%; }
    main { display:grid; grid-template-columns:1.1fr .9fr; gap:80px; align-items:center; min-height:calc(100vh - 180px); padding:60px 0 80px; animation:fadeIn .8s ease-out .2s both; }
    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
    .eyebrow { display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:99px; background:rgba(74,107,87,.1); color:var(--moss); font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; border:1px solid rgba(74,107,87,.2); }
    .eyebrow i { font-size:12px; }
    h1 { max-width:680px; margin:24px 0 0; font-family:Newsreader,Georgia,serif; font-size:clamp(48px,6vw,76px); font-weight:700; line-height:1.1; letter-spacing:-.025em; color:var(--navy-deep); }
    .intro { max-width:600px; margin:20px 0 0; color:var(--ink-soft); font-size:16px; line-height:1.8; }
    .trust { display:flex; flex-wrap:wrap; gap:28px; margin-top:36px; }
    .trust-item { display:flex; align-items:center; gap:8px; color:var(--moss); font-size:13px; font-weight:600; }
    .trust-item i { font-size:16px; opacity:.8; }
    .choices { display:grid; gap:20px; margin-top:12px; }
    .portal-card { display:flex; align-items:stretch; position:relative; border:none; border-radius:18px; background:var(--white); color:inherit; text-decoration:none; box-shadow:0 12px 40px rgba(16,26,43,.1); transition:all .35s cubic-bezier(.25,.46,.45,.94); overflow:hidden; }
    .card-icon { display:flex; align-items:center; justify-content:center; width:100px; min-width:100px; background:linear-gradient(135deg,var(--moss) 0%,#3d5946 100%); color:var(--white); font-size:42px; transition:all .35s ease; }
    .portal-card.admin .card-icon { background:linear-gradient(135deg,var(--navy) 0%,var(--navy-deep) 100%); }
    .card-content { flex:1; padding:32px 36px; display:flex; flex-direction:column; justify-content:center; }
    .card-label { color:var(--moss); font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; display:block; margin-bottom:8px; opacity:.8; }
    .admin .card-label { color:var(--marigold); }
    .portal-card h2 { margin:0 0 8px; font-family:Newsreader,Georgia,serif; font-size:26px; line-height:1.2; font-weight:700; }
    .portal-card p { margin:0; color:var(--ink-soft); font-size:15px; line-height:1.65; }
    .admin p { color:#7a8799; }
    .card-arrow { display:none; }
    .card-link { display:inline-flex; align-items:center; gap:6px; margin-top:16px; color:var(--moss); font-size:14px; font-weight:600; text-decoration:none; position:relative; transition:all .3s ease; width:fit-content; }
    .card-link i { transition:transform .3s ease, color .3s ease; }
    .card-link::after { content:''; position:absolute; bottom:-3px; left:0; width:100%; height:2px; background:var(--moss); transform:scaleX(0); transform-origin:left; transition:transform .3s ease; }
    .card-link:hover::after { transform:scaleX(1); }
    .card-link:hover i { transform:translateX(3px); color:var(--moss); }
    .admin .card-link { color:var(--marigold); }
    .admin .card-link i { color:var(--marigold); }
    .admin .card-link::after { background:var(--marigold); }
    .admin .card-link:hover { color:var(--marigold); }
    .admin .card-link:hover i { transform:translateX(3px); color:var(--marigold); }
    .admin .card-link:hover::after { transform:scaleX(1); }
    .portal-card:hover { transform:translateY(-8px); box-shadow:0 28px 60px rgba(16,26,43,.18); }
    .portal-card:hover .card-icon { transform:scale(1.1); }
    footer { padding:0 0 28px; color:var(--ink-soft); font-size:12px; animation:slideUp .6s ease-out .4s both; }
    @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
    @media (max-width:760px) { 
      .shell { width:min(100% - 34px,560px); } 
      header { padding:18px 0; } 
      .help-link { display:none; } 
      main { grid-template-columns:1fr; gap:40px; min-height:auto; padding:55px 0; } 
      h1 { font-size:48px; margin-top:16px; } 
      .intro { font-size:15px; margin-top:14px; } 
      .choices { gap:16px; }
      .portal-card { flex-direction:column; }
      .card-icon { width:100%; min-width:100%; height:80px; font-size:36px; }
      .card-content { padding:24px 20px; }
      .card-arrow { display:none; }
    }
  </style>
</head>
<body>
  <div class="shell">
    <header>
      <div class="brand"><span class="brand-mark"><img class="brand-logo" src="assets/logo.svg" alt=""></span><span>College Feedback</span></div>
      <a class="help-link" href="public-portal/contact.php"><i class="fas fa-headset"></i>Need help? Contact us</a>
    </header>

    <main>
      <section aria-labelledby="welcome-title">
        <span class="eyebrow"><i class="fas fa-map-marker-alt"></i>Patan Nistha Campus</span>
        <h1 id="welcome-title">Every voice matters in shaping our campus.</h1>
        <p class="intro">Welcome to the College Online Feedback System for Patan Nistha Campus, an established institution in Balkumari, Gwarko, Lalitpur. Share your experience and help us improve continuously.</p>
        <div class="trust" aria-label="Feedback portal benefits">
          <div class="trust-item"><i class="fas fa-mask"></i>Anonymous option</div>
          <div class="trust-item"><i class="fas fa-feather"></i>Simple to use</div>
          <div class="trust-item"><i class="fas fa-graduation-cap"></i>BCA, BBS & MBS</div>
        </div>
      </section>

      <section class="choices" aria-label="Choose a portal">
        <div class="portal-card">
          <div class="card-icon"><i class="fas fa-comments"></i></div>
          <div class="card-content">
            <span class="card-label">For students, staff & visitors</span>
            <h2>Public Feedback Portal</h2>
            <p>Tell us about courses, facilities, services, and your overall experience.</p>
            <a href="public-portal/index.php" class="card-link">Explore Public Portal<i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
        <div class="portal-card admin">
          <div class="card-icon"><i class="fas fa-lock"></i></div>
          <div class="card-content">
            <span class="card-label">Authorized staff only</span>
            <h2>Administrator Dashboard</h2>
            <p>Sign in to review, filter, and manage submitted feedback securely.</p>
            <a href="admin-portal/login.php" class="card-link">Access Admin Portal<i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
      </section>
    </main>
  </div>
  <script>
    // Only the link text is clickable, not the entire card
  </script>
</body>
</html>
