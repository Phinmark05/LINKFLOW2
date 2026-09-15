<?php
/**
 * Toggle Student Active/Suspended Action
 *
 * Switches a student's status between 'active' and 'suspended'.
 * Suspended students cannot log in or access student pages.
 *
 * Only admin users can perform this action.
 */
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}

if (empty($_SESSION['user_id'])) {
    redirect('/FMS/auth/login.php');
}

// Only admins can toggle student status
if (!user_has_role($pdo, (int) $_SESSION['user_id'], 'admin')) {
    set_flash('error', 'Access denied. Only administrators can perform this action.');
    redirect('/FMS/index.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/students.php');
}

$studentId = (int) ($_POST['student_id'] ?? 0);
if ($studentId === 0) {
    set_flash('error', 'Invalid student.');
    redirect('/FMS/admin/students.php');
}

$student = get_student($pdo, $studentId);
if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('/FMS/admin/students.php');
}

// Toggle between active and suspended (don't touch 'graduated' status)
if ($student['status'] === 'active') {
    $stmt = $pdo->prepare("UPDATE students SET status = 'suspended' WHERE id = ?");
    $stmt->execute([$studentId]);
    set_flash('success', 'Student account has been suspended.');
} else {
    $stmt = $pdo->prepare("UPDATE students SET status = 'active' WHERE id = ?");
    $stmt->execute([$studentId]);
    set_flash('success', 'Student account has been activated.');
}

redirect('/FMS/admin/students.php');
