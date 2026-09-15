<?php
require_once __DIR__ . '/../includes/admin_check.php';

if (!current_user_is_admin()) {
    set_flash('error', 'Only administrators can edit staff accounts.');
    redirect('/FMS/admin/supervisors.php');
}

$userId = (int) ($_GET['id'] ?? 0);
$user = $userId > 0 ? get_user($pdo, $userId) : null;
if (!$user) {
    set_flash('error', 'Staff account not found.');
    redirect('/FMS/admin/supervisors.php');
}

$roles = get_all_roles($pdo);
$assignableRoles = ['secretary', 'field_coordinator', 'hod', 'placement_officer', 'academic_supervisor', 'industrial_supervisor', 'supervisor'];
$userRoleIds = get_user_role_ids($pdo, $userId);
$selectedRoleId = $userRoleIds[0] ?? '';
$pageTitle = 'Edit Staff Account';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header"><div class="container-fluid"><h1 class="m-0">Edit Staff Account</h1></div></div>
    <div class="content"><div class="container-fluid"><div class="row"><div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Update <?= e($user['full_name']) ?></h3></div>
            <div class="card-body">
                <form action="/FMS/actions/update_supervisor.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                    <div class="form-group"><label>Username</label><input type="text" class="form-control" value="<?= e($user['username']) ?>" readonly></div>
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone_number" class="form-control" value="<?= e($user['phone_number'] ?? '') ?>"></div>
                    <div class="form-group"><label>Designation</label><input type="text" name="designation" class="form-control" value="<?= e($user['designation'] ?? '') ?>"></div>
                    <div class="form-group"><label>Role</label><select name="role_id" class="form-control" required><?php foreach ($roles as $role): ?><?php if (in_array($role['name'], $assignableRoles, true)): ?><option value="<?= (int) $role['id'] ?>" <?= (int) $selectedRoleId === (int) $role['id'] ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $role['name']))) ?></option><?php endif; ?><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control" required><?php foreach (['active', 'suspended'] as $status): ?><option value="<?= $status ?>" <?= $user['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select></div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="/FMS/admin/supervisors.php" class="btn btn-default">Cancel</a>
                </form>
            </div>
        </div>
    </div></div></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
