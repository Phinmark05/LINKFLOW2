-- Run this migration on an existing FMS database.
CREATE TABLE IF NOT EXISTS institutions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_institution_name UNIQUE (name)
) ENGINE=InnoDB;

ALTER TABLE students
    ADD COLUMN institution_id INT UNSIGNED NULL AFTER study_level_id;

ALTER TABLE students
    ADD CONSTRAINT fk_students_institution
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE SET NULL;
