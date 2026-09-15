<?php
/**
 * My Applications Page
 *
 * Lists all applications belonging to the logged-in student.
 * Shows reference number, status, training type, study level, and dates.
 * Provides links to view/edit drafts and applications returned for correction.
 */
require_once __DIR__ . '/../includes/student_check.php';

$pageTitle = 'My Applications';

$apps = get_student_applications($pdo, (int) $currentStudent['id']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">My Applications</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Applications</h3>
                    <a href="/FMS/student/application.php" class="btn btn-primary btn-sm float-right"><i class="fas fa-plus"></i> New</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($apps)): ?>
                        <p class="p-3">You have no applications yet. <a href="/FMS/student/application.php">Create one now</a>.</p>
                    <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Window</th>
                                <th>Training Type</th>
                                <th>Study Level</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apps as $a): ?>
                            <tr>
                                <td><?= e($a['reference_number']) ?></td>
                                <td><?= e($a['window_name'] ?? '—') ?></td>
                                <td><?= e($a['training_type_name'] ?? '—') ?></td>
                                <td><?= e($a['study_level_name'] ?? '—') ?></td>
                                <td><span class="badge <?= status_badge_class($a['status']) ?>"><?= status_label($a['status']) ?></span></td>
                                <td><?= format_date($a['created_at']) ?></td>
                                <td>
                                    <?php
                                    // Students can edit drafts and returned-for-correction applications
                                    $canEdit = in_array($a['status'], ['draft', 'returned_for_correction'], true);
                                    ?>
                                    <?php if ($canEdit): ?>
                                        <a href="/FMS/student/edit_application.php?id=<?= (int) $a['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    <?php endif; ?>
                                    <a href="/FMS/student/view_application.php?id=<?= (int) $a['id'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
