<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db_config.php';

// Ensure user is logged in
requireLogin();

// Get user details from database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize variables
$success_message = '';
$error_message = '';

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get and sanitize inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif ($email !== $user['email']) {
        // Check if email already exists (only if changed)
        $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmailStmt->bind_param("si", $email, $user_id);
        $checkEmailStmt->execute();
        $checkEmailResult = $checkEmailStmt->get_result();
        if ($checkEmailResult->num_rows > 0) {
            $errors[] = "Email is already registered";
        }
    }
    
    // If changing password, validate current and new password
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to set a new password";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        // Start with basic update data
        $updateData = [
            'full_name' => $full_name,
            'email' => $email
        ];
        
        // If changing password, add to update data
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateData['password'] = $hashed_password;
        }
        
        // Build the SQL query based on whether password is being updated
        if (!empty($new_password)) {
            $updateSql = "UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("sssi", $full_name, $email, $hashed_password, $user_id);
        } else {
            $updateSql = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssi", $full_name, $email, $user_id);
        }
        
        if ($updateStmt->execute()) {
            // Update session variables
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            
            $success_message = "Profile updated successfully";
            
            // Refresh user data
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error_message = "Failed to update profile: " . $conn->error;
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Get appointment statistics for dashboard
$appointmentStatsSql = "SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                           SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                        FROM appointments
                        WHERE patient_id = ?";
$statsStmt = $conn->prepare($appointmentStatsSql);
$statsStmt->bind_param("i", $user_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// Get upcoming appointments
$upcomingApptSql = "SELECT a.*, d.specialty, u.full_name as doctor_name 
                    FROM appointments a 
                    JOIN doctors d ON a.doctor_id = d.id 
                    JOIN users u ON d.user_id = u.id 
                    WHERE a.patient_id = ? AND a.status IN ('pending', 'confirmed') 
                    AND a.appointment_date >= CURDATE()
                    ORDER BY a.appointment_date ASC, a.appointment_time ASC
                    LIMIT 3";
$upcomingStmt = $conn->prepare($upcomingApptSql);
$upcomingStmt->bind_param("i", $user_id);
$upcomingStmt->execute();
$upcomingAppointments = $upcomingStmt->get_result();
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm" data-aos="fade-right">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; font-size: 2rem;">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                    </div>
                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                    <hr>
                    <div class="d-grid gap-2">
                        <a href="#profile-settings" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-cog me-2"></i>Edit Profile
                        </a>
                        <a href="appointments.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-calendar-check me-2"></i>My Appointments
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Stats -->
            <div class="card shadow-sm mt-4" data-aos="fade-right" data-aos-delay="100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">Appointment Overview</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark">Total Appointments</span>
                            <span class="fw-bold"><?php echo $stats['total']; ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark">Pending</span>
                            <span class="fw-bold"><?php echo $stats['pending']; ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                style="width: <?php echo $stats['total'] > 0 ? ($stats['pending'] / $stats['total'] * 100) : 0; ?>%;" 
                                aria-valuenow="<?php echo $stats['pending']; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="<?php echo $stats['total']; ?>"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark">Confirmed</span>
                            <span class="fw-bold"><?php echo $stats['confirmed']; ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: <?php echo $stats['total'] > 0 ? ($stats['confirmed'] / $stats['total'] * 100) : 0; ?>%;" 
                                aria-valuenow="<?php echo $stats['confirmed']; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="<?php echo $stats['total']; ?>"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark">Completed</span>
                            <span class="fw-bold"><?php echo $stats['completed']; ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" role="progressbar" 
                                style="width: <?php echo $stats['total'] > 0 ? ($stats['completed'] / $stats['total'] * 100) : 0; ?>%;" 
                                aria-valuenow="<?php echo $stats['completed']; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="<?php echo $stats['total']; ?>"></div>
                        </div>
                    </div>
                    
                    <div class="mb-0">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold text-dark">Cancelled</span>
                            <span class="fw-bold"><?php echo $stats['cancelled']; ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-danger" role="progressbar" 
                                style="width: <?php echo $stats['total'] > 0 ? ($stats['cancelled'] / $stats['total'] * 100) : 0; ?>%;" 
                                aria-valuenow="<?php echo $stats['cancelled']; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="<?php echo $stats['total']; ?>"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Alerts -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" data-aos="fade-up">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-up">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Upcoming Appointments Card -->
            <div class="card shadow-sm mb-4" data-aos="fade-up">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Upcoming Appointments</h5>
                    <a href="appointments.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($upcomingAppointments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Doctor</th>
                                        <th scope="col">Specialty</th>
                                        <th scope="col">Date & Time</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($appointment = $upcomingAppointments->fetch_assoc()): ?>
                                        <?php 
                                        // Format date and time
                                        $date = new DateTime($appointment['appointment_date']);
                                        $formattedDate = $date->format('M d, Y');
                                        
                                        $time = new DateTime($appointment['appointment_time']);
                                        $formattedTime = $time->format('h:i A');
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['specialty']); ?></td>
                                            <td>
                                                <div><?php echo $formattedDate; ?></div>
                                                <small class="text-muted"><?php echo $formattedTime; ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                switch ($appointment['status']) {
                                                    case 'pending':
                                                        echo '<span class="badge bg-warning text-dark">Pending</span>';
                                                        break;
                                                    case 'confirmed':
                                                        echo '<span class="badge bg-success">Confirmed</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Unknown</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                            <h5>No Upcoming Appointments</h5>
                            <p class="text-muted mb-3">You don't have any upcoming appointments scheduled.</p>
                            <a href="doctors.php" class="btn btn-primary">Book an Appointment</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Profile Settings Card -->
            <div class="card shadow-sm" id="profile-settings" data-aos="fade-up" data-aos-delay="100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">Profile Settings</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="full_name" class="form-label fw-bold text-dark">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            <div class="invalid-feedback fw-semibold">Please enter your full name.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold text-dark">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <div class="invalid-feedback fw-semibold">Please enter a valid email address.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label fw-bold text-dark">Username</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
                            <small class="text-muted fw-semibold">Username cannot be changed.</small>
                        </div>
                        
                        <hr class="my-4">
                        <h6 class="mb-3 fw-bold text-dark">Change Password</h6>
                        <p class="text-muted small mb-3 fw-semibold">Leave the fields below empty if you don't want to change your password.</p>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-bold text-dark">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text fw-semibold">Required only if you're changing your password.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-bold text-dark">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text fw-semibold">Must be at least 6 characters long.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-bold text-dark">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary px-4 fw-bold">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add CSS styles at the end of the file, before the footer is included -->
<style>
.form-control:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

.form-label {
    margin-bottom: 0.5rem;
}

.toggle-password {
    cursor: pointer;
}

.text-dark {
    color: #212529 !important;
}

.fw-bold {
    font-weight: 700 !important;
}

.fw-semibold {
    font-weight: 600 !important;
}

.card-title.fw-bold {
    letter-spacing: -0.02em;
}
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?> 