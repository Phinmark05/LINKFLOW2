<?php
/**
 * Review Application Action (Mark as Under Review)
 *
 * This action is kept for backward compatibility. In the new multi-stage
 * workflow, the secretary uses "Forward" to move the application to the
 * field coordinator stage, which also sets the status to 'under_review'.
 *
 * If called directly, it redirects to the application view page.
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

if ($applicationId === 0) {
    set_flash('error', 'Invalid application.');
    redirect('/FMS/admin/applications.php');
}

$app = get_application($pdo, $applicationId);
if (!$app) {
    set_flash('error', 'Application not found.');
    redirect('/FMS/admin/applications.php');
}

// Only submitted applications can be moved to under_review
if ($app['status'] !== 'submitted') {
    set_flash('error', 'Only submitted applications can be moved to under review.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

// Role gate
$currentStage = $app['current_review_stage'] ?? 'secretary';
if (!can_user_act_on_stage($pdo, $userId, $currentStage)) {
    set_flash('error', 'You do not have permission to act on this stage (' . stage_label($currentStage) . ').');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

try {
    $pdo->beginTransaction();

    $oldStatus = $app['status'];
    $newStatus = 'under_review';

    // Update the application status
    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $applicationId]);

    // Create a review record in the reviews table
    $stmt = $pdo->prepare("
        INSERT INTO reviews (application_id, user_id, decision, status, stage)
        VALUES (?, ?, 'under_review', ?, ?)
    ");
    $stmt->execute([$applicationId, $userId, $newStatus, $currentStage]);

    // Create an audit log entry
    log_application_action($pdo, $applicationId, $userId, 'application_moved_to_review',
        ['status' => $oldStatus, 'stage' => $currentStage],
        ['status' => $newStatus, 'stage' => $currentStage]
    );

    $pdo->commit();
} catch (PDOException $ex) {
    $pdo->rollBack();
    set_flash('error', 'An error occurred while updating the application status.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

set_flash('success', 'Application is now under review.');
redirect('/FMS/admin/view_application.php?id=' . $applicationId);
