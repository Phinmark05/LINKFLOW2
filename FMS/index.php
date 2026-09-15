<?php
/**
 * FMS Index / Landing Page
 *
 * Redirects the user to the appropriate dashboard based on their session:
 *   - Students → student dashboard
 *   - Staff → admin dashboard
 *   - Not logged in → login page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['student_id'])) {
    redirect('/FMS/student/dashboard.php');
}

if (!empty($_SESSION['user_id'])) {

    require_once __DIR__ . '/includes/functions.php';
    if (user_has_role($pdo, (int) $_SESSION['user_id'], 'admin')) {
        redirect('/FMS/admin/dashboard.php');
    }
    redirect('/FMS/staff/dashboard.php');
}


redirect('/FMS/auth/login.php');
