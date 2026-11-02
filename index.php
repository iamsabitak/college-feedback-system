<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>College Online Feedback System – Patan Nistha Campus</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:wght@600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="assets/logo.svg">
<style>
:root{--navy:#0f1a35;--navy2:#16254a;--green:#3f6a52;--green2:#8fc7a6;--gold:#f2b441;--bg:#f4f2ed;--card:#fff;--ink:#14213d;--muted:#5b6478;--line:#e4e0d6;--chip:#e3efe8;
box-sizing:border-box;padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px);color-scheme:light}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){--bg:#0b1224;--card:#15203b;--ink:#eef0f6;--muted:#a3adc4;--line:#27335a;--chip:#1f3a31;color-scheme:dark}}
:root[data-theme="dark"]{--bg:#0b1224;--card:#15203b;--ink:#eef0f6;--muted:#a3adc4;--line:#27335a;--chip:#1f3a31;color-scheme:dark}
html{scroll-behavior:smooth;scroll-padding-top:90px}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.6 Inter,system-ui,sans-serif}
h1,h2,h3{font-family:"Source Serif 4",Georgia,serif;margin:0;letter-spacing:-.015em}
a{color:inherit;text-decoration:none}
.wrap{max-width:1180px;margin:0 auto;padding:0 clamp(18px,4vw,32px)}
/* nav */
.nav{position:sticky;top:0;z-index:20;background:rgba(15,26,53,.92);backdrop-filter:blur(10px);border-bottom:1px solid rgba(255,255,255,.08);color:#fff}
.nav .wrap{display:flex;align-items:center;justify-content:space-between;height:72px;gap:16px}
.brand{display:flex;align-items:center;gap:14px}
.brand img{flex-shrink:0;object-fit:contain}
.brand i{width:1px;height:34px;background:rgba(255,255,255,.18)}
.brand small{display:block;font-size:11px;letter-spacing:.14em;color:var(--green2);font-weight:700}
.brand b{font:700 17px "Source Serif 4",Georgia,serif}
.links{display:flex;align-items:center;gap:26px;font-weight:600;font-size:14px}
.links a:not(.btn):hover{color:var(--gold)}
.btn{display:inline-flex;align-items:center;gap:10px;padding:12px 22px;border-radius:12px;font-weight:700;font-size:15px;transition:.15s;border:1.5px solid transparent}
.btn.light{background:#f4f2ed;color:#14213d}.btn.light:hover{background:#fff;transform:translateY(-1px)}
.btn.ghost{border-color:rgba(255,255,255,.28);color:#fff}.btn.ghost:hover{border-color:var(--gold);color:var(--gold)}
.btn.lg{padding:17px 28px;font-size:16px;border-radius:14px}
/* hero */
.hero{background:radial-gradient(60% 75% at 78% 42%,rgba(63,106,82,.42),transparent 70%),radial-gradient(40% 50% at 10% 100%,rgba(242,180,65,.10),transparent 70%),linear-gradient(180deg,#0c1630,#111f40);color:#fff;position:relative;overflow:hidden}
.hero .wrap{display:grid;grid-template-columns:1.05fr .95fr;gap:30px;align-items:center;padding-top:64px;padding-bottom:86px}
.pill{display:inline-flex;align-items:center;gap:9px;border:1px solid rgba(255,255,255,.2);border-radius:99px;padding:8px 16px;font-size:12px;font-weight:700;letter-spacing:.1em;background:rgba(255,255,255,.05)}
.pill:before{content:"";width:8px;height:8px;border-radius:50%;background:var(--green2);box-shadow:0 0 10px var(--green2)}
h1{font-size:clamp(40px,6vw,72px);line-height:1.04;margin:22px 0 20px;font-weight:700}
h1 em{font-style:normal;background:linear-gradient(90deg,var(--gold),var(--green2));-webkit-background-clip:text;background-clip:text;color:transparent}
.sub{font-size:18px;color:#b8c2da;max-width:560px;margin:0 0 30px}
.cta{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:28px}
.tags{display:flex;gap:10px;flex-wrap:wrap}
.tags span{border:1px solid rgba(255,255,255,.22);border-radius:99px;padding:8px 16px;font-size:13.5px;font-weight:600}
.art{width:100%;height:auto;filter:drop-shadow(0 30px 50px rgba(0,0,0,.35))}
/* stats */
.stats{background:linear-gradient(180deg,#111f40,var(--navy));color:#fff;border-top:1px solid rgba(255,255,255,.06)}
.stats .wrap{display:grid;grid-template-columns:repeat(3,1fr);gap:0;padding-top:34px;padding-bottom:34px}
.stat{padding:0 28px;border-left:1px solid rgba(255,255,255,.12)}.stat:first-child{border:0;padding-left:0}
.stat b{display:block;font:700 40px/1.1 Inter,sans-serif}
.stat span{color:#9fb0cf;font-weight:500;font-size:14px}
/* sections */
section.s{padding:84px 0 20px}
.head{display:flex;justify-content:space-between;align-items:flex-end;gap:40px;margin-bottom:34px;flex-wrap:wrap}
.eyebrow{font-size:12px;letter-spacing:.16em;font-weight:700;color:var(--green)}
.head h2{font-size:clamp(30px,4vw,44px);margin-top:6px}
.head p{max-width:480px;color:var(--muted);margin:0}
.portals{display:grid;grid-template-columns:1fr 1fr;gap:22px}
.pc{background:var(--card);border:1px solid var(--line);border-radius:22px;padding:30px;box-shadow:0 10px 30px rgba(20,33,61,.06);transition:.2s;display:flex;flex-direction:column}
.pc:hover{transform:translateY(-4px);border-color:var(--green);box-shadow:0 18px 40px rgba(20,33,61,.12)}
.ic{width:60px;height:60px;border-radius:16px;background:var(--green);display:grid;place-items:center;margin-bottom:22px}
.pc .eyebrow{font-size:11px}.pc h3{font-size:28px;margin:4px 0 10px}
.pc p{color:var(--muted);margin:0 0 20px}
.pc ul{list-style:none;padding:0;margin:0 0 24px;display:grid;gap:8px;font-size:14.5px;font-weight:500}
.pc li:before{content:"✓";color:var(--green);font-weight:800;margin-right:10px}
.go{margin-top:auto;font-weight:700;color:var(--green);display:inline-flex;gap:8px;align-items:center}
.pc:hover .go{gap:14px}
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.step{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:28px}
.num{width:48px;height:48px;border-radius:12px;background:var(--navy);color:#fff;font-weight:700;display:grid;place-items:center;margin-bottom:34px}
.step h3{font:700 20px Inter,sans-serif;margin-bottom:8px}.step p{margin:0;color:var(--muted);font-size:14.5px}
footer{margin-top:84px;background:var(--navy);color:#9fb0cf;font-size:14px}
footer .wrap{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;padding-top:26px;padding-bottom:26px}
@media(max-width:900px){.hero .wrap{grid-template-columns:1fr;padding-top:44px}.art{max-width:460px;margin:0 auto}.links a:not(.btn){display:none}.portals,.steps{grid-template-columns:1fr}.stats .wrap{grid-template-columns:1fr;gap:18px}.stat{border:0;padding:0}}
@media(max-width:520px){.brand i,.brand small{display:none}.btn.lg{width:100%;justify-content:center}}
</style>
</head>
<body>
<header class="nav"><div class="wrap">
  <a class="brand" href="#top">
    <img src="assets/logo.svg" width="40" height="40" alt="Patan Nistha Campus logo"/>
    <i></i><div><small>PATAN NISTHA CAMPUS</small><b>College Online Feedback</b></div>
  </a>
  <nav class="links"><a href="#top">Home</a><a href="#portals">Portals</a><a href="#how">How it works</a><a class="btn light" href="#portals">Access Portal</a></nav>
</div></header>

<main id="top">
<section class="hero"><div class="wrap">
  <div>
    <span class="pill">PATAN NISTHA CAMPUS</span>
    <h1>Every voice matters in <em>shaping our campus.</em></h1>
    <p class="sub">Welcome to the College Online Feedback System for Patan Nistha Campus, an established institution in Balkumari, Gwarko, Lalitpur. Share your experience and help us improve continuously.</p>
    <div class="cta"><a class="btn light lg" href="student-portal/login.php">Student Portal <span>→</span></a><a class="btn ghost lg" href="staff-portal/login.php">Staff Portal</a></div>
    <div class="tags"><span>Anonymous option</span><span>Simple to use</span><span>BCA, BBS &amp; MBS</span></div>
  </div>
  <svg class="art" viewBox="0 0 520 460" role="img" aria-label="Illustration of feedback cards, ratings and a graduation cap">
    <defs>
      <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8fc7a6"/><stop offset="1" stop-color="#3f6a52"/></linearGradient>
      <linearGradient id="g2" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f7cb6b"/><stop offset="1" stop-color="#e09a1e"/></linearGradient>
      <symbol id="st" viewBox="0 0 24 24"><path d="M12 2l3 6.9 7.5.7-5.7 5 1.7 7.4L12 18l-6.5 4 1.7-7.4-5.7-5 7.5-.7z"/></symbol>
    </defs>
    <circle cx="260" cy="230" r="215" fill="none" stroke="rgba(255,255,255,.07)"/>
    <circle cx="260" cy="230" r="165" fill="none" stroke="rgba(255,255,255,.07)"/>
    <g transform="rotate(-8 260 230)">
      <rect x="50" y="70" width="370" height="290" rx="38" fill="rgba(255,255,255,.07)" stroke="rgba(255,255,255,.14)"/>
      <rect x="92" y="104" width="250" height="140" rx="24" fill="#0b1530" stroke="rgba(255,255,255,.1)"/>
      <rect x="116" y="124" width="110" height="10" rx="5" fill="#8fc7a6" opacity=".7"/>
      <rect x="116" y="158" width="62" height="62" rx="16" fill="url(#g1)"/>
      <rect x="190" y="158" width="62" height="62" rx="16" fill="url(#g2)"/>
      <rect x="264" y="158" width="62" height="62" rx="16" fill="#cfe5d8"/>
      <path d="M135 180h24v18h-10l-6 6v-6h-8z" fill="#fff" opacity=".9"/>
      <rect x="40" y="272" width="290" height="92" rx="24" fill="url(#g1)"/>
      <rect x="54" y="284" width="262" height="68" rx="16" fill="none" stroke="rgba(255,255,255,.35)"/>
      <g fill="#fff"><use href="#st" x="76" y="302" width="30" height="30"/><use href="#st" x="118" y="302" width="30" height="30"/><use href="#st" x="160" y="302" width="30" height="30"/><use href="#st" x="202" y="302" width="30" height="30"/></g>
      <use href="#st" x="244" y="302" width="30" height="30" fill="#f2b441"/>
    </g>
    <g transform="rotate(9 400 330)">
      <rect x="335" y="235" width="140" height="190" rx="22" fill="#fff"/>
      <rect x="356" y="262" width="98" height="12" rx="6" fill="#d9deea"/><rect x="356" y="286" width="98" height="10" rx="5" fill="#e5e9f1"/><rect x="356" y="306" width="70" height="10" rx="5" fill="#e5e9f1"/>
      <rect x="352" y="340" width="106" height="62" rx="14" fill="#d5ecdf"/>
    </g>
    <circle cx="415" cy="130" r="58" fill="rgba(63,106,82,.4)" stroke="rgba(143,199,166,.5)"/>
    <path d="M415 98l38 15-38 15-38-15z" fill="#f2b441"/><path d="M392 128v16c14 9 32 9 46 0v-16l-23 9z" fill="#f7e2ad"/><path d="M448 118v26" stroke="#f2b441" stroke-width="4" stroke-linecap="round"/>
  </svg>
</div></section>

<section class="stats"><div class="wrap">
  <div class="stat"><b>3</b><span>Programs: BCA, BBS &amp; MBS</span></div>
  <div class="stat"><b>2</b><span>Portals for students &amp; staff</span></div>
  <div class="stat"><b>1</b><span>Place to share and track feedback</span></div>
</div></section>

<section class="s" id="portals"><div class="wrap">
  <div class="head"><div><div class="eyebrow">CHOOSE YOUR PORTAL</div><h2>Where would you like to start?</h2></div>
    <p>Sign in to the portal that fits your role and tell the college what is working and what could be better.</p></div>
  <div class="portals">
    <a class="pc" id="student" href="student-portal/login.php">
      <div class="ic"><svg width="32" height="32" viewBox="0 0 24 24" fill="#fff"><path d="M12 3L1 9l4 2.2V16c0 1.7 3.1 3 7 3s7-1.3 7-3v-4.8l2-1.1V16h2V9zM12 12.5L5.6 9 12 5.5 18.4 9z"/></svg></div>
      <div class="eyebrow">STUDENT ACCESS</div><h3>Student Portal</h3>
      <p>Track your submissions, review status, and recover passwords in one place.</p>
      <ul><li>Submit feedback on courses and facilities</li><li>Choose to stay anonymous</li><li>Follow review progress</li></ul>
      <span class="go">Open Student Portal <span>→</span></span>
    </a>
    <a class="pc" id="staff" href="staff-portal/login.php">
      <div class="ic"><svg width="32" height="32" viewBox="0 0 24 24" fill="#fff"><path d="M9 3a2 2 0 00-2 2v2H3a1 1 0 00-1 1v11a1 1 0 001 1h18a1 1 0 001-1V8a1 1 0 00-1-1h-4V5a2 2 0 00-2-2zm0 2h6v2H9zM4 13h6v1.5h4V13h6v6H4z"/></svg></div>
      <div class="eyebrow">STAFF ACCESS</div><h3>Staff Portal</h3>
      <p>Share workplace feedback, ideas and concerns, and follow up on what you have sent.</p>
      <ul><li>Rate topics and workplace satisfaction</li><li>Identified or anonymous submissions</li><li>View your own feedback history</li></ul>
      <span class="go">Open Staff Portal <span>→</span></span>
    </a>
  </div>
</div></section>

<section class="s" id="how"><div class="wrap">
  <div class="head"><div><div class="eyebrow">HOW IT WORKS</div><h2>Built around your voice</h2></div>
    <p>A simple three-step flow that keeps feedback moving from your message to a real improvement.</p></div>
  <div class="steps">
    <div class="step"><div class="num">01</div><h3>Share</h3><p>Pick a topic, rate your experience and describe it in your own words, with an anonymous option.</p></div>
    <div class="step"><div class="num">02</div><h3>Review</h3><p>Authorized reviewers read each submission and act on it, with urgent items prioritized.</p></div>
    <div class="step"><div class="num">03</div><h3>Track</h3><p>Follow the status of everything you have submitted from your own portal.</p></div>
  </div>
</div></section>
</main>

</body>
</html>
