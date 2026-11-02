USE college_feedback_system;

SET @feedback_schema = DATABASE();

SET @has_feedback_title = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'feedback_title');
SET @feedback_sql = IF(@has_feedback_title = 0, 'ALTER TABLE feedback ADD COLUMN feedback_title VARCHAR(200) NULL AFTER category', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_department_faculty = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'department_faculty');
SET @feedback_sql = IF(@has_department_faculty = 0, 'ALTER TABLE feedback ADD COLUMN department_faculty VARCHAR(50) NULL AFTER feedback_title', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_semester_year = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'semester_year');
SET @feedback_sql = IF(@has_semester_year = 0, 'ALTER TABLE feedback ADD COLUMN semester_year VARCHAR(40) NULL AFTER department_faculty', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_urgency_level = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'urgency_level');
SET @feedback_sql = IF(@has_urgency_level = 0, 'ALTER TABLE feedback ADD COLUMN urgency_level VARCHAR(12) NULL AFTER semester_year', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_overall_satisfaction = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'overall_satisfaction');
SET @feedback_sql = IF(@has_overall_satisfaction = 0, 'ALTER TABLE feedback ADD COLUMN overall_satisfaction TINYINT NULL AFTER rating', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_preferred_solution = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'preferred_solution');
SET @feedback_sql = IF(@has_preferred_solution = 0, 'ALTER TABLE feedback ADD COLUMN preferred_solution TEXT NULL AFTER message', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_follow_up_required = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'follow_up_required');
SET @feedback_sql = IF(@has_follow_up_required = 0, 'ALTER TABLE feedback ADD COLUMN follow_up_required TINYINT(1) NOT NULL DEFAULT 0 AFTER preferred_solution', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_staff_department = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'staff_department');
SET @feedback_sql = IF(@has_staff_department = 0, 'ALTER TABLE feedback ADD COLUMN staff_department VARCHAR(60) NULL AFTER follow_up_required', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_staff_role = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'staff_role');
SET @feedback_sql = IF(@has_staff_role = 0, 'ALTER TABLE feedback ADD COLUMN staff_role VARCHAR(60) NULL AFTER staff_department', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_suggested_improvements = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'suggested_improvements');
SET @feedback_sql = IF(@has_suggested_improvements = 0, 'ALTER TABLE feedback ADD COLUMN suggested_improvements TEXT NULL AFTER staff_role', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_attachment_path = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'attachment_path');
SET @feedback_sql = IF(@has_attachment_path = 0, 'ALTER TABLE feedback ADD COLUMN attachment_path VARCHAR(255) NULL AFTER suggested_improvements', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;

SET @has_attachment_name = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @feedback_schema AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'attachment_name');
SET @feedback_sql = IF(@has_attachment_name = 0, 'ALTER TABLE feedback ADD COLUMN attachment_name VARCHAR(255) NULL AFTER attachment_path', 'SELECT 1');
PREPARE feedback_stmt FROM @feedback_sql; EXECUTE feedback_stmt; DEALLOCATE PREPARE feedback_stmt;