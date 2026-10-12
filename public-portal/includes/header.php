<?php
/**
 * Shared public portal head, logo, favicon, navigation, and mobile menu.
 *
 * Application-owned logic for includes/header.php.
 */
// $active is set by each page before including this file, e.g. $active = 'home';
if (!isset($active)) { $active = ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · ' : ''; ?>College Online Feedback System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,500;0,600;0,700;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="icon" type="image/svg+xml" href="../assets/logo.svg">
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header" id="siteHeader">
  <div class="header-inner">
    <a href="index.php" class="brand">
      <span class="brand-mark"><img class="brand-logo" src="../assets/logo.svg" alt=""></span>
      <span class="brand-text">College Feedback<br><small>Patan Nistha Campus</small></span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <nav class="main-nav" id="mainNav">
      <a href="index.php"   class="nav-link <?php echo $active === 'home' ? 'active' : ''; ?>">Home</a>
      <a href="about.php"   class="nav-link <?php echo $active === 'about' ? 'active' : ''; ?>">About</a>
      <a href="faq.php"     class="nav-link <?php echo $active === 'faq' ? 'active' : ''; ?>">FAQ</a>
      <a href="privacy.php" class="nav-link <?php echo $active === 'privacy' ? 'active' : ''; ?>">Privacy</a>
      <a href="contact.php" class="nav-link <?php echo $active === 'contact' ? 'active' : ''; ?>">Contact</a>
      <a href="feedback.php" class="btn btn-primary nav-cta">Give Feedback</a>
    </nav>
  </div>
</header>
