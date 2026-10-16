USE college_feedback_system;

CREATE TABLE IF NOT EXISTS feedback_status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL,
    old_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_by_type ENUM('admin','staff','system') NOT NULL DEFAULT 'system',
    changed_by_id INT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_status_log_feedback (feedback_id, changed_at),
    CONSTRAINT fk_feedback_status_log_feedback FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO feedback_status_log (feedback_id, old_status, new_status, changed_by_type, changed_by_id, changed_at)
SELECT f.id, NULL, 'New', 'system', NULL, f.submit_date
FROM feedback f
WHERE NOT EXISTS (
    SELECT 1 FROM feedback_status_log l WHERE l.feedback_id = f.id AND l.new_status = 'New' AND l.changed_at = f.submit_date
);

INSERT INTO feedback_status_log (feedback_id, old_status, new_status, changed_by_type, changed_by_id, changed_at)
SELECT f.id, 'New', f.status, 'system', NULL, NOW()
FROM feedback f
WHERE f.status IS NOT NULL AND f.status <> 'New'
AND NOT EXISTS (
    SELECT 1 FROM feedback_status_log l WHERE l.feedback_id = f.id AND l.new_status = f.status AND l.changed_at = NOW()
);

-- Compatibility note: admin/staff pages can record more precise actor data in PHP when they update statuses.
