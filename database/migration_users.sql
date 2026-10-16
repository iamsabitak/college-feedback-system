USE college_feedback_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    student_id VARCHAR(30) NULL UNIQUE,
    role ENUM('student','staff') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_password_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    purpose ENUM('forgot_password','change_password') NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_password_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS account_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_type ENUM('admin','staff') NOT NULL,
    actor_id INT NULL,
    action VARCHAR(50) NOT NULL,
    target_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_actor (actor_type, actor_id),
    INDEX idx_audit_target (target_user_id)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE feedback
    ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER submitter_name,
    ADD COLUMN IF NOT EXISTS receipt_hash CHAR(64) NULL UNIQUE AFTER user_id;

ALTER TABLE feedback
    ADD CONSTRAINT fk_feedback_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Some MySQL/MariaDB versions reject CHECK constraints; keep this guarded in PHP instead.
-- The application enforces: when is_anonymous = 1, user_id must remain NULL and submitter_name must remain NULL.

-- Seed demo accounts for the new student/staff portals.
INSERT INTO users (full_name, email, student_id, role, password_hash, status, must_change_password)
VALUES
    ('Student Demo User', 'student@example.com', 'STU-1001', 'student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 1),
    ('Staff Demo User', 'staff@example.com', 'STF-1001', 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 1)
ON DUPLICATE KEY UPDATE email = email;

-- Seed sample portal-linked feedback if the user exists.
INSERT INTO feedback (submitter_name, user_id, submitter_type, is_anonymous, category, rating, message, status, submit_date)
SELECT 'Student Demo User', u.id, 'Student', 0, 'Library', 4, 'The library seats are a big help during exam weeks.', 'New', NOW() - INTERVAL 2 DAY
FROM users u WHERE u.email = 'student@example.com'
ON DUPLICATE KEY UPDATE submitter_name = submitter_name;

INSERT INTO feedback (submitter_name, user_id, submitter_type, is_anonymous, category, rating, message, status, submit_date)
SELECT 'Staff Demo User', u.id, 'Teacher & Staff', 0, 'Administration', 5, 'The admin support process feels much more responsive.', 'Reviewed', NOW() - INTERVAL 1 DAY
FROM users u WHERE u.email = 'staff@example.com'
ON DUPLICATE KEY UPDATE submitter_name = submitter_name;
