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
    submitter_type  ENUM('Student','Teacher & Staff','Visitor & Outsider') NOT NULL,
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
