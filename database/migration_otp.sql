-- OTP password-reset support. Run once after database/schema.sql.
-- Local root/root import: mysql -u root -proot < database/migration_otp.sql
USE college_feedback_system;

ALTER TABLE admin
  ADD COLUMN email VARCHAR(100) NULL UNIQUE AFTER username;

UPDATE admin
  SET email = 'sabitakhadka091@gmail.com'
  WHERE username = 'admin' AND email IS NULL;

CREATE TABLE password_otp (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT NOT NULL,
    otp_code    VARCHAR(6) NOT NULL,
    purpose     ENUM('forgot_password','change_password') NOT NULL,
    expires_at  DATETIME NOT NULL,
    is_used     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE,
    INDEX idx_password_otp_lookup (admin_id, purpose, is_used, expires_at)
) ENGINE=InnoDB;
