<?php
require_once __DIR__ . '/../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !current_user_is_admin()) {
    redirect('/FMS/admin/specializations.php');
}
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/specializations.php');
}

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
if ($name === '') {
    set_flash('error', 'Specialization name is required.');
    redirect('/FMS/admin/specializations.php');
}

$stmt = $pdo->prepare('SELECT id FROM specializations WHERE name = ?');
$stmt->execute([$name]);
if ($stmt->fetch()) {
    set_flash('error', 'That specialization already exists.');
    redirect('/FMS/admin/specializations.php');
}

$stmt = $pdo->prepare('INSERT INTO specializations (name, description) VALUES (?, ?)');
$stmt->execute([$name, $description !== '' ? $description : null]);
set_flash('success', 'Specialization created successfully.');
redirect('/FMS/admin/specializations.php');
