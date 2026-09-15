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

$departments = get_all_departments($pdo);

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
                            <table class="table table-striped">
                                <thead><tr><th>Department</th><th>Active</th><th>Actions</th></tr></thead>
                                <tbody>
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
                                            <form action="/FMS/actions/toggle_department.php" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="department_id" value="<?= (int) $d['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-<?= $d['is_active'] ? 'secondary' : 'success' ?>">
                                                    <?= $d['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
