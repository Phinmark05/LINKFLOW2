<?php
/**
 * Admin Applications Management
 *
 * Lists all applications with filtering by status and search by
 * student name or reference number. Each row links to the detail view.
 */
require_once __DIR__ . '/../includes/admin_check.php';

$pageTitle = 'Applications';

// Get filter parameters from the URL
$statusFilter = $_GET['status'] ?? '';
$stageFilter  = $_GET['stage'] ?? '';
$searchQuery  = trim($_GET['search'] ?? '');

// Build the query with optional filters
$sql = "
    SELECT a.*, s.full_name AS student_name, s.registration_no,
           tw.name AS window_name, tt.name AS training_type_name
    FROM applications a
    JOIN students s ON a.student_id = s.id
    LEFT JOIN application_windows tw ON a.application_window_id = tw.id
    LEFT JOIN training_types tt ON a.training_type_id = tt.id
";
$conditions = [];
$params = [];

if ($statusFilter !== '' && $statusFilter !== 'all') {
    $conditions[] = "a.status = ?";
    $params[] = $statusFilter;
}
if ($stageFilter !== '' && $stageFilter !== 'all') {
    $conditions[] = "a.current_review_stage = ?";
    $params[] = $stageFilter;
}
if ($searchQuery !== '') {
    $conditions[] = "(s.full_name LIKE ? OR a.reference_number LIKE ? OR s.registration_no LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if ($conditions) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}
$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apps = $stmt->fetchAll();

// Get pending applications for the current user's stage(s)
$userId = (int) $currentUser['id'];
$pendingApps = get_pending_for_user($pdo, $userId);
$pendingCount = count($pendingApps);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Applications</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">

            <?php if ($pendingCount > 0): ?>
            <!-- My Pending Reviews -->
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock"></i> My Pending Reviews
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
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Applications</h3>
                </div>
                <div class="card-body">
                    <!-- Filter form -->
                    <form method="get" class="form-inline mb-3">
                        <select name="status" class="form-control mr-2">
                            <option value="all">All Statuses</option>
                            <?php
                            $statuses = ['draft','submitted','under_review','returned_for_correction','accepted','rejected','placement_assigned'];
                            foreach ($statuses as $s): ?>
                                <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= status_label($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="stage" class="form-control mr-2">
                            <option value="all">All Stages</option>
                            <?php
                            $stages = ['secretary','field_coordinator','hod','placement_officer','done'];
                            foreach ($stages as $stg): ?>
                                <option value="<?= e($stg) ?>" <?= $stageFilter === $stg ? 'selected' : '' ?>><?= stage_label($stg) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="search" class="form-control mr-2" placeholder="Search name / reference / reg no" value="<?= e($searchQuery) ?>">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>

                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Student</th>
                                <th>Reg No</th>
                                <th>Window</th>
                                <th>Training Type</th>
                                <th>Status</th>
                                <th>Stage</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($apps)): ?>
                                <tr><td colspan="9" class="text-center text-muted">No applications found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($apps as $a): ?>
                                <tr>
                                    <td><?= e($a['reference_number']) ?></td>
                                    <td><?= e($a['student_name']) ?></td>
                                    <td><?= e($a['registration_no']) ?></td>
                                    <td><?= e($a['window_name'] ?? '—') ?></td>
                                    <td><?= e($a['training_type_name'] ?? '—') ?></td>
                                    <td><span class="badge <?= status_badge_class($a['status']) ?>"><?= status_label($a['status']) ?></span></td>
                                    <td><span class="badge <?= stage_badge_class($a['current_review_stage'] ?? 'secretary') ?>"><?= stage_label($a['current_review_stage'] ?? 'secretary') ?></span></td>
                                    <td><?= format_date($a['created_at']) ?></td>
                                    <td><a href="/FMS/admin/view_application.php?id=<?= (int) $a['id'] ?>" class="btn btn-sm btn-info">View</a></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
