<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}

if (empty($_SESSION['user_id'])) {
    redirect('/FMS/auth/login.php');
}
if (!current_user_is_admin()) {
     set_flash('error', 'Only administrators can update student accounts.');
     redirect('/FMS/admin/students.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/students.php');
}

$studentId = (int) ($_POST['student_id'] ?? 0);
$editUrl = '/FMS/admin/edit_student.php?id=' . $studentId;

$student = $studentId > 0 ? get_student($pdo, $studentId) : null;
if (!$student) {
    set_flash('error', 'Student account not found.');
    redirect('/FMS/admin/students.php');
}

$fullName       = trim($_POST['full_name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$gender         = $_POST['gender'] ?? '';
$dob            = trim($_POST['dob'] ?? '');
$nationalityId  = $_POST['nationality_id'] ?? '';
$studyLevelId   = $_POST['study_level_id'] ?? '';
$institutionId  = $_POST['institution_id'] ?? '';
$courseOfStudy  = trim($_POST['course_of_study'] ?? '');
$status         = $_POST['status'] ?? $student['status'];
$newPassword     = $_POST['new_password'] ?? '';
$newPasswordConf = $_POST['new_password_confirm'] ?? '';

$errors = [];

if ($fullName === '') $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if ($gender !== '' && !in_array($gender, ['Male', 'Female', 'Other'], true)) $errors[] = 'Invalid gender.';
if ($dob !== '' && strtotime($dob) === false) $errors[] = 'Invalid date of birth.';
if (!in_array($status, ['active', 'suspended', 'graduated'], true)) $errors[] = 'Invalid student status.';
if ($institutionId !== '') {
    $stmt = $pdo->prepare('SELECT id FROM institutions WHERE id = ? AND deleted = 0');
    $stmt->execute([(int) $institutionId]);
    if (!$stmt->fetch()) $errors[] = 'The selected institution does not exist.';
}

$stmt = $pdo->prepare('SELECT id FROM students WHERE email = ? AND id != ?');
$stmt->execute([$email, $studentId]);
if ($stmt->fetch()) $errors[] = 'That email is already in use.';

if ($newPassword !== '') {
    if (strlen($newPassword) < 8) $errors[] = 'New password must be at least 8 characters.';
    if ($newPassword !== $newPasswordConf) $errors[] = 'New passwords do not match.';
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect($editUrl);
}

$params = [
    $fullName,
    $email,
    $gender !== '' ? $gender : null,
    $dob !== '' ? $dob : null,
    $nationalityId !== '' ? (int) $nationalityId : null,
    $studyLevelId !== '' ? (int) $studyLevelId : null,
    $institutionId !== '' ? (int) $institutionId : null,
    $courseOfStudy !== '' ? $courseOfStudy : null,
    $status,
];

$passwordSql = '';
if ($newPassword !== '') {
    $passwordSql = ', password = ?';
    $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
}

$params[] = $studentId;
$stmt = $pdo->prepare("UPDATE students SET
    full_name = ?, email = ?, gender = ?, dob = ?, nationality_id = ?,
    study_level_id = ?, institution_id = ?, course_of_study = ?, status = ?$passwordSql
    WHERE id = ?");
$stmt->execute($params);

set_flash('success', 'Student details updated successfully.');
redirect('/FMS/admin/students.php');
