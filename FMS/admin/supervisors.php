<?php
require_once __DIR__ . '/../includes/admin_check.php';
if (!current_user_is_admin()) {
    set_flash('error', 'Only administrators can manage staff accounts.');
    redirect('/FMS/admin/dashboard.php');
}

$pageTitle = 'Staff Management';

// Fetch base data
$allUsers = get_all_users($pdo, true);
$roles    = get_all_roles($pdo);

// --- PAGINATION LOGIC ---
$limit       = 4; // Number of items per page
$totalUsers  = count($allUsers);
$totalPages  = max(1, ceil($totalUsers / $limit));$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
if ($currentPage >$totalPages) {
    $currentPage =$totalPages;
}
$offset = ($currentPage - 1) *$limit;

// Slice array for display (If `get_all_users` supports LIMIT/OFFSET in SQL, pass $limit and$offset directly to SQL instead)
$users = array_slice($allUsers, $offset,$limit);

// Build a map of role_id => role_name for quick lookup
$roleMap = [];
foreach ($roles as $r) {$roleMap[$r['id']] =$r['name'];
}

// Staff roles that can be assigned (excluding admin — admin is assigned separately)
$assignableRoles = ['secretary', 'field_coordinator', 'hod', 'placement_officer', 'academic_supervisor', 'industrial_supervisor', 'supervisor'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Staff Management</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Create new staff user -->
                <div class="col-md-3">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Add Staff Member</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/save_supervisor.php" method="post">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control" required>
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
                                    <label>Password</label>
                                    <input type="password" name="password" class="form-control" required minlength="8">
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone_number" class="form-control" minlength="9">
                                </div>
                                <div class="form-group">
                                    <label>Designation</label>
                                    <input type="text" name="designation" class="form-control" placeholder="e.g. Head of Department">
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <select name="role_id" class="form-control" required>
                                        <option value="">— Select Role —</option>
                                        <?php foreach ($roles as$r): ?>
                                            <?php if (in_array($r['name'],$assignableRoles, true)): ?>
                                                <option value="<?= (int) $r['id'] ?>"><?= e(ucwords(str_replace('_', ' ', $r['name']))) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">
                                        Secretary → checks completeness<br>
                                        Field Coordinator → verifies academic details<br>
                                        HOD → approves or rejects<br>
                                        Placement Officer → assigns placement
                                    </small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Create Staff Account</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- All staff users -->
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">All Staff Members</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role(s)</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                        <th>Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No staff members found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($users as$u): ?>
                                        <tr>
                                            <td><?= e($u['full_name']) ?></td>
                                            <td><?= e($u['username']) ?></td>
                                            <td><?= e($u['email']) ?></td>
                                            <td>
                                                <?php
                                                $userRoleIds = get_user_role_ids($pdo, (int)$u['id']);
                                                foreach ($userRoleIds as$rid):
                                                    if (isset($roleMap[$rid])):$rname = $roleMap[$rid];
                                                        $badgeClass = match ($rname) {
                                                            'admin' => 'bg-danger',
                                                            'secretary' => 'bg-info',
                                                            'field_coordinator' => 'bg-primary',
                                                            'hod' => 'bg-warning text-dark',
                                                            'placement_officer' => 'bg-success',
                                                            'academic_supervisor' => 'bg-secondary',
                                                            'industrial_supervisor' => 'bg-secondary',
                                                            'supervisor' => 'bg-secondary',
                                                            default => 'bg-secondary',
                                                        };
                                                ?>
                                                    <span class="badge <?= $badgeClass ?>"><?= e(ucwords(str_replace('_', ' ', $rname))) ?></span>
                                                <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            </td>
                                            <td><?php if ($u['status'] === 'suspended'): ?>
                                                <span class="badge bg-danger">Suspended</span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?= e(ucfirst($u['status'])) ?></span>
                                            <?php endif; ?></td>
                                            <td>
                                                <a href="/FMS/admin/edit_supervisor.php?id=<?= (int) $u['id'] ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </td>
                                            <td>
                                                <form action="/FMS/actions/toggle_deleted.php" method="post" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="entity" value="supervisor">
                                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= $u['deleted'] ? 'Restore' : 'Delete' ?></button>
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
                                <!-- Previous Link -->
                                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $currentPage - 1 ?>">&laquo;</a>
                                </li>
                                
                                <!-- Page Numbers -->
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i ===$currentPage) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Next Link -->
                                <li class="page-item <?= ($currentPage >=$totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $currentPage + 1 ?>">&raquo;</a>
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