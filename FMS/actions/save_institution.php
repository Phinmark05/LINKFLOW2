<?php
require_once __DIR__ . '/../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !current_user_is_admin()) {
    redirect('/FMS/admin/institutions.php');
}
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/institutions.php');
}

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    set_flash('error', 'Institution name is required.');
    redirect('/FMS/admin/institutions.php');
}

$stmt = $pdo->prepare('SELECT id FROM institutions WHERE name = ?');
$stmt->execute([$name]);
if ($stmt->fetch()) {
    set_flash('error', 'That institution already exists.');
    redirect('/FMS/admin/institutions.php');
}

$stmt = $pdo->prepare('INSERT INTO institutions (name) VALUES (?)');
$stmt->execute([$name]);
set_flash('success', 'Institution created successfully.');
redirect('/FMS/admin/institutions.php');
