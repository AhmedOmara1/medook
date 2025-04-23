<?php
// Start output buffering to prevent header issues
ob_start();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/path_config.php';

// Add debugging to check session status
error_log("Login page - Session ID: " . session_id());
error_log("Login page - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Not Active"));

// Initialize variables
$email = $password = '';
$errors = [];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // If no validation errors, process login
    if (empty($errors)) {
        // Check if user exists
        $sql = "SELECT id, username, password, full_name, email, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Set backup cookies to help with session persistence
                $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                setcookie('user_id', $user['id'], time() + 86400, '/', '', $secure, true);
                setcookie('user_role', $user['role'], time() + 86400, '/', '', $secure, true);
                setcookie('user_name', $user['full_name'], time() + 86400, '/', '', $secure, true);
                
                error_log("Login successful - User ID: {$user['id']}, Role: {$user['role']}");
                error_log("Session variables set: " . print_r($_SESSION, true));
                error_log("Backup cookies set for user_id, user_role, and user_name");
                
                // Make sure session data is saved before redirect
                session_write_close();
                
                // Redirect based on role using direct paths
                if ($user['role'] === 'admin') {
                    header("Location: /medook/pages/admin/dashboard.php");
                } elseif ($user['role'] === 'doctor') {
                    header("Location: /medook/pages/doctor/dashboard.php");
                } else {
                    header("Location: /medook/pages/home.php");
                }
                exit();
            } else {
                $errors['login'] = 'Invalid email or password';
                error_log("Login failed - Password verification failed for email: $email");
            }
        } else {
            $errors['login'] = 'Invalid email or password';
            error_log("Login failed - No user found with email: $email");
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6" data-aos="fade-up">
            <div class="card auth-card shadow">
                <div class="card-body p-4">
                    <h2 class="card-title fw-bold text-dark">Welcome Back</h2>
                    
                    <?php if (!empty($errors['login'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $errors['login']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold text-dark">Email Address</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback fw-semibold">
                                    <?php echo $errors['email']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold text-dark">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback fw-semibold">
                                        <?php echo $errors['password']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label fw-bold text-dark" for="remember">Remember me</label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">Login</button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="/medook/pages/forgot_password.php" class="text-muted fw-semibold">Forgot Password?</a>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0 fw-semibold text-dark">Don't have an account? <a href="/medook/pages/register.php" class="text-primary fw-bold">Sign Up</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.auth-card {
    border: none;
    border-radius: 10px;
}

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
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?> 