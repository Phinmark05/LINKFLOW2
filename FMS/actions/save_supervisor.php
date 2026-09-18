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
    redirect('/FMS/admin/supervisors.php');
}

$username    = trim($_POST['username'] ?? '');
$fullName    = trim($_POST['full_name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$password    = $_POST['password'] ?? '';
$phone       = trim($_POST['phone_number'] ?? '');
$designation = trim($_POST['designation'] ?? '');
$roleId      = (int) ($_POST['role_id'] ?? 0);

$errors = [];
if ($username === '') $errors[] = 'Username is required.';
if ($fullName === '') $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if ($phone !== '' && strlen($phone) < 9) $errors[] = 'Phone number must be at least 9 characters.';
if ($roleId === 0) $errors[] = 'Please select a supervisor type.';

// Check for duplicate username
if ($username !== '') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) $errors[] = 'That username is already taken.';
}

// Check for duplicate email
if ($email !== '') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = 'That email is already in use.';
}

// Validate the role exists and is an assignable staff role
$assignableRoles = ['secretary', 'field_coordinator', 'hod', 'placement_officer', 'academic_supervisor', 'industrial_supervisor', 'supervisor'];
if ($roleId > 0) {
    $stmt = $pdo->prepare("SELECT id, name FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    $role = $stmt->fetch();
    if (!$role) {
        $errors[] = 'The selected role does not exist.';
    } elseif (!in_array($role['name'], $assignableRoles, true)) {
        $errors[] = 'That role cannot be assigned from this page.';
    }
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/admin/supervisors.php');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    // Insert the user record
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, phone_number, designation)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $username, $email, $hashedPassword, $fullName,
        $phone !== '' ? $phone : null,
        $designation !== '' ? $designation : null,
    ]);

    $newUserId = (int) $pdo->lastInsertId();

    // Assign the supervisor role via user_roles
    $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    $stmt->execute([$newUserId, $roleId]);

    $pdo->commit();
} catch (PDOException $ex) {
    $pdo->rollBack();
    set_flash('error', 'An error occurred while creating the supervisor.');
    redirect('/FMS/admin/supervisors.php');
}

set_flash('success', 'Staff account created successfully.');
redirect('/FMS/admin/supervisors.php');
