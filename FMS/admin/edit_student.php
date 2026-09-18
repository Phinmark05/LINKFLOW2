<?php
require_once __DIR__ . '/../includes/admin_check.php';

if (!current_user_is_admin()) {
    set_flash('error', 'Only administrators can edit student accounts.');
    redirect('/FMS/admin/students.php');
}

$studentId = (int) ($_GET['id'] ?? 0);
$student = $studentId > 0 ? get_student($pdo, $studentId) : null;
if (!$student) {
    set_flash('error', 'Student account not found.');
    redirect('/FMS/admin/students.php');
}

$nationalities = get_nationalities($pdo);
$studyLevels = get_study_levels($pdo);
$institutions = get_all_institutions($pdo);
$pageTitle = 'Edit Student';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Edit Student</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Update <?= e($student['full_name']) ?></h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/update_student.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                <div class="form-group">
                                    <label>Registration Number</label>
                                    <input type="text" class="form-control" value="<?= e($student['registration_no']) ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="<?= e($student['full_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= e($student['email']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">— Select —</option>
                                        <?php foreach (['Male', 'Female', 'Other'] as $gender): ?>
                                            <option value="<?= $gender ?>" <?= $student['gender'] === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="dob" class="form-control" value="<?= e($student['dob'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Nationality</label>
                                    <select name="nationality_id" class="form-control">
                                        <option value="">— Select —</option>
                                        <?php foreach ($nationalities as $nationality): ?>
                                            <option value="<?= (int) $nationality['id'] ?>" <?= (int) $student['nationality_id'] === (int) $nationality['id'] ? 'selected' : '' ?>><?= e($nationality['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Study Level</label>
                                    <select name="study_level_id" class="form-control">
                                        <option value="">— Select —</option>
                                        <?php foreach ($studyLevels as $studyLevel): ?>
                                            <option value="<?= (int) $studyLevel['id'] ?>" <?= (int) $student['study_level_id'] === (int) $studyLevel['id'] ? 'selected' : '' ?>><?= e($studyLevel['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Course of Study</label>
                                    <input type="text" name="course_of_study" class="form-control" value="<?= e($student['course_of_study'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Institution</label>
                                    <select name="institution_id" class="form-control" required>
                                        <option value="">— Select Institution —</option>
                                        <?php foreach ($institutions as $institution): ?>
                                            <option value="<?= (int) $institution['id'] ?>" <?= (int) $student['institution_id'] === (int) $institution['id'] ? 'selected' : '' ?>><?= e($institution['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control" required>
                                        <?php foreach (['active', 'suspended', 'graduated'] as $status): ?>
                                            <option value="<?= $status ?>" <?= $student['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="/FMS/admin/students.php" class="btn btn-default">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-info">
                        <div class="card-header"><h3 class="card-title">Account Info</h3></div>
                        <div class="card-body">
                            <p><strong>Registration No:</strong> <?= e($student['registration_no']) ?></p>
                            <p><strong>Status:</strong> <?= e(ucfirst($student['status'])) ?></p>
                        </div>
                    </div>
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Change Password</h3></div>
                        <div class="card-body">
                            <form action="/FMS/actions/update_student.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                <input type="hidden" name="full_name" value="<?= e($student['full_name']) ?>">
                                <input type="hidden" name="email" value="<?= e($student['email']) ?>">
                                <input type="hidden" name="gender" value="<?= e($student['gender'] ?? '') ?>">
                                <input type="hidden" name="dob" value="<?= e($student['dob'] ?? '') ?>">
                                <input type="hidden" name="nationality_id" value="<?= (int) $student['nationality_id'] ?>">
                                <input type="hidden" name="study_level_id" value="<?= (int) $student['study_level_id'] ?>">
                                <input type="hidden" name="institution_id" value="<?= $student['institution_id'] !== null ? (int) $student['institution_id'] : '' ?>">
                                <input type="hidden" name="course_of_study" value="<?= e($student['course_of_study'] ?? '') ?>">
                                <input type="hidden" name="status" value="<?= e($student['status']) ?>">
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" class="form-control" minlength="8" required>
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="new_password_confirm" class="form-control" minlength="8" required>
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