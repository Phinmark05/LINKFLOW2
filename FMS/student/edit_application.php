<?php

require_once __DIR__ . '/../includes/student_check.php';
require_once __DIR__ . '/../includes/document_helpers.php';
include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/document_ui.php';

$pageTitle = 'Edit Application';
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
    set_flash('error', 'You do not have permission to edit this application.');
    redirect('/FMS/student/my_application.php');
}
if (!in_array($app['status'], ['draft', 'returned_for_correction'], true)) {
    set_flash('error', 'This application cannot be edited in its current status.');
    redirect('/FMS/student/my_application.php');
}
$windows = get_active_application_windows($pdo);
$trainingTypes = get_training_types($pdo);
$studyLevels = get_study_levels($pdo);
$specializations = get_specializations($pdo);
$selectedSpecs = get_application_specializations($pdo, $appId);
$selectedSpecIds = array_map(fn($s) => (int) $s['id'], $selectedSpecs);


include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Edit Application — <?= e($app['reference_number']) ?></h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-10">
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Edit Application</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($app['status'] === 'returned_for_correction'): ?><div class="alert alert-warning">This application was returned for correction. Please review the comments and resubmit.</div><?php endif; ?>
                            <form action="/FMS/actions/save_application.php" method="post" enctype="multipart/form-data">
                                <?= csrf_field() ?><input type="hidden" name="application_id" value="<?= (int) $appId ?>">
                                <div class="form-group"><label>Application Window</label>
                                    <select name="application_window_id" class="form-control">
                                        <option value="">— Select —</option><?php foreach ($windows as $w): ?><option value="<?= (int) $w['id'] ?>" <?= $app['application_window_id'] == $w['id'] ? 'selected' : '' ?>><?= e($w['name']) ?> (<?= format_date($w['open_date']) ?> — <?= format_date($w['close_date']) ?>)</option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group"><label>Training Type</label><select name="training_type_id" class="form-control">
                                                <option value="">— Select —</option><?php foreach ($trainingTypes as $t): ?><option value="<?= (int) $t['id'] ?>" <?= $app['training_type_id'] == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
                                            </select></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group"><label>Study Level</label><select name="study_level_id" class="form-control">
                                                <option value="">— Select —</option><?php foreach ($studyLevels as $sl): ?><option value="<?= (int) $sl['id'] ?>" <?= $app['study_level_id'] == $sl['id'] ? 'selected' : '' ?>><?= e($sl['name']) ?></option><?php endforeach; ?>
                                            </select></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group"><label>Application Type</label><select name="application_type" class="form-control">
                                                <option value="initial" <?= $app['application_type'] === 'initial' ? 'selected' : '' ?>>Initial</option>
                                                <option value="reapplication" <?= $app['application_type'] === 'reapplication' ? 'selected' : '' ?>>Reapplication</option>
                                            </select></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group"><label>Skill Level</label><select name="skill_level" class="form-control">
                                                <option value="beginner" <?= $app['skill_level'] === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                                                <option value="intermediate" <?= $app['skill_level'] === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                                                <option value="advanced" <?= $app['skill_level'] === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                                            </select></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Interest Statement</label>
                                    <textarea id="interest_statement" name="interest_statement" class="form-control" rows="3"><?= e($app['interest_statement']) ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Reason for Application</label>
                                    <textarea id="reason_for_application" name="reason_for_application" class="form-control" rows="3"><?= e($app['reason_for_application']) ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Expected Learning Objectives</label>
                                    <textarea id="expected_learning_objectives" name="expected_learning_objectives" class="form-control" rows="3"><?= e($app['expected_learning_objectives']) ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group"><label>Requested Start Date</label><input type="date" name="requested_start_date" class="form-control" value="<?= e($app['requested_start_date'] ?? '') ?>"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group"><label>Requested End Date</label><input type="date" name="requested_end_date" class="form-control" value="<?= e($app['requested_end_date'] ?? '') ?>"></div>
                                    </div>
                                </div>
                                <?php render_document_upload_fields($pdo); ?>
                                <div class="form-group"><label>Specializations</label>
                                    <div class="row"><?php foreach ($specializations as $sp): ?><div class="col-md-4 mb-1">
                                                <div class="form-check"><input class="form-check-input" type="checkbox" name="specializations[]" value="<?= (int) $sp['id'] ?>" id="edit_sp_<?= (int) $sp['id'] ?>" <?= in_array((int) $sp['id'], $selectedSpecIds, true) ? 'checked' : '' ?>><label class="form-check-label" for="edit_sp_<?= (int) $sp['id'] ?>"><?= e($sp['name']) ?></label></div>
                                            </div><?php endforeach; ?></div>
                                </div>
                                <button type="submit" name="action" value="draft" class="btn btn-secondary">Save Changes</button> <button type="submit" name="action" value="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to submit this application?') && confirm('Final Warning: Once submitted, you cannot edit your responses. Proceed?');">Submit Application</button> <a href="/FMS/student/my_application.php" class="btn btn-default">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($app['status'] === 'returned_for_correction'): ?><?php $comments = get_comments($pdo, 'application', $appId); ?><?php if (!empty($comments)): ?><div class="row mt-3">
                <div class="col-md-10">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Review Comments</h3>
                        </div>
                        <div class="card-body"><?php foreach ($comments as $c): ?><div class="mb-2 p-2 border-bottom"><strong><?= e($c['staff_name'] ?? 'Staff') ?></strong><small class="text-muted"> — <?= format_datetime($c['created_at']) ?></small>
                                    <p><?= e($c['comment_text']) ?></p>
                                </div><?php endforeach; ?></div>
                    </div>
                </div>
            </div><?php endif; ?><?php endif; ?>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
