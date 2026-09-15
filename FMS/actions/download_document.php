<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/document_helpers.php';
if (empty($_SESSION['student_id']) && empty($_SESSION['user_id'])) { redirect('/FMS/auth/login.php'); }
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT ad.*, a.student_id FROM application_documents ad JOIN applications a ON a.id = ad.application_id WHERE ad.id = ?");
$stmt->execute([$id]);
$document = $stmt->fetch();
if (!$document || (empty($_SESSION['user_id']) && (int) $document['student_id'] !== (int) $_SESSION['student_id'])) { http_response_code(403); exit('Forbidden'); }
$path = application_document_storage_path() . basename($document['stored_filename']);
if (!is_file($path)) { http_response_code(404); exit('Document not found'); }
header('Content-Type: ' . $document['mime_type']);
header('Content-Disposition: attachment; filename="' . addcslashes(basename($document['original_filename']), '"\\') . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
