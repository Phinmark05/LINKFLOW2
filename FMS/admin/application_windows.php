<?php
/**
 * Admin Application Windows Management
 *
 * Lists all application windows and provides a form to create new ones.
 * Staff can activate/deactivate windows. Each window has a name,
 * open date, close date, and optional max capacity.
 */
require_once __DIR__ . '/../includes/admin_check.php';

// Only admins can manage application windows
if (!current_user_is_admin()) {
    set_flash('error', 'Only administrators can manage application windows.');
    redirect('/FMS/staff/dashboard.php');
}

$pageTitle = 'Application Windows';

$windows = get_all_application_windows($pdo, true);
$editId = (int) ($_GET['edit'] ?? 0);
$editingWindow = null;
foreach ($windows as $window) {
    if ((int) $window['id'] === $editId) {
        $editingWindow = $window;
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
            <h1 class="m-0">Application Windows</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Create form -->
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Create Window</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/save_window.php" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Open Date & Time</label>
                                    <input type="datetime-local" name="open_date" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Close Date & Time</label>
                                    <input type="datetime-local" name="close_date" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Max Capacity (optional)</label>
                                    <input type="number" name="max_capacity" class="form-control" min="1">
                                </div>
                                <button type="submit" class="btn btn-primary">Create</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Windows list -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">All Windows</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr><th>Name</th><th>Opens</th><th>Closes</th><th>Capacity</th><th>Active</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($windows)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No application windows yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($windows as $w): ?>
                                        <tr>
                                            <td><?= e($w['name']) ?></td>
                                            <td><?= format_datetime($w['open_date']) ?></td>
                                            <td><?= format_datetime($w['close_date']) ?></td>
                                            <td><?= $w['max_capacity'] !== null ? (int) $w['max_capacity'] : '—' ?></td>
                                            <td>
                                                <?php if ($w['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="/FMS/admin/application_windows.php?edit=<?= (int) $w['id'] ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <form action="/FMS/actions/toggle_window.php" method="post" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="window_id" value="<?= (int) $w['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-<?= $w['is_active'] ? 'secondary' : 'success' ?>">
                                                        <?= $w['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </form>
                                                <form action="/FMS/actions/toggle_deleted.php" method="post" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="entity" value="application_window">
                                                    <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= $w['deleted'] ? 'Restore' : 'Delete' ?></button>
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
            <?php if ($editingWindow): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-warning">
                        <div class="card-header"><h3 class="card-title">Edit Application Window</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/update_window.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="window_id" value="<?= (int) $editingWindow['id'] ?>">
                                <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="<?= e($editingWindow['name']) ?>" required></div>
                                <div class="form-group"><label>Open Date &amp; Time</label><input type="datetime-local" name="open_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($editingWindow['open_date'])) ?>" required></div>
                                <div class="form-group"><label>Close Date &amp; Time</label><input type="datetime-local" name="close_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($editingWindow['close_date'])) ?>" required></div>
                                <div class="form-group"><label>Max Capacity</label><input type="number" name="max_capacity" class="form-control" min="1" value="<?= $editingWindow['max_capacity'] !== null ? (int) $editingWindow['max_capacity'] : '' ?>"></div>
                                <div class="form-check mb-3"><input type="checkbox" name="is_active" class="form-check-input" id="windowActive" <?= $editingWindow['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="windowActive">Active</label></div>
                                <button type="submit" class="btn btn-warning">Update Window</button>
                                <a href="/FMS/admin/application_windows.php" class="btn btn-default ml-2">Cancel</a>
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
