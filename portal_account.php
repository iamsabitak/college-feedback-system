<?php
require_once __DIR__ . '/portal_notifications.php';
require_once __DIR__ . '/admin-portal/includes/mailer.php';

function processPortalAccountPost(PDO $pdo, int $portalUserId, int $linkedUserId, string $role, array &$account, string $csrfToken): array
{
    $error = '';
    $notice = '';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_POST['action'] ?? '', ['update_profile', 'request_password_otp', 'resend_password_otp', 'verify_password_otp'], true)) {
        return [$error, $notice];
    }

    if (!hash_equals($csrfToken, (string) ($_POST['account_csrf_token'] ?? ''))) {
        return ['The form expired. Refresh the page and try again.', $notice];
    }

    if ($_POST['action'] === 'update_profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $departmentFaculty = trim((string) ($_POST['department_faculty'] ?? ''));
        $semesterYear = trim((string) ($_POST['semester_year'] ?? ''));
        $staffDepartment = trim((string) ($_POST['staff_department'] ?? ''));
        $staffRole = trim((string) ($_POST['staff_role'] ?? ''));
        $nameLength = function_exists('mb_strlen') ? mb_strlen($fullName, 'UTF-8') : strlen($fullName);

        if ($fullName === '' || $nameLength > 100) {
            return ['Name is required and must be 100 characters or fewer.', $notice];
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
            return ['Username must be 3-60 characters using letters, numbers, dots, underscores, or hyphens.', $notice];
        }
        $phoneDigits = preg_replace('/\D/', '', $phone);
        if ($phone !== '' && (!preg_match('/^\+?[0-9][0-9 ()-]{5,18}$/', $phone) || strlen($phoneDigits) < 7 || strlen($phoneDigits) > 15)) {
            return ['Enter a valid phone number with 7 to 15 digits.', $notice];
        }
        $optionExists = static function (string $portal, string $group, string $value, ?string $parent = null) use ($pdo): bool {
            if ($value === '') return false;
            $sql = 'SELECT 1 FROM feedback_dropdown_options WHERE portal = :portal AND option_group = :option_group AND option_value = :option_value';
            $params = [':portal' => $portal, ':option_group' => $group, ':option_value' => $value];
            if ($parent !== null) { $sql .= ' AND parent_value = :parent_value'; $params[':parent_value'] = $parent; }
            $stmt = $pdo->prepare($sql . ' LIMIT 1'); $stmt->execute($params);
            return (bool) $stmt->fetchColumn();
        };
        if ($role === 'student' && (!$optionExists('student', 'departments', $departmentFaculty) || !$optionExists('student', 'semesters', $semesterYear, $departmentFaculty))) {
            return ['Choose a valid department/faculty and semester/year.', $notice];
        }
        if ($role === 'staff' && (!$optionExists('staff', 'departments', $staffDepartment) || !$optionExists('staff', 'roles', $staffRole))) {
            return ['Choose a valid staff department and staff role.', $notice];
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
            $updatePortal = $pdo->prepare('UPDATE portal_users SET full_name = :full_name, username = :username, phone = :phone, department_faculty = :department_faculty, semester_year = :semester_year, staff_department = :staff_department, staff_role = :staff_role WHERE id = :id AND role = :role');
            $updatePortal->execute([':full_name' => $fullName, ':username' => $username, ':phone' => $phone ?: null, ':department_faculty' => $role === 'student' ? $departmentFaculty : null, ':semester_year' => $role === 'student' ? $semesterYear : null, ':staff_department' => $role === 'staff' ? $staffDepartment : null, ':staff_role' => $role === 'staff' ? $staffRole : null, ':id' => $portalUserId, ':role' => $role]);
            if ($linkedUserId > 0) {
                $updateManaged = $pdo->prepare('UPDATE users SET full_name = :full_name, username = :username, phone = :phone, department_faculty = :department_faculty, semester_year = :semester_year, staff_department = :staff_department, staff_role = :staff_role WHERE id = :id AND role = :role');
                $updateManaged->execute([':full_name' => $fullName, ':username' => $username, ':phone' => $phone ?: null, ':department_faculty' => $role === 'student' ? $departmentFaculty : null, ':semester_year' => $role === 'student' ? $semesterYear : null, ':staff_department' => $role === 'staff' ? $staffDepartment : null, ':staff_role' => $role === 'staff' ? $staffRole : null, ':id' => $linkedUserId, ':role' => $role]);
            }
            notifyAllAdministrators($pdo, 'identity_update', ucfirst($role) . ' identity updated', $fullName . ' updated their profile details.');
            $pdo->commit();
            $account['full_name'] = $fullName;
            $account['username'] = $username;
            $account['phone'] = $phone;
            $account['department_faculty'] = $role === 'student' ? $departmentFaculty : null;
            $account['semester_year'] = $role === 'student' ? $semesterYear : null;
            $account['staff_department'] = $role === 'staff' ? $staffDepartment : null;
            $account['staff_role'] = $role === 'staff' ? $staffRole : null;
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

    $otpSessionKey = 'portal_change_otp_' . $role;
    $pendingOtp = $_SESSION[$otpSessionKey] ?? null;
    $action = (string) $_POST['action'];

    if ($action === 'request_password_otp' || $action === 'resend_password_otp') {
        if (!otp_mailer_ready()) return ['Email delivery is not configured. Ask the system administrator to configure the Gmail App Password.', $notice];
        if (empty($account['email'])) return ['Your account has no registered email address.', $notice];
        if ($action === 'resend_password_otp' && (!$pendingOtp || (int) ($pendingOtp['resend_at'] ?? 0) > time())) {
            return ['Please wait before requesting another code.', $notice];
        }
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE portal_password_otp SET is_used = 1 WHERE portal_user_id = :id AND purpose = 'change_password' AND is_used = 0")->execute([':id' => $portalUserId]);
            $code = (string) random_int(100000, 999999);
            $pdo->prepare("INSERT INTO portal_password_otp (portal_user_id, otp_code, purpose, expires_at) VALUES (:id, :code, 'change_password', DATE_ADD(NOW(), INTERVAL 10 MINUTE))")->execute([':id' => $portalUserId, ':code' => $code]);
            if (!send_otp_email($account['email'], $code, 'change_password')) throw new RuntimeException('Email delivery failed.');
            $pdo->commit();
            $_SESSION[$otpSessionKey] = ['user_id' => $portalUserId, 'expires_at' => time() + 600, 'resend_at' => time() + 60];
            return [$error, $action === 'resend_password_otp' ? 'A new verification code has been sent. Your previous code is no longer valid.' : 'A 6-digit verification code was sent to your account email.'];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Portal change-password OTP failed: ' . $exception->getMessage());
            return ['We could not send a verification code. Please try again.', $notice];
        }
    }

    if ($action === 'verify_password_otp') {
        if (!$pendingOtp || (int) ($pendingOtp['user_id'] ?? 0) !== $portalUserId) return ['Request a verification code before changing your password.', $notice];
        $otpCode = preg_replace('/\D/', '', (string) ($_POST['otp_code'] ?? ''));
        if (!preg_match('/^[0-9]{6}$/', $otpCode)) return ['That verification code is not valid.', $notice];
    } else {
        $otpCode = '';
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
        if ($action === 'verify_password_otp') {
            $otpStmt = $pdo->prepare("SELECT id FROM portal_password_otp WHERE portal_user_id = :id AND purpose = 'change_password' AND otp_code = :code AND is_used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $otpStmt->execute([':id' => $portalUserId, ':code' => $otpCode]);
            $otpId = $otpStmt->fetchColumn();
            if (!$otpId) throw new RuntimeException('Invalid or expired code.');
        }
        $updatePortalPassword = $pdo->prepare('UPDATE portal_users SET password_hash = :password_hash WHERE id = :id AND role = :role');
        $updatePortalPassword->execute([':password_hash' => $newHash, ':id' => $portalUserId, ':role' => $role]);
        if ($linkedUserId > 0) {
            $updateManagedPassword = $pdo->prepare('UPDATE users SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id AND role = :role');
            $updateManagedPassword->execute([':password_hash' => $newHash, ':id' => $linkedUserId, ':role' => $role]);
        }
        if (!empty($otpId)) $pdo->prepare('UPDATE portal_password_otp SET is_used = 1 WHERE id = :id')->execute([':id' => $otpId]);
        $pdo->commit();
        unset($_SESSION[$otpSessionKey]);
        $notice = 'Password changed successfully. You are still signed in.';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Portal password change failed: ' . $exception->getMessage());
        $error = $exception->getMessage() === 'Invalid or expired code.' ? $exception->getMessage() : 'Unable to change your password. Please try again.';
    }

    return [$error, $notice];
}
