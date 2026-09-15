CREATE DATABASE IF NOT EXISTS LAS CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE LAS;

-- 1. ROLES & PERMISSIONS (For Staff, Admins, and Supervisors)
CREATE TABLE roles (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(50) NOT NULL UNIQUE,
description VARCHAR(255) NULL   ,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
description VARCHAR(255) NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
role_id INT UNSIGNED NOT NULL,
permission_id INT UNSIGNED NOT NULL,
CONSTRAINT uq_role_permissions UNIQUE (role_id, permission_id),
CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- 2. USERS (Staff, Admins, Academic & Industrial Supervisors Auth & Profile)
CREATE TABLE users (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(100) NOT NULL UNIQUE,
email VARCHAR(150) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
full_name VARCHAR(150) NOT NULL,
phone_number VARCHAR(30) NULL,
designation VARCHAR(100) NULL,
department_id INT UNSIGNED NULL,
remember_token VARCHAR(100) NULL,
email_verified_at DATETIME NULL,
status ENUM('active', 'suspended', 'deleted') NOT NULL DEFAULT 'active',
failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
locked_until DATETIME NULL,
last_login_at DATETIME NULL,
deleted_at DATETIME NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
INDEX idx_users_status (status),
INDEX idx_users_email (email)
);

CREATE TABLE user_roles (
user_id INT UNSIGNED NOT NULL,
role_id INT UNSIGNED NOT NULL,
CONSTRAINT uq_user_roles UNIQUE (user_id, role_id),
CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- 3. LOOKUP & REFERENCE TABLES
CREATE TABLE nationalities (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL UNIQUE,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE training_types (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL UNIQUE,
description VARCHAR(255) NULL,
is_active BOOLEAN NOT NULL DEFAULT TRUE,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE study_levels (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(80) NOT NULL UNIQUE,
description VARCHAR(255) NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE document_types (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
training_type_id INT UNSIGNED NULL,
name VARCHAR(150) NOT NULL UNIQUE,
description VARCHAR(255) NULL,
max_size_bytes INT UNSIGNED NOT NULL DEFAULT 2097152,
allowed_mime_types JSON NULL,
is_required BOOLEAN NOT NULL DEFAULT FALSE,
is_active BOOLEAN NOT NULL DEFAULT TRUE,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
CONSTRAINT fk_document_types_training FOREIGN KEY (training_type_id) REFERENCES training_types(id) ON DELETE CASCADE
);

-- 4. STUDENTS (Standalone Authentication & Profile Table)
CREATE TABLE students (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
registration_no VARCHAR(50) NOT NULL UNIQUE,
email VARCHAR(150) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
full_name VARCHAR(150) NOT NULL,
gender ENUM('Male','Female','Other') NULL,
nationality_id INT UNSIGNED NULL,
dob DATE NULL,
study_level_id INT UNSIGNED NULL,
course_of_study VARCHAR(150) NULL,
status ENUM('active', 'suspended', 'graduated') NOT NULL DEFAULT 'active',
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
CONSTRAINT fk_students_nationality FOREIGN KEY (nationality_id) REFERENCES nationalities(id) ON DELETE SET NULL,
CONSTRAINT fk_students_education_level FOREIGN KEY (study_level_id) REFERENCES study_levels(id) ON DELETE SET NULL,
INDEX idx_students_search (full_name, course_of_study)
) ENGINE=InnoDB;

CREATE TABLE departments (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
is_active BOOLEAN NOT NULL DEFAULT TRUE,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
CONSTRAINT uq_department_name UNIQUE (name)
);

-- Foreign key referencing department in users
ALTER TABLE users
ADD CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

-- 6. APPLICATION WINDOWS (With Max Capacity)
CREATE TABLE application_windows (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
open_date DATETIME NOT NULL,
close_date DATETIME NOT NULL,
max_capacity INT UNSIGNED NULL,
is_active BOOLEAN NOT NULL DEFAULT TRUE,
created_by INT UNSIGNED NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
CONSTRAINT fk_app_windows_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
CONSTRAINT chk_app_window_dates CHECK (close_date > open_date),
CONSTRAINT uq_app_window_name UNIQUE (name)
);

CREATE TABLE specializations (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(150) NOT NULL UNIQUE,
description TEXT NULL,
is_active BOOLEAN NOT NULL DEFAULT TRUE,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 7. APPLICATIONS (Stripped of institution_id, programme_id, and lifecycle timestamps)
CREATE TABLE applications (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
student_id INT UNSIGNED NOT NULL,
application_window_id INT UNSIGNED NULL,
training_type_id INT UNSIGNED NULL,
study_level_id INT UNSIGNED NULL,
reference_number VARCHAR(40) NOT NULL UNIQUE,
application_type ENUM('initial', 'reapplication') NOT NULL DEFAULT 'initial',
skill_level ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
interest_statement TEXT NULL,
reason_for_application TEXT NULL,
expected_learning_objectives TEXT NULL,
requested_start_date DATE NULL,
requested_end_date DATE NULL,
status ENUM('draft', 'submitted', 'under_review', 'returned_for_correction', 'accepted', 'rejected', 'cancelled', 'placement_assigned', 'in_training', 'completed') NOT NULL DEFAULT 'draft',
current_review_stage ENUM('secretary', 'field_coordinator', 'hod', 'placement_officer', 'done') NOT NULL DEFAULT 'secretary',
submission_date DATETIME NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
CONSTRAINT fk_applications_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
CONSTRAINT fk_applications_window FOREIGN KEY (application_window_id) REFERENCES application_windows(id) ON DELETE RESTRICT,
CONSTRAINT fk_applications_training_type FOREIGN KEY (training_type_id) REFERENCES training_types(id) ON DELETE RESTRICT,
CONSTRAINT fk_applications_study_level FOREIGN KEY (study_level_id) REFERENCES study_levels(id) ON DELETE RESTRICT,
CONSTRAINT chk_requested_dates CHECK (requested_end_date IS NULL OR requested_start_date IS NULL OR requested_end_date >= requested_start_date),
INDEX idx_applications_student (student_id),
INDEX idx_applications_status (status)
);

CREATE TABLE application_specializations (
application_id INT UNSIGNED NOT NULL,
specialization_id INT UNSIGNED NOT NULL,
CONSTRAINT uq_application_specialization UNIQUE (application_id, specialization_id),
CONSTRAINT fk_app_spec_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
CONSTRAINT fk_app_spec_specialization FOREIGN KEY (specialization_id) REFERENCES specializations(id) ON DELETE RESTRICT
);

-- 8. POLYMORPHIC COMMENTS TABLE
CREATE TABLE comments (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id INT UNSIGNED NULL,
student_id INT UNSIGNED NULL,
commentable_type ENUM('application', 'placement', 'logbook', 'review') NOT NULL,
commentable_id INT UNSIGNED NOT NULL,
comment_text TEXT NOT NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
CONSTRAINT fk_comments_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
INDEX idx_comments_entity (commentable_type, commentable_id)
) ENGINE=InnoDB;

-- 9. APPLICATION REVIEWS (Houses decision lifecycle timestamps)
CREATE TABLE reviews (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
application_id INT UNSIGNED NOT NULL,
user_id INT UNSIGNED NOT NULL,
decision ENUM('accepted', 'rejected', 'cancelled', 'completed', 'under_review', 'forwarded') NOT NULL,
status VARCHAR(50) NOT NULL,
stage VARCHAR(50) NOT NULL,
comment_id INT UNSIGNED NULL,
accepted_at DATETIME NULL,
rejected_at DATETIME NULL,
cancelled_at DATETIME NULL,
completed_at DATETIME NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT fk_reviews_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
CONSTRAINT fk_reviews_comment FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE SET NULL
);

-- 10. PLACEMENTS & LOGBOOKS
CREATE TABLE placements (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
application_id INT UNSIGNED NOT NULL UNIQUE,
department_id INT UNSIGNED NOT NULL,
academic_supervisor_id INT UNSIGNED NULL,
industrial_supervisor_id INT UNSIGNED NULL,
start_date DATE NOT NULL,
end_date DATE NOT NULL,
status ENUM('pending', 'active', 'completed', 'terminated') NOT NULL DEFAULT 'pending',
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
CONSTRAINT fk_placements_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE RESTRICT,
CONSTRAINT fk_placements_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
CONSTRAINT fk_placements_academic_sup FOREIGN KEY (academic_supervisor_id) REFERENCES users(id) ON DELETE SET NULL,
CONSTRAINT fk_placements_industrial_sup FOREIGN KEY (industrial_supervisor_id) REFERENCES users(id) ON DELETE SET NULL,
CONSTRAINT chk_placement_dates CHECK (end_date >= start_date)
);

CREATE TABLE logbook_entries (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
placement_id INT UNSIGNED NOT NULL,
entry_date DATE NOT NULL,
tasks_performed TEXT NOT NULL,
skills_acquired TEXT NULL,
challenges_faced TEXT NULL,
is_verified_by_industrial BOOLEAN NOT NULL DEFAULT FALSE,
verified_at DATETIME NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
CONSTRAINT fk_logbook_placement FOREIGN KEY (placement_id) REFERENCES placements(id) ON DELETE CASCADE,
CONSTRAINT uq_placement_entry_date UNIQUE (placement_id, entry_date)
);

-- 11. AUDIT LOGS
CREATE TABLE application_audit_logs (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
application_id INT UNSIGNED NOT NULL,
user_id INT UNSIGNED NULL,
action VARCHAR(100) NOT NULL,
old_values JSON NULL,
new_values JSON NULL,
ip_address VARCHAR(45) NULL,
user_agent VARCHAR(255) NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT fk_app_audit_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
CONSTRAINT fk_app_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE placement_audit_logs (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
placement_id INT UNSIGNED NOT NULL,
user_id INT UNSIGNED NULL,
action VARCHAR(100) NOT NULL,
old_values JSON NULL,
new_values JSON NULL,
ip_address VARCHAR(45) NULL,
user_agent VARCHAR(255) NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT fk_placement_audit_placement FOREIGN KEY (placement_id) REFERENCES placements(id) ON DELETE CASCADE,
CONSTRAINT fk_placement_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- SEED DATA: Roles, Permissions, and an Admin user
-- =============================================

-- Roles
INSERT INTO roles (name, description) VALUES
('admin', 'System administrator with full access'),
('secretary', 'Checks application completeness and documents'),
('field_coordinator', 'Reviews application and verifies academic details'),
('hod', 'Head of Department — reviews and approves/rejects'),
('placement_officer', 'Processes placement after HOD approval'),
('academic_supervisor', 'Academic supervisor who oversees students'),
('industrial_supervisor', 'Industrial placement supervisor'),
('supervisor', 'General supervisor role');

-- Permissions
INSERT INTO permissions (description) VALUES
('manage_applications'),
('manage_departments'),
('manage_students'),
('manage_windows'),
('manage_supervisors'),
('review_applications'),
('create_placements'),
('review_stage_secretary'),
('review_stage_field_coordinator'),
('review_stage_hod'),
('review_stage_placement');

-- Assign all permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name = 'admin';

-- Assign stage-specific permissions to each review role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'secretary' AND p.description = 'review_stage_secretary';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'field_coordinator' AND p.description = 'review_stage_field_coordinator';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'hod' AND p.description = 'review_stage_hod';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'placement_officer' AND p.description = 'review_stage_placement';

-- Admin also gets all stage permissions (already covered by all-permissions assignment above)

-- Default admin user (username: admin, password: admin123)
-- Password hash for 'admin123' using bcrypt
INSERT INTO users (username, email, password, full_name, status) VALUES
('admin', 'admin@fms.local', '$2y$12$zTt7E/sfqQ6JfXLJdwNpPOg3hQpd.bj/9PPlRdqhkxtb6h/oAdP36', 'System Administrator', 'active');

-- Assign admin role to the default admin user
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.username = 'admin' AND r.name = 'admin';

-- Sample nationalities
INSERT INTO nationalities (name) VALUES
('Kenyan'),
('Ugandan'),
('Tanzanian'),
('Nigerian'),
('Ghanaian'),
('South African'),
('Other');

-- Sample study levels
INSERT INTO study_levels (name, description) VALUES
('Certificate', 'Certificate level'),
('Diploma', 'Diploma level'),
('Bachelors', 'Bachelors degree'),
('Masters', 'Masters degree'),
('PhD', 'Doctorate degree');

-- Sample training types
INSERT INTO training_types (name, description) VALUES
('Internship', 'Industrial internship placement'),
('Attachment', 'Industrial attachment'),
('Practicum', 'Field practicum'),
('Industrial Training', 'General industrial training');

-- Sample specializations
INSERT INTO specializations (name, description) VALUES
('Software Development', 'Software engineering and development'),
('Network Engineering', 'Network infrastructure and administration'),
('Database Administration', 'Database management and administration'),
('Cybersecurity', 'Information security and cybersecurity'),
('Data Science', 'Data analysis and machine learning'),
('Web Development', 'Frontend and backend web development'),
('Mobile Development', 'Mobile application development'),
('IT Support', 'General IT support and maintenance');
