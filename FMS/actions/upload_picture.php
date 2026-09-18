<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}

if (empty($_SESSION['student_id'])) {
    redirect('/FMS/auth/login.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/student/profile.php');
}

$studentId = (int) $_SESSION['student_id'];

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
    set_flash('error', 'Please select a file to upload.');
    redirect('/FMS/student/profile.php');
}

$file = $_FILES['profile_picture'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    set_flash('error', 'File upload failed. Please try again.');
    redirect('/FMS/student/profile.php');
}

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

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
    set_flash('error', 'Only PNG and JPEG files are allowed.');
    redirect('/FMS/student/profile.php');
}

$extension = $allowedTypes[$mimeType];

$uploadDir = __DIR__ . '/../uploads/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = 'student_' . $studentId . '_' . time() . '.' . $extension;
$destination = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    set_flash('error', 'Failed to save the uploaded file. Please try again.');
    redirect('/FMS/student/profile.php');
}
$student = get_student($pdo, $studentId);
if ($student && !empty($student['profile_picture'])) {
    $oldFile = $uploadDir . $student['profile_picture'];
    if (file_exists($oldFile)) {
        unlink($oldFile);
    }
}

$stmt = $pdo->prepare("UPDATE students SET profile_picture = ? WHERE id = ?");
$stmt->execute([$filename, $studentId]);

set_flash('success', 'Profile picture updated successfully.');
redirect('/FMS/student/profile.php');
