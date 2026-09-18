<?php
require_once __DIR__ . '/../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !current_user_is_admin()) {
    redirect('/FMS/admin/departments.php');
}
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/departments.php');
}

$id = (int) ($_POST['department_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$isActive = isset($_POST['is_active']) ? 1 : 0;
if ($id < 1 || $name === '') {
    set_flash('error', 'Department name is required.');
    redirect('/FMS/admin/departments.php');
}

$stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? AND id != ?');
$stmt->execute([$name, $id]);
if ($stmt->fetch()) {
    set_flash('error', 'That department already exists.');
    redirect('/FMS/admin/departments.php');
}

$stmt = $pdo->prepare('UPDATE departments SET name = ?, is_active = ? WHERE id = ?');
$stmt->execute([$name, $isActive, $id]);
set_flash('success', 'Department updated successfully.');
redirect('/FMS/admin/departments.php');
