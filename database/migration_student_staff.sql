USE college_feedback_system;

CREATE TABLE IF NOT EXISTS portal_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student','staff') NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS portal_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(80) NOT NULL,
    status ENUM('Submitted','Under Review','Completed') NOT NULL DEFAULT 'Submitted',
    description TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_portal_submission_user FOREIGN KEY (user_id) REFERENCES portal_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS portal_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_token_user FOREIGN KEY (user_id) REFERENCES portal_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

INSERT INTO portal_users (role, username, full_name, email, password_hash)
VALUES
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
SELECT id, 'Course timetable review', 'Academics', 'Under Review', 'Please add one more revision session before mid-semester.'
FROM portal_users WHERE username = 'student01'
ON DUPLICATE KEY UPDATE title = title;

INSERT INTO portal_submissions (user_id, title, category, status, description)
SELECT id, 'Faculty support review', 'Faculty', 'Completed', 'The mentorship support has improved significantly.'
FROM portal_users WHERE username = 'student01'
ON DUPLICATE KEY UPDATE title = title;

INSERT INTO portal_submissions (user_id, title, category, status, description)
SELECT id, 'Cafeteria quality feedback', 'Cafeteria', 'Submitted', 'The food variety needs improvement for evening meals.'
FROM portal_users WHERE username = 'staff01'
ON DUPLICATE KEY UPDATE title = title;

INSERT INTO portal_submissions (user_id, title, category, status, description)
SELECT id, 'Administrative workflow proposal', 'Administration', 'Completed', 'Streamline the attendance review process for staff members.'
FROM portal_users WHERE username = 'staff01'
ON DUPLICATE KEY UPDATE title = title;
