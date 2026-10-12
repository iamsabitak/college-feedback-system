<?php
/**
 * Protects administrator pages and redirects unauthenticated users to login.
 *
 * Application-owned logic for includes/auth.php.
 */
/**
 * Session guard. Every admin page (except login.php) includes this at the very top.
 * Section 13: "Every admin page verifies an active login session before showing private data."
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();

// This check protects every administrator page from direct access without a valid session.
}

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
