<?php
require_once __DIR__ . '/../includes/admin_check.php';

if (!current_user_is_admin()) {
    set_flash('error', 'Only administrators can manage specializations.');
    redirect('/FMS/admin/dashboard.php');
}

$pageTitle = 'Specializations';

// 1. Fetch ALL data first
$allSpecializations = get_specializations($pdo, false, true);
$editId = (int) ($_GET['edit'] ?? 0);
$editing = null;

// Find the item being edited from the FULL array (so it works even if on page 2)
foreach ($allSpecializations as $spec) {
    if ((int) $spec['id'] === $editId) {
        $editing = $spec;
        break;
    }
}

// 2. Pagination Logic (Limit to 4)
$limit       = 4; 
$totalSpecs  = count($allSpecializations);
$totalPages  = max(1, ceil($totalSpecs / $limit));
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $limit;

// Slice the array for the current page
$specializations = array_slice($allSpecializations, $offset, $limit);

// Preserve edit ID in pagination links so the form doesn't disappear when clicking next page
$urlParams = $editId ? "&edit={$editId}" : "";

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header"><div class="container-fluid"><h1 class="m-0">Specializations</h1></div></div>
    <div class="content"><div class="container-fluid"><div class="row">
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Add Specialization</h3></div>
                <div class="card-body">
                    <form action="/FMS/actions/save_specialization.php" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" required></div>
                        <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                        <button type="submit" class="btn btn-primary btn-block">Save Specialization</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Available Specializations</h3></div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead><tr><th>Name</th><th>Description</th><th>Status</th><th>Action</th><th>Delete</th></tr></thead>
                        <tbody>
                        <?php if (empty($specializations)): ?><tr><td colspan="5" class="text-center text-muted">No specializations found.</td></tr>
                        <?php else: foreach ($specializations as $specialization): ?>
                            <tr>
                                <td><?= e($specialization['name']) ?></td>
                                <td><?= e($specialization['description'] ?? '—') ?></td>
                                <td><span class="badge <?= $specialization['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $specialization['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                <td>
                                    <a href="/FMS/admin/specializations.php?edit=<?= (int) $specialization['id'] ?>&page=<?= $currentPage ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                                <td>
                                    <form action="/FMS/actions/toggle_deleted.php" method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="entity" value="specialization">
                                        <input type="hidden" name="id" value="<?= (int) $specialization['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><?= $specialization['deleted'] ? 'Restore' : 'Delete' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
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
    
    <?php if ($editing): ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card card-warning">
                <div class="card-header"><h3 class="card-title">Edit Specialization</h3></div>
                <div class="card-body">
                    <form action="/FMS/actions/update_specialization.php" method="post">
                        <?= csrf_field() ?><input type="hidden" name="specialization_id" value="<?= (int) $editing['id'] ?>">
                        <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="<?= e($editing['name']) ?>" required></div>
                        <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"><?= e($editing['description'] ?? '') ?></textarea></div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" class="form-check-input" id="specializationActive" <?= $editing['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="specializationActive">Active and available to students</label>
                        </div>
                        <button type="submit" class="btn btn-warning">Update Specialization</button>
                        <!-- Keep current page when cancelling -->
                        <a href="/FMS/admin/specializations.php?page=<?= $currentPage ?>" class="btn btn-default ml-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    </div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>