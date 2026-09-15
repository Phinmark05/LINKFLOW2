<?php
/**
 * Update Profile Action
 *
 * Processes the student profile form. Updates allowed fields in the
 * `students` table. If a new password is provided, it is hashed and
 * updated. Otherwise the existing password is kept.
 */
require_once __DIR__ . '/../includes/functions.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}

// Must be a logged-in student
if (empty($_SESSION['student_id'])) {
    redirect('/FMS/auth/login.php');
}

// Verify CSRF token
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/student/profile.php');
}

$studentId = (int) $_SESSION['student_id'];

// Load current student record
$student = get_student($pdo, $studentId);
if (!$student) {
    session_destroy();
    redirect('/FMS/auth/login.php');
}

// --- Collect submitted values ---
$fullName       = trim($_POST['full_name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$gender         = $_POST['gender'] ?? '';
$dob            = $_POST['dob'] ?? '';
$nationalityId  = $_POST['nationality_id'] ?? '';
$studyLevelId   = $_POST['study_level_id'] ?? '';
$courseOfStudy  = trim($_POST['course_of_study'] ?? '');
$newPassword     = $_POST['new_password'] ?? '';
$newPasswordConf = $_POST['new_password_confirm'] ?? '';
$isPasswordChange = ($_POST['form_type'] ?? '') === 'password';

// --- Validate ---
$errors = [];

if (!$isPasswordChange) {
    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($gender !== '' && !in_array($gender, ['Male', 'Female', 'Other'], true)) $errors[] = 'Invalid gender.';
    if ($dob !== '' && strtotime($dob) === false) $errors[] = 'Invalid date of birth.';

    // Check email uniqueness (excluding current student)
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $stmt->execute([$email, $studentId]);
    if ($stmt->fetch()) $errors[] = 'That email is already in use.';
}

// Validate password change if provided
if ($newPassword !== '') {
    if (strlen($newPassword) < 6) $errors[] = 'New password must be at least 6 characters.';
    if ($newPassword !== $newPasswordConf) $errors[] = 'New passwords do not match.';
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/student/profile.php');
}

if ($isPasswordChange) {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $studentId]);

    set_flash('success', 'Password changed successfully.');
    redirect('/FMS/student/profile.php');
}

// --- Build the update query ---
// We use named parameters for clarity. Password is only updated if provided.
if ($newPassword !== '') {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        UPDATE students SET
            full_name = ?, email = ?, gender = ?, dob = ?,
            nationality_id = ?, study_level_id = ?, course_of_study = ?,
            password = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $fullName, $email,
        $gender !== '' ? $gender : null,
        $dob !== '' ? $dob : null,
        $nationalityId !== '' ? (int) $nationalityId : null,
        $studyLevelId !== '' ? (int) $studyLevelId : null,
        $courseOfStudy !== '' ? $courseOfStudy : null,
        $hashedPassword,
        $studentId,
    ]);
} else {
    // No password change — update everything except password
    $stmt = $pdo->prepare("
        UPDATE students SET
            full_name = ?, email = ?, gender = ?, dob = ?,
            nationality_id = ?, study_level_id = ?, course_of_study = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $fullName, $email,
        $gender !== '' ? $gender : null,
        $dob !== '' ? $dob : null,
        $nationalityId !== '' ? (int) $nationalityId : null,
        $studyLevelId !== '' ? (int) $studyLevelId : null,
        $courseOfStudy !== '' ? $courseOfStudy : null,
        $studentId,
    ]);
}

set_flash('success', 'Profile updated successfully.');
redirect('/FMS/student/profile.php');
