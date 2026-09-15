<?php
/**
 * Staff Dashboard
 *
 * A read-only dashboard for non-admin staff users (secretary, field
 * coordinator, HOD, placement officer, supervisors). Shows summary
 * statistics (counts only — no management links) and the applications
 * currently waiting for the logged-in user's review stage.
 *
 * Admin users are redirected to the admin dashboard.
 */
require_once __DIR__ . '/../includes/admin_check.php';

// Admins use the full admin dashboard instead
if (current_user_is_admin()) {
    redirect('/FMS/admin/dashboard.php');
}

$pageTitle = 'My Dashboard';

$totalStudents      = count_students($pdo);
$totalApps          = count_applications($pdo);
$totalPlacements    = count_placements($pdo);
$activeWindows      = count_active_windows($pdo);
$statusCounts       = get_application_status_counts($pdo);
$stageCounts        = get_application_stage_counts($pdo);

// Get applications pending for this user's stage(s)
$userId = (int) $currentUser['id'];
$pendingApps = get_pending_for_user($pdo, $userId);
$pendingCount = count($pendingApps);

// Get the user's role label for display
$roleLabel = '';
$roleMap = [
    'secretary'         => 'Secretary',
    'field_coordinator' => 'Field Coordinator',
    'hod'               => 'HOD',
    'placement_officer' => 'Placement Officer',
    'academic_supervisor' => 'Academic Supervisor',
    'industrial_supervisor' => 'Industrial Supervisor',
    'supervisor'        => 'Supervisor',
];
foreach ($currentUserRoles as $r) {
    if (isset($roleMap[$r])) {
        $roleLabel = $roleMap[$r];
        break;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Dashboard</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">

            <!-- Summary stat cards (read-only, no links) -->
            <div class="row">
                <div class="col-lg-3 col-sm-6">
                    <div class="small-box bg-info">
                        <div class="inner"><h3><?= $totalStudents ?></h3><p>Students</p></div>
                        <div class="icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="small-box-footer" style="cursor:default;">Total Registered</div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="small-box bg-primary">
                        <div class="inner"><h3><?= $totalApps ?></h3><p>Applications</p></div>
                        <div class="icon"><i class="fas fa-file-alt"></i></div>
                        <div class="small-box-footer" style="cursor:default;">Total Submitted</div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="small-box bg-success">
                        <div class="inner"><h3><?= $totalPlacements ?></h3><p>Placements</p></div>
                        <div class="icon"><i class="fas fa-briefcase"></i></div>
                        <div class="small-box-footer" style="cursor:default;">Total Assigned</div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="small-box bg-warning">
                        <div class="inner"><h3><?= $activeWindows ?></h3><p>Active Windows</p></div>
                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="small-box-footer" style="cursor:default;">Currently Open</div>
                    </div>
                </div>
            </div>

            <!-- Pending reviews for this user -->
            <?php if ($pendingCount > 0): ?>
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock"></i> Applications Awaiting Your Action
                        <span class="badge bg-warning text-dark ml-2"><?= $pendingCount ?></span>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Student</th>
                                <th>Reg No</th>
                                <th>Status</th>
                                <th>Stage</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingApps as $pa): ?>
                            <tr>
                                <td><?= e($pa['reference_number']) ?></td>
                                <td><?= e($pa['student_name']) ?></td>
                                <td><?= e($pa['registration_no']) ?></td>
                                <td><span class="badge <?= status_badge_class($pa['status']) ?>"><?= status_label($pa['status']) ?></span></td>
                                <td><span class="badge <?= stage_badge_class($pa['current_review_stage'] ?? 'secretary') ?>"><?= stage_label($pa['current_review_stage'] ?? 'secretary') ?></span></td>
                                <td><?= format_date($pa['submission_date'] ?? $pa['created_at']) ?></td>
                                <td><a href="/FMS/admin/view_application.php?id=<?= (int) $pa['id'] ?>" class="btn btn-sm btn-warning">Review Now</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle" style="font-size:3rem; color:#28a745;"></i>
                    <p class="mt-2 mb-0" style="font-weight:600;">No applications are waiting for your action right now.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Application status overview (read-only) -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Applications by Status</h3></div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr><th>Status</th><th>Count</th></tr>
                                <?php
                                $allStatuses = ['draft','submitted','under_review','returned_for_correction','accepted','rejected','cancelled','placement_assigned','in_training','completed'];
                                foreach ($allStatuses as $st): ?>
                                    <tr>
                                        <td><span class="badge <?= status_badge_class($st) ?>"><?= status_label($st) ?></span></td>
                                        <td><?= (int) ($statusCounts[$st] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-success">
                        <div class="card-header"><h3 class="card-title">Applications by Review Stage</h3></div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr><th>Stage</th><th>Count</th></tr>
                                <?php
                                $allStages = ['secretary','field_coordinator','hod','placement_officer','done'];
                                foreach ($allStages as $stg): ?>
                                    <tr>
                                        <td><span class="badge <?= stage_badge_class($stg) ?>"><?= stage_label($stg) ?></span></td>
                                        <td><?= (int) ($stageCounts[$stg] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
