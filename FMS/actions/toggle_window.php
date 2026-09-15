<?php
/**
 * Toggle Application Window Active/Inactive Action
 *
 * Switches the is_active flag for an application window.
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

$windowId = (int) ($_POST['window_id'] ?? 0);
if ($windowId === 0) {
    set_flash('error', 'Invalid window.');
    redirect('/FMS/admin/application_windows.php');
}

$stmt = $pdo->prepare("SELECT is_active FROM application_windows WHERE id = ?");
$stmt->execute([$windowId]);
$window = $stmt->fetch();
if (!$window) {
    set_flash('error', 'Window not found.');
    redirect('/FMS/admin/application_windows.php');
}

$newActive = $window['is_active'] ? 0 : 1;
$stmt = $pdo->prepare("UPDATE application_windows SET is_active = ? WHERE id = ?");
$stmt->execute([$newActive, $windowId]);

set_flash('success', $newActive ? 'Window activated.' : 'Window deactivated.');
redirect('/FMS/admin/application_windows.php');
