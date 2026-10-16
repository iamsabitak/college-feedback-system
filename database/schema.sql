-- College Online Feedback System
-- Database schema (Section 11 of the Project Proposal)
-- Local root/root import: mysql -u root -proot < database/schema.sql

CREATE DATABASE IF NOT EXISTS college_feedback_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE college_feedback_system;

-- 11.2 admin table -----------------------------------------------------
CREATE TABLE IF NOT EXISTS admin (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Default admin account: username "admin", password "admin123"
-- (password_hash generated with PHP's password_hash(), bcrypt)
INSERT INTO admin (username, password_hash) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username = username;

-- 11.1 feedback table ----------------------------------------------------
CREATE TABLE IF NOT EXISTS feedback (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    submitter_name  VARCHAR(100) NULL,                         -- nullable: empty when anonymous
    submitter_type  ENUM('Student','Teacher & Staff','Visitor & Outsider','Public') NOT NULL,
    is_anonymous    TINYINT(1) NOT NULL DEFAULT 0,
    category        VARCHAR(100) NOT NULL,
    rating          TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    message         TEXT NOT NULL,
    submit_date     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (submitter_type),
    INDEX idx_category (category),
    INDEX idx_rating (rating),
    INDEX idx_date (submit_date)
) ENGINE=InnoDB;

-- A few sample rows so the admin dashboard isn't empty on first run
INSERT INTO feedback (submitter_name, submitter_type, is_anonymous, category, rating, message, submit_date) VALUES
('Aayush Shrestha', 'Student', 0, 'Library', 4, 'Library needs more seating during exam weeks, but the collection is great.', NOW() - INTERVAL 2 DAY),
(NULL, 'Student', 1, 'Teacher', 2, 'One of the lecturers is frequently absent without notice.', NOW() - INTERVAL 1 DAY),
('Subien Byanjankar', 'Teacher & Staff', 0, 'Administration', 5, 'The new online notice system has made communication much smoother.', NOW()),
(NULL, 'Visitor & Outsider', 1, 'Campus', 3, 'Signage around the main gate could be clearer for first-time visitors.', NOW());

-- 11.3 portal user tables for student and staff access -------------------
CREATE TABLE IF NOT EXISTS portal_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    role          ENUM('student','staff') NOT NULL,
    username      VARCHAR(60) NOT NULL UNIQUE,
    full_name     VARCHAR(120) NOT NULL,
    email         VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_portal_role (role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS portal_submissions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    title      VARCHAR(150) NOT NULL,
    category   VARCHAR(80) NOT NULL,
    status     ENUM('Submitted','Under Review','Completed') NOT NULL DEFAULT 'Submitted',
    description TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_portal_submission_user FOREIGN KEY (user_id) REFERENCES portal_users(id) ON DELETE CASCADE,
    INDEX idx_portal_user_id (user_id),
    INDEX idx_portal_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS portal_reset_tokens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token      VARCHAR(128) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at    DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_token_user FOREIGN KEY (user_id) REFERENCES portal_users(id) ON DELETE CASCADE,
    INDEX idx_reset_user (user_id),
    INDEX idx_reset_expires (expires_at)
) ENGINE=InnoDB;

INSERT INTO portal_users (role, username, full_name, email, password_hash) VALUES
('student', 'student01', 'Anita Student', 'student@example.com', '$2y$10$cl8WqgU4KNHSVTYDAT2jHeR/9BcHkWL2Ab6ZpN16VbmYtwPx0Ydgy'),
('staff', 'staff01', 'Priya Staff', 'staff@example.com', '$2y$10$/FDlz2GSMRfRjShYoVA77ucsiIsnU2mYYfUexsBSzt.bmHtkgWPD.')
ON DUPLICATE KEY UPDATE
    role = VALUES(role),
    full_name = VALUES(full_name),
    email = VALUES(email),
    password_hash = VALUES(password_hash);

INSERT INTO portal_submissions (user_id, title, category, status, description)
SELECT id, 'Library access request', 'Library', 'Submitted', 'Need more seating during exam week.'
FROM portal_users WHERE username = 'student01'
ON DUPLICATE KEY UPDATE title = title;

INSERT INTO portal_submissions (user_id, title, category, status, description)
SELECT id, 'Course timetable review', 'Academics', 'Under Review', 'Add a revision session before the mid-semester exam.'
FROM portal_users WHERE username = 'student01'
ON DUPLICATE KEY UPDATE title = title;

INSERT INTO portal_submissions (user_id, title, category, status, description)
SELECT id, 'Faculty support review', 'Faculty', 'Completed', 'The mentoring support has improved significantly.'
FROM portal_users WHERE username = 'student01'
ON DUPLICATE KEY UPDATE title = title;

INSERT INTO portal_submissions (user_id, title, category, status, description)
SELECT id, 'Cafeteria quality feedback', 'Cafeteria', 'Submitted', 'Improve evening meal variety.'
FROM portal_users WHERE username = 'staff01'
ON DUPLICATE KEY UPDATE title = title;

INSERT INTO portal_submissions (user_id, title, category, status, description)
SELECT id, 'Administrative workflow proposal', 'Administration', 'Completed', 'Streamline the attendance review process for staff members.'
FROM portal_users WHERE username = 'staff01'
ON DUPLICATE KEY UPDATE title = title;
