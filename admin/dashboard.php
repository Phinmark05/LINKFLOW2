<?php
/**
 * Admin Dashboard
 *
 * Shows summary statistics from the database:
 *   - Total students, applications, and placements
 *   - Application counts by status (submitted, under review, accepted, etc.)
 *   - Active application windows
 *
 * All statistics use real database queries — nothing is hard-coded.
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/admin_check.php';

$pageTitle = 'Admin Dashboard';

// Get statistics from the database
$totalStudents      = count_students($pdo);
$totalApps          = count_applications($pdo);
$totalPlacements    = count_placements($pdo);
$activeWindows      = count_active_windows($pdo);
$statusCounts       = get_application_status_counts($pdo);
$stageCounts        = get_application_stage_counts($pdo);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Admin Dashboard</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">

            <!-- Summary stat cards -->
            <div class="row">
                <div class="col-lg-3 col-sm-6">
                    <div class="small-box bg-info">
                        <div class="inner"><h3><?= $totalStudents ?></h3><p>Students</p></div>
                        <div class="icon"><i class="fas fa-user-graduate"></i></div>
                        <a href="/FMS/admin/students.php" class="small-box-footer">View Students <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="small-box bg-primary">
                        <div class="inner"><h3><?= $totalApps ?></h3><p>Applications</p></div>
                        <div class="icon"><i class="fas fa-file-alt"></i></div>
                        <a href="/FMS/admin/applications.php" class="small-box-footer">View Applications <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="small-box bg-success">
                        <div class="inner"><h3><?= $totalPlacements ?></h3><p>Placements</p></div>
                        <div class="icon"><i class="fas fa-briefcase"></i></div>
                        <a href="/FMS/admin/applications.php?status=placement_assigned" class="small-box-footer">View Placements <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="small-box bg-warning">
                        <div class="inner"><h3><?= $activeWindows ?></h3><p>Active Windows</p></div>
                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                        <a href="/FMS/admin/application_windows.php" class="small-box-footer">Manage Windows <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- Application status breakdown -->
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
                    <div class="card card-info">
                        <div class="card-header"><h3 class="card-title">Quick Links</h3></div>
                        <div class="card-body">
                            <a href="/FMS/admin/applications.php?status=submitted" class="btn btn-info mb-2">Submitted Applications</a>
                            <a href="/FMS/admin/applications.php?status=under_review" class="btn btn-warning mb-2">Under Review</a>
                            <a href="/FMS/admin/applications.php?status=accepted" class="btn btn-success mb-2">Accepted</a>
                            <a href="/FMS/admin/application_windows.php" class="btn btn-secondary mb-2">Manage Windows</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review stage breakdown -->
            <div class="row">
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
                <div class="col-md-6">
                    <div class="card card-warning">
                        <div class="card-header"><h3 class="card-title">Stage Quick Filters</h3></div>
                        <div class="card-body">
                            <a href="/FMS/admin/applications.php?stage=secretary" class="btn btn-info mb-2">Secretary</a>
                            <a href="/FMS/admin/applications.php?stage=field_coordinator" class="btn btn-primary mb-2">Field Coordinator</a>
                            <a href="/FMS/admin/applications.php?stage=hod" class="btn btn-warning mb-2">HOD</a>
                            <a href="/FMS/admin/applications.php?stage=placement_officer" class="btn btn-success mb-2">Placement Officer</a>
                            <a href="/FMS/admin/applications.php?stage=done" class="btn btn-secondary mb-2">Completed Pipeline</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
