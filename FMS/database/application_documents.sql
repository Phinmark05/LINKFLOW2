-- Run after FMS.sql. This migration uses a 2 MiB limit for every application document.
INSERT INTO document_types (name, description, is_required, max_size_bytes, allowed_mime_types)
VALUES
('Application Letter', 'A formal letter requesting the placement', TRUE, 2097152, JSON_ARRAY('application/pdf','image/jpeg','image/png')),
('National ID', 'A copy of a government-issued ID', TRUE, 2097152, JSON_ARRAY('application/pdf','image/jpeg','image/png')),
('Introduction Letter', 'Introduction letter from the institution', TRUE, 2097152, JSON_ARRAY('application/pdf','image/jpeg','image/png')),
('Supportive Document', 'Any extra supporting file (CV, transcript, etc.)', FALSE, 2097152, JSON_ARRAY('application/pdf','image/jpeg','image/png'))
ON DUPLICATE KEY UPDATE
 description = VALUES(description), is_required = VALUES(is_required),
 max_size_bytes = VALUES(max_size_bytes), allowed_mime_types = VALUES(allowed_mime_types);

CREATE TABLE IF NOT EXISTS application_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    document_type_id INT UNSIGNED NOT NULL,
    label VARCHAR(150) NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL UNIQUE,
    mime_type VARCHAR(100) NOT NULL,
    file_size_bytes INT UNSIGNED NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verification_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    verification_note VARCHAR(255) NULL,
    CONSTRAINT fk_app_documents_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_app_documents_type FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_app_documents_verifier FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_app_documents_application (application_id)
) ENGINE=InnoDB;

-- If application_documents already exists, apply the following separately instead of rerunning CREATE TABLE:
-- ALTER TABLE application_documents ADD COLUMN verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending', ADD COLUMN verified_by INT UNSIGNED NULL, ADD COLUMN verified_at DATETIME NULL, ADD COLUMN verification_note VARCHAR(255) NULL;
-- ALTER TABLE application_documents ADD CONSTRAINT fk_app_documents_verifier FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL;
