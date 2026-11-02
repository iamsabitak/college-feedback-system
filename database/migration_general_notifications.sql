-- General admin notifications: run once after migration_notifications.sql.
USE college_feedback_system;

ALTER TABLE notifications
  MODIFY feedback_id INT NULL,
  ADD COLUMN notification_type VARCHAR(40) NOT NULL DEFAULT 'feedback' AFTER feedback_id,
  ADD COLUMN title VARCHAR(180) NULL AFTER notification_type,
  ADD COLUMN message VARCHAR(500) NULL AFTER title;
