<?php
/**
 * Forward Application Action
 *
 * Advances an application to the next review stage in the pipeline:
 *   secretary → field_coordinator → hod → placement_officer
 *
 * The HOD stage does not use "forward" — the HOD approves (accepts)
 * or rejects instead. So forwarding is available for all stages except
 * the last one (placement_officer) and the HOD stage (which uses
 * accept/reject instead).
 *
 * Role-gated: only the user responsible for the application's current
 * review stage (or an admin) can forward it.
 *
 * Optionally accepts a comment. Creates a review record with
 * decision='forwarded', the current stage, and an audit log entry.
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

// Only submitted or under_review applications can be forwarded
if (!in_array($app['status'], ['submitted', 'under_review'], true)) {
    set_flash('error', 'This application cannot be forwarded in its current status.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

// Role gate
$currentStage = $app['current_review_stage'] ?? 'secretary';
if (!can_user_act_on_stage($pdo, $userId, $currentStage)) {
    set_flash('error', 'You do not have permission to act on this stage (' . stage_label($currentStage) . ').');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

// HOD doesn't forward — HOD accepts or rejects
if ($currentStage === 'hod') {
    set_flash('error', 'The HOD stage uses Approve/Reject, not Forward.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

// Can't forward from the last stage
if ($currentStage === 'placement_officer') {
    set_flash('error', 'This application is already at the final stage. Assign a placement instead.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

$nextStage = next_stage($currentStage);
if ($nextStage === 'done') {
    set_flash('error', 'Cannot forward past the final stage.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

try {
    $pdo->beginTransaction();

    $oldStatus = $app['status'];
    $newStatus = 'under_review';

    // Update application stage and status
    $stmt = $pdo->prepare("UPDATE applications SET status = ?, current_review_stage = ? WHERE id = ?");
    $stmt->execute([$newStatus, $nextStage, $applicationId]);

    // Optionally add a comment
    $commentId = null;
    if ($commentText !== '') {
        $commentId = add_comment($pdo, $userId, null, 'application', $applicationId, $commentText);
    }

    // Create a review record with decision = forwarded and stage
    $stmt = $pdo->prepare("
        INSERT INTO reviews (application_id, user_id, decision, status, stage, comment_id)
        VALUES (?, ?, 'forwarded', ?, ?, ?)
    ");
    $stmt->execute([$applicationId, $userId, $newStatus, $currentStage, $commentId]);

    // Audit log
    log_application_action($pdo, $applicationId, $userId, 'application_forwarded',
        ['status' => $oldStatus, 'stage' => $currentStage],
        ['status' => $newStatus, 'stage' => $nextStage, 'comment' => $commentText ?: null]
    );

    $pdo->commit();
} catch (PDOException $ex) {
    $pdo->rollBack();
    set_flash('error', 'An error occurred while forwarding the application.');
    redirect('/FMS/admin/view_application.php?id=' . $applicationId);
}

set_flash('success', 'Application has been forwarded to ' . stage_label($nextStage) . '.');
redirect('/FMS/admin/view_application.php?id=' . $applicationId);
