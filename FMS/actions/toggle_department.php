<?php
/**
 * Toggle Department Active/Inactive Action
 *
 * Switches the is_active flag for a department.
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

$deptId = (int) ($_POST['department_id'] ?? 0);
if ($deptId === 0) {
    set_flash('error', 'Invalid department.');
    redirect('/FMS/admin/departments.php');
}

$stmt = $pdo->prepare("SELECT is_active FROM departments WHERE id = ?");
$stmt->execute([$deptId]);
$dept = $stmt->fetch();
if (!$dept) {
    set_flash('error', 'Department not found.');
    redirect('/FMS/admin/departments.php');
}

$newActive = $dept['is_active'] ? 0 : 1;
$stmt = $pdo->prepare("UPDATE departments SET is_active = ? WHERE id = ?");
$stmt->execute([$newActive, $deptId]);

set_flash('success', $newActive ? 'Department activated.' : 'Department deactivated.');
redirect('/FMS/admin/departments.php');
