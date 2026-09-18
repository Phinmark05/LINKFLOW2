<?php
require_once __DIR__ . '/../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !current_user_is_admin()) {
    redirect('/FMS/admin/application_windows.php');
}
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/application_windows.php');
}

$id = (int) ($_POST['window_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$openDate = $_POST['open_date'] ?? '';
$closeDate = $_POST['close_date'] ?? '';
$maxCapacity = $_POST['max_capacity'] ?? '';
$isActive = isset($_POST['is_active']) ? 1 : 0;
$errors = [];
if ($id < 1 || $name === '') $errors[] = 'Window name is required.';
if ($openDate === '' || $closeDate === '') $errors[] = 'Both dates are required.';
if ($openDate !== '' && $closeDate !== '' && strtotime($closeDate) <= strtotime($openDate)) $errors[] = 'Close date must be after the open date.';

$stmt = $pdo->prepare('SELECT id FROM application_windows WHERE name = ? AND id != ?');
$stmt->execute([$name, $id]);
if ($stmt->fetch()) $errors[] = 'That window name already exists.';

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/admin/application_windows.php');
}

$openDate = str_replace('T', ' ', $openDate) . (strlen($openDate) === 16 ? ':00' : '');
$closeDate = str_replace('T', ' ', $closeDate) . (strlen($closeDate) === 16 ? ':00' : '');
$stmt = $pdo->prepare('UPDATE application_windows SET name = ?, open_date = ?, close_date = ?, max_capacity = ?, is_active = ? WHERE id = ?');
$stmt->execute([$name, $openDate, $closeDate, $maxCapacity !== '' ? (int) $maxCapacity : null, $isActive, $id]);
set_flash('success', 'Application window updated successfully.');
redirect('/FMS/admin/application_windows.php');
