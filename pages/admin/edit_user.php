<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/db_config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../pages/unauthorized.php");
    exit();
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id === 0) {
    header("Location: users.php");
    exit();
}

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: users.php");
    exit();
}

$user = $result->fetch_assoc();

// Get doctor details if user is a doctor
$doctorDetails = null;
if ($user['role'] === 'doctor') {
    $doctorSql = "SELECT * FROM doctors WHERE user_id = ?";
    $doctorStmt = $conn->prepare($doctorSql);
    $doctorStmt->bind_param("i", $user_id);
    $doctorStmt->execute();
    $doctorResult = $doctorStmt->get_result();
    
    if ($doctorResult->num_rows > 0) {
        $doctorDetails = $doctorResult->fetch_assoc();
    }
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    // Get and sanitize inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $role = trim($_POST['role']);
    $new_password = trim($_POST['new_password']);
    
    // Additional fields for doctor
    $specialty = isset($_POST['specialty']) ? trim($_POST['specialty']) : '';
    $experience = isset($_POST['experience']) ? intval($_POST['experience']) : 0;
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    
    // File upload handling
    $profile_image = '';
    $current_image = '';
    
    // Get current image if exists
    if ($role === 'doctor' && $doctorDetails && isset($doctorDetails['profile_image'])) {
        $current_image = $doctorDetails['profile_image'];
    }
    
    // Check if a new image was uploaded
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $upload_dir = "../../assets/img/doctors/";
        $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = array('jpg', 'jpeg', 'png', 'gif');
        
        // Validate file extension
        if (!in_array($file_ext, $allowed_exts)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed";
        } 
        // Validate file size (max 2MB)
        elseif ($_FILES['profile_image']['size'] > 2097152) {
            $errors[] = "File size should not exceed 2MB";
        } 
        else {
            // Generate unique filename
            $profile_image = $username . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $profile_image;
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image. Please try again.";
                $profile_image = $current_image; // Keep current image on failure
            }
        }
    } else {
        $profile_image = $current_image; // Keep current image if no new one uploaded
    }
    
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
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif ($username !== $user['username']) {
        // Check if username already exists (only if changed)
        $checkUsernameStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $checkUsernameStmt->bind_param("si", $username, $user_id);
        $checkUsernameStmt->execute();
        $checkUsernameResult = $checkUsernameStmt->get_result();
        if ($checkUsernameResult->num_rows > 0) {
            $errors[] = "Username is already taken";
        }
    }
    
    if ($role === 'doctor') {
        if (empty($specialty)) {
            $errors[] = "Specialty is required for doctors";
        }
        if ($experience <= 0) {
            $errors[] = "Experience must be a positive number";
        }
    }
    
    // If no errors, update user
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Prepare base user update query
            $updateUserSql = "UPDATE users SET full_name = ?, email = ?, username = ?, role = ? WHERE id = ?";
            $updateUserParams = array($full_name, $email, $username, $role, $user_id);
            $updateUserTypes = "ssssi";
            
            // If new password provided, add it to the update
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateUserSql = "UPDATE users SET full_name = ?, email = ?, username = ?, role = ?, password = ? WHERE id = ?";
                $updateUserParams[] = $hashed_password;
                $updateUserParams[] = $user_id;
                $updateUserTypes = "sssssi";
                
                // Remove the last element (duplicate user_id)
                array_pop($updateUserParams);
            }
            
            // Execute user update
            $updateUserStmt = $conn->prepare($updateUserSql);
            $updateUserStmt->bind_param($updateUserTypes, ...$updateUserParams);
            $updateUserResult = $updateUserStmt->execute();
            
            if (!$updateUserResult) {
                throw new Exception("Error updating user: " . $conn->error);
            }
            
            // Handle doctor-specific updates
            if ($role === 'doctor') {
                if ($doctorDetails) {
                    // Update existing doctor record with profile image
                    $updateDoctorSql = "UPDATE doctors SET specialty = ?, experience = ?, bio = ?, profile_image = ? WHERE user_id = ?";
                    $updateDoctorStmt = $conn->prepare($updateDoctorSql);
                    $updateDoctorStmt->bind_param("sissi", $specialty, $experience, $bio, $profile_image, $user_id);
                    $updateDoctorResult = $updateDoctorStmt->execute();
                    
                    if (!$updateDoctorResult) {
                        throw new Exception("Error updating doctor details: " . $conn->error);
                    }
                } else {
                    // Insert new doctor record with profile image
                    $insertDoctorSql = "INSERT INTO doctors (user_id, specialty, experience, bio, profile_image) VALUES (?, ?, ?, ?, ?)";
                    $insertDoctorStmt = $conn->prepare($insertDoctorSql);
                    $insertDoctorStmt->bind_param("isiss", $user_id, $specialty, $experience, $bio, $profile_image);
                    $insertDoctorResult = $insertDoctorStmt->execute();
                    
                    if (!$insertDoctorResult) {
                        throw new Exception("Error creating doctor details: " . $conn->error);
                    }
                }
            } elseif ($doctorDetails) {
                // User was a doctor but role changed, remove doctor record
                $deleteDoctorSql = "DELETE FROM doctors WHERE user_id = ?";
                $deleteDoctorStmt = $conn->prepare($deleteDoctorSql);
                $deleteDoctorStmt->bind_param("i", $user_id);
                $deleteDoctorResult = $deleteDoctorStmt->execute();
                
                if (!$deleteDoctorResult) {
                    throw new Exception("Error removing doctor details: " . $conn->error);
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "User updated successfully";
            
            // Refresh user data
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            // Refresh doctor data if needed
            if ($role === 'doctor') {
                $doctorStmt->execute();
                $doctorResult = $doctorStmt->get_result();
                $doctorDetails = $doctorResult->fetch_assoc();
            } else {
                $doctorDetails = null;
            }
            
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 bg-primary text-white p-0 min-vh-100">
            <div class="p-3">
                <h5 class="mb-0">Admin Dashboard</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="dashboard.php" class="list-group-item list-group-item-action bg-primary text-white border-0">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="users.php" class="list-group-item list-group-item-action bg-primary text-white border-0 active" style="background-color: rgba(255,255,255,0.2) !important;">
                    <i class="fas fa-users me-2"></i> Users
                </a>
                <a href="doctors.php" class="list-group-item list-group-item-action bg-primary text-white border-0">
                    <i class="fas fa-user-md me-2"></i> Doctors
                </a>
                <a href="appointments.php" class="list-group-item list-group-item-action bg-primary text-white border-0">
                    <i class="fas fa-calendar-check me-2"></i> Appointments
                </a>
                <a href="settings.php" class="list-group-item list-group-item-action bg-primary text-white border-0">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
                <a href="../logout.php" class="list-group-item list-group-item-action bg-primary text-white border-0 mt-4">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit User</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Edit User</h2>
                <a href="users.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Users
                </a>
            </div>
            
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
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" action="" class="needs-validation" enctype="multipart/form-data" novalidate>
                        <div class="row g-3">
                            <!-- Basic Information -->
                            <div class="col-md-12">
                                <h5 class="mb-3">Basic Information</h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                <div class="invalid-feedback">Please enter the full name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                <div class="invalid-feedback">Please enter a username.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="patient" <?php echo $user['role'] === 'patient' ? 'selected' : ''; ?>>Patient</option>
                                    <option value="doctor" <?php echo $user['role'] === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                                <div class="invalid-feedback">Please select a role.</div>
                            </div>
                            
                            <!-- Doctor Specific Fields -->
                            <div class="col-12 doctor-fields" style="display: <?php echo $user['role'] === 'doctor' ? 'block' : 'none'; ?>;">
                                <hr class="my-3">
                                <h5 class="mb-3">Doctor Details</h5>
                                
                                <div class="row g-3">
                                    <!-- Profile Image Upload -->
                                    <div class="col-md-12">
                                        <label for="profile_image" class="form-label">Profile Photo</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if ($doctorDetails && !empty($doctorDetails['profile_image'])) : ?>
                                            <div class="current-image">
                                                <img src="/medook/assets/img/doctors/<?php echo htmlspecialchars($doctorDetails['profile_image']); ?>" 
                                                     alt="Current profile image" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                                <p class="small text-muted mt-1">Current photo</p>
                                            </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif">
                                                <div class="form-text">Upload a professional photo (JPG, PNG, GIF). Max size: 2MB.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="specialty" class="form-label">Specialty</label>
                                        <input type="text" class="form-control" id="specialty" name="specialty" value="<?php echo htmlspecialchars($doctorDetails['specialty'] ?? ''); ?>">
                                        <div class="invalid-feedback">Please enter the specialty.</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="experience" class="form-label">Experience (years)</label>
                                        <input type="number" class="form-control" id="experience" name="experience" value="<?php echo $doctorDetails['experience'] ?? 0; ?>" min="0">
                                        <div class="invalid-feedback">Please enter the years of experience.</div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="bio" class="form-label">Bio/Description</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($doctorDetails['bio'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Password Reset -->
                            <div class="col-12">
                                <hr class="my-3">
                                <h5 class="mb-3">Password Reset</h5>
                                <p class="text-muted small mb-3">Leave the field below empty if you don't want to change the user's password.</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#new_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Must be at least 6 characters long if provided.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <button type="submit" name="update_user" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                                <a href="view_user.php?id=<?php echo $user_id; ?>" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-eye me-2"></i>View User
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle doctor fields based on role selection
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const doctorFields = document.querySelector('.doctor-fields');
    
    roleSelect.addEventListener('change', function() {
        if (this.value === 'doctor') {
            doctorFields.style.display = 'block';
        } else {
            doctorFields.style.display = 'none';
        }
    });
    
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.querySelector(targetId);
            
            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                this.querySelector('i').classList.remove('fa-eye');
                this.querySelector('i').classList.add('fa-eye-slash');
            } else {
                targetInput.type = 'password';
                this.querySelector('i').classList.remove('fa-eye-slash');
                this.querySelector('i').classList.add('fa-eye');
            }
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?> 