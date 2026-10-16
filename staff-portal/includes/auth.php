<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}
