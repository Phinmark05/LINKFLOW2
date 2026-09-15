<?php
/**
 * Reject Application Action
 *
 * Moves an application from 'submitted' or 'under_review' to 'rejected'.
 * Requires a rejection reason comment. Creates a review record with
 * decision='rejected', the current stage, and rejected_at timestamp.
 * Creates an audit log entry.
 *
 * Role-gated: only the user responsible for the application's current
 * review stage (or an admin) can reject it.
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
    set_flash('error', 'A rejection reason is required.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

$app = get_application($pdo, $applicationId);
if (!$app) {
    set_flash('error', 'Application not found.');
    redirect('/FMS/admin/applications.php');
}

// Only submitted or under_review applications can be rejected
if (!in_array($app['status'], ['submitted', 'under_review'], true)) {
    set_flash('error', 'This application cannot be rejected in its current status.');
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
    $newStatus = 'rejected';

    // Update application status and mark stage as done
    $stmt = $pdo->prepare("UPDATE applications SET status = ?, current_review_stage = 'done' WHERE id = ?");
    $stmt->execute([$newStatus, $applicationId]);

    // Add the rejection reason as a comment
    $commentId = add_comment($pdo, $userId, null, 'application', $applicationId, $commentText);

    // Create a review record with decision = rejected and stage
    $stmt = $pdo->prepare("
        INSERT INTO reviews (application_id, user_id, decision, status, stage, comment_id, rejected_at)
        VALUES (?, ?, 'rejected', ?, ?, ?, NOW())
    ");
    $stmt->execute([$applicationId, $userId, $newStatus, $currentStage, $commentId]);

    // Audit log
    log_application_action($pdo, $applicationId, $userId, 'application_rejected',
        ['status' => $oldStatus, 'stage' => $currentStage],
        ['status' => $newStatus, 'stage' => 'done', 'comment' => $commentText]
    );

    $pdo->commit();
} catch (PDOException $ex) {
    $pdo->rollBack();
    set_flash('error', 'An error occurred while rejecting the application.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

set_flash('success', 'Application has been rejected.');
redirect('/FMS/admin/view_application.php?id=' . $applicationId);
