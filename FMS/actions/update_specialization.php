<?php
require_once __DIR__ . '/../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !current_user_is_admin()) {
    redirect('/FMS/admin/specializations.php');
}
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/specializations.php');
}

$id = (int) ($_POST['specialization_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$isActive = isset($_POST['is_active']) ? 1 : 0;

if ($id < 1 || $name === '') {
    set_flash('error', 'Specialization name is required.');
    redirect('/FMS/admin/specializations.php');
}

$stmt = $pdo->prepare('SELECT id FROM specializations WHERE name = ? AND id != ?');
$stmt->execute([$name, $id]);
if ($stmt->fetch()) {
    set_flash('error', 'That specialization already exists.');
    redirect('/FMS/admin/specializations.php');
}

$stmt = $pdo->prepare('UPDATE specializations SET name = ?, description = ?, is_active = ? WHERE id = ?');
$stmt->execute([$name, $description !== '' ? $description : null, $isActive, $id]);
set_flash('success', 'Specialization updated successfully.');
redirect('/FMS/admin/specializations.php');
