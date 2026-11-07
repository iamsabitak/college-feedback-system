USE college_feedback_system;

CREATE TABLE IF NOT EXISTS portal_password_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    portal_user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose ENUM('forgot_password') NOT NULL DEFAULT 'forgot_password',
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_portal_password_otp_user FOREIGN KEY (portal_user_id) REFERENCES portal_users(id) ON DELETE CASCADE,
    INDEX idx_portal_password_otp_lookup (portal_user_id, purpose, is_used, expires_at)
) ENGINE=InnoDB;
