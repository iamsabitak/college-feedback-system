USE college_feedback_system;

SET @approval_schema = DATABASE();

SET @has_user_approval_status = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @approval_schema
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'approval_status'
);
SET @user_approval_status_sql = IF(@has_user_approval_status = 0,
    'ALTER TABLE users ADD COLUMN approval_status ENUM(''pending'',''approved'',''rejected'') NOT NULL DEFAULT ''approved'' AFTER status',
    'SELECT 1');
PREPARE user_approval_status_stmt FROM @user_approval_status_sql;
EXECUTE user_approval_status_stmt;
DEALLOCATE PREPARE user_approval_status_stmt;

SET @has_user_creator = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @approval_schema
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'created_by_staff_id'
);
SET @user_creator_sql = IF(@has_user_creator = 0,
    'ALTER TABLE users ADD COLUMN created_by_staff_id INT NULL AFTER approval_status',
    'SELECT 1');
PREPARE user_creator_stmt FROM @user_creator_sql;
EXECUTE user_creator_stmt;
DEALLOCATE PREPARE user_creator_stmt;

SET @has_portal_approval_status = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @approval_schema
      AND TABLE_NAME = 'portal_users'
      AND COLUMN_NAME = 'approval_status'
);
SET @portal_approval_status_sql = IF(@has_portal_approval_status = 0,
    'ALTER TABLE portal_users ADD COLUMN approval_status ENUM(''pending'',''approved'',''rejected'') NOT NULL DEFAULT ''approved'' AFTER status',
    'SELECT 1');
PREPARE portal_approval_status_stmt FROM @portal_approval_status_sql;
EXECUTE portal_approval_status_stmt;
DEALLOCATE PREPARE portal_approval_status_stmt;

SET @has_student_approval_index = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @approval_schema
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_staff_student_approval'
);
SET @student_approval_index_sql = IF(@has_student_approval_index = 0,
    'CREATE INDEX idx_staff_student_approval ON users (role, created_by_staff_id, approval_status)',
    'SELECT 1');
PREPARE student_approval_index_stmt FROM @student_approval_index_sql;
EXECUTE student_approval_index_stmt;
DEALLOCATE PREPARE student_approval_index_stmt;