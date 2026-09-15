<?php
/**
 * Create Placement Action
 *
 * Creates a placement for an accepted application. This action:
 *   1. Validates that the application exists, belongs to a student, and is 'accepted'
 *   2. Validates that no placement already exists for this application
 *   3. Validates the department
 *   4. Validates supervisors are valid users
 *   5. Validates dates (end >= start)
 *   6. Uses a transaction to: create the placement, update application status
 *      to 'placement_assigned', and create a placement audit log entry
 *
 * The placement has a UNIQUE constraint on application_id, so the database
 * also prevents duplicate placements at the database level.
 */
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}

if (empty($_SESSION['user_id'])) {
    redirect('/FMS/auth/login.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/applications.php');
}

$userId        = (int) $_SESSION['user_id'];
$applicationId = (int) ($_POST['application_id'] ?? 0);
$deptId        = (int) ($_POST['department_id'] ?? 0);
$academicSup   = $_POST['academic_supervisor_id'] ?? '';
$industrialSup = $_POST['industrial_supervisor_id'] ?? '';
$startDate     = $_POST['start_date'] ?? '';
$endDate       = $_POST['end_date'] ?? '';

if ($applicationId === 0) {
    set_flash('error', 'Invalid application.');
    redirect('/FMS/admin/applications.php');
}

$errors = [];

// --- Validate application ---
$app = get_application($pdo, $applicationId);
if (!$app) {
    set_flash('error', 'Application not found.');
    redirect('/FMS/admin/applications.php');
}

if ($app['status'] !== 'accepted') {
    $errors[] = 'Only accepted applications can have a placement assigned.';
}

// Role gate: only placement_officer stage (or admin) can create placement
$currentStage = $app['current_review_stage'] ?? 'secretary';
if (!can_user_act_on_stage($pdo, $userId, $currentStage)) {
    set_flash('error', 'You do not have permission to act on this stage (' . stage_label($currentStage) . '). Only the Placement Officer or an admin can assign placements.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}
if ($currentStage !== 'placement_officer') {
    $errors[] = 'Placements can only be assigned when the application reaches the Placement Officer stage.';
}

// Check for existing placement (database also enforces this via UNIQUE constraint)
if (get_placement_by_application($pdo, $applicationId)) {
    $errors[] = 'A placement already exists for this application.';
}

// --- Validate department ---
if ($deptId === 0) {
    $errors[] = 'Please select a department.';
} else {
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE id = ? AND is_active = 1");
    $stmt->execute([$deptId]);
    if (!$stmt->fetch()) {
        $errors[] = 'The selected department does not exist or is inactive.';
    }
}

// --- Validate supervisors (if provided, must be active users) ---
$academicSupId = $academicSup !== '' ? (int) $academicSup : null;
$industrialSupId = $industrialSup !== '' ? (int) $industrialSup : null;

if ($academicSupId !== null) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND status = 'active' AND deleted_at IS NULL");
    $stmt->execute([$academicSupId]);
    if (!$stmt->fetch()) $errors[] = 'The academic supervisor is not a valid active user.';
}
if ($industrialSupId !== null) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND status = 'active' AND deleted_at IS NULL");
    $stmt->execute([$industrialSupId]);
    if (!$stmt->fetch()) $errors[] = 'The industrial supervisor is not a valid active user.';
}

// --- Validate dates ---
if (empty($startDate) || empty($endDate)) {
    $errors[] = 'Start and end dates are required.';
} elseif (strtotime($endDate) < strtotime($startDate)) {
    $errors[] = 'End date cannot be before the start date.';
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

// --- Use a transaction: create placement + update application status + audit log ---
try {
    $pdo->beginTransaction();

    // 1. Create the placement record
    $stmt = $pdo->prepare("
        INSERT INTO placements
            (application_id, department_id,
             academic_supervisor_id, industrial_supervisor_id,
             start_date, end_date, status)
        VALUES (?, ?, ?, ?, ?, ?, 'completed')
    ");
    $stmt->execute([
        $applicationId, $deptId,
        $academicSupId, $industrialSupId,
        $startDate, $endDate,
    ]);

    $placementId = (int) $pdo->lastInsertId();

    // 2. Update the application status to 'placement_assigned' and mark stage as done
    $oldStatus = $app['status'];
    $newStatus = 'placement_assigned';
    $stmt = $pdo->prepare("UPDATE applications SET status = ?, current_review_stage = 'done' WHERE id = ?");
    $stmt->execute([$newStatus, $applicationId]);

    // 3. Create a placement audit log entry
    $newValues = [
        'placement_id'           => $placementId,
        'department_id'          => $deptId,
        'academic_supervisor_id' => $academicSupId,
        'industrial_supervisor_id' => $industrialSupId,
        'start_date'             => $startDate,
        'end_date'               => $endDate,
        'status'                 => 'pending',
    ];
    log_placement_action($pdo, $placementId, $userId, 'placement_created', null, $newValues);

    // 4. Create an application audit log entry for the status change
    log_application_action($pdo, $applicationId, $userId, 'application_placement_assigned', ['status' => $oldStatus], ['status' => $newStatus, 'placement_id' => $placementId]);

    $pdo->commit();
} catch (PDOException $ex) {
    // If any part fails, roll everything back
    $pdo->rollBack();
    set_flash('error', 'An error occurred while creating the placement.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

set_flash('success', 'Placement has been created and the student has been notified via the application status change.');
redirect('/FMS/admin/view_application.php?id=' . $applicationId);
