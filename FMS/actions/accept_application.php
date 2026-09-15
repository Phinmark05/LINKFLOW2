<?php
/**
 * Accept (Approve) Application Action
 *
 * Moves an application from 'under_review' to 'accepted'.
 * Only the HOD (or admin) can approve — this is the final approval
 * in the review pipeline. After acceptance, the stage moves to
 * 'placement_officer' so the placement officer can assign a placement.
 *
 * Creates a review record with decision='accepted', the current stage,
 * and accepted_at timestamp. Creates an audit log entry.
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

$app = get_application($pdo, $applicationId);
if (!$app) {
    set_flash('error', 'Application not found.');
    redirect('/FMS/admin/applications.php');
}

// Only under_review applications can be accepted
if ($app['status'] !== 'under_review') {
    set_flash('error', 'This application cannot be accepted in its current status.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

// Role gate: only HOD stage (or admin) can accept
$currentStage = $app['current_review_stage'] ?? 'secretary';
if (!can_user_act_on_stage($pdo, $userId, $currentStage)) {
    set_flash('error', 'You do not have permission to act on this stage (' . stage_label($currentStage) . ').');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

if ($currentStage !== 'hod') {
    set_flash('error', 'Only the HOD stage can approve applications. Please forward to HOD first.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

try {
    $pdo->beginTransaction();

    $oldStatus = $app['status'];
    $newStatus = 'accepted';

    // Update application status and advance stage to placement_officer
    $stmt = $pdo->prepare("UPDATE applications SET status = ?, current_review_stage = 'placement_officer' WHERE id = ?");
    $stmt->execute([$newStatus, $applicationId]);

    // Optionally add a comment
    $commentId = null;
    if ($commentText !== '') {
        $commentId = add_comment($pdo, $userId, null, 'application', $applicationId, $commentText);
    }

    // Create a review record with decision = accepted and stage
    $stmt = $pdo->prepare("
        INSERT INTO reviews (application_id, user_id, decision, status, stage, comment_id, accepted_at)
        VALUES (?, ?, 'accepted', ?, ?, ?, NOW())
    ");
    $stmt->execute([$applicationId, $userId, $newStatus, $currentStage, $commentId]);

    // Audit log
    log_application_action($pdo, $applicationId, $userId, 'application_accepted',
        ['status' => $oldStatus, 'stage' => $currentStage],
        ['status' => $newStatus, 'stage' => 'placement_officer']
    );

    $pdo->commit();
} catch (PDOException $ex) {
    $pdo->rollBack();
    set_flash('error', 'An error occurred while accepting the application.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

set_flash('success', 'Application has been approved by HOD. The Placement Officer can now assign a placement.');
redirect('/FMS/admin/view_application.php?id=' . $applicationId);
