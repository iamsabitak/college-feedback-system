<?php
$DB_HOST = 'localhost';
$DB_NAME = 'college_feedback_system';
$DB_USER = 'root';
$DB_PASS = 'root';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Student portal DB error: ' . $e->getMessage());
    http_response_code(500);
    die('Student portal database connection failed. Please import the database schema first.');
}
