<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/db_config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../pages/unauthorized.php");
    exit();
}

// Include animation disabling for admin area
require_once __DIR__ . '/../../includes/admin_animations.php';

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    // Get and sanitize inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    
    // Additional fields for doctor
    $specialty = isset($_POST['specialty']) ? trim($_POST['specialty']) : '';
    $experience = isset($_POST['experience']) ? intval($_POST['experience']) : 0;
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists
        $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $checkEmailResult = $checkEmailStmt->get_result();
        if ($checkEmailResult->num_rows > 0) {
            $errors[] = "Email is already registered";
        }
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } else {
        // Check if username already exists
        $checkUsernameStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkUsernameStmt->bind_param("s", $username);
        $checkUsernameStmt->execute();
        $checkUsernameResult = $checkUsernameStmt->get_result();
        if ($checkUsernameResult->num_rows > 0) {
            $errors[] = "Username is already taken";
        }
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($role === 'doctor') {
        if (empty($specialty)) {
            $errors[] = "Specialty is required for doctors";
        }
        if ($experience <= 0) {
            $errors[] = "Experience must be a positive number";
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insertUserSql = "INSERT INTO users (full_name, email, username, password, role) VALUES (?, ?, ?, ?, ?)";
            $insertUserStmt = $conn->prepare($insertUserSql);
            $insertUserStmt->bind_param("sssss", $full_name, $email, $username, $hashed_password, $role);
            $insertUserResult = $insertUserStmt->execute();
            
            if (!$insertUserResult) {
                throw new Exception("Error creating user: " . $conn->error);
            }
            
            $new_user_id = $conn->insert_id;
            
            // If role is doctor, add doctor details
            if ($role === 'doctor') {
                $insertDoctorSql = "INSERT INTO doctors (user_id, specialty, experience, bio) VALUES (?, ?, ?, ?)";
                $insertDoctorStmt = $conn->prepare($insertDoctorSql);
                $insertDoctorStmt->bind_param("isis", $new_user_id, $specialty, $experience, $bio);
                $insertDoctorResult = $insertDoctorStmt->execute();
                
                if (!$insertDoctorResult) {
                    throw new Exception("Error creating doctor profile: " . $conn->error);
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "User created successfully";
            
            // Reset form fields
            $full_name = $email = $username = $password = $specialty = $bio = '';
            $experience = 0;
            $role = 'patient';
            
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
                    <li class="breadcrumb-item active" aria-current="page">Add User</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Add New User</h2>
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
                    <form method="post" action="" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <!-- Basic Information -->
                            <div class="col-md-12">
                                <h5 class="mb-3">Basic Information</h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
                                <div class="invalid-feedback">Please enter the full name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                                <div class="invalid-feedback">Please enter a username.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Must be at least 6 characters long.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="patient" <?php echo isset($role) && $role === 'patient' ? 'selected' : ''; ?>>Patient</option>
                                    <option value="doctor" <?php echo isset($role) && $role === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                                    <option value="admin" <?php echo isset($role) && $role === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                                <div class="invalid-feedback">Please select a role.</div>
                            </div>
                            
                            <!-- Doctor Specific Fields -->
                            <div class="col-12 doctor-fields" style="display: <?php echo isset($role) && $role === 'doctor' ? 'block' : 'none'; ?>;">
                                <hr class="my-3">
                                <h5 class="mb-3">Doctor Details</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="specialty" class="form-label">Specialty</label>
                                        <input type="text" class="form-control" id="specialty" name="specialty" value="<?php echo isset($specialty) ? htmlspecialchars($specialty) : ''; ?>">
                                        <div class="invalid-feedback">Please enter the specialty.</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="experience" class="form-label">Experience (years)</label>
                                        <input type="number" class="form-control" id="experience" name="experience" value="<?php echo isset($experience) ? $experience : 0; ?>" min="0">
                                        <div class="invalid-feedback">Please enter the years of experience.</div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="bio" class="form-label">Bio/Description</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo isset($bio) ? htmlspecialchars($bio) : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <button type="submit" name="add_user" class="btn btn-primary px-4">
                                    <i class="fas fa-user-plus me-2"></i>Create User
                                </button>
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