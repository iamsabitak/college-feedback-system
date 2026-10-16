<?php
/**
 * Shared account-management helper methods for admin-managed student and staff accounts.
 */

function account_temp_password(): string
{
    $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghijkmnopqrstuvwxyz';
    $digits = '23456789';
    $symbols = '!@#$%^&*?-_';
    $pool = $upper . $lower . $digits . $symbols;

    $parts = [
        $upper[random_int(0, strlen($upper) - 1)],
        $lower[random_int(0, strlen($lower) - 1)],
        $digits[random_int(0, strlen($digits) - 1)],
        $symbols[random_int(0, strlen($symbols) - 1)],
    ];

    $password = implode('', $parts);
    $length = 12;
    while (strlen($password) < $length) {
        $password .= $pool[random_int(0, strlen($pool) - 1)];
    }

    $password = str_shuffle($password);
    return $password;
}

function account_text_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $length = preg_match_all('/./us', $value, $matches);
    return $length === false ? strlen($value) : $length;
}

function account_is_valid_name(string $value): bool
{
    $value = trim($value);
    $length = account_text_length($value);
    return $length >= 2 && $length <= 100;
}

function account_is_valid_email(string $value): bool
{
    $email = trim($value);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && account_text_length($email) <= 100;
}

function account_is_valid_student_id(?string $value): bool
{
    if ($value === null || trim($value) === '') {
        return false;
    }

    return preg_match('/^[A-Za-z0-9\/-]{3,30}$/', trim($value)) === 1;
}

function account_staff_id_exists(PDO $pdo, string $staffId, ?int $ignoreUserId = null): bool
{
    $sql = 'SELECT id FROM users WHERE LOWER(staff_id) = LOWER(:staff_id)';
    $params = [':staff_id' => trim($staffId)];
    if ($ignoreUserId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreUserId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool) $stmt->fetchColumn();
}

function account_email_exists(PDO $pdo, string $email, ?int $ignoreUserId = null): bool
{
    $email = trim($email);
    $sql = 'SELECT id FROM users WHERE LOWER(email) = LOWER(:email)';
    $params = [':email' => $email];

    if ($ignoreUserId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreUserId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetchColumn()) {
        return true;
    }

    $portalSql = 'SELECT id FROM portal_users WHERE LOWER(email) = LOWER(:email)';
    $portalParams = [':email' => $email];
    if ($ignoreUserId !== null) {
        $current = $pdo->prepare('SELECT email FROM users WHERE id = :id LIMIT 1');
        $current->execute([':id' => $ignoreUserId]);
        $currentEmail = $current->fetchColumn();
        if ($currentEmail !== false) {
            $portalSql .= ' AND LOWER(email) != LOWER(:current_email)';
            $portalParams[':current_email'] = $currentEmail;
        }
    }
    $portalStmt = $pdo->prepare($portalSql);
    $portalStmt->execute($portalParams);
    if ($portalStmt->fetchColumn()) {
        return true;
    }

    $adminHasEmail = account_admin_email_column_exists($pdo);
    if ($adminHasEmail) {
        $sql2 = 'SELECT id FROM admin WHERE LOWER(email) = LOWER(:email)';
        if ($ignoreUserId !== null) {
            $sql2 .= ' AND id != :ignore_id';
        }
        $stmt2 = $pdo->prepare($sql2);
        $params2 = [':email' => $email];
        if ($ignoreUserId !== null) {
            $params2[':ignore_id'] = $ignoreUserId;
        }
        $stmt2->execute($params2);
        if ($stmt2->fetchColumn()) {
            return true;
        }
    }

    return false;
}

function account_username_exists(PDO $pdo, string $username, ?int $ignoreUserId = null): bool
{
    $username = trim($username);
    $sql = 'SELECT id FROM users WHERE LOWER(username) = LOWER(:username)';
    $params = [':username' => $username];
    if ($ignoreUserId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreUserId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetchColumn()) {
        return true;
    }

    $portalSql = 'SELECT id FROM portal_users WHERE LOWER(username) = LOWER(:username)';
    $portalParams = [':username' => $username];
    if ($ignoreUserId !== null) {
        $current = $pdo->prepare('SELECT email FROM users WHERE id = :id LIMIT 1');
        $current->execute([':id' => $ignoreUserId]);
        $currentEmail = $current->fetchColumn();
        if ($currentEmail !== false) {
            $portalSql .= ' AND email != :current_email';
            $portalParams[':current_email'] = $currentEmail;
        }
    }
    $portalStmt = $pdo->prepare($portalSql);
    $portalStmt->execute($portalParams);
    return (bool) $portalStmt->fetchColumn();
}

function account_student_id_exists(PDO $pdo, string $studentId, ?int $ignoreUserId = null): bool
{
    $studentId = trim($studentId);
    $sql = 'SELECT id FROM users WHERE LOWER(student_id) = LOWER(:student_id)';
    $params = [':student_id' => $studentId];

    if ($ignoreUserId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreUserId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool) $stmt->fetchColumn();
}

function account_admin_email_column_exists(PDO $pdo): bool
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin' AND COLUMN_NAME = 'email'");
    return (int) $stmt->fetchColumn() > 0;
}

function account_snap_label(array $user): string
{
    $name = trim((string) ($user['full_name'] ?? ''));
    $email = trim((string) ($user['email'] ?? ''));
    if ($name !== '' && $email !== '') {
        return $name . ' <' . $email . '>';
    }
    return $name !== '' ? $name : $email;
}

function account_audit_limit_details(string $details): string
{
    return function_exists('mb_strcut')
        ? mb_strcut($details, 0, 255, 'UTF-8')
        : substr($details, 0, 255);
}

function account_audit_changes(array $before, array $after): string
{
    $fields = [
        'full_name' => 'Name',
        'username' => 'Username',
        'email' => 'Email',
        'role' => 'Role',
        'student_id' => 'Student ID',
        'staff_id' => 'Staff ID',
    ];
    $changes = [];
    foreach ($fields as $field => $label) {
        $oldValue = trim((string) ($before[$field] ?? ''));
        $newValue = trim((string) ($after[$field] ?? ''));
        if ($oldValue !== $newValue) {
            $changes[] = $label . ': ' . ($oldValue !== '' ? $oldValue : '(empty)') . ' -> ' . ($newValue !== '' ? $newValue : '(empty)');
        }
    }

    return account_audit_limit_details($changes ? 'Changed: ' . implode('; ', $changes) : 'No account fields changed.');
}

function account_audit_lifecycle_details(string $verb, array $user): string
{
    $role = (string) ($user['role'] ?? 'account');
    $accountId = $role === 'student' ? ($user['student_id'] ?? '') : ($user['staff_id'] ?? '');
    $details = ucfirst($verb) . ' ' . $role . ' account; Username: ' . ($user['username'] ?? '-')
        . '; ID: ' . ($accountId !== '' ? $accountId : '-')
        . '; Status: ' . ($user['status'] ?? '-');

    return account_audit_limit_details($details);
}

function account_write_audit_log(PDO $pdo, string $actorType, int $actorId, string $action, ?int $targetUserId, ?string $targetLabel, ?string $details): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO account_audit_log (actor_type, actor_id, action, target_user_id, target_label, details, created_at)
         VALUES (:actor_type, :actor_id, :action, :target_user_id, :target_label, :details, NOW())'
    );
    $stmt->execute([
        ':actor_type' => $actorType,
        ':actor_id' => $actorId,
        ':action' => $action,
        ':target_user_id' => $targetUserId,
        ':target_label' => $targetLabel,
        ':details' => $details,
    ]);
}

function account_validate_payload(PDO $pdo, array $payload, ?int $existingId = null): array
{
    $errors = [];
    $clean = [
        'role' => trim((string) ($payload['role'] ?? '')),
        'full_name' => trim((string) ($payload['full_name'] ?? '')),
        'email' => trim((string) ($payload['email'] ?? '')),
        'username' => trim((string) ($payload['username'] ?? '')),
        'student_id' => '',
        'staff_id' => '',
    ];
    $accountId = trim((string) ($payload['account_id'] ?? ''));

    if (!in_array($clean['role'], ['student', 'staff'], true)) {
        $errors['role'] = 'Choose a valid account role.';
    }

    if (!account_is_valid_name($clean['full_name'])) {
        $errors['full_name'] = 'Name must be 2-100 characters long.';
    }

    if (!account_is_valid_email($clean['email'])) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (account_email_exists($pdo, $clean['email'], $existingId)) {
        $errors['email'] = 'This email address is already in use.';
    }

    if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $clean['username'])) {
        $errors['username'] = 'Username must be 3-60 characters using letters, numbers, dots, underscores, or hyphens.';
    } elseif (account_username_exists($pdo, $clean['username'], $existingId)) {
        $errors['username'] = 'This username is already in use.';
    }

    if ($clean['role'] === 'student') {
        $clean['student_id'] = $accountId;
        if (!account_is_valid_student_id($clean['student_id'])) {
            $errors['student_id'] = 'Student ID must be 3-30 characters using letters, numbers, /, or -.';
        } elseif (account_student_id_exists($pdo, $clean['student_id'], $existingId)) {
            $errors['student_id'] = 'This student ID is already in use.';
        }
    } else {
        $clean['staff_id'] = $accountId;
        if (!account_is_valid_student_id($clean['staff_id'])) {
            $errors['staff_id'] = 'Staff ID must be 3-30 characters using letters, numbers, /, or -.';
        } elseif (account_staff_id_exists($pdo, $clean['staff_id'], $existingId)) {
            $errors['staff_id'] = 'This staff ID is already in use.';
        }
    }

    return ['errors' => $errors, 'clean' => $clean];
}

function account_select_users(PDO $pdo, array $filters, int $limit, int $offset): array
{
    $where = ['1 = 1'];
    $params = [];

    if (!empty($filters['role'])) {
        $where[] = 'u.role = :role';
        $params[':role'] = $filters['role'];
    }
    if (!empty($filters['search'])) {
        $where[] = '(LOWER(u.full_name) LIKE :search OR LOWER(u.email) LIKE :search OR LOWER(u.username) LIKE :search OR LOWER(u.student_id) LIKE :search OR LOWER(u.staff_id) LIKE :search)';
        $params[':search'] = '%' . strtolower($filters['search']) . '%';
    }
    if (!empty($filters['status'])) {
        $where[] = 'u.status = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'u.last_login >= :date_from';
        $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'u.last_login <= :date_to';
        $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }

    $sql = 'SELECT u.*,
            (SELECT COUNT(*) FROM feedback f WHERE f.user_id = u.id AND f.is_anonymous = 0) AS named_submissions
            FROM users u
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function account_count_users(PDO $pdo, array $filters): int
{
    $where = ['1 = 1'];
    $params = [];

    if (!empty($filters['role'])) {
        $where[] = 'role = :role';
        $params[':role'] = $filters['role'];
    }
    if (!empty($filters['search'])) {
        $where[] = '(LOWER(full_name) LIKE :search OR LOWER(email) LIKE :search OR LOWER(username) LIKE :search OR LOWER(student_id) LIKE :search OR LOWER(staff_id) LIKE :search)';
        $params[':search'] = '%' . strtolower($filters['search']) . '%';
    }
    if (!empty($filters['status'])) {
        $where[] = 'status = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'last_login >= :date_from';
        $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'last_login <= :date_to';
        $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }

    $sql = 'SELECT COUNT(*) FROM users WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function account_select_audit_log(PDO $pdo, array $filters, int $limit, int $offset): array
{
    $where = ['1 = 1'];
    $params = [];

    if (!empty($filters['action'])) {
        $where[] = 'al.action = :action';
        $params[':action'] = $filters['action'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'al.created_at >= :date_from';
        $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'al.created_at <= :date_to';
        $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }
    if (!empty($filters['search'])) {
        $where[] = '(LOWER(al.action) LIKE :search_action OR LOWER(al.target_label) LIKE :search_target OR LOWER(al.details) LIKE :search_details OR LOWER(al.actor_type) LIKE :search_actor_type OR CAST(al.actor_id AS CHAR) LIKE :search_actor_id OR CAST(al.target_user_id AS CHAR) LIKE :search_target_id OR EXISTS (SELECT 1 FROM admin search_admin WHERE search_admin.id = al.actor_id AND LOWER(search_admin.username) LIKE :search_admin))';
        $search = '%' . strtolower($filters['search']) . '%';
        $params[':search_action'] = $search;
        $params[':search_target'] = $search;
        $params[':search_details'] = $search;
        $params[':search_actor_type'] = $search;
        $params[':search_actor_id'] = $search;
        $params[':search_target_id'] = $search;
        $params[':search_admin'] = $search;
    }

    $sql = 'SELECT al.*, a.username AS actor_username
            FROM account_audit_log al
            LEFT JOIN admin a ON a.id = al.actor_id AND al.actor_type = :actor_type
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':actor_type', 'admin');
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function account_count_audit_log(PDO $pdo, array $filters): int
{
    $where = ['1 = 1'];
    $params = [];

    if (!empty($filters['action'])) {
        $where[] = 'action = :action';
        $params[':action'] = $filters['action'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'created_at >= :date_from';
        $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'created_at <= :date_to';
        $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
    }
    if (!empty($filters['search'])) {
        $where[] = '(LOWER(al.action) LIKE :search_action OR LOWER(al.target_label) LIKE :search_target OR LOWER(al.details) LIKE :search_details OR LOWER(al.actor_type) LIKE :search_actor_type OR CAST(al.actor_id AS CHAR) LIKE :search_actor_id OR CAST(al.target_user_id AS CHAR) LIKE :search_target_id OR EXISTS (SELECT 1 FROM admin search_admin WHERE search_admin.id = al.actor_id AND LOWER(search_admin.username) LIKE :search_admin))';
        $search = '%' . strtolower($filters['search']) . '%';
        $params[':search_action'] = $search;
        $params[':search_target'] = $search;
        $params[':search_details'] = $search;
        $params[':search_actor_type'] = $search;
        $params[':search_actor_id'] = $search;
        $params[':search_target_id'] = $search;
        $params[':search_admin'] = $search;
    }

    $sql = 'SELECT COUNT(*) FROM account_audit_log al WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}
