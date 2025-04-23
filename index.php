<?php
// Start output buffering to prevent header issues
ob_start();

// Start the session
session_start();

// Debug session information
error_log("Index - Session ID: " . session_id());
error_log("Index - Session active: " . (session_status() === PHP_SESSION_ACTIVE ? "Yes" : "No"));
error_log("Index - User logged in: " . (isset($_SESSION['user_id']) ? "Yes (ID: {$_SESSION['user_id']})" : "No"));
error_log("Index - Full session data: " . print_r($_SESSION, true));

// Include path configuration
require_once __DIR__ . '/config/path_config.php';

// Add a test cookie to check if cookies are working
setcookie('medook_index_test', 'index_' . time(), time() + 3600, '/');
error_log("Index - Setting test cookie");

// Make sure to write session data before redirecting
session_write_close();

// Redirect to home page
redirect('pages.home');
?> 