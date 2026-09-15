<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/document_helpers.php';

if (empty($_SESSION['student_id'] ) && empty($_SESSION['user_id'])) {
    redirect('/FMS/auth/login.php');
}

$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$documentId = (int) ($documentId ?: 0);

if ($documentId <= 0) {
    http_response_code(400 );
    exit('Invalid document ID.');
}

$stmt = $pdo->prepare("
    SELECT 
        ad.*,
        a.student_id
    FROM application_documents ad
    INNER JOIN applications a ON a.id = ad.application_id
    WHERE ad.id = ?
    LIMIT 1
");

$stmt->execute([$documentId]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    http_response_code(404 );
    exit('Document not found.');
}

$isStaff = !empty($_SESSION['user_id']);

$isOwner = !empty($_SESSION['student_id'])
    && (int) $document['student_id'] === (int) $_SESSION['student_id'];

if (!$isStaff && !$isOwner) {
    http_response_code(403 );
    exit('Forbidden.');
}

$allowedMimeTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
];

if (!in_array($document['mime_type'], $allowedMimeTypes, true)) {
    http_response_code(415 );
    exit('This document type cannot be previewed.');
}

$storagePath = __DIR__ . '/../storage/application_documents/';
$storedFilename = basename($document['stored_filename']);
$filePath = $storagePath . $storedFilename;

if (!is_file($filePath)) {
    http_response_code(404 );
    exit('Stored document file not found.');
}

header('Content-Type: ' . $document['mime_type']);
header(
    'Content-Disposition: inline; filename="' .
    addcslashes(basename($document['original_filename']), '"\\') .
    '"'
);
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');

readfile($filePath);
exit;
