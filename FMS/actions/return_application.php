<?php
/**
 * Return Application Action (Return for Correction)
 *
 * Moves an application from 'submitted' or 'under_review' to
 * 'returned_for_correction'. Creates a comment with the reason,
 * a review record (with the current stage), and an audit log entry.
 *
 * The student will then be able to edit and resubmit the application.
 * On resubmission, the stage resets to 'secretary'.
 *
 * Role-gated: only the user responsible for the application's current
 * review stage (or an admin) can return it.
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
$commentText   = trim($_POST['comment'] ?? '');

if ($applicationId === 0) {
    set_flash('error', 'Invalid application.');
    redirect('/FMS/admin/applications.php');
}

if ($commentText === '') {
    set_flash('error', 'A comment explaining the reason for return is required.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

$app = get_application($pdo, $applicationId);
if (!$app) {
    set_flash('error', 'Application not found.');
    redirect('/FMS/admin/applications.php');
}

// Only submitted or under_review applications can be returned
if (!in_array($app['status'], ['submitted', 'under_review'], true)) {
    set_flash('error', 'This application cannot be returned in its current status.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

// Role gate: only the user responsible for the current stage can act
$currentStage = $app['current_review_stage'] ?? 'secretary';
if (!can_user_act_on_stage($pdo, $userId, $currentStage)) {
    set_flash('error', 'You do not have permission to act on this stage (' . stage_label($currentStage) . ').');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

try {
    $pdo->beginTransaction();

    $oldStatus = $app['status'];
    $newStatus = 'returned_for_correction';

    // Update application status (stage stays the same — it resets on resubmit)
    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $applicationId]);

    // Add a comment so the student can see the reason
    $commentId = add_comment($pdo, $userId, null, 'application', $applicationId, $commentText);

    // Create a review record with the stage
    $stmt = $pdo->prepare("
        INSERT INTO reviews (application_id, user_id, decision, status, stage, comment_id)
        VALUES (?, ?, 'under_review', ?, ?, ?)
    ");
    $stmt->execute([$applicationId, $userId, $newStatus, $currentStage, $commentId]);

    // Audit log
    log_application_action($pdo, $applicationId, $userId, 'application_returned_for_correction',
        ['status' => $oldStatus, 'stage' => $currentStage],
        ['status' => $newStatus, 'stage' => $currentStage, 'comment' => $commentText]
    );

    $pdo->commit();
} catch (PDOException $ex) {
    $pdo->rollBack();
    set_flash('error', 'An error occurred while returning the application.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

set_flash('success', 'Application has been returned to the student for correction.');
redirect('/FMS/admin/view_application.php?id=' . $applicationId);
