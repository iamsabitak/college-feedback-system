-- Per-admin unread state for feedback notifications.
-- Local root/root import: mysql -u root -proot < database/migration_notifications.sql
USE college_feedback_system;

CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL,
    admin_id    INT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at     DATETIME NULL,
    UNIQUE KEY uq_notifications_feedback_admin (feedback_id, admin_id),
    INDEX idx_notifications_admin_read_id (admin_id, is_read, id),
    FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Keep existing feedback visible in the dropdown without marking it unread on rollout.
INSERT IGNORE INTO notifications (feedback_id, admin_id, is_read, read_at)
SELECT feedback.id, admin.id, 1, NOW()
FROM feedback CROSS JOIN admin;
