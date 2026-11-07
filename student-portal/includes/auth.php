<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('college_feedback_student');
    session_start();
}

if (empty($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}
