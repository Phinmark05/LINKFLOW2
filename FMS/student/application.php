<?php
/**
 * New Application Page
 *
 * Shows a form for the student to create a new application.
 * The form includes:
 *   - Application window (dropdown of active windows)
 *   - Training type (dropdown)
 *   - Study level (dropdown)
 *   - Application type (initial / reapplication)
 *   - Skill level (beginner / intermediate / advanced)
 *   - Interest statement, reason, learning objectives (text areas)
 *   - Requested start/end dates
 *   - Specializations (multi-select checkboxes)
 *
 * If the student already has a non-draft application, they are informed
 * that creating a new one may not be allowed depending on the window rules.
 */
require_once __DIR__ . '/../includes/student_check.php';
require_once __DIR__ . '/../includes/document_helpers.php';
require_once __DIR__ . '/../includes/document_ui.php';

$pageTitle = 'New Application';

// Load data for dropdowns
$windows         = get_active_application_windows($pdo);
$trainingTypes   = get_training_types($pdo);
$studyLevels     = get_study_levels($pdo);
$specializations = get_specializations($pdo);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">New Application</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-10">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Application Form</h3></div>
                        <div class="card-body">
                            <?php if (empty($windows)): ?>
                                <div class="alert alert-warning">There are no active application windows at this time. Please check back later.</div>
                            <?php else: ?>
                            <form action="/FMS/actions/save_application.php" method="post" enctype="multipart/form-data">
                                <?= csrf_field() ?>

                                <div class="form-group"><label>Application Window</label><select name="application_window_id" class="form-control"><option value="">— Select —</option><?php foreach ($windows as $w): ?><option value="<?= (int) $w['id'] ?>"><?= e($w['name']) ?> (<?= format_date($w['open_date']) ?> — <?= format_date($w['close_date']) ?>)</option><?php endforeach; ?></select></div>
                                <div class="row"><div class="col-md-6"><div class="form-group"><label>Training Type</label><select name="training_type_id" class="form-control"><option value="">— Select —</option><?php foreach ($trainingTypes as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div></div><div class="col-md-6"><div class="form-group"><label>Study Level</label><select name="study_level_id" class="form-control"><option value="">— Select —</option><?php foreach ($studyLevels as $sl): ?><option value="<?= (int) $sl['id'] ?>"><?= e($sl['name']) ?></option><?php endforeach; ?></select></div></div></div>
                                <div class="row"><div class="col-md-6"><div class="form-group"><label>Application Type</label><select name="application_type" class="form-control"><option value="initial">Initial</option><option value="reapplication">Reapplication</option></select></div></div><div class="col-md-6"><div class="form-group"><label>Skill Level</label><select name="skill_level" class="form-control"><option value="beginner">Beginner</option><option value="intermediate">Intermediate</option><option value="advanced">Advanced</option></select></div></div></div>
                                <div class="form-group"><label>Interest Statement</label><textarea id="mytextarea" name="interest_statement" class="form-control" rows="3"></textarea></div>
                                <div class="form-group"><label>Reason for Application</label><textarea id="mytextarea" name="reason_for_application" class="form-control" rows="3"></textarea></div>
                                <div class="form-group"><label>Expected Learning Objectives</label><textarea id="mytextarea" name="expected_learning_objectives" class="form-control" rows="3"></textarea></div>
                                <div class="row mb-3"><div class="col-md-6"><div class="form-group"><label for="requested_start_date">Requested Start Date</label><input type="text" id="requested_start_date" name="requested_start_date" class="form-control" placeholder="Select start date"></div></div><div class="col-md-6"><div class="form-group"><label for="requested_end_date">Requested End Date</label><input type="text" id="requested_end_date" name="requested_end_date" class="form-control" placeholder="Select end date"></div></div></div>

                                <?php render_document_upload_fields($pdo); ?>

                                <div class="form-group"><label>Specializations (select all that apply)</label><div class="row"><?php foreach ($specializations as $sp): ?><div class="col-md-4 mb-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="specializations[]" value="<?= (int) $sp['id'] ?>" id="sp_<?= (int) $sp['id'] ?>"><label class="form-check-label" for="sp_<?= (int) $sp['id'] ?>"><?= e($sp['name']) ?></label></div></div><?php endforeach; ?></div></div>
                                <button type="submit" name="action" value="draft" class="btn btn-secondary">Save as Draft</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
