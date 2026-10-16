<?php
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/accounts.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (empty($_FILES['csv']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Upload a CSV file.']);
    exit;
}

$csv = fopen($_FILES['csv']['tmp_name'], 'r');
if ($csv === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unable to read the uploaded file.']);
    exit;
}

$header = fgetcsv($csv);
$rows = 0;
$created = 0;

while (($row = fgetcsv($csv)) !== false) {
    if (count($row) < 4) {
        continue;
    }

    [$fullName, $email, $studentId, $role] = array_pad($row, 4, '');
    $fullName = trim($fullName);
    $email = trim($email);
    $studentId = trim($studentId);
    $role = trim(strtolower($role));

    if ($fullName === '' || $email === '' || !in_array($role, ['student', 'staff'], true)) {
        continue;
    }

    $studentIdValue = $role === 'student' ? $studentId : '';
    $validation = account_validate_payload($pdo, [
        'role' => $role,
        'full_name' => $fullName,
        'email' => $email,
        'student_id' => $studentIdValue,
    ]);

    if (!empty($validation['errors'])) {
        continue;
    }

    $copy = account_temp_password();
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, student_id, role, password_hash, status, must_change_password)
         VALUES (:full_name, :email, :student_id, :role, :password_hash, :status, 1)'
    );
    $stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':student_id' => $studentIdValue !== '' ? $studentIdValue : null,
        ':role' => $role,
        ':password_hash' => password_hash($copy, PASSWORD_DEFAULT),
        ':status' => 'active',
    ]);
    $created++;
    $rows++;
}

fclose($csv);

echo json_encode(['success' => true, 'created' => $created, 'rows' => $rows, 'message' => 'CSV import completed.']);
