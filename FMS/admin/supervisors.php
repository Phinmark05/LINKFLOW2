<?php
require_once __DIR__ . '/../includes/admin_check.php';
if (!current_user_is_admin()) {
    set_flash('error', 'Only administrators can manage staff accounts.');
    redirect('/FMS/admin/dashboard.php');
}

$pageTitle = 'Staff Management';

$users   = get_all_users($pdo);
$roles   = get_all_roles($pdo);

// Build a map of role_id => role_name for quick lookup
$roleMap = [];
foreach ($roles as $r) {
    $roleMap[$r['id']] = $r['name'];
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
                                    <input type="password" name="password" class="form-control" required minlength="6">
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone_number" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Designation</label>
                                    <input type="text" name="designation" class="form-control" placeholder="e.g. Head of Department">
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <select name="role_id" class="form-control" required>
                                        <option value="">— Select Role —</option>
                                        <?php foreach ($roles as $r): ?>
                                            <?php if (in_array($r['name'], $assignableRoles, true)): ?>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">No staff members found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><?= e($u['full_name']) ?></td>
                                            <td><?= e($u['username']) ?></td>
                                            <td><?= e($u['email']) ?></td>
                                            <td>
                                                <?php
                                                $userRoleIds = get_user_role_ids($pdo, (int) $u['id']);
                                                foreach ($userRoleIds as $rid):
                                                    if (isset($roleMap[$rid])):
                                                        $rname = $roleMap[$rid];
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
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
