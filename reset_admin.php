<?php
// Include the database configuration
require_once __DIR__ . '/config/db_config.php';

// New admin password
$new_password = 'admin123';

// Hash the password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the admin user's password
$sql = "UPDATE users SET password = ? WHERE email = 'admin@medook.com'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hashed_password);
$result = $stmt->execute();

if ($result) {
    echo "Admin password has been reset to: " . $new_password;
} else {
    echo "Error resetting password: " . $conn->error;
}
?> 