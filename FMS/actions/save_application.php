<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/document_helpers.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}


if (empty($_SESSION['student_id'])) {
    redirect('/FMS/auth/login.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/student/application.php');
}

$studentId    = (int) $_SESSION['student_id'];
$action       = $_POST['action'] ?? 'draft';
$editAppId    = (int) ($_POST['application_id'] ?? 0);
$isEdit        = $editAppId > 0;


$student = get_student($pdo, $studentId);
if (!$student) {
    session_destroy();
    redirect('/FMS/auth/login.php');
}

$existingApp = null;
if ($isEdit) {
    $existingApp = get_application($pdo, $editAppId);
    if (!$existingApp || (int) $existingApp['student_id'] !== $studentId) {
        set_flash('error', 'You do not have permission to edit this application.');
        redirect('/FMS/student/my_application.php');
    }
    // Only drafts and returned-for-correction applications can be edited
    if (!in_array($existingApp['status'], ['draft', 'returned_for_correction'], true)) {
        set_flash('error', 'This application cannot be edited in its current status.');
        redirect('/FMS/student/my_application.php');
    }
}
function clean_tinymce_text(string $value): string
{
    $value = preg_replace('/<br\s*\/?>/i', "\n", $value);
    $value = preg_replace('/<\/p>/i', "\n", $value);
    $value = strip_tags($value);

    return trim($value);
}
// --- Collect form values ---
$windowId       = (int) ($_POST['application_window_id'] ?? 0);
$trainingTypeId = (int) ($_POST['training_type_id'] ?? 0);
$studyLevelId   = (int) ($_POST['study_level_id'] ?? 0);
$appType        = $_POST['application_type'] ?? 'initial';
$skillLevel     = $_POST['skill_level'] ?? 'beginner';
$interest   = clean_tinymce_text($_POST['interest_statement'] ?? '');
$reason     = clean_tinymce_text($_POST['reason_for_application'] ?? '');
$objectives = clean_tinymce_text($_POST['expected_learning_objectives'] ?? '');
$startDate      = $_POST['requested_start_date'] ?? '';
$endDate        = $_POST['requested_end_date'] ?? '';
$specializations = $_POST['specializations'] ?? [];
$documentTypes = get_document_types($pdo);
$uploadedFiles = $_FILES ?? [];
$windowValue = $windowId > 0 ? $windowId : null;
$trainingTypeValue = $trainingTypeId > 0 ? $trainingTypeId : null;
$studyLevelValue = $studyLevelId > 0 ? $studyLevelId : null;


if (!in_array($appType, ['initial', 'reapplication'], true)) $appType = 'initial';
if (!in_array($skillLevel, ['beginner', 'intermediate', 'advanced'], true)) $skillLevel = 'beginner';

$errors = [];

if ($action === 'submit') {
    if ($windowId === 0) $errors[] = 'Please select an application window.';
    if ($trainingTypeId === 0) $errors[] = 'Please select a training type.';
    if ($studyLevelId === 0) $errors[] = 'Please select a study level.';
    if ($interest === '') $errors[] = 'Interest statement is required.';
    if ($reason === '') $errors[] = 'Reason for application is required.';
    if ($objectives === '') $errors[] = 'Expected learning objectives are required.';
}

if ($startDate !== '' && $endDate !== '' && strtotime($endDate) < strtotime($startDate)) {
    $errors[] = 'Requested end date cannot be before the start date.';
}

if ($windowId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM application_windows WHERE id = ? AND is_active = 1 AND deleted = 0");
    $stmt->execute([$windowId]);
    $window = $stmt->fetch();
    
    if (!$window) {
        $errors[] = 'The selected application window is not available.';
    } else {
        if ($action === 'submit') {
            $timezone = new DateTimeZone('Africa/Dar_es_Salaam'); 
            
            $now = new DateTime('now', $timezone);
            $openDate = new DateTime($window['open_date'], $timezone);
            $closeDate = new DateTime($window['close_date'], $timezone);

            if ($openDate > $now) {
                $errors[] = 'This application window has not opened yet.';
            }
            if ($closeDate < $now) {
                $errors[] = 'This application window has already closed.';
            }
        }
    }
}
if ($trainingTypeId > 0) {
    $stmt = $pdo->prepare("SELECT id FROM training_types WHERE id = ? AND is_active = 1");
    $stmt->execute([$trainingTypeId]);
    if (!$stmt->fetch()) $errors[] = 'The selected training type is not available.';
}

if ($studyLevelId > 0) {
    $stmt = $pdo->prepare("SELECT id FROM study_levels WHERE id = ?");
    $stmt->execute([$studyLevelId]);
    if (!$stmt->fetch()) $errors[] = 'The selected study level does not exist.';
}

if ($action === 'submit') {
    $errors = array_merge($errors, validate_application_uploads($pdo, $documentTypes, $uploadedFiles, $editAppId, true));
}

if ($action === 'submit' && empty($specializations)) {
    $errors[] = 'Please select at least one specialization.';
}

if (!empty($specializations)) {
    foreach ($specializations as $spId) {
        $stmt = $pdo->prepare("SELECT id FROM specializations WHERE id = ? AND is_active = 1 AND deleted = 0");
        $stmt->execute([(int) $spId]);
        if (!$stmt->fetch()) {
            $errors[] = 'One or more selected specializations are invalid.';
            break;
        }
    }
}
if ($windowId > 0) {
    if ($isEdit) {
        $stmt = $pdo->prepare("
            SELECT id FROM applications
            WHERE student_id = ? AND application_window_id = ?
              AND id != ? AND status NOT IN ('rejected', 'cancelled')
        ");
        $stmt->execute([$studentId, $windowId, $editAppId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM applications
            WHERE student_id = ? AND application_window_id = ?
              AND status NOT IN ('rejected', 'cancelled')
        ");
        $stmt->execute([$studentId, $windowId]);
    }
    if ($stmt->fetch()) {
        $errors[] = 'You already have an active application for this window.';
    }
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    if ($isEdit) {
        redirect('/FMS/student/edit_application.php?id=' . $editAppId);
    } else {
        redirect('/FMS/student/application.php');
    }
}

// --- Determine the status ---
$isSubmit = ($action === 'submit');
$status   = $isSubmit ? 'submitted' : 'draft';
$submissionDate = $isSubmit ? date('Y-m-d H:i:s') : null;

// --- Use a transaction for the application + specializations ---
try {
    $pdo->beginTransaction();

    if ($isEdit) {
         $oldValues = [
            'status'                  => $existingApp['status'],
            'window_id'               => $existingApp['application_window_id'],
            'training_type_id'        => $existingApp['training_type_id'],
            'study_level_id'          => $existingApp['study_level_id'],
            'interest_statement'      => $existingApp['interest_statement'],
            'reason_for_application'   => $existingApp['reason_for_application'],
            'expected_learning_objectives' => $existingApp['expected_learning_objectives'],
        ];

        $stmt = $pdo->prepare("
            UPDATE applications SET
                application_window_id = ?, training_type_id = ?, study_level_id = ?,
                application_type = ?, skill_level = ?,
                interest_statement = ?, reason_for_application = ?, expected_learning_objectives = ?,
                requested_start_date = ?, requested_end_date = ?,
                status = ?, submission_date = ?,
                current_review_stage = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $windowId, $trainingTypeId, $studyLevelId, $appType, $skillLevel,
            $interest, $reason, $objectives,
            $startDate !== '' ? $startDate : null,
            $endDate !== '' ? $endDate : null,
            $status, $submissionDate,
            $isSubmit ? 'secretary' : ($existingApp['current_review_stage'] ?? 'secretary'),
            $editAppId,
        ]);

        $applicationId = $editAppId;

        $pdo->prepare("DELETE FROM application_specializations WHERE application_id = ?")->execute([$applicationId]);
        if (!empty($specializations)) {
            $spStmt = $pdo->prepare("INSERT INTO application_specializations (application_id, specialization_id) VALUES (?, ?)");
            foreach ($specializations as $spId) {
                $spStmt->execute([$applicationId, (int) $spId]);
            }
        }

        save_application_uploads($pdo, $applicationId, $documentTypes, $uploadedFiles);

        $auditAction = $isSubmit ? 'application_submitted' : 'application_updated';
        $newValues = ['status' => $status, 'window_id' => $windowId, 'training_type_id' => $trainingTypeId];
        log_application_action($pdo, $applicationId, null, $auditAction, $oldValues, $newValues);
    } else {
        // --- Insert new application ---
        $refNumber = generate_reference_number();

        $stmt = $pdo->prepare("
            INSERT INTO applications
                (student_id, application_window_id, training_type_id, study_level_id,
                 reference_number, application_type, skill_level,
                 interest_statement, reason_for_application, expected_learning_objectives,
                 requested_start_date, requested_end_date, status, submission_date,
                 current_review_stage)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $studentId, $windowId, $trainingTypeId, $studyLevelId,
            $refNumber, $appType, $skillLevel,
            $interest, $reason, $objectives,
            $startDate !== '' ? $startDate : null,
            $endDate !== '' ? $endDate : null,
            $status, $submissionDate,
            'secretary',
        ]);

        $applicationId = (int) $pdo->lastInsertId();

        if (!empty($specializations)) {
            $spStmt = $pdo->prepare("INSERT INTO application_specializations (application_id, specialization_id) VALUES (?, ?)");
            foreach ($specializations as $spId) {
                $spStmt->execute([$applicationId, (int) $spId]);
            }
        }

        save_application_uploads($pdo, $applicationId, $documentTypes, $uploadedFiles);

        $auditAction = $isSubmit ? 'application_submitted' : 'application_created';
        $newValues = [
            'reference_number' => $refNumber,
            'status'           => $status,
            'window_id'        => $windowId,
            'training_type_id' => $trainingTypeId,
            'study_level_id'   => $studyLevelId,
        ];
        log_application_action($pdo, $applicationId, null, $auditAction, null, $newValues);
    }

    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    set_flash('error', 'An error occurred while saving your application. Please try again.');
    if ($isEdit) {
        redirect('/FMS/student/edit_application.php?id=' . $editAppId);
    } else {
        redirect('/FMS/student/application.php');
    }
}

if ($isSubmit) {
    set_flash('success', $isEdit
        ? 'Your application has been submitted successfully.'
        : 'Your application has been submitted successfully. Reference: ' . ($refNumber ?? ''));
} else {
    set_flash('success', $isEdit
        ? 'Your application has been updated.'
        : 'Your application has been saved as a draft.');
}
redirect('/FMS/student/my_application.php');
