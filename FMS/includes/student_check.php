<?php
/**
 * Student Check — verifies that the logged-in person is a student.
 *
 * This file is included at the top of every student page. It first
 * runs the general auth check (which loads $currentStudent), then
 * confirms a student session exists. If a staff user is logged in
 * but not a student, they are redirected away from student pages.
 */

require_once __DIR__ . '/auth_check.php';

// Student pages require a student session
if (!$currentStudent) {
    set_flash('error', 'Access denied. Student login required.');
    redirect('/FMS/auth/login.php');
}
