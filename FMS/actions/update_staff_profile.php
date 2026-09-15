<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}
if (empty($_SESSION['user_id'])) {
    redirect('/FMS/auth/login.php');
}
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/staff/profile.php');
}

$userId = (int) $_SESSION['user_id'];
$user = get_user($pdo, $userId);
if (!$user) {
    session_destroy();
    redirect('/FMS/auth/login.php');
}

// Admins should use the admin system, not this form
if (user_has_role($pdo, $userId, 'admin')) {
    redirect('/FMS/admin/dashboard.php');
}

// --- Collect submitted values ---
$fullName       = trim($_POST['full_name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$phoneNumber   = trim($_POST['phone_number'] ?? '');
$newPassword     = $_POST['new_password'] ?? '';
$newPasswordConf = $_POST['new_password_confirm'] ?? '';

// The password card is submitted separately from the profile details form.
if (($_POST['action'] ?? '') === 'password') {
    if ($newPassword === '') {
        set_flash('error', 'Please enter a new password.');
        redirect('/FMS/staff/profile.php');
    }

    if (strlen($newPassword) < 6) {
        set_flash('error', 'New password must be at least 6 characters.');
        redirect('/FMS/staff/profile.php');
    }

    if ($newPassword !== $newPasswordConf) {
        set_flash('error', 'New passwords do not match.');
        redirect('/FMS/staff/profile.php');
    }

    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    set_flash('success', 'Password changed successfully.');
    redirect('/FMS/staff/profile.php');
}

// --- Validate ---
$errors = [];

if ($fullName === '') $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

// Check email uniqueness (excluding current user)
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $userId]);
if ($stmt->fetch()) $errors[] = 'That email is already in use.';

// Validate password change if provided
if ($newPassword !== '') {
    if (strlen($newPassword) < 6) $errors[] = 'New password must be at least 6 characters.';
    if ($newPassword !== $newPasswordConf) $errors[] = 'New passwords do not match.';
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/staff/profile.php');
}

// --- Build the update query ---
if ($newPassword !== '') {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        UPDATE users SET
            full_name = ?, email = ?, phone_number = ?, password = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $fullName, $email,
        $phoneNumber !== '' ? $phoneNumber : null,
        $hashedPassword,
        $userId,
    ]);
} else {
    $stmt = $pdo->prepare("
        UPDATE users SET
            full_name = ?, email = ?, phone_number = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $fullName, $email,
        $phoneNumber !== '' ? $phoneNumber : null,
        $userId,
    ]);
}

set_flash('success', 'Profile updated successfully.');
redirect('/FMS/staff/profile.php');
