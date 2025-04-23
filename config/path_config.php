<?php
// Path configuration for the MedOok application

// Define the base URL for the application
// This should be the root URL where the application is installed
// Example: http://localhost/medook or https://example.com/medook
$base_url = "/medook";

// Define common paths
$config = [
    'base_url' => $base_url,
    'pages' => [
        'home' => "$base_url/pages/home.php",
        'login' => "$base_url/pages/login.php",
        'register' => "$base_url/pages/register.php",
        'unauthorized' => "$base_url/pages/unauthorized.php",
        'profile' => "$base_url/pages/profile.php",
        'doctors' => "$base_url/pages/doctors.php",
        'doctor_profile' => "$base_url/pages/doctor_profile.php",
        'appointments' => "$base_url/pages/appointments.php",
        'appointment_confirmation' => "$base_url/pages/appointment_confirmation.php",
        'process_appointment' => "$base_url/pages/process_appointment.php",
        'logout' => "$base_url/pages/logout.php",
        'forgot_password' => "$base_url/pages/forgot_password.php",
        'contact' => "$base_url/pages/contact.php"
    ],
    'admin' => [
        'dashboard' => "$base_url/pages/admin/dashboard.php",
        'users' => "$base_url/pages/admin/users.php",
        'doctors' => "$base_url/pages/admin/doctors.php",
        'appointments' => "$base_url/pages/admin/appointments.php"
    ],
    'doctor' => [
        'dashboard' => "$base_url/pages/doctor/dashboard.php",
        'appointments' => "$base_url/pages/doctor/appointments.php"
    ],
    'assets' => [
        'css' => "$base_url/assets/css",
        'js' => "$base_url/assets/js",
        'images' => "$base_url/assets/images"
    ]
];

// Function to get URL for a specific path
function getUrl($path) {
    global $config;
    
    // Parse the path (e.g., "admin.dashboard", "pages.login")
    $parts = explode('.', $path);
    
    $current = $config;
    foreach ($parts as $part) {
        if (isset($current[$part])) {
            $current = $current[$part];
        } else {
            return $config['base_url']; // Return base URL if path not found
        }
    }
    
    return $current;
}

// Function to redirect to a specific path
function redirect($path, $params = []) {
    $url = getUrl($path);
    
    // Add query parameters if any
    if (!empty($params)) {
        $queryString = http_build_query($params);
        $url .= '?' . $queryString;
    }
    
    // Ensure session data is written before redirecting
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    // Log the redirect for debugging
    error_log("Redirecting to: $url");
    
    header("Location: " . $url);
    exit();
}
?> 