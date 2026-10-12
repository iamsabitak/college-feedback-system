-- Add profile fields and timestamps. Safe after migration_otp.sql or on its own.
-- Local root/root import: mysql -u root -proot < database/migration_profile.sql
USE college_feedback_system;

SET @profile_schema = DATABASE();

SET @has_email = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @profile_schema AND TABLE_NAME = 'admin' AND COLUMN_NAME = 'email');
SET @profile_sql = IF(@has_email = 0,
  'ALTER TABLE admin ADD COLUMN email VARCHAR(100) NULL UNIQUE AFTER username',
  'SELECT 1');
PREPARE profile_stmt FROM @profile_sql;
EXECUTE profile_stmt;
DEALLOCATE PREPARE profile_stmt;

SET @has_phone = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @profile_schema AND TABLE_NAME = 'admin' AND COLUMN_NAME = 'phone');
SET @profile_sql = IF(@has_phone = 0,
  'ALTER TABLE admin ADD COLUMN phone VARCHAR(20) NULL AFTER email',
  'SELECT 1');
PREPARE profile_stmt FROM @profile_sql;
EXECUTE profile_stmt;
DEALLOCATE PREPARE profile_stmt;

SET @has_updated_at = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @profile_schema AND TABLE_NAME = 'admin' AND COLUMN_NAME = 'updated_at');
SET @profile_sql = IF(@has_updated_at = 0,
  'ALTER TABLE admin ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1');
PREPARE profile_stmt FROM @profile_sql;
EXECUTE profile_stmt;
DEALLOCATE PREPARE profile_stmt;
