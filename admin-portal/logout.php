<?php
/**
 * Clears the administrator session and returns to the login page.
 *
 * Application-owned logic for logout.php.
 */
session_start();

// Destroy the session data and its cookie-backed identifier before redirecting.
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
