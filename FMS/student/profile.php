<?php
/**
 * Student Profile
 *
 * Allows the logged-in student to view and update their information
 * stored in the `students` table. Only fields the student is allowed
 * to change are editable (e.g. email, phone, name, gender, nationality,
 * DOB, study level, course). The registration number is shown but not
 * editable.
 */
require_once __DIR__ . '/../includes/student_check.php';

$pageTitle = 'My Profile';

// Load lookup data for dropdowns
$nationalities = get_nationalities($pdo);
$studyLevels   = get_study_levels($pdo);

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
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Profile Picture</h3></div>
                        <div class="card-body text-center">
                            <?php if (!empty($currentStudent['profile_picture']) && file_exists(__DIR__ . '/../uploads/profiles/' . $currentStudent['profile_picture'])): ?>
                                <img src="/FMS/uploads/profiles/<?= e($currentStudent['profile_picture']) ?>" alt="Profile Picture" style="width:150px; height:150px; border-radius:50%; object-fit:cover; border:3px solid #dee2e6; margin-bottom:16px;">
                            <?php else: ?>
                                <i class="fas fa-user-circle" style="font-size:150px; color:#adb5bd; margin-bottom:16px;"></i>
                            <?php endif; ?>

                            <form action="/FMS/actions/upload_picture.php" method="post" enctype="multipart/form-data">
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <input type="file" name="profile_picture" class="form-control-file" accept="image/png,image/jpeg" required>
                                    <small class="form-text text-muted">PNG or JPEG only. Max 2MB.</small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-upload"></i> Upload Picture
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Change Password</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/update_profile.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="form_type" value="password">
                             <hr>
                                <h5>Change Password (leave blank to keep current)</h5>
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" class="form-control" minlength="6">
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="new_password_confirm" class="form-control" minlength="6">
                                </div>
                                <button type="submit" class="btn btn-warning">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Update Profile</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/update_profile.php" method="post">
                                <?= csrf_field() ?>

                                <!-- Registration number is read-only -->
                                <div class="form-group">
                                    <label>Registration Number</label>
                                    <input type="text" class="form-control" value="<?= e($currentStudent['registration_no']) ?>" readonly disabled>
                                </div>
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="<?= e($currentStudent['full_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= e($currentStudent['email']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">— Select —</option>
                                        <option value="Male" <?= $currentStudent['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= $currentStudent['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= $currentStudent['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input id="dob" type="text" name="dob" class="form-control" value="<?= e($currentStudent['dob'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Nationality</label>
                                    <select name="nationality_id" class="form-control">
                                        <option value="">— Select —</option>
                                        <?php foreach ($nationalities as $n): ?>
                                            <option value="<?= (int) $n['id'] ?>" <?= $currentStudent['nationality_id'] == $n['id'] ? 'selected' : '' ?>>
                                                <?= e($n['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Study Level</label>
                                    <select name="study_level_id" class="form-control">
                                        <option value="">— Select —</option>
                                        <?php foreach ($studyLevels as $sl): ?>
                                            <option value="<?= (int) $sl['id'] ?>" <?= $currentStudent['study_level_id'] == $sl['id'] ? 'selected' : '' ?>>
                                                <?= e($sl['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Course of Study</label>
                                    <input type="text" name="course_of_study" class="form-control" value="<?= e($currentStudent['course_of_study'] ?? '') ?>">
                                </div>

                               

                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
