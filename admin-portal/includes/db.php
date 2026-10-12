<?php
/**
 * Creates the PDO connection used by all administrator pages.
 *
 * Application-owned logic for includes/db.php.
 */
/**
 * Database connection for the Admin Portal.
 * Same database as the public portal (Section 11: one shared MySQL database).
 */

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
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed. Start MySQL and import database/schema.sql before logging in.');
}
