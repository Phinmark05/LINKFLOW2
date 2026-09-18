<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
    redirect('/FMS/auth/login.php');
}

$adminId = (int) $_SESSION['user_id'];
if (!user_has_role($pdo, $adminId, 'admin')) {
    set_flash('error', 'Access denied. Only administrators can update staff accounts.');
    redirect('/FMS/index.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/supervisors.php');
}

$userId = (int) ($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone_number'] ?? '');
$designation = trim($_POST['designation'] ?? '');
$roleId = (int) ($_POST['role_id'] ?? 0);
$status = $_POST['status'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$newPasswordConfirm = $_POST['new_password_confirm'] ?? '';
$assignableRoles = ['secretary', 'field_coordinator', 'hod', 'placement_officer', 'academic_supervisor', 'industrial_supervisor', 'supervisor'];

$user = $userId > 0 ? get_user($pdo, $userId, true) : null;
$errors = [];
if (!$user) $errors[] = 'Staff account not found.';

if ($action === 'password') {
    if ($newPassword === '') $errors[] = 'Please enter a new password.';
    if (strlen($newPassword) < 6) $errors[] = 'New password must be at least 6 characters.';
    if ($newPassword !== $newPasswordConfirm) $errors[] = 'New passwords do not match.';

    if ($errors) {
        set_flash('error', implode(' ', $errors));
        redirect('/FMS/admin/edit_supervisor.php?id=' . $userId);
    }

    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

    set_flash('success', 'Password changed successfully.');
    redirect('/FMS/admin/edit_supervisor.php?id=' . $userId);
}

if ($fullName === '') $errors[] = 'Full name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if ($phone !== '' && strlen($phone) < 9) $errors[] = 'Phone number must be at least 9 characters.';
if (!in_array($status, ['active', 'suspended'], true)) $errors[] = 'Invalid account status.';
if ($newPassword !== '') {
    if (strlen($newPassword) < 6) $errors[] = 'New password must be at least 6 characters.';
    if ($newPassword !== $newPasswordConfirm) $errors[] = 'New passwords do not match.';
}

if ($user) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) $errors[] = 'That email is already in use.';
}

$stmt = $pdo->prepare('SELECT id, name FROM roles WHERE id = ?');
$stmt->execute([$roleId]);
$role = $stmt->fetch();
if (!$role || !in_array($role['name'], $assignableRoles, true)) $errors[] = 'Invalid staff role.';

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/admin/edit_supervisor.php?id=' . $userId);
}

$passwordSql = $newPassword !== '' ? ', password = ?' : '';
$params = [$fullName, $email, $phone !== '' ? $phone : null, $designation !== '' ? $designation : null, $status];
if ($newPassword !== '') $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
$params[] = $userId;

$stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone_number = ?, designation = ?, status = ?$passwordSql WHERE id = ?");
$stmt->execute($params);

$pdo->beginTransaction();
$pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$userId]);
$pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$userId, $role['id']]);
$pdo->commit();

set_flash('success', 'Staff account updated successfully.');
redirect('/FMS/admin/supervisors.php');
