USE college_feedback_system;

ALTER TABLE feedback
    MODIFY submitter_type ENUM('Student','Teacher & Staff','Visitor & Outsider','Public') NOT NULL;