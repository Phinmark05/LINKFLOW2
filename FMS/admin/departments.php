<?php
/**
 * Admin Departments Management
 *
 * Lists all departments and provides a form to add new departments.
 */
require_once __DIR__ . '/../includes/admin_check.php';

// Only admins can manage departments
if (!current_user_is_admin()) {
    set_flash('error', 'Only administrators can manage departments.');
    redirect('/FMS/staff/dashboard.php');
}

$pageTitle = 'Departments';

// 1. Fetch ALL departments first
$allDepartments = get_all_departments($pdo, true);

// Find editing target from full array
$editId = (int) ($_GET['edit'] ?? 0);
$editingDepartment = null;
foreach ($allDepartments as $department) {
    if ((int) $department['id'] === $editId) {
        $editingDepartment = $department;
        break;
    }
}

// 2. Pagination Logic (Limit set to 4)
$limit            = 4;
$totalDepartments = count($allDepartments);
$totalPages       = max(1, ceil($totalDepartments / $limit));
$currentPage      = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $limit;

// Slice array for the active page
$departments = array_slice($allDepartments, $offset, $limit);

// Maintain edit ID in pagination links if active
$urlParams = $editId ? "&edit={$editId}" : "";

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Departments</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Add Department</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/save_department.php" method="post">
                                <?= csrf_field() ?>
                                
                                <div class="form-group">
                                    <label>Department Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">All Departments</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead><tr><th>Department</th><th>Active</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php if (empty($departments)): ?>
                                        <tr><td colspan="3" class="text-center text-muted">No departments found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($departments as $d): ?>
                                        <tr>
                                            <td><?= e($d['name']) ?></td>
                                            <td>
                                                <?php if ($d['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="/FMS/admin/departments.php?edit=<?= (int) $d['id'] ?>&page=<?= $currentPage ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <form action="/FMS/actions/toggle_department.php" method="post" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="department_id" value="<?= (int) $d['id'] ?>">
                                                    <input type="hidden" name="page" value="<?= $currentPage ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-<?= $d['is_active'] ? 'secondary' : 'success' ?>">
                                                        <?= $d['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </form>
                                                <form action="/FMS/actions/toggle_deleted.php" method="post" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="entity" value="department">
                                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= $d['deleted'] ? 'Restore' : 'Delete' ?></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINATION FOOTER -->
                        <?php if ($totalPages > 1): ?>
                        <div class="card-footer clearfix">
                            <ul class="pagination pagination-sm m-0 float-right">
                                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $urlParams ?>">&laquo;</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $urlParams ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $urlParams ?>">&raquo;</a>
                                </li>
                            </ul>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            <?php if ($editingDepartment): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-warning">
                        <div class="card-header"><h3 class="card-title">Edit Department</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/update_department.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="department_id" value="<?= (int) $editingDepartment['id'] ?>">
                                <div class="form-group"><label>Department Name</label><input type="text" name="name" class="form-control" value="<?= e($editingDepartment['name']) ?>" required></div>
                                <div class="form-check mb-3"><input type="checkbox" name="is_active" class="form-check-input" id="departmentActive" <?= $editingDepartment['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="departmentActive">Active</label></div>
                                <button type="submit" class="btn btn-warning">Update Department</button>
                                <a href="/FMS/admin/departments.php?page=<?= $currentPage ?>" class="btn btn-default ml-2">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>