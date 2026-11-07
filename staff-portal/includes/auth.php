<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('college_feedback_staff');
    session_start();
}

if (empty($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}
