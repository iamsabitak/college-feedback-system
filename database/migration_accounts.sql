USE college_feedback_system;

SET @account_schema = DATABASE();

-- portal login accounts used by the student and staff portals
CREATE TABLE IF NOT EXISTS portal_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student','staff') NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_portal_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    student_id VARCHAR(30) NULL UNIQUE,
    staff_id VARCHAR(30) NULL UNIQUE,
    role ENUM('student','staff') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    failed_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- add username to admin-managed records without breaking existing rows
SET @has_users_username = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @account_schema
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'username'
);
SET @users_username_sql = IF(@has_users_username = 0,
    'ALTER TABLE users ADD COLUMN username VARCHAR(60) NULL AFTER full_name',
    'SELECT 1');
PREPARE users_username_stmt FROM @users_username_sql;
EXECUTE users_username_stmt;
DEALLOCATE PREPARE users_username_stmt;

SET @has_users_username_index = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @account_schema
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'uq_users_username'
);
SET @users_username_index_sql = IF(@has_users_username_index = 0,
    'ALTER TABLE users ADD UNIQUE INDEX uq_users_username (username)',
    'SELECT 1');
PREPARE users_username_index_stmt FROM @users_username_index_sql;
EXECUTE users_username_index_stmt;
DEALLOCATE PREPARE users_username_index_stmt;

-- add staff ID to existing managed accounts
SET @has_users_staff_id = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @account_schema
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'staff_id'
);
SET @users_staff_id_sql = IF(@has_users_staff_id = 0,
    'ALTER TABLE users ADD COLUMN staff_id VARCHAR(30) NULL AFTER student_id',
    'SELECT 1');
PREPARE users_staff_id_stmt FROM @users_staff_id_sql;
EXECUTE users_staff_id_stmt;
DEALLOCATE PREPARE users_staff_id_stmt;

SET @has_users_staff_id_index = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @account_schema
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'uq_users_staff_id'
);
SET @users_staff_id_index_sql = IF(@has_users_staff_id_index = 0,
    'ALTER TABLE users ADD UNIQUE INDEX uq_users_staff_id (staff_id)',
    'SELECT 1');
PREPARE users_staff_id_index_stmt FROM @users_staff_id_index_sql;
EXECUTE users_staff_id_index_stmt;
DEALLOCATE PREPARE users_staff_id_index_stmt;

-- older portal installations did not track disabled accounts
SET @has_portal_user_status = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @account_schema
      AND TABLE_NAME = 'portal_users'
      AND COLUMN_NAME = 'status'
);
SET @portal_user_status_sql = IF(@has_portal_user_status = 0,
    'ALTER TABLE portal_users ADD COLUMN status ENUM(''active'',''disabled'') NOT NULL DEFAULT ''active'' AFTER password_hash',
    'SELECT 1');
PREPARE portal_user_status_stmt FROM @portal_user_status_sql;
EXECUTE portal_user_status_stmt;
DEALLOCATE PREPARE portal_user_status_stmt;

-- attach previously-created admin accounts to matching portal login usernames
UPDATE users u
INNER JOIN portal_users p ON LOWER(p.email) = LOWER(u.email)
SET u.username = p.username
WHERE u.username IS NULL;

-- account audit
CREATE TABLE IF NOT EXISTS account_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_type ENUM('admin','staff') NOT NULL,
    actor_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    target_user_id INT NULL,
    target_label VARCHAR(150) NULL,
    details VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_account_audit_created (created_at),
    CONSTRAINT fk_account_audit_target_user FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- extend audit tables created by earlier student/staff migrations
SET @has_audit_target_label = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @account_schema
      AND TABLE_NAME = 'account_audit_log'
      AND COLUMN_NAME = 'target_label'
);
SET @audit_target_label_sql = IF(@has_audit_target_label = 0,
    'ALTER TABLE account_audit_log ADD COLUMN target_label VARCHAR(150) NULL AFTER target_user_id',
    'SELECT 1');
PREPARE audit_target_label_stmt FROM @audit_target_label_sql;
EXECUTE audit_target_label_stmt;
DEALLOCATE PREPARE audit_target_label_stmt;

SET @has_audit_details = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @account_schema
      AND TABLE_NAME = 'account_audit_log'
      AND COLUMN_NAME = 'details'
);
SET @audit_details_sql = IF(@has_audit_details = 0,
    'ALTER TABLE account_audit_log ADD COLUMN details VARCHAR(255) NULL AFTER target_label',
    'SELECT 1');
PREPARE audit_details_stmt FROM @audit_details_sql;
EXECUTE audit_details_stmt;
DEALLOCATE PREPARE audit_details_stmt;

-- ensure feedback.user_id exists
SET @has_feedback_user_id = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @account_schema
      AND TABLE_NAME = 'feedback'
      AND COLUMN_NAME = 'user_id'
);
SET @feedback_user_id_sql = IF(@has_feedback_user_id = 0,
    'ALTER TABLE feedback ADD COLUMN user_id INT NULL AFTER submitter_name',
    'SELECT 1');
PREPARE feedback_user_id_stmt FROM @feedback_user_id_sql;
EXECUTE feedback_user_id_stmt;
DEALLOCATE PREPARE feedback_user_id_stmt;

-- add FK if missing
SET @has_feedback_user_fk = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = @account_schema
      AND TABLE_NAME = 'feedback'
      AND CONSTRAINT_NAME = 'fk_feedback_user'
);
SET @feedback_user_fk_sql = IF(@has_feedback_user_fk = 0,
    'ALTER TABLE feedback ADD CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE feedback_user_fk_stmt FROM @feedback_user_fk_sql;
EXECUTE feedback_user_fk_stmt;
DEALLOCATE PREPARE feedback_user_fk_stmt;

-- add unique index for null-safe confirmation if absent
SET @has_feedback_receipt_hash = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @account_schema
      AND TABLE_NAME = 'feedback'
      AND COLUMN_NAME = 'receipt_hash'
);
SET @feedback_receipt_sql = IF(@has_feedback_receipt_hash = 0,
    'ALTER TABLE feedback ADD COLUMN receipt_hash CHAR(64) NULL UNIQUE AFTER user_id',
    'SELECT 1');
PREPARE feedback_receipt_stmt FROM @feedback_receipt_sql;
EXECUTE feedback_receipt_stmt;
DEALLOCATE PREPARE feedback_receipt_stmt;

-- seed a demo student and staff account if absent
INSERT INTO users (full_name, email, student_id, role, password_hash, status, must_change_password)
VALUES
    ('Student Demo User', 'student@example.com', 'STU-1001', 'student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 1),
    ('Staff Demo User', 'staff@example.com', NULL, 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 1)
ON DUPLICATE KEY UPDATE email = email;

-- link existing feedback rows for demonstration on first run, without overwriting anonymous rows
UPDATE feedback f
LEFT JOIN users u ON u.email = 'student@example.com'
SET f.user_id = u.id
WHERE f.submitter_name = 'Student Demo User' AND f.user_id IS NULL;

UPDATE feedback f
LEFT JOIN users u ON u.email = 'staff@example.com'
SET f.user_id = u.id
WHERE f.submitter_name = 'Staff Demo User' AND f.user_id IS NULL;
