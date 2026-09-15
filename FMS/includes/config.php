<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'LAS');
define('DB_USER', 'liltreck');
define('DB_PASS', '1'); 
define('DB_CHARSET', 'utf8mb4');
define('APP_URL', 'http://localhost/FMS');
date_default_timezone_set('UTC');
