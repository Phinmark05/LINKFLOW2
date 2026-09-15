<?php
/** Secure application-document helpers. Files are stored with random names and never served directly. */
const APPLICATION_DOCUMENT_MAX_BYTES = 2097152;
const APPLICATION_DOCUMENT_ALLOWED_MIMES = ['application/pdf', 'image/jpeg', 'image/png'];

function get_document_types(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM document_types WHERE is_active = 1 ORDER BY is_required DESC, id ASC");
    return $stmt->fetchAll();
}

function get_application_documents(PDO $pdo, int $applicationId): array
{
    $stmt = $pdo->prepare("SELECT ad.*, dt.name AS document_type_name, dt.is_required FROM application_documents ad JOIN document_types dt ON dt.id = ad.document_type_id WHERE ad.application_id = ? ORDER BY dt.is_required DESC, dt.id ASC, ad.uploaded_at DESC");
    $stmt->execute([$applicationId]);
    return $stmt->fetchAll();
}

function application_document_storage_path(): string
{
    $path = __DIR__ . '/../storage/application_documents/';
    if (!is_dir($path)) {
        mkdir($path, 0750, true);
    }
    return $path;
}

function uploaded_file_error_message(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The selected file is larger than 2 MB.',
        UPLOAD_ERR_PARTIAL => 'The file upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'No file selected.',
        default => 'The file could not be uploaded.'
    };
}

/** @return array<int, string> */
function validate_application_uploads(PDO $pdo, array $documentTypes, array $files, int $applicationId = 0, bool $requireRequired = false): array
{
    $errors = [];
    $existing = [];
    if ($applicationId > 0) {
        foreach (get_application_documents($pdo, $applicationId) as $doc) {
            $existing[(int) $doc['document_type_id']] = true;
        }
    }
    foreach ($documentTypes as $type) {
        $typeId = (int) $type['id'];
        $file = $files['document_' . $typeId] ?? null;
        $hasFile = is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($requireRequired && (int) $type['is_required'] === 1 && !$hasFile && empty($existing[$typeId])) {
            $errors[] = $type['name'] . ' is required.';
            continue;
        }
        if (!$hasFile) continue;
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $type['name'] . ': ' . uploaded_file_error_message((int) $file['error']);
            continue;
        }
        if ((int) $file['size'] < 1 || (int) $file['size'] > APPLICATION_DOCUMENT_MAX_BYTES) {
            $errors[] = $type['name'] . ' must be between 1 byte and 2 MB.';
            continue;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = json_decode((string) ($type['allowed_mime_types'] ?? '[]'), true) ?: APPLICATION_DOCUMENT_ALLOWED_MIMES;
        $allowed = array_values(array_intersect($allowed, APPLICATION_DOCUMENT_ALLOWED_MIMES));
        if (!in_array($mime, $allowed, true)) {
            $errors[] = $type['name'] . ' must be a PDF, JPEG, or PNG file.';
        }
    }
    return $errors;
}

/** Stores valid uploads and replaces the student's previous file for that document type. */
function save_application_uploads(PDO $pdo, int $applicationId, array $documentTypes, array $files): void
{
    $storage = application_document_storage_path();
    foreach ($documentTypes as $type) {
        $typeId = (int) $type['id'];
        $file = $files['document_' . $typeId] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extension = match ($mime) { 'application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', default => throw new RuntimeException('Unsupported file type.') };
        $stored = bin2hex(random_bytes(24)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $storage . $stored)) throw new RuntimeException('Unable to save uploaded document.');
        $oldStmt = $pdo->prepare('SELECT stored_filename FROM application_documents WHERE application_id = ? AND document_type_id = ?');
        $oldStmt->execute([$applicationId, $typeId]);
        $oldFiles = $oldStmt->fetchAll(PDO::FETCH_COLUMN);
        $pdo->prepare('DELETE FROM application_documents WHERE application_id = ? AND document_type_id = ?')->execute([$applicationId, $typeId]);
        $insert = $pdo->prepare('INSERT INTO application_documents (application_id, document_type_id, label, original_filename, stored_filename, mime_type, file_size_bytes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $label = $type['name'] === 'Supportive Document' ? trim((string) ($_POST['document_label_' . $typeId] ?? '')) : null;
        $insert->execute([$applicationId, $typeId, $label !== '' ? substr($label, 0, 150) : null, substr(basename((string) $file['name']), 0, 255), $stored, $mime, (int) $file['size']]);
        foreach ($oldFiles as $old) if (is_string($old)) @unlink($storage . basename($old));
    }
}
