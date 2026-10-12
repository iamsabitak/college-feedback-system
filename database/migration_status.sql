-- Adds workflow statuses to an existing College Online Feedback System database.
-- Run once after importing database/schema.sql.
USE college_feedback_system;

ALTER TABLE feedback
  ADD COLUMN status ENUM('New', 'Reviewed', 'Resolved') NOT NULL DEFAULT 'New' AFTER submit_date;
