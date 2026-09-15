<?php
/**
 * Logout Action
 *
 * Destroys the session and redirects to the login page.
 * This is a GET request — no CSRF token needed because logout
 * does not modify any data.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Unset all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login
redirect('/FMS/auth/login.php');
