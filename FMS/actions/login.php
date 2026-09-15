<?php
/**
 * Login Action
 *
 * Processes the login form submission. Authenticates either a student
 * or a staff user by matching the identifier against both account tables.
 *
 * For students:
 *   - Look up by registration_no in the `students` table
 *   - Verify password with password_verify()
 *   - Check account status is 'active'
 *   - Store student_id in session
 *
 * For staff:
 *   - Look up by username in the `users` table
 *   - Verify password with password_verify()
 *   - Check account status is 'active' (not suspended or deleted)
 *   - Check if account is locked (locked_until > now)
 *   - Reset failed_login_attempts on success
 *   - Increment failed_login_attempts on failure, lock after 5 attempts
 *   - Store user_id in session
 *
 * In both cases, regenerate the session ID after login to prevent
 * session fixation attacks.
 */
require_once __DIR__ . '/../includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}

// Verify CSRF token to prevent cross-site request forgery
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission. Please try again.');
    redirect('/FMS/auth/login.php');
}

// Get submitted values
$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';

// Basic validation — both fields must be non-empty
if ($identifier === '' || $password === '') {
    set_flash('error', 'Please enter your credentials.');
    redirect('/FMS/auth/login.php');
}

// Try staff first so lockout and failed-attempt tracking are preserved.
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND deleted_at IS NULL");
$stmt->execute([$identifier]);
$user = $stmt->fetch();

// --- Staff Login ---
if ($user) {

    if (!$user) {
        set_flash('error', 'No user found with that username.');
        redirect('/FMS/auth/login.php');
    }

    // Check if the account is locked due to too many failed attempts
    if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
        set_flash('error', 'Your account is locked until ' . format_datetime($user['locked_until']) . '. Please try again later or contact the administrator.');
        redirect('/FMS/auth/login.php');
    }

    // Verify the password
    if (!password_verify($password, $user['password'])) {
        // Increment failed login attempts
        $newAttempts = (int) $user['failed_login_attempts'] + 1;

        // Lock the account after 5 failed attempts (for 30 minutes)
        if ($newAttempts >= 5) {
            $lockedUntil = date('Y-m-d H:i:s', time() + 1800);
            $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?");
            $stmt->execute([$newAttempts, $lockedUntil, $user['id']]);
            set_flash('error', 'Too many failed attempts. Your account has been locked for 30 minutes.');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?");
            $stmt->execute([$newAttempts, $user['id']]);
            set_flash('error', 'Incorrect password. Attempt ' . $newAttempts . ' of 5.');
        }
        redirect('/FMS/auth/login.php');
    }

    // Check account status — must be 'active'
    if ($user['status'] !== 'active') {
        set_flash('error', 'Your account is ' . $user['status'] . '. Contact the administrator.');
        redirect('/FMS/auth/login.php');
    }

    // Login successful — reset failed attempts and update last login time
    $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Store the user ID in the session
    $_SESSION['user_id'] = (int) $user['id'];

    redirect('/FMS/admin/dashboard.php');
}

// --- Student Login ---
$stmt = $pdo->prepare("SELECT * FROM students WHERE registration_no = ?");
$stmt->execute([$identifier]);
$student = $stmt->fetch();

if ($student) {
    if (!password_verify($password, $student['password'])) {
        set_flash('error', 'Incorrect password.');
        redirect('/FMS/auth/login.php');
    }

    if ($student['status'] !== 'active') {
        set_flash('error', 'Your account is ' . $student['status'] . '. Contact the administrator.');
        redirect('/FMS/auth/login.php');
    }

    session_regenerate_id(true);
    $_SESSION['student_id'] = (int) $student['id'];
    redirect('/FMS/student/dashboard.php');
}

set_flash('error', 'No account found with that username or registration number.');
redirect('/FMS/auth/login.php');
