<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/db_config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../pages/unauthorized.php");
    exit();
}

// Initialize messages
$success_message = '';
$error_message = '';

// Get current settings
$tableExistsQuery = "SHOW TABLES LIKE 'settings'";
$tableExists = $conn->query($tableExistsQuery)->num_rows > 0;

if (!$tableExists) {
    // Create settings table if it doesn't exist
    $createTableSql = "CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        site_name VARCHAR(100) NOT NULL DEFAULT 'MedOok',
        admin_email VARCHAR(100) NOT NULL DEFAULT 'admin@example.com',
        appointment_time_slot INT NOT NULL DEFAULT 30,
        max_appointments_per_day INT NOT NULL DEFAULT 20,
        notification_enabled BOOLEAN NOT NULL DEFAULT 0,
        maintenance_mode BOOLEAN NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTableSql) === TRUE) {
        // Insert default settings
        $insertDefaultSql = "INSERT INTO settings (site_name, admin_email, appointment_time_slot, max_appointments_per_day) 
                             VALUES ('MedOok', 'admin@example.com', 30, 20)";
        if ($conn->query($insertDefaultSql) === TRUE) {
            $success_message = "Settings table created with default values";
        } else {
            $error_message = "Error creating default settings: " . $conn->error;
        }
    } else {
        $error_message = "Error creating settings table: " . $conn->error;
    }
}

// Now try to get settings
$settingsSql = "SELECT * FROM settings WHERE id = 1";
$settingsResult = $conn->query($settingsSql);
$settings = $settingsResult && $settingsResult->num_rows > 0 ? $settingsResult->fetch_assoc() : null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        // Get form data
        $site_name = trim($_POST['site_name']);
        $admin_email = trim($_POST['admin_email']);
        $appointment_time_slot = intval($_POST['appointment_time_slot']);
        $max_appointments_per_day = intval($_POST['max_appointments_per_day']);
        $notification_enabled = isset($_POST['notification_enabled']) ? 1 : 0;
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        
        // Validate inputs
        $errors = [];
        
        if (empty($site_name)) {
            $errors[] = "Site name is required";
        }
        
        if (empty($admin_email)) {
            $errors[] = "Admin email is required";
        } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if ($appointment_time_slot < 15 || $appointment_time_slot > 60) {
            $errors[] = "Appointment time slot must be between 15 and 60 minutes";
        }
        
        if ($max_appointments_per_day < 1 || $max_appointments_per_day > 100) {
            $errors[] = "Maximum appointments per day must be between 1 and 100";
        }
        
        // Update settings if no errors
        if (empty($errors)) {
            $updateSql = "UPDATE settings SET 
                         site_name = ?,
                         admin_email = ?,
                         appointment_time_slot = ?,
                         max_appointments_per_day = ?,
                         notification_enabled = ?,
                         maintenance_mode = ?
                         WHERE id = 1";
            
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssiiii", $site_name, $admin_email, $appointment_time_slot, $max_appointments_per_day, $notification_enabled, $maintenance_mode);
            
            if ($updateStmt->execute()) {
                $success_message = "Settings updated successfully";
                
                // Refresh settings
                $settingsResult = $conn->query($settingsSql);
                $settings = $settingsResult->fetch_assoc();
            } else {
                $error_message = "Error updating settings: " . $conn->error;
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    } elseif (isset($_POST['clear_cache'])) {
        // Simulate cache clearing
        sleep(1); // Simulate operation
        $success_message = "System cache cleared successfully";
    } elseif (isset($_POST['update_password'])) {
        $current_password = trim($_POST['current_password']);
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);
        
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = "Current password is required";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            // Get current user's password
            $user_id = $_SESSION['user_id'];
            $userSql = "SELECT password FROM users WHERE id = ?";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param("i", $user_id);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $user = $userResult->fetch_assoc();
            
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updatePasswordSql = "UPDATE users SET password = ? WHERE id = ?";
                $updatePasswordStmt = $conn->prepare($updatePasswordSql);
                $updatePasswordStmt->bind_param("si", $hashed_password, $user_id);
                
                if ($updatePasswordStmt->execute()) {
                    $success_message = "Password updated successfully";
                } else {
                    $error_message = "Error updating password: " . $conn->error;
                }
            } else {
                $error_message = "Current password is incorrect";
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-3 bg-primary text-white p-0 min-vh-100">
            <div class="p-4">
                <h4 class="mb-4">Admin Dashboard</h4>
            </div>
            <div class="px-3">
                <a href="dashboard.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item">
                        <i class="fas fa-tachometer-alt me-3 text-white-50"></i>
                        <span class="text-white">Dashboard</span>
                    </div>
                </a>
                <a href="users.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item">
                        <i class="fas fa-users me-3 text-white-50"></i>
                        <span class="text-white">Patients</span>
                    </div>
                </a>
                <a href="doctors.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item">
                        <i class="fas fa-user-md me-3 text-white-50"></i>
                        <span class="text-white">Doctors</span>
                    </div>
                </a>
                <a href="appointments.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item">
                        <i class="fas fa-calendar-check me-3 text-white-50"></i>
                        <span class="text-white">Appointments</span>
                    </div>
                </a>
                <a href="settings.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 active d-flex align-items-center sidebar-item" style="background-color: rgba(255,255,255,0.2);">
                        <i class="fas fa-cog me-3 text-white-50"></i>
                        <span class="text-white">Settings</span>
                    </div>
                </a>
                <a href="../logout.php" class="text-decoration-none mt-5 d-inline-block menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item sidebar-item-logout">
                        <i class="fas fa-sign-out-alt me-3 text-white-50"></i>
                        <span class="text-white">Logout</span>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-9 p-4">
            <h2 class="mb-4">System Settings</h2>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <!-- Settings Tabs -->
                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                                <i class="fas fa-sliders-h me-2"></i>General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="appointment-tab" data-bs-toggle="tab" data-bs-target="#appointment" type="button" role="tab" aria-controls="appointment" aria-selected="false">
                                <i class="fas fa-calendar-alt me-2"></i>Appointment
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                                <i class="fas fa-shield-alt me-2"></i>Security
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab" aria-controls="system" aria-selected="false">
                                <i class="fas fa-server me-2"></i>System
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content pt-4" id="settingsTabsContent">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">General Settings</h5>
                                    
                                    <form method="post" action="">
                                        <div class="mb-3">
                                            <label for="site_name" class="form-label">Site Name</label>
                                            <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'MedOok'); ?>" required>
                                            <div class="form-text">This name will be used throughout the application.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="admin_email" class="form-label">Admin Email</label>
                                            <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email'] ?? 'admin@example.com'); ?>" required>
                                            <div class="form-text">Main admin contact email.</div>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                            <div class="form-text">Enables maintenance mode which restricts site access.</div>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="notification_enabled" name="notification_enabled" <?php echo ($settings['notification_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="notification_enabled">Email Notifications</label>
                                            <div class="form-text">Enable email notifications for new appointments.</div>
                                        </div>
                                        
                                        <button type="submit" name="update_settings" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Appointment Settings -->
                        <div class="tab-pane fade" id="appointment" role="tabpanel" aria-labelledby="appointment-tab">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Appointment Settings</h5>
                                    
                                    <form method="post" action="">
                                        <div class="mb-3">
                                            <label for="appointment_time_slot" class="form-label">Appointment Time Slot (minutes)</label>
                                            <select class="form-select" id="appointment_time_slot" name="appointment_time_slot">
                                                <option value="15" <?php echo ($settings['appointment_time_slot'] ?? 30) == 15 ? 'selected' : ''; ?>>15 minutes</option>
                                                <option value="30" <?php echo ($settings['appointment_time_slot'] ?? 30) == 30 ? 'selected' : ''; ?>>30 minutes</option>
                                                <option value="45" <?php echo ($settings['appointment_time_slot'] ?? 30) == 45 ? 'selected' : ''; ?>>45 minutes</option>
                                                <option value="60" <?php echo ($settings['appointment_time_slot'] ?? 30) == 60 ? 'selected' : ''; ?>>60 minutes</option>
                                            </select>
                                            <div class="form-text">Default duration for appointment slots.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="max_appointments_per_day" class="form-label">Maximum Appointments Per Day</label>
                                            <input type="number" class="form-control" id="max_appointments_per_day" name="max_appointments_per_day" value="<?php echo intval($settings['max_appointments_per_day'] ?? 20); ?>" min="1" max="100">
                                            <div class="form-text">Maximum number of appointments allowed per day for each doctor.</div>
                                        </div>
                                        
                                        <!-- Hidden fields to maintain other settings -->
                                        <input type="hidden" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'MedOok'); ?>">
                                        <input type="hidden" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email'] ?? 'admin@example.com'); ?>">
                                        <input type="hidden" name="notification_enabled" value="<?php echo ($settings['notification_enabled'] ?? 0) ? '1' : '0'; ?>">
                                        <input type="hidden" name="maintenance_mode" value="<?php echo ($settings['maintenance_mode'] ?? 0) ? '1' : '0'; ?>">
                                        
                                        <button type="submit" name="update_settings" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Settings -->
                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Security Settings</h5>
                                    
                                    <form method="post" action="">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <div class="form-text">Password must be at least 6 characters long.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        
                                        <button type="submit" name="update_password" class="btn btn-primary">
                                            <i class="fas fa-key me-2"></i>Update Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Settings -->
                        <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">System Management</h5>
                                    
                                    <div class="mb-4">
                                        <h6>System Information</h6>
                                        <dl class="row">
                                            <dt class="col-sm-4">PHP Version</dt>
                                            <dd class="col-sm-8"><?php echo phpversion(); ?></dd>
                                            
                                            <dt class="col-sm-4">Database</dt>
                                            <dd class="col-sm-8">MySQL</dd>
                                            
                                            <dt class="col-sm-4">Server</dt>
                                            <dd class="col-sm-8"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></dd>
                                            
                                            <dt class="col-sm-4">Last Updated</dt>
                                            <dd class="col-sm-8"><?php echo isset($settings['updated_at']) ? date('F d, Y g:i A', strtotime($settings['updated_at'])) : 'N/A'; ?></dd>
                                        </dl>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>System Maintenance</h6>
                                        <div class="d-flex gap-2">
                                            <form method="post" action="">
                                                <button type="submit" name="clear_cache" class="btn btn-outline-primary">
                                                    <i class="fas fa-broom me-2"></i>Clear System Cache
                                                </button>
                                            </form>
                                            
                                            <a href="backup.php" class="btn btn-outline-info">
                                                <i class="fas fa-database me-2"></i>Database Backup
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Help and Instructions -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Help & Instructions</h5>
                            <p class="card-text">Configure your system settings to optimize the application for your needs.</p>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Changes to settings take effect immediately.
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Enabling maintenance mode will restrict access to admins only.
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Update your password regularly for security.
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- System Status -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">System Status</h5>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Database Connection</span>
                                    <span class="badge bg-success">Connected</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>System Status</span>
                                    <?php if (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == 1): ?>
                                        <span class="badge bg-warning text-dark">Maintenance</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Online</span>
                                    <?php endif; ?>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <?php if (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == 1): ?>
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                    <?php else: ?>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Email Notifications</span>
                                    <?php if (isset($settings['notification_enabled']) && $settings['notification_enabled'] == 1): ?>
                                        <span class="badge bg-success">Enabled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Disabled</span>
                                    <?php endif; ?>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <?php if (isset($settings['notification_enabled']) && $settings['notification_enabled'] == 1): ?>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    <?php else: ?>
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Animation Styles -->
<style>
    .sidebar-item {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .sidebar-item:not(.active):hover {
        background-color: rgba(255,255,255,0.1);
        transform: translateX(5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .sidebar-item:before {
        content: '';
        position: absolute;
        left: -20px;
        top: 0;
        height: 100%;
        width: 5px;
        background-color: #fff;
        opacity: 0;
        transition: all 0.3s ease;
    }
    
    .sidebar-item:not(.active):hover:before {
        left: 0;
        opacity: 0.6;
    }
    
    .menu-link:hover .fas {
        transform: scale(1.2);
        transition: transform 0.3s ease;
    }
    
    .sidebar-item-logout:hover {
        background-color: rgba(220, 53, 69, 0.2) !important;
    }
    
    .sidebar-item.active {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
</style>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?> 