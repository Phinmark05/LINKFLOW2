<?php
require_once __DIR__ . '/../includes/admin_check.php';

if (!current_user_is_admin()) {
    set_flash('error', 'Only administrators can manage institutions.');
    redirect('/FMS/admin/dashboard.php');
}

$pageTitle = 'Institutions';

// 1. Fetch ALL institutions first
$allInstitutions = get_all_institutions($pdo, true);

// Handle edit target before slicing
$editId = (int) ($_GET['edit'] ?? 0);
$editing = null;
foreach ($allInstitutions as $inst) {
    if ((int) $inst['id'] === $editId) {
        $editing = $inst;
        break;
    }
}

// 2. Pagination Logic (Limit set to 4)
$limit             = 4;
$totalInstitutions = count($allInstitutions);
$totalPages        = max(1, ceil($totalInstitutions / $limit));
$currentPage       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $limit;

// Slice array for the active page
$institutions = array_slice($allInstitutions, $offset, $limit);

// Preserve edit ID in pagination links if currently editing
$urlParams = $editId ? "&edit={$editId}" : "";

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header"><div class="container-fluid"><h1 class="m-0">Institutions</h1></div></div>
    <div class="content"><div class="container-fluid"><div class="row">
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Add Institution</h3></div>
                <div class="card-body">
                    <form action="/FMS/actions/save_institution.php" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group"><label>Institution Name</label><input type="text" name="name" class="form-control" required></div>
                        <button type="submit" class="btn btn-primary btn-block">Save Institution</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">All Institutions</h3></div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead><tr><th>Institution</th><th>Status</th><th>Action</th><th>Delete</th></tr></thead>
                        <tbody>
                        <?php if (empty($institutions)): ?><tr><td colspan="4" class="text-center text-muted">No institutions found.</td></tr>
                        <?php else: foreach ($institutions as $institution): ?>
                            <tr>
                                <td><?= e($institution['name']) ?></td>
                                <td><span class="badge <?= $institution['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $institution['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                <td>
                                    <a href="/FMS/admin/institutions.php?edit=<?= (int) $institution['id'] ?>&page=<?= $currentPage ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                                <td>
                                    <form action="/FMS/actions/toggle_deleted.php" method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="entity" value="institution">
                                        <input type="hidden" name="id" value="<?= (int) $institution['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><?= $institution['deleted'] ? 'Restore' : 'Delete' ?></button>
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
                <div class="card-header"><h3 class="card-title">Edit Institution</h3></div>
                <div class="card-body">
                    <form action="/FMS/actions/update_institution.php" method="post">
                        <?= csrf_field() ?><input type="hidden" name="institution_id" value="<?= (int) $editing['id'] ?>">
                        <div class="form-group"><label>Institution Name</label><input type="text" name="name" class="form-control" value="<?= e($editing['name']) ?>" required></div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" class="form-check-input" id="institutionActive" <?= $editing['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="institutionActive">Active</label>
                        </div>
                        <button type="submit" class="btn btn-warning">Update Institution</button>
                        <a href="/FMS/admin/institutions.php?page=<?= $currentPage ?>" class="btn btn-default ml-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    </div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>