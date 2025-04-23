<?php
// Authentication helper functions

// Include path configuration
require_once __DIR__ . '/../config/path_config.php';

// Ensure output buffering is started in any file that includes this one
if (ob_get_level() == 0) ob_start();

// Check if user is logged in
function isLoggedIn() {
    $loggedIn = isset($_SESSION['user_id']);
    
    // Add debugging
    $sessionId = session_id();
    $sessionStatus = session_status() === PHP_SESSION_ACTIVE ? "Active" : "Not Active";
    $userId = $loggedIn ? $_SESSION['user_id'] : "Not set";
    
    error_log("isLoggedIn() check - Result: " . ($loggedIn ? "true" : "false"));
    error_log("isLoggedIn() check - Session ID: $sessionId, Status: $sessionStatus, User ID: $userId");
    
    if (!$loggedIn && $sessionStatus === "Active") {
        error_log("Session is active but user_id is not set. Session data: " . print_r($_SESSION, true));
    }
    
    // Additional check - if cookie exists, use it to confirm the session
    if (!$loggedIn && isset($_COOKIE['user_id'])) {
        error_log("Session lost but user_id cookie exists: " . $_COOKIE['user_id']);
        // Restore session from cookie (this is a temporary fix)
        $_SESSION['user_id'] = $_COOKIE['user_id'];
        if (isset($_COOKIE['user_role'])) {
            $_SESSION['role'] = $_COOKIE['user_role'];
        }
        if (isset($_COOKIE['user_name'])) {
            $_SESSION['full_name'] = $_COOKIE['user_name'];
        }
        return true;
    }
    
    return $loggedIn;
}

// Check if user has a specific role
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    return $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        // Ensure session is saved before redirect
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Use direct path instead of the redirect function
        header("Location: /medook/pages/login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        // Ensure session is saved before redirect
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Use direct path instead of the redirect function
        header("Location: /medook/pages/unauthorized.php");
        exit();
    }
}

// Redirect if not doctor
function requireDoctor() {
    requireLogin();
    if (!hasRole('doctor')) {
        // Ensure session is saved before redirect
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Use direct path instead of the redirect function
        header("Location: /medook/pages/unauthorized.php");
        exit();
    }
}
?> 