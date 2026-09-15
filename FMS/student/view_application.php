
<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/student_check.php';
require_once __DIR__ . '/../includes/document_helpers.php';
require_once __DIR__ . '/../includes/document_ui.php';

$appId = (int) ($_GET['id'] ?? 0);

if ($appId === 0) {
    set_flash('error', 'Invalid application.');
    redirect('/FMS/student/my_application.php');
}

$app = get_application($pdo, $appId);

if (!$app) {
    set_flash('error', 'Application not found.');
    redirect('/FMS/student/my_application.php');
}

if ((int) $app['student_id'] !== (int) $currentStudent['id']) {
    set_flash('error', 'You do not have permission to view this application.');
    redirect('/FMS/student/my_application.php');
}

$specs = get_application_specializations($pdo, $appId);
$reviews = get_application_reviews($pdo, $appId);
$comments = get_comments($pdo, 'application', $appId);
$placement = get_placement_by_application($pdo, $appId);

$currentStage = $app['current_review_stage'] ?? 'secretary';
$stages = review_stages();
$stageIndex = array_search($currentStage, $stages, true);

if ($stageIndex === false) {
    $stageIndex = 0;
}

if ($currentStage === 'done') {
    $stageIndex = count($stages);
}

$canEdit = in_array($app['status'], ['draft', 'returned_for_correction'], true);

$pageTitle = 'View Application';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="content-wrapper">

    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="m-0">
                        Application <?= e($app['reference_number']) ?>
                    </h1>
                    <small class="text-muted">
                        Application Details and Progress
                    </small>
                </div>

                <span class="badge <?= status_badge_class($app['status']) ?> p-2">
                    <?= status_label($app['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-route mr-1"></i>
                        Application Progress
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <?php foreach ($stages as $i => $stage): ?>
                            <?php $isDone = $i < $stageIndex; $isActive = $i === $stageIndex; $stepClass = $isDone ? 'bg-success' : ($isActive ? 'bg-primary' : 'bg-secondary');
                            ?>
                            <div class="text-center flex-fill">
                                <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center <?= $stepClass ?>" style="width:42px;height:42px;color:white;" >
                                    <?php if ($isDone): ?>
                                        <i class="fas fa-check"></i>
                                    <?php elseif ($isActive): ?>
                                        <i class="fas fa-clock"></i>
                                    <?php else: ?>
                                        <?= $i + 1 ?>
                                    <?php endif; ?>
                                </div>
                                <small class="d-block mt-2 <?= $isActive ? 'font-weight-bold' : 'text-muted' ?>">
                                    <?= stage_label($stage) ?>
                                </small>
                            </div>
                            <?php if ($i < count($stages) - 1): ?>
                                <div class="d-flex align-items-center flex-fill">
                                    <hr class="flex-grow-1 <?= $i < $stageIndex ? 'border-success' : 'border-secondary' ?>">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <?php if ($app['status'] === 'rejected'): ?>
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-times-circle mr-1"></i>
                                <strong>Application Rejected</strong>
                                <br>
                                Please check the comments below for more information.
                            </div>
                        <?php elseif ($app['status'] === 'returned_for_correction'): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <strong>Correction Required</strong>
                                <br>
                                Your application has been returned for correction.
                                Please check the comments below.
                            </div>
                        <?php elseif ($currentStage === 'done'): ?>
                            <div class="alert alert-success mb-0">
                                <i class="fas fa-check-circle mr-1"></i>
                                <strong>Application Process Completed</strong>
                            </div>
                        <?php else: ?>
                            <span class="badge <?= stage_badge_class($currentStage) ?> p-2">
                                Current Stage: <?= stage_label($currentStage) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-alt mr-1"></i>
                                Application Details
                            </h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width:220px;">Reference Number</th>
                                    <td><?= e($app['reference_number']) ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge <?= status_badge_class($app['status']) ?>">
                                            <?= status_label($app['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Current Review Stage</th>
                                    <td>
                                        <span class="badge <?= stage_badge_class($currentStage) ?>">
                                            <?= stage_label($currentStage) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Application Type</th>
                                    <td><?= e(ucfirst($app['application_type'])) ?></td>
                                </tr>

                                <tr>
                                    <th>Skill Level</th>
                                    <td><?= e(ucfirst($app['skill_level'])) ?></td>
                                </tr>

                                <tr>
                                    <th>Training Type</th>
                                    <td><?= e($app['training_type_name'] ?? '—') ?></td>
                                </tr>

                                <tr>
                                    <th>Study Level</th>
                                    <td><?= e($app['study_level_name'] ?? '—') ?></td>
                                </tr>

                                <tr>
                                    <th>Application Window</th>
                                    <td><?= e($app['window_name'] ?? '—') ?></td>
                                </tr>

                                <tr>
                                    <th>Interest Statement</th>
                                    <td><?= nl2br(e($app['interest_statement'] ?? '—')) ?></td>
                                </tr>

                                <tr>
                                    <th>Reason for Application</th>
                                    <td><?= nl2br(e($app['reason_for_application'] ?? '—')) ?></td>
                                </tr>

                                <tr>
                                    <th>Expected Learning Objectives</th>
                                    <td><?= nl2br(e($app['expected_learning_objectives'] ?? '—')) ?></td>
                                </tr>

                                <tr>
                                    <th>Requested Start Date</th>
                                    <td><?= format_date($app['requested_start_date']) ?></td>
                                </tr>

                                <tr>
                                    <th>Requested End Date</th>
                                    <td><?= format_date($app['requested_end_date']) ?></td>
                                </tr>

                                <tr>
                                    <th>Submission Date</th>
                                    <td><?= format_datetime($app['submission_date']) ?></td>
                                </tr>

                                <tr>
                                    <th>Created</th>
                                    <td><?= format_datetime($app['created_at']) ?></td>
                                </tr>

                            </table>
                            <h5 class="mt-4">Selected Specializations</h5>
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
                    <?php $documents = get_application_documents($pdo, $appId); ?>
                    <?php render_application_document_cards($documents, 'student'); ?>
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-comments mr-1"></i>
                                Staff Comments & Updates
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($comments)): ?>
                                <p class="text-muted">
                                    No comments or updates yet.
                                </p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="mb-3 p-3 border rounded">
                                        <div class="d-flex justify-content-between">
                                            <strong>
                                                <i class="fas fa-user-tie mr-1"></i>
                                                <?= e($comment['staff_name'] ?? $comment['student_name'] ?? 'Staff') ?>
                                            </strong>
                                            <small class="text-muted">
                                                <?= format_datetime($comment['created_at']) ?>
                                            </small>
                                        </div>
                                        <hr class="my-2">
                                        <p class="mb-0">
                                            <?= nl2br(e($comment['comment_text'])) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-1"></i>
                                Review History
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($reviews)): ?>
                                <p class="text-muted">
                                    No review activity yet.
                                </p>
                            <?php else: ?>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="mb-3 p-2 border-bottom">
                                        <strong>
                                            <?= e($review['reviewer_name'] ?? 'Staff') ?>
                                        </strong>
                                        <div class="mt-1">
                                            <?php if (!empty($review['stage'])): ?>
                                                <span class="badge <?= stage_badge_class($review['stage']) ?>">
                                                    <?= stage_label($review['stage']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="badge <?= status_badge_class($review['decision']) ?>">
                                                <?= status_label($review['decision']) ?>
                                            </span>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <?= format_datetime($review['created_at']) ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($placement): ?>

                        <!-- Placement information -->
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-building mr-1"></i>
                                    Placement Information
                                </h3>
                            </div>

                            <div class="card-body">

                                <table class="table table-sm">

                                    <?php if (!empty($placement['department_name'])): ?>
                                        <tr>
                                            <th>Department</th>
                                            <td><?= e($placement['department_name']) ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if (!empty($placement['academic_supervisor_name'])): ?>
                                        <tr>
                                            <th>Academic Supervisor</th>
                                            <td><?= e($placement['academic_supervisor_name']) ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if (!empty($placement['industrial_supervisor_name'])): ?>
                                        <tr>
                                            <th>Industrial Supervisor</th>
                                            <td><?= e($placement['industrial_supervisor_name']) ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if (!empty($placement['start_date'])): ?>
                                        <tr>
                                            <th>Start Date</th>
                                            <td><?= format_date($placement['start_date']) ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if (!empty($placement['end_date'])): ?>
                                        <tr>
                                            <th>End Date</th>
                                            <td><?= format_date($placement['end_date']) ?></td>
                                        </tr>
                                    <?php endif; ?>

                                </table>

                            </div>
                        </div>

                    <?php endif; ?>

                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-1"></i>
                                Application Information
                            </h3>
                        </div>

                        <div class="card-body">

                            <p>
                                Your application passes through several review stages.
                            </p>

                            <p class="mb-0">
                                Check this page regularly for status changes,
                                staff comments and placement information.
                            </p>

                        </div>
                    </div>

                </div>

            </div>

            <div class="mb-4">

                <a
                    href="/FMS/student/my_application.php"
                    class="btn btn-default"
                >
                    <i class="fas fa-arrow-left"></i>
                    Back to My Applications
                </a>

                <?php if ($canEdit): ?>

                    <a
                        href="/FMS/student/edit_application.php?id=<?= (int) $app['id'] ?>"
                        class="btn btn-warning"
                    >
                        <i class="fas fa-edit"></i>
                        Edit Application
                    </a>

                <?php endif; ?>

            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
```
