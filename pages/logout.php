<?php
// Start output buffering to prevent header issues
ob_start();

// Include path configuration
require_once __DIR__ . '/../config/path_config.php';

// Unset all session variables
$_SESSION = array();

// Clear authentication cookies
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
setcookie('user_id', '', time() - 3600, '/', '', $secure, true);
setcookie('user_role', '', time() - 3600, '/', '', $secure, true);
setcookie('user_name', '', time() - 3600, '/', '', $secure, true);

// If a session cookie is used, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Log the logout
error_log("User logged out. Session and cookies cleared.");

// Redirect to login page
redirect('pages.login');
?> 