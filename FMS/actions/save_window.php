<?php
/**
 * Save Application Window Action
 *
 * Creates a new application window. Validates that close_date > open_date
 * (also enforced by a database CHECK constraint). Sets created_by to
 * the logged-in admin user.
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
    redirect('/FMS/admin/application_windows.php');
}

$userId      = (int) $_SESSION['user_id'];
$name        = trim($_POST['name'] ?? '');
$openDate    = $_POST['open_date'] ?? '';
$closeDate   = $_POST['close_date'] ?? '';
$maxCapacity = $_POST['max_capacity'] ?? '';

$errors = [];
if ($name === '') $errors[] = 'Window name is required.';
if (empty($openDate)) $errors[] = 'Open date is required.';
if (empty($closeDate)) $errors[] = 'Close date is required.';

// Validate close_date > open_date
if (!empty($openDate) && !empty($closeDate) && strtotime($closeDate) <= strtotime($openDate)) {
    $errors[] = 'Close date must be after the open date.';
}

// Check for duplicate window name (database has UNIQUE constraint)
if ($name !== '') {
    $stmt = $pdo->prepare("SELECT id FROM application_windows WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) $errors[] = 'A window with this name already exists.';
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/admin/application_windows.php');
}

// Convert datetime-local format (Y-m-d\TH:i) to MySQL datetime (Y-m-d H:i:s)
$openDateMysql = str_replace('T', ' ', $openDate) . ':00';
$closeDateMysql = str_replace('T', ' ', $closeDate) . ':00';

$stmt = $pdo->prepare("
    INSERT INTO application_windows (name, open_date, close_date, max_capacity, is_active, created_by)
    VALUES (?, ?, ?, ?, 1, ?)
");
$stmt->execute([
    $name,
    $openDateMysql,
    $closeDateMysql,
    $maxCapacity !== '' ? (int) $maxCapacity : null,
    $userId,
]);

set_flash('success', 'Application window created successfully.');
redirect('/FMS/admin/application_windows.php');
