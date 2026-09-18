<?php
require_once __DIR__ . '/../includes/functions.php';
if (!empty($_SESSION['student_id'])) {
    redirect('/FMS/student/dashboard.php');
}
if (!empty($_SESSION['user_id'])) {
    redirect('/FMS/admin/dashboard.php');
}
$nationalities = get_nationalities($pdo);
$studyLevels   = get_study_levels($pdo);
$institutions  = get_all_institutions($pdo);
$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>

<div class="fms-split-page">
    <!-- Left: intro panel -->
    <div class="fms-split-left">
        <div class="fms-brand">
            <div class="login-intro-brand" style="border-radius: auto;">
                <img
                    src="/FMS/assets/img/Screenshot%202026-09-08%20at%2009-25-17%20Design%20Editor.png"
                    alt="LinkFlow logo"
                    class="login-intro-logo login-intro-logo-image"
                >
            </div>
            <h1>LinkFlow</h1>
            <p>Field Application Management System</p>
        </div>
        <div class="fms-intro">
            <h2>Start your field journey</h2>
            <p>Create your student account to apply for field placements, track applications, and connect with supervisors.</p>
            <ul class="fms-features">
                <li>Apply for field placements, internships or volunteer opportunities online</li>
                <li>Track your application status</li>
                <li>Get notified on approvals, or any changes applied</li>
            </ul>
        </div>
        <div class="fms-left-footer">
           
        </div>
    </div>

    <!-- Right: registration form -->
    <div class="fms-split-right">
        <div class="fms-form-card">
            <div class="fms-form-header">
                <h3>Create Account</h3>
                <p>Fill in your details to register</p>
            </div>

            <form action="/FMS/actions/register.php" method="post">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label>Registration Number</label>
                    <input type="text" name="registration_no" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">— Select —</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirm" class="form-control" required minlength="6">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input id="dob" type="text" name="dob" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Nationality</label>
                        <select name="nationality_id" class="form-control">
                            <option value="">— Select —</option>
                            <?php foreach ($nationalities as $n): ?>
                                <option value="<?= (int) $n['id'] ?>"><?= e($n['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Study Level</label>
                        <select name="study_level_id" class="form-control">
                            <option value="">— Select —</option>
                            <?php foreach ($studyLevels as $sl): ?>
                                <option value="<?= (int) $sl['id'] ?>"><?= e($sl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Course of Study</label>
                        <input type="text" name="course_of_study" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label>Institution</label>
                    <select name="institution_id" class="form-control" required>
                        <option value="">— Select Institution —</option>
                        <?php foreach ($institutions as $institution): ?>
                            <?php if ($institution['is_active']): ?>
                                <option value="<?= (int) $institution['id'] ?>"><?= e($institution['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>

            <p class="fms-alt-link">
                Already have an account? <a href="/FMS/auth/login.php">Login</a>
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
