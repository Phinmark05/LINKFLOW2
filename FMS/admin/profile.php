<?php

require_once __DIR__ . '/../includes/admin_check.php';



$pageTitle = 'My Profile';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">My Profile</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Update My Details</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/update_admin_profile.php" method="post">
                                <?= csrf_field() ?>

                                <!-- Username is read-only -->
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control" value="<?= e($currentUser['username']) ?>">
                                </div>
                                <!-- Designation is read-only -->
                                <div class="form-group">
                                    <label>Designation</label>
                                    <input type="text" name="designation" class="form-control" value="<?= e($currentUser['designation'] ?? '—') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="<?= e($currentUser['full_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= e($currentUser['email']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone_number" class="form-control" value="<?= e($currentUser['phone_number'] ?? '') ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-info">
                        <div class="card-header"><h3 class="card-title">Account Info</h3></div>
                        <div class="card-body">
                            <p><strong>Username:</strong> <?= e($currentUser['username']) ?></p>
                            <p><strong>Designation:</strong> <?= e($currentUser['designation'] ?? '—') ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-success"><?= e(ucfirst($currentUser['status'])) ?></span></p>
                            
                        </div>
                    </div>
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Change Password</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/update_admin_profile.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="password">
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" class="form-control" minlength="8">
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="new_password_confirm" class="form-control" minlength="8">
                                </div>
                                <button type="submit" class="btn btn-warning">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
