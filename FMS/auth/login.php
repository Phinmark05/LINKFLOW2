<?php

/**
 * Login Page
 *
 * This page handles login for BOTH students and staff users.
 * The identifier is matched against registration numbers and usernames.
 *
 * For staff users, we also check account status (active/suspended/deleted),
 * failed login attempts, and locked-until time.
 */

require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to the appropriate dashboard
if (!empty($_SESSION['student_id'])) {
    redirect('/FMS/student/dashboard.php');
}

if (!empty($_SESSION['user_id'])) {
    redirect('/FMS/admin/dashboard.php');
}

$pageTitle = 'Login';

include __DIR__ . '/../includes/header.php';
?>

<div class="login-split">
    <!-- Left side: About the system -->
    <div class="login-intro col-md-6">
        <div class="login-intro-content">
            <div class="login-intro-brand" style="border-radius: auto;">
                <img
                    src="/FMS/assets/img/Screenshot%202026-09-08%20at%2009-25-17%20Design%20Editor.png"
                    alt="LinkFlow logo"
                    class="login-intro-logo login-intro-logo-image"
                >
            </div>
            <h1 class="login-intro-title">Field Application Management System</h1>
            <p class="login-intro-lead">
                A simple, centralized platform for managing student field applications,
                reviews, and placements from application to approval.
            </p>

            <ul class="login-intro-features">
                <li>
                    <span class="feature-icon"><i class="fas fa-paper-plane"></i></span>
                    <span>Submit and track field applications online</span>
                </li>
                <li>
                    <span class="feature-icon"><i class="fas fa-clipboard-check"></i></span>
                    <span>Review, approve, or reject applications</span>
                </li>
                <li>
                    <span class="feature-icon"><i class="fas fa-briefcase"></i></span>
                    <span>Manage placements and field assignments</span>
                </li>
                <li>
                    <span class="feature-icon"><i class="fas fa-bell"></i></span>
                    <span>Get real-time status notifications</span>
                </li>
            </ul>

            <div class="login-intro-footer">
                <p>Need an account?</p>
                <a href="/FMS/auth/register.php" class="login-intro-link">Register as a student <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="login-intro-shape"></div>
    </div>

    <!-- Right side: Login form -->
    <div class="login-form-side col-md-6">
        <div class="login-box">
            <div class="card login-card">
                <div class="card-body login-card-body">
                    <div class="login-logo text-center mb-3">
                        <b>Welcome Back</b>
                    </div>
                    <p class="login-box-msg">Sign in to start your session</p>

                    <form action="/FMS/actions/login.php" method="post">
                        <?= csrf_field() ?>

                        <div class="input-group mb-3">
                            <input type="text" name="identifier" class="form-control" placeholder="Registration No / Username" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-user"></span>
                                </div>
                            </div>
                        </div>

                        <div class="input-group mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-block btn-signin">Sign In</button>
                            </div>
                        </div>
                    </form>

                    <p class="mb-1 text-center mt-3">
                        <a href="/FMS/auth/register.php">Register as a student</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
