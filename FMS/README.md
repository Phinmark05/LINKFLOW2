# FMS — Field Application Management System

A plain procedural PHP backend for managing student field applications, reviews, and placements. Built with PHP 8+, MySQL/MariaDB, PDO, and AdminLTE for the dashboard UI.

## Requirements

- XAMPP / WAMP / MAMP (or any Apache + PHP 8+ + MySQL/MariaDB setup)
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- A web browser

## Setup Instructions

### 1. Copy the project

Copy the entire `FMS` folder into your web server's document root:
- **XAMPP:** `C:\xampp\htdocs\FMS`
- **WAMP:** `C:\wamp\www\FMS`
- **MAMP:** `/Applications/MAMP/htdocs/FMS`
- **Linux Apache:** `/var/www/html/FMS`

### 2. Import the database

1. Open phpMyAdmin (usually `http://localhost/phpmyadmin`)
2. Click the **Import** tab
3. Choose the file `FMS/database/FMS.sql`
4. Click **Go** to import

This creates the `FMS` database with all tables, relationships, seed data (roles, permissions, nationalities, study levels, training types, specializations), and a default admin user.

### 3. Configure database credentials

Open `FMS/includes/config.php` and adjust the credentials if your MySQL setup uses a different password:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'FMS');
define('DB_USER', 'root');
define('DB_PASS', '');  // Change this if your MySQL has a password
```

### 4. Access the system

Open your browser and go to: `http://localhost/FMS`

## Default Login

### Admin (Staff) Login
- **Login type:** Staff
- **Username:** `admin`
- **Password:** `admin123`

### Student Login
Students must register first via the registration page, then log in using their registration number and password.

## Features

### Student
- Register with registration number, email, and password
- Login / logout
- View dashboard with application status and placement info
- Edit profile (name, email, gender, nationality, DOB, study level, course)
- Create applications (draft or submit)
- Edit drafts and returned-for-correction applications
- Resubmit corrected applications
- View all applications and their statuses
- View placement details when assigned
- View notifications (status changes and staff comments)

### Admin / Staff
- Login / logout with account locking after 5 failed attempts
- Dashboard with real database statistics
- View, filter, and search all applications
- Review application details (student info, specializations, comments, audit log)
- Mark applications as under review
- Return applications for correction (with comment)
- Accept applications
- Reject applications (with reason)
- Assign placements to accepted applications
- Manage departments (add, activate/deactivate)
- Manage supervisors (add supervisor users)
- Manage students (view, suspend/activate)
- Manage application windows (create, activate/deactivate)

### Security
- PDO prepared statements (SQL injection protection)
- Password hashing with `password_hash()` / `password_verify()`
- CSRF tokens on all POST forms
- Session regeneration after login
- Server-side validation on all inputs
- Output escaping with `htmlspecialchars()`
- Role-based authorization checks on every protected page and action
- Account locking after 5 failed login attempts (30-minute lockout)
- Suspended/deleted accounts cannot log in

### Audit Logging
- Application audit logs: created, submitted, moved to review, returned, resubmitted, accepted, rejected, placement assigned
- Placement audit logs: placement created
- All logs record user ID, action, old/new values (JSON), IP address, user agent, and timestamp

## Project Structure

```
FMS/
├── index.php                  # Redirects to appropriate dashboard
├── auth/
│   ├── login.php              # Login page (student + staff)
│   ├── register.php           # Student registration
│   └── logout.php             # Destroys session
├── student/
│   ├── dashboard.php          # Student overview
│   ├── profile.php            # Edit profile
│   ├── application.php        # New application form
│   ├── edit_application.php   # Edit draft/returned application
│   ├── my_application.php      # List all applications
│   ├── placement.php          # View placement details
│   └── notifications.php      # View notifications
├── admin/
│   ├── dashboard.php           # Admin statistics
│   ├── applications.php        # List/filter/search applications
│   ├── view_application.php    # Application detail + review actions
│   ├── departments.php         # Manage departments
│   ├── supervisors.php         # Manage supervisors
│   ├── students.php            # Manage students
│   └── application_windows.php # Manage application windows
├── actions/
│   ├── login.php               # Login handler
│   ├── register.php            # Registration handler
│   ├── update_profile.php      # Profile update handler
│   ├── save_application.php    # Create/update application
│   ├── review_application.php   # Mark under review
│   ├── return_application.php   # Return for correction
│   ├── accept_application.php   # Accept application
│   ├── reject_application.php   # Reject application
│   ├── create_placement.php     # Create placement
│   ├── save_department.php      # Create department
│   ├── toggle_department.php    # Activate/deactivate dept
│   ├── save_supervisor.php      # Create supervisor user
│   ├── toggle_student.php       # Suspend/activate student
│   ├── save_window.php          # Create application window
│   ├── toggle_window.php        # Activate/deactivate window
│   └── get_departments.php      # AJAX: departments by org
├── includes/
│   ├── config.php               # DB credentials + session start
│   ├── database.php             # PDO connection
│   ├── functions.php            # Helper functions
│   ├── auth_check.php           # General auth check
│   ├── admin_check.php          # Admin role check
│   ├── student_check.php        # Student session check
│   ├── header.php               # HTML head + AdminLTE CSS
│   ├── navbar.php               # Top nav bar
│   ├── sidebar.php              # Left sidebar menu
│   └── footer.php               # Footer + AdminLTE JS
├── assets/
│   ├── css/style.css            # Custom styles
│   └── js/script.js             # Custom JavaScript
├── database/
│   └── FMS.sql                  # Database schema + seed data
└── README.md
```

## Application Status Workflow

```
draft → submitted → under_review → accepted → placement_assigned
                     ↓                ↓
                     rejected    returned_for_correction → (student edits) → submitted
```

## Notes

- The system uses the provided FMS database schema exactly as supplied. No tables were redesigned or replaced.
- Notifications are implemented using the existing `comments` table (staff comments on applications) and application status changes. A dedicated notifications table would be needed for persistent, feature-rich notifications in the future.
- The `logbook_entries` table exists in the database but logbook/training/completion functionality is not implemented in this version (future expansion).
- AdminLTE 3.2 is loaded from CDN (no local files needed). jQuery and Bootstrap 4 are also loaded from CDN.
