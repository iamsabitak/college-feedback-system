<?php

function processPortalAccountPost(PDO $pdo, int $portalUserId, int $linkedUserId, string $role, array &$account, string $csrfToken): array
{
    $error = '';
    $notice = '';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_POST['action'] ?? '', ['update_profile', 'change_password'], true)) {
        return [$error, $notice];
    }

    if (!hash_equals($csrfToken, (string) ($_POST['account_csrf_token'] ?? ''))) {
        return ['The form expired. Refresh the page and try again.', $notice];
    }

    if ($_POST['action'] === 'update_profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $nameLength = function_exists('mb_strlen') ? mb_strlen($fullName, 'UTF-8') : strlen($fullName);

        if ($fullName === '' || $nameLength > 100) {
            return ['Name is required and must be 100 characters or fewer.', $notice];
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
            return ['Username must be 3-60 characters using letters, numbers, dots, underscores, or hyphens.', $notice];
        }

        $portalUsername = $pdo->prepare('SELECT id FROM portal_users WHERE LOWER(username) = LOWER(:username) AND id <> :id LIMIT 1');
        $portalUsername->execute([':username' => $username, ':id' => $portalUserId]);
        if ($portalUsername->fetchColumn()) {
            return ['That username is already in use.', $notice];
        }

        if ($linkedUserId > 0) {
            $managedUsername = $pdo->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(:username) AND id <> :id LIMIT 1');
            $managedUsername->execute([':username' => $username, ':id' => $linkedUserId]);
            if ($managedUsername->fetchColumn()) {
                return ['That username is already in use.', $notice];
            }
        }

        try {
            $pdo->beginTransaction();
            $updatePortal = $pdo->prepare('UPDATE portal_users SET full_name = :full_name, username = :username WHERE id = :id AND role = :role');
            $updatePortal->execute([':full_name' => $fullName, ':username' => $username, ':id' => $portalUserId, ':role' => $role]);
            if ($linkedUserId > 0) {
                $updateManaged = $pdo->prepare('UPDATE users SET full_name = :full_name, username = :username WHERE id = :id AND role = :role');
                $updateManaged->execute([':full_name' => $fullName, ':username' => $username, ':id' => $linkedUserId, ':role' => $role]);
            }
            $pdo->commit();
            $account['full_name'] = $fullName;
            $account['username'] = $username;
            $notice = 'Profile details updated.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Portal profile update failed: ' . $exception->getMessage());
            $error = 'Unable to update profile details. Please try again.';
        }

        return [$error, $notice];
    }

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    if (strlen($newPassword) < 8) {
        return ['Your new password must be at least 8 characters.', $notice];
    }
    if ($newPassword !== $confirmPassword) {
        return ['New password and confirmation do not match.', $notice];
    }

    $passwordStmt = $pdo->prepare('SELECT password_hash FROM portal_users WHERE id = :id AND role = :role LIMIT 1');
    $passwordStmt->execute([':id' => $portalUserId, ':role' => $role]);
    $currentHash = $passwordStmt->fetchColumn();
    if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
        return ['Current password is incorrect.', $notice];
    }
    if (password_verify($newPassword, $currentHash)) {
        return ['Choose a password different from your current password.', $notice];
    }

    try {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->beginTransaction();
        $updatePortalPassword = $pdo->prepare('UPDATE portal_users SET password_hash = :password_hash WHERE id = :id AND role = :role');
        $updatePortalPassword->execute([':password_hash' => $newHash, ':id' => $portalUserId, ':role' => $role]);
        if ($linkedUserId > 0) {
            $updateManagedPassword = $pdo->prepare('UPDATE users SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id AND role = :role');
            $updateManagedPassword->execute([':password_hash' => $newHash, ':id' => $linkedUserId, ':role' => $role]);
        }
        $pdo->commit();
        $notice = 'Password changed successfully. You are still signed in.';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Portal password change failed: ' . $exception->getMessage());
        $error = 'Unable to change your password. Please try again.';
    }

    return [$error, $notice];
}