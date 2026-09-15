<?php
/**
 * Admin Students Management
 *
 * Lists all students registered in the system. Allows searching by
 * name, registration number, or email. Shows student status and
 * allows the admin to suspend/activate student accounts.
 */
require_once __DIR__ . '/../includes/admin_check.php';

$pageTitle = 'Students';

// Search filter
$searchQuery = trim($_GET['search'] ?? '');

if ($searchQuery !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM students
        WHERE full_name LIKE ? OR registration_no LIKE ? OR email LIKE ?
        ORDER BY full_name ASC
    ");
    $stmt->execute(["%$searchQuery%", "%$searchQuery%", "%$searchQuery%"]);
    $students = $stmt->fetchAll();
} else {
    $students = get_all_students($pdo);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Students</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header"><h3 class="card-title">All Students</h3></div>
                <div class="card-body">
                    <form method="get" class="form-inline mb-3">
                        <input type="text" name="search" class="form-control mr-2" placeholder="Search name / reg no / email" value="<?= e($searchQuery) ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if ($searchQuery !== ''): ?>
                            <a href="/FMS/admin/students.php" class="btn btn-default ml-2">Clear</a>
                        <?php endif; ?>
                    </form>

                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr><th>Registration No</th><th>Name</th><th>Email</th><th>Course</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr><td colspan="6" class="text-center text-muted">No students found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><?= e($s['registration_no']) ?></td>
                                    <td><?= e($s['full_name']) ?></td>
                                    <td><?= e($s['email']) ?></td>
                                    <td><?= e($s['course_of_study'] ?? '—') ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match($s['status']) {
                                            'active' => 'bg-success',
                                            'suspended' => 'bg-danger',
                                            'graduated' => 'bg-info',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= e(ucfirst($s['status'])) ?></span>
                                    </td>
                                    <td>
                                        <!-- Toggle suspend/activate -->
                                        <form action="/FMS/actions/toggle_student.php" method="post" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
                                            <?php if ($s['status'] === 'active'): ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Suspend this student account?')">Suspend</button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
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
