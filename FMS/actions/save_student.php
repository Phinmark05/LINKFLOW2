<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/admin/students.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission. Please try again.');
    redirect('/FMS/admin/students.php');
}

// --- Collect and trim all submitted values ---
$registrationNo = trim($_POST['registration_no'] ?? '');
$fullName       = trim($_POST['full_name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$password        = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
$gender         = $_POST['gender'] ?? '';
$dob            = $_POST['dob'] ?? '';
$nationalityId  = $_POST['nationality_id'] ?? '';
$studyLevelId   = $_POST['study_level_id'] ?? '';
$institutionId  = $_POST['institution_id'] ?? '';
$courseOfStudy  = trim($_POST['course_of_study'] ?? '');

$errors = [];

if ($registrationNo === '') $errors[] = 'Registration number is required.';
if ($fullName === '') $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match.';

if ($gender !== '' && !in_array($gender, ['Male', 'Female', 'Other'], true)) {
    $errors[] = 'Invalid gender value.';
}

if ($dob !== '' && strtotime($dob) === false) {
    $errors[] = 'Invalid date of birth.';
}

if ($institutionId === '') {
    $errors[] = 'Please select an institution.';
} else {
    $stmt = $pdo->prepare('SELECT id FROM institutions WHERE id = ? AND is_active = 1 AND deleted = 0');
    $stmt->execute([(int) $institutionId]);
    if (!$stmt->fetch()) $errors[] = 'The selected institution is not available.';
}

$stmt = $pdo->prepare("SELECT id FROM students WHERE registration_no = ?");
$stmt->execute([$registrationNo]);
if ($stmt->fetch()) {
    $errors[] = 'A student with this registration number already exists.';
}

$stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $errors[] = 'A student with this email already exists.';
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/auth/register.php');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO students
        (registration_no, email, password, full_name, gender, nationality_id, dob, study_level_id, institution_id, course_of_study)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $registrationNo,
    $email,
    $hashedPassword,
    $fullName,
    $gender !== '' ? $gender : null,
    $nationalityId !== '' ? (int) $nationalityId : null,
    $dob !== '' ? $dob : null,
    $studyLevelId !== '' ? (int) $studyLevelId : null,
    $institutionId !== '' ? (int) $institutionId : null,
    $courseOfStudy !== '' ? $courseOfStudy : null,
]);

// Registration successful — redirect to login
set_flash('success', 'The student was added successfully.');
redirect('/FMS/admin/students.php');
