<?php
/** @var array $currentStudent */
require_once __DIR__ . '/../includes/student_check.php';

$pageTitle = 'Student Dashboard';

$apps = get_student_applications($pdo, (int) $currentStudent['id']);
$latestApp = !empty($apps) ? $apps[0] : null;

// Check if the student has a placement
$placement = get_placement_by_student($pdo, (int) $currentStudent['id']);

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

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">My Information</h3></div>
                        <div class="card-body">
                            <p><strong>Name:</strong> <?= e($currentStudent['full_name']) ?></p>
                            <p><strong>Registration No:</strong> <?= e($currentStudent['registration_no']) ?></p>
                            <p><strong>Email:</strong> <?= e($currentStudent['email']) ?></p>
                            <p><strong>Course:</strong> <?= e($currentStudent['course_of_study'] ?? '—') ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-success"><?= e(ucfirst($currentStudent['status'])) ?></span></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-info">
                        <div class="card-header"><h3 class="card-title">Current Application</h3></div>
                        <div class="card-body">
                            <?php if ($latestApp): ?>
                                <p><strong>Reference:</strong> <?= e($latestApp['reference_number']) ?></p>
                                <p><strong>Status:</strong>
                                    <span class="badge <?= status_badge_class($latestApp['status']) ?>">
                                        <?= status_label($latestApp['status']) ?>
                                    </span>
                                </p>
                                <p><strong>Training Type:</strong> <?= e($latestApp['training_type_name'] ?? '—') ?></p>
                                <p><strong>Study Level:</strong> <?= e($latestApp['study_level_name'] ?? '—') ?></p>
                                <p><strong>Submitted:</strong> <?= format_date($latestApp['submission_date']) ?></p>
                                <a href="/FMS/student/my_application.php" class="btn btn-sm btn-info mt-2">View Applications</a>
                            <?php else: ?>
                                <p>You have not created any applications yet.</p>
                                <a href="/FMS/student/application.php" class="btn btn-sm btn-primary mt-2">Create Application</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-success">
                        <div class="card-header"><h3 class="card-title">Placement</h3></div>
                        <div class="card-body">
                            <?php if ($placement): ?>
                                <p><strong>Department:</strong> <?= e($placement['department_name']) ?></p>
                                <p><strong>Academic Supervisor:</strong> <?= e($placement['academic_supervisor_name'] ?? '—') ?></p>
                                <p><strong>Industrial Supervisor:</strong> <?= e($placement['industrial_supervisor_name'] ?? '—') ?></p>
                                <p><strong>Start:</strong> <?= format_date($placement['start_date']) ?></p>
                                <p><strong>End:</strong> <?= format_date($placement['end_date']) ?></p>
                                <p><strong>Status:</strong>
                                    <span class="badge bg-primary"><?= status_label($placement['status']) ?></span>
                                </p>
                                <a href="/FMS/student/placement.php" class="btn btn-sm btn-success mt-2">View Details</a>
                            <?php else: ?>
                                <p>No placement has been assigned yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Quick Actions</h3></div>
                        <div class="card-body">
                            <a href="/FMS/student/application.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Application</a>
                            <a href="/FMS/student/profile.php" class="btn btn-outline-secondary"><i class="fas fa-user"></i> Edit Profile</a>
                            <a href="/FMS/student/notifications.php" class="btn btn-outline-info"><i class="fas fa-bell"></i> Notifications</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
