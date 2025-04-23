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

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate inputs
    if (empty($new_password)) {
        $error_message = "New password is required";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update the password
        $updateSql = "UPDATE users SET password = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $hashed_password, $user_id);
        
        if ($updateStmt->execute()) {
            $success_message = "Password reset successfully";
        } else {
            $error_message = "Failed to reset password: " . $conn->error;
        }
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
                    <li class="breadcrumb-item"><a href="view_user.php?id=<?php echo $user_id; ?>"><?php echo htmlspecialchars($user['full_name']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Reset Password</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Reset Password</h2>
                <a href="view_user.php?id=<?php echo $user_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to User
                </a>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                                <?php
                                $initials = strtoupper(substr($user['full_name'], 0, 1));
                                $bgClass = '';
                                
                                switch ($user['role']) {
                                    case 'admin':
                                        $bgClass = 'bg-warning';
                                        break;
                                    case 'doctor':
                                        $bgClass = 'bg-info';
                                        break;
                                    case 'patient':
                                        $bgClass = 'bg-success';
                                        break;
                                    default:
                                        $bgClass = 'bg-secondary';
                                }
                                ?>
                                <div class="rounded-circle <?php echo $bgClass; ?> text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                    <?php echo $initials; ?>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
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
                            
                            <form method="post" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#new_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Must be at least 6 characters long.</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Reset Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Password Guidelines</h5>
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Important Information</h6>
                                <p class="mb-0">When you reset a user's password, they will need to be informed of their new password through a secure channel.</p>
                            </div>
                            <ul class="list-group mb-4">
                                <li class="list-group-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Minimum 6 characters long
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Mix of uppercase and lowercase letters
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Include at least one number
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Include at least one special character
                                </li>
                            </ul>
                            <p class="mb-0">After resetting the password, inform the user to change it to something they can remember but still secure.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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