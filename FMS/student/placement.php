<?php
/**
 * Student Placement Page
 *
 * Shows the student's placement details if one has been assigned.
 * This includes department, supervisors, dates, and status.
 * If no placement exists, an informational message is shown.
 */
require_once __DIR__ . '/../includes/student_check.php';

$pageTitle = 'My Placement';

$placement = get_placement_by_student($pdo, (int) $currentStudent['id']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">My Placement</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <?php if ($placement): ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-success">
                            <div class="card-header"><h3 class="card-title">Placement Details</h3></div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr><th>Reference</th><td><?= e($placement['reference_number']) ?></td></tr>
                                    <tr><th>Department</th><td><?= e($placement['department_name']) ?></td></tr>
                                    <tr><th>Academic Supervisor</th><td><?= e($placement['academic_supervisor_name'] ?? 'Not assigned') ?></td></tr>
                                    <tr><th>Industrial Supervisor</th><td><?= e($placement['industrial_supervisor_name'] ?? 'Not assigned') ?></td></tr>
                                    <tr><th>Start Date</th><td><?= format_date($placement['start_date']) ?></td></tr>
                                    <tr><th>End Date</th><td><?= format_date($placement['end_date']) ?></td></tr>
                                    <tr>
                                        <th>Status</th>
                                        <td><span class="badge <?= status_badge_class($placement['status']) ?>"><?= status_label($placement['status']) ?></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No placement has been assigned to you yet. Once your application is accepted and a placement is created, you will see the details here.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
