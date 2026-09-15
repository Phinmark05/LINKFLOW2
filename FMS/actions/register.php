<?php
/**
 * Register Action
 *
 * Processes the student registration form. Validates all input,
 * checks for duplicate registration No / Email, hashes the password,
 * and inserts a new record into the `students` table.
 *
 * Only students self-register. Staff accounts are created by admins.
 */
require_once __DIR__ . '/../includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/register.php');
}

// Verify CSRF token
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission. Please try again.');
    redirect('/FMS/auth/register.php');
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
$courseOfStudy  = trim($_POST['course_of_study'] ?? '');

// --- Server-side validation ---
$errors = [];

if ($registrationNo === '') $errors[] = 'Registration number is required.';
if ($fullName === '') $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match.';

// Validate gender if provided
if ($gender !== '' && !in_array($gender, ['Male', 'Female', 'Other'], true)) {
    $errors[] = 'Invalid gender value.';
}

// Validate date of birth if provided
if ($dob !== '' && strtotime($dob) === false) {
    $errors[] = 'Invalid date of birth.';
}

// Check for duplicate registration number
$stmt = $pdo->prepare("SELECT id FROM students WHERE registration_no = ?");
$stmt->execute([$registrationNo]);
if ($stmt->fetch()) {
    $errors[] = 'A student with this registration number already exists.';
}

// Check for duplicate email
$stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $errors[] = 'A student with this email already exists.';
}

// If there were any validation errors, send the user back
if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/auth/register.php');
}

// --- Hash the password securely ---
// password_hash() uses bcrypt by default with a random salt.
// We never store plain-text passwords.
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// --- Insert the new student record ---
// Foreign-key fields are stored as NULL if not provided.
$stmt = $pdo->prepare("
    INSERT INTO students
        (registration_no, email, password, full_name, gender, nationality_id, dob, study_level_id, course_of_study)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
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
    $courseOfStudy !== '' ? $courseOfStudy : null,
]);

// Registration successful — redirect to login
set_flash('success', 'Registration successful. You can now log in.');
redirect('/FMS/auth/login.php');
