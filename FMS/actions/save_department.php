<?php
/**
 * Save Department Action
 *
 * Creates a new department.
 */
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}

if (empty($_SESSION['user_id'])) {
    redirect('/FMS/auth/login.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/departments.php');
}

$deptName   = trim($_POST['name'] ?? '');

$errors = [];
if ($deptName === '') $errors[] = 'Department name is required.';

// Department names are unique.
if ($deptName !== '') {
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
    $stmt->execute([$deptName]);
    if ($stmt->fetch()) $errors[] = 'A department with this name already exists.';
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/admin/departments.php');
}

$stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
$stmt->execute([$deptName]);

set_flash('success', 'Department created successfully.');
redirect('/FMS/admin/departments.php');
