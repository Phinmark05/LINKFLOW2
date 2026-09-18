<?php
require_once __DIR__ . '/../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !current_user_is_admin()) {
    redirect('/FMS/admin/institutions.php');
}
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/institutions.php');
}

$id = (int) ($_POST['institution_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$isActive = isset($_POST['is_active']) ? 1 : 0;

if ($id < 1 || $name === '') {
    set_flash('error', 'Institution name is required.');
    redirect('/FMS/admin/institutions.php');
}

$stmt = $pdo->prepare('SELECT id FROM institutions WHERE name = ? AND id != ?');
$stmt->execute([$name, $id]);
if ($stmt->fetch()) {
    set_flash('error', 'That institution already exists.');
    redirect('/FMS/admin/institutions.php');
}

$stmt = $pdo->prepare('UPDATE institutions SET name = ?, is_active = ? WHERE id = ?');
$stmt->execute([$name, $isActive, $id]);
set_flash('success', 'Institution updated successfully.');
redirect('/FMS/admin/institutions.php');
