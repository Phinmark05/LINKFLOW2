<?php
/**
 * Admin Students Management
 *
 * Lists all students registered in the system. Allows searching by
 * name, registration number, or email. Shows student status and
 * allows the admin to suspend/activate student accounts.
 */
require_once __DIR__ . '/../includes/admin_check.php';

// Only admins can manage students
if (!current_user_is_admin()) {
    set_flash('error', 'Only administrators can manage students.');
    redirect('/FMS/staff/dashboard.php');
}

$pageTitle = 'Student Management';

$nationalities = get_nationalities($pdo);
$studyLevels = get_study_levels($pdo);
$institutions = get_all_institutions($pdo);

// Search filter
$searchQuery = trim($_GET['search'] ?? '');

if ($searchQuery !== '') {
    $stmt = $pdo->prepare("
        SELECT s.*, i.name AS institution_name
        FROM students s
        LEFT JOIN institutions i ON i.id = s.institution_id
        WHERE s.full_name LIKE ? OR s.registration_no LIKE ? OR s.email LIKE ?
        ORDER BY s.full_name ASC
    ");
    $stmt->execute(["%$searchQuery%", "%$searchQuery%", "%$searchQuery%"]);
    $allStudents = $stmt->fetchAll();
} else {
    $allStudents = get_all_students($pdo);
}

// --- PAGINATION LOGIC (Limit set to 4) ---
$limit         = 4;
$totalStudents = count($allStudents);
$totalPages    = max(1, ceil($totalStudents / $limit));
$currentPage   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $limit;

// Slice array for the active page
$students = array_slice($allStudents, $offset, $limit);

// Maintain search term in pagination links
$searchParam = $searchQuery !== '' ? '&search=' . urlencode($searchQuery) : '';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Student Management</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Add Student</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/save_student.php" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label>Registration Number</label>
                                    <input type="text" name="registration_no" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="full_name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">— Select —</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" name="password" class="form-control" required minlength="8">
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <input type="password" name="password_confirm" class="form-control" required minlength="8">
                                </div>
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" id="dob" name="dob" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Nationality</label>
                                    <select name="nationality_id" class="form-control">
                                        <option value="">— Select —</option>
                                        <?php foreach ($nationalities as $nationality): ?>
                                            <option value="<?= (int) $nationality['id'] ?>"><?= e($nationality['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Study Level</label>
                                    <select name="study_level_id" class="form-control">
                                        <option value="">— Select —</option>
                                        <?php foreach ($studyLevels as $studyLevel): ?>
                                            <option value="<?= (int) $studyLevel['id'] ?>"><?= e($studyLevel['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Course of Study</label>
                                    <input type="text" name="course_of_study" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Institution</label>
                                    <select name="institution_id" class="form-control" required>
                                        <option value="">— Select Institution —</option>
                                        <?php foreach ($institutions as $institution): ?>
                                            <?php if ($institution['is_active']): ?>
                                                <option value="<?= (int) $institution['id'] ?>"><?= e($institution['name']) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Register Student</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">All Students</h3></div>
                        <div class="card-body p-0">
                            <form method="get" class="form-inline p-3 mb-0">
                                <input type="text" name="search" class="form-control mr-2" placeholder="Search name / reg no / email" value="<?= e($searchQuery) ?>">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <?php if ($searchQuery !== ''): ?>
                                    <a href="/FMS/admin/students.php" class="btn btn-default ml-2">Clear</a>
                                <?php endif; ?>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered mb-0">
                                    <thead>
                                        <tr><th>Registration No</th><th>Name</th><th>Email</th><th>Institution</th><th>Course</th><th>Status</th><th>Action</th><th>Delete</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($students)): ?>
                                            <tr><td colspan="8" class="text-center text-muted">No students found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($students as $s): ?>
                                                <tr>
                                                    <td><?= e($s['registration_no']) ?></td>
                                                    <td><?= e($s['full_name']) ?></td>
                                                    <td><?= e($s['email']) ?></td>
                                                    <td><?= e($s['institution_name'] ?? '—') ?></td>
                                                    <td><?= e($s['course_of_study'] ?? '—') ?></td>
                                                    <td>
                                                        <?php
                                                        $badgeClass = match ($s['status']) {
                                                            'active' => 'bg-success',
                                                            'suspended' => 'bg-danger',
                                                            'graduated' => 'bg-info',
                                                            default => 'bg-secondary',
                                                        };
                                                        ?>
                                                        <span class="badge <?= $badgeClass ?>"><?= e(ucfirst($s['status'])) ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="/FMS/admin/edit_student.php?id=<?= (int) $s['id'] ?>&page=<?= $currentPage ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <form action="/FMS/actions/toggle_deleted.php" method="post" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="entity" value="student">
                                                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= $s['deleted'] ? 'Restore' : 'Delete' ?></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- PAGINATION FOOTER -->
                        <?php if ($totalPages > 1): ?>
                        <div class="card-footer clearfix">
                            <ul class="pagination pagination-sm m-0 float-right">
                                <!-- Previous Link -->
                                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $searchParam ?>">&laquo;</a>
                                </li>
                                
                                <!-- Page Numbers -->
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $searchParam ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Next Link -->
                                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $searchParam ?>">&raquo;</a>
                                </li>
                            </ul>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>