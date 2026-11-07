<?php
/**
 * Clears the administrator session and returns to the login page.
 *
 * Application-owned logic for logout.php.
 */
session_name('college_feedback_admin');
session_start();

// Destroy the session data and its cookie-backed identifier before redirecting.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: login.php');
exit;
