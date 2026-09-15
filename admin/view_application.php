<?php
/**
 * Admin View Application
 *
 * Shows full details of a single application, including:
 *   - Student information
 *   - Application fields
 *   - Selected specializations
 *   - Multi-stage review progress tracker
 *   - Review history
 *   - Comments
 *   - Audit log
 *
 * Action buttons are gated by the application's current review stage
 * and the logged-in user's role:
 *   - Secretary: can return for correction, reject, or forward to field coordinator
 *   - Field Coordinator: can return, reject, or forward to HOD
 *   - HOD: can return, reject, or accept (approve)
 *   - Placement Officer: can assign placement
 *   - Admin: can act on any stage
 */
require_once __DIR__ . '/../includes/admin_check.php';

$pageTitle = 'View Application';

$appId = (int) ($_GET['id'] ?? 0);
if ($appId === 0) {
    set_flash('error', 'Invalid application ID.');
    redirect('/FMS/admin/applications.php');
}

$app = get_application($pdo, $appId);
if (!$app) {
    set_flash('error', 'Application not found.');
    redirect('/FMS/admin/applications.php');
}

// Load related data
$specs         = get_application_specializations($pdo, $appId);
$reviews       = get_application_reviews($pdo, $appId);
$comments      = get_comments($pdo, 'application', $appId);
$auditLogs     = get_application_audit_logs($pdo, $appId);
$placement     = get_placement_by_application($pdo, $appId);

// Determine the current stage and whether the current user can act
$currentStage = $app['current_review_stage'] ?? 'secretary';
$userId = (int) $currentUser['id'];
$canActOnStage = can_user_act_on_stage($pdo, $userId, $currentStage);

// Application must be in a reviewable status for stage actions
$isReviewableStatus = in_array($app['status'], ['submitted', 'under_review'], true);

// Which action buttons to show
$canReturn  = $canActOnStage && $isReviewableStatus;
$canReject  = $canActOnStage && $isReviewableStatus;
$canForward = $canActOnStage && $isReviewableStatus && $currentStage !== 'placement_officer';
$canAccept  = $canActOnStage && $isReviewableStatus && $currentStage === 'hod';
$canPlace   = $canActOnStage && $app['status'] === 'accepted' && !$placement;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Application <?= e($app['reference_number']) ?></h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">

            <!-- Multi-stage progress tracker -->
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title">Review Pipeline</h3></div>
                <div class="card-body">
                    <?php
                    $stages = review_stages();
                    $stageIndex = array_search($currentStage, $stages, true);
                    if ($currentStage === 'done') $stageIndex = count($stages);
                    ?>
                    <div class="d-flex justify-content-between mb-3">
                        <?php foreach ($stages as $i => $stg): ?>
                            <?php
                            $isActive = $i === $stageIndex;
                            $isDone = $i < $stageIndex;
                            $isPending = $i > $stageIndex;
                            $stepClass = $isDone ? 'bg-success' : ($isActive ? 'bg-primary' : 'bg-secondary');
                            ?>
                            <div class="text-center flex-fill">
                                <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center"
                                     style="width:40px;height:40px;" >
                                    <span class="badge <?= $stepClass ?> p-2" style="font-size:1rem;">
                                        <?= $isDone ? '<i class="fas fa-check"></i>' : ($i + 1) ?>
                                    </span>
                                </div>
                                <small class="d-block mt-1 <?= $isActive ? 'font-weight-bold' : 'text-muted' ?>">
                                    <?= stage_label($stg) ?>
                                </small>
                            </div>
                            <?php if ($i < count($stages) - 1): ?>
                                <div class="d-flex align-items-center flex-fill">
                                    <hr class="flex-grow-1 <?= $i < $stageIndex ? 'border-success' : 'border-secondary' ?>">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-2">
                        <?php if ($app['status'] === 'rejected'): ?>
                            <span class="badge bg-danger">Application Rejected</span>
                        <?php elseif ($app['status'] === 'returned_for_correction'): ?>
                            <span class="badge bg-warning text-dark">Returned for Correction</span>
                        <?php elseif ($currentStage === 'done'): ?>
                            <span class="badge bg-success">Pipeline Complete</span>
                        <?php else: ?>
                            <span class="badge <?= stage_badge_class($currentStage) ?>">
                                Current Stage: <?= stage_label($currentStage) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Application details -->
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Application Details</h3></div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr><th style="width:200px;">Reference Number</th><td><?= e($app['reference_number']) ?></td></tr>
                                <tr><th>Status</th><td><span class="badge <?= status_badge_class($app['status']) ?>"><?= status_label($app['status']) ?></span></td></tr>
                                <tr><th>Review Stage</th><td><span class="badge <?= stage_badge_class($currentStage) ?>"><?= stage_label($currentStage) ?></span></td></tr>
                                <tr><th>Application Type</th><td><?= e(ucfirst($app['application_type'])) ?></td></tr>
                                <tr><th>Skill Level</th><td><?= e(ucfirst($app['skill_level'])) ?></td></tr>
                                <tr><th>Training Type</th><td><?= e($app['training_type_name'] ?? '—') ?></td></tr>
                                <tr><th>Study Level</th><td><?= e($app['study_level_name'] ?? '—') ?></td></tr>
                                <tr><th>Application Window</th><td><?= e($app['window_name'] ?? '—') ?></td></tr>
                                <tr><th>Interest Statement</th><td><?= e($app['interest_statement']) ?></td></tr>
                                <tr><th>Reason for Application</th><td><?= e($app['reason_for_application']) ?></td></tr>
                                <tr><th>Expected Learning Objectives</th><td><?= e($app['expected_learning_objectives']) ?></td></tr>
                                <tr><th>Requested Start Date</th><td><?= format_date($app['requested_start_date']) ?></td></tr>
                                <tr><th>Requested End Date</th><td><?= format_date($app['requested_end_date']) ?></td></tr>
                                <tr><th>Submission Date</th><td><?= format_datetime($app['submission_date']) ?></td></tr>
                                <tr><th>Created</th><td><?= format_datetime($app['created_at']) ?></td></tr>
                            </table>

                            <h5 class="mt-3">Selected Specializations</h5>
                            <?php if (empty($specs)): ?>
                                <p class="text-muted">None selected.</p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($specs as $sp): ?>
                                        <li><?= e($sp['name']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Student information -->
                    <div class="card card-info">
                        <div class="card-header"><h3 class="card-title">Student Information</h3></div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr><th style="width:200px;">Name</th><td><?= e($app['student_name']) ?></td></tr>
                                <tr><th>Registration No</th><td><?= e($app['registration_no']) ?></td></tr>
                                <tr><th>Email</th><td><?= e($app['student_email']) ?></td></tr>
                                <tr><th>Course of Study</th><td><?= e($app['course_of_study'] ?? '—') ?></td></tr>
                                <tr><th>Gender</th><td><?= e($app['gender'] ?? '—') ?></td></tr>
                            </table>
                        </div>
                    </div>

                    <!-- Review actions -->
                    <?php if ($canReturn || $canReject || $canForward || $canAccept || $canPlace): ?>
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Review Actions</h3>
                            <?php if (!$canActOnStage && $isReviewableStatus): ?>
                                <span class="float-right badge bg-secondary">
                                    Awaiting <?= stage_label($currentStage) ?>
                                </span>
                            <?php elseif ($canActOnStage): ?>
                                <span class="float-right badge bg-primary">
                                    Your stage: <?= stage_label($currentStage) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (!$canActOnStage && $isReviewableStatus): ?>
                                <div class="alert alert-info">
                                    This application is currently at the <strong><?= stage_label($currentStage) ?></strong> stage.
                                    Only users with the <?= stage_label($currentStage) ?> role (or an admin) can take action.
                                </div>
                            <?php endif; ?>

                            <!-- Return for correction -->
                            <?php if ($canReturn): ?>
                            <form action="/FMS/actions/return_application.php" method="post" class="mb-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="application_id" value="<?= (int) $appId ?>">
                                <div class="form-group">
                                    <label>Comment for student (reason for return)</label>
                                    <textarea name="comment" class="form-control" rows="2" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-warning"><i class="fas fa-undo"></i> Return for Correction</button>
                            </form>
                            <?php endif; ?>

                            <!-- Forward to next stage -->
                            <?php if ($canForward): ?>
                            <hr>
                            <form action="/FMS/actions/forward_application.php" method="post" class="mb-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="application_id" value="<?= (int) $appId ?>">
                                <div class="form-group">
                                    <label>Comment (optional)</label>
                                    <textarea name="comment" class="form-control" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-forward"></i> Forward to <?= stage_label(next_stage($currentStage)) ?>
                                </button>
                            </form>
                            <?php endif; ?>

                            <!-- Accept (HOD only) -->
                            <?php if ($canAccept): ?>
                            <hr>
                            <form action="/FMS/actions/accept_application.php" method="post" class="mb-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="application_id" value="<?= (int) $appId ?>">
                                <div class="form-group">
                                    <label>Approval comment (optional)</label>
                                    <textarea name="comment" class="form-control" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Approve Application</button>
                            </form>
                            <?php endif; ?>

                            <!-- Reject -->
                            <?php if ($canReject): ?>
                            <hr>
                            <form action="/FMS/actions/reject_application.php" method="post" class="mb-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="application_id" value="<?= (int) $appId ?>">
                                <div class="form-group">
                                    <label>Rejection reason</label>
                                    <textarea name="comment" class="form-control" rows="2" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject Application</button>
                            </form>
                            <?php endif; ?>

                            <!-- Assign placement -->
                            <?php if ($canPlace): ?>
                            <hr>
                            <h5>Assign Placement</h5>
                            <form action="/FMS/actions/create_placement.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="application_id" value="<?= (int) $appId ?>">
                                <div class="form-group">
                                    <label>Department</label>
                                    <select name="department_id" class="form-control" required>
                                        <option value="">— Select —</option>
                                    </select>
                                    <small class="text-muted">Select the department for this placement.</small>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Academic Supervisor</label>
                                            <select name="academic_supervisor_id" class="form-control">
                                                <option value="">— None —</option>
                                                <?php
                                                $sups = get_supervisors($pdo);
                                                foreach ($sups as $su): ?>
                                                    <option value="<?= (int) $su['id'] ?>"><?= e($su['full_name']) ?> (<?= e($su['username']) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Industrial Supervisor</label>
                                            <select name="industrial_supervisor_id" class="form-control">
                                                <option value="">— None —</option>
                                                <?php foreach ($sups as $su): ?>
                                                    <option value="<?= (int) $su['id'] ?>"><?= e($su['full_name']) ?> (<?= e($su['username']) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Start Date</label>
                                            <input type="date" name="start_date" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>End Date</label>
                                            <input type="date" name="end_date" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-briefcase"></i> Create Placement</button>
                            </form>
                            <?php endif; ?>

                            <!-- If placement already exists -->
                            <?php if ($placement): ?>
                                <div class="alert alert-success">
                                    A placement has already been assigned for this application.
                                    <a href="/FMS/admin/applications.php" class="btn btn-sm btn-success">Back to Applications</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right column: reviews, comments, audit log -->
                <div class="col-md-4">
                    <!-- Reviews -->
                    <div class="card card-secondary">
                        <div class="card-header"><h3 class="card-title">Review History</h3></div>
                        <div class="card-body">
                            <?php if (empty($reviews)): ?>
                                <p class="text-muted">No reviews yet.</p>
                            <?php else: ?>
                                <?php foreach ($reviews as $r): ?>
                                    <div class="mb-2 p-2 border-bottom">
                                        <strong><?= e($r['reviewer_name']) ?></strong>
                                        <span class="badge <?= status_badge_class($r['decision']) ?>"><?= status_label($r['decision']) ?></span>
                                        <?php if (!empty($r['stage'])): ?>
                                            <span class="badge <?= stage_badge_class($r['stage']) ?>"><?= stage_label($r['stage']) ?></span>
                                        <?php endif; ?>
                                        <small class="text-muted d-block"><?= format_datetime($r['created_at']) ?></small>
                                        <small><?= e($r['status']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Comments -->
                    <div class="card card-secondary">
                        <div class="card-header"><h3 class="card-title">Comments</h3></div>
                        <div class="card-body">
                            <?php if (empty($comments)): ?>
                                <p class="text-muted">No comments yet.</p>
                            <?php else: ?>
                                <?php foreach ($comments as $c): ?>
                                    <div class="mb-2 p-2 border-bottom">
                                        <strong><?= e($c['staff_name'] ?? $c['student_name'] ?? 'Unknown') ?></strong>
                                        <small class="text-muted d-block"><?= format_datetime($c['created_at']) ?></small>
                                        <p><?= e($c['comment_text']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Audit log -->
                    <div class="card card-dark">
                        <div class="card-header"><h3 class="card-title">Audit Log</h3></div>
                        <div class="card-body">
                            <?php if (empty($auditLogs)): ?>
                                <p class="text-muted">No audit entries.</p>
                            <?php else: ?>
                                <?php foreach ($auditLogs as $al): ?>
                                    <div class="mb-1 p-1 border-bottom">
                                        <small>
                                            <strong><?= e($al['action']) ?></strong>
                                            <?php if ($al['user_name']): ?> by <?= e($al['user_name']) ?><?php endif; ?>
                                            <span class="text-muted d-block"><?= format_datetime($al['created_at']) ?></span>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Load departments for the placement form. -->
<script>
$(document).ready(function() {
    var deptSelect = $('select[name="department_id"]');
    $.get('/FMS/actions/get_departments.php', function(data) {
        data.forEach(function(d) {
            deptSelect.append('<option value="' + d.id + '">' + d.name + '</option>');
        });
    }, 'json');
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
