<?php
/**
 * Upload Profile Picture Action
 *
 * Handles profile picture upload for students only.
 * Validates: file type (PNG or JPEG only), max size (2MB).
 * Stores the file in /FMS/uploads/profiles/ with a unique name.
 * Updates the students.profile_picture column with the filename.
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

// Check if a file was uploaded
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
    set_flash('error', 'Please select a file to upload.');
    redirect('/FMS/student/profile.php');
}

$file = $_FILES['profile_picture'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    set_flash('error', 'File upload failed. Please try again.');
    redirect('/FMS/student/profile.php');
}

// Validate file size (2MB max = 2097152 bytes)
$maxSize = 2 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    set_flash('error', 'File is too large. Maximum size is 2MB.');
    redirect('/FMS/student/profile.php');
}

// Validate file type using the actual file content (not just the extension)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$allowedTypes = [
    'image/png'  => 'png',
    'image/jpeg' => 'jpg',
];

if (!isset($allowedTypes[$mimeType])) {
    set_flash('error', 'Only PNG and JPEG files are allowed.');
    redirect('/FMS/student/profile.php');
}

// Double-check the extension matches
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
    set_flash('error', 'Only PNG and JPEG files are allowed.');
    redirect('/FMS/student/profile.php');
}

// Normalize extension
$extension = $allowedTypes[$mimeType];

// Create upload directory if it doesn't exist
$uploadDir = __DIR__ . '/../uploads/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate a unique filename
$filename = 'student_' . $studentId . '_' . time() . '.' . $extension;
$destination = $uploadDir . $filename;

// Move the uploaded file
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    set_flash('error', 'Failed to save the uploaded file. Please try again.');
    redirect('/FMS/student/profile.php');
}

// Get the current student record to delete the old picture
$student = get_student($pdo, $studentId);
if ($student && !empty($student['profile_picture'])) {
    $oldFile = $uploadDir . $student['profile_picture'];
    if (file_exists($oldFile)) {
        unlink($oldFile);
    }
}

// Update the database with the new filename
$stmt = $pdo->prepare("UPDATE students SET profile_picture = ? WHERE id = ?");
$stmt->execute([$filename, $studentId]);

set_flash('success', 'Profile picture updated successfully.');
redirect('/FMS/student/profile.php');
