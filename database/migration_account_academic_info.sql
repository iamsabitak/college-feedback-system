USE college_feedback_system;

-- Academic and employment details for portal and managed accounts.
-- Nullable columns keep existing installations compatible; the backfill below
-- supplies valid defaults only where a legacy record has no value.
SET @account_schema = DATABASE();

SET @add_columns = (
  SELECT GROUP_CONCAT(CONCAT('ADD COLUMN ', column_name, ' ', definition, ' NULL') SEPARATOR ', ')
  FROM (
    SELECT 'department_faculty' AS column_name, 'VARCHAR(120)' AS definition UNION ALL
    SELECT 'semester_year', 'VARCHAR(80)' UNION ALL
    SELECT 'staff_department', 'VARCHAR(120)' UNION ALL
    SELECT 'staff_role', 'VARCHAR(120)'
  ) requested
  WHERE NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS c
    WHERE c.TABLE_SCHEMA = @account_schema AND c.TABLE_NAME = 'users' AND c.COLUMN_NAME = requested.column_name
  )
);
SET @users_sql = IF(@add_columns IS NULL, 'SELECT 1', CONCAT('ALTER TABLE users ', @add_columns));
PREPARE users_columns_stmt FROM @users_sql;
EXECUTE users_columns_stmt;
DEALLOCATE PREPARE users_columns_stmt;

SET @add_columns = (
  SELECT GROUP_CONCAT(CONCAT('ADD COLUMN ', column_name, ' ', definition, ' NULL') SEPARATOR ', ')
  FROM (
    SELECT 'department_faculty' AS column_name, 'VARCHAR(120)' AS definition UNION ALL
    SELECT 'semester_year', 'VARCHAR(80)' UNION ALL
    SELECT 'staff_department', 'VARCHAR(120)' UNION ALL
    SELECT 'staff_role', 'VARCHAR(120)'
  ) requested
  WHERE NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS c
    WHERE c.TABLE_SCHEMA = @account_schema AND c.TABLE_NAME = 'portal_users' AND c.COLUMN_NAME = requested.column_name
  )
);
SET @portal_sql = IF(@add_columns IS NULL, 'SELECT 1', CONCAT('ALTER TABLE portal_users ', @add_columns));
PREPARE portal_columns_stmt FROM @portal_sql;
EXECUTE portal_columns_stmt;
DEALLOCATE PREPARE portal_columns_stmt;

-- Preserve all existing values; only give legacy blank records valid choices
-- from the existing feedback dropdown configuration.
UPDATE users
SET department_faculty = COALESCE(NULLIF(department_faculty, ''), 'BCA'),
    semester_year = COALESCE(NULLIF(semester_year, ''), '1st Semester')
WHERE role = 'student';
UPDATE portal_users
SET department_faculty = COALESCE(NULLIF(department_faculty, ''), 'BCA'),
    semester_year = COALESCE(NULLIF(semester_year, ''), '1st Semester')
WHERE role = 'student';
UPDATE users
SET staff_department = COALESCE(NULLIF(staff_department, ''), 'Teaching / Faculty'),
    staff_role = COALESCE(NULLIF(staff_role, ''), 'Lecturer / Teacher')
WHERE role = 'staff';
UPDATE portal_users
SET staff_department = COALESCE(NULLIF(staff_department, ''), 'Teaching / Faculty'),
    staff_role = COALESCE(NULLIF(staff_role, ''), 'Lecturer / Teacher')
WHERE role = 'staff';
