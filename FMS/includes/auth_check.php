<?php
require_once __DIR__ . '/functions.php';
if (empty($_SESSION['student_id']) && empty($_SESSION['user_id'])) {
    set_flash('error', 'Please log in to access that page.');
    redirect('/FMS/auth/login.php');
}
$currentStudent = null;
if (!empty($_SESSION['student_id'])) {
    $currentStudent = get_student($pdo, (int) $_SESSION['student_id']);
    if (!$currentStudent) {
        session_destroy();
        redirect('/FMS/auth/login.php');
    }
    if ($currentStudent['status'] === 'suspended') {
        session_destroy();
        set_flash('error', 'Your account has been suspended. Contact the administrator.');
        redirect('/FMS/auth/login.php');
    }
}
$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $currentUser = get_user($pdo, (int) $_SESSION['user_id']);
    if (!$currentUser) {
        session_destroy();
        redirect('/FMS/auth/login.php');
    }
    if ($currentUser['status'] !== 'active') {
        session_destroy();
        set_flash('error', 'Your account is not active. Contact the administrator.');
        redirect('/FMS/auth/login.php');
    }
}
