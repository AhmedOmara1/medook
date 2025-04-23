<?php
// Start output buffering to prevent header issues
ob_start();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db_config.php';

// Initialize variables
$full_name = $email = $username = $password = $confirm_password = '';
$errors = [];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['email'] = 'Email is already registered';
        }
    }
    
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username must be at least 4 characters';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['username'] = 'Username is already taken';
        }
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no validation errors, register the user
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'patient')");
        $stmt->bind_param("ssss", $username, $hashed_password, $email, $full_name);
        
        if ($stmt->execute()) {
            // Set session variables for automatic login
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'patient';
            
            // Redirect to home page
            header("Location: home.php");
            exit();
        } else {
            $errors['register'] = 'Registration failed: ' . $conn->error;
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8" data-aos="fade-up">
            <div class="card auth-card shadow">
                <div class="card-body p-4">
                    <h2 class="card-title fw-bold text-dark">Create Your Account</h2>
                    
                    <?php if (!empty($errors['register'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $errors['register']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label fw-bold text-dark">Full Name</label>
                                <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                                <?php if (isset($errors['full_name'])): ?>
                                    <div class="invalid-feedback fw-semibold">
                                        <?php echo $errors['full_name']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-bold text-dark">Email Address</label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback fw-semibold">
                                        <?php echo $errors['email']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label fw-bold text-dark">Username</label>
                            <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback fw-semibold">
                                    <?php echo $errors['username']; ?>
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
                            <small class="text-muted fw-semibold">Must be at least 6 characters long</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-bold text-dark">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback fw-semibold">
                                        <?php echo $errors['confirm_password']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label fw-bold text-dark" for="terms">I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a></label>
                            <div class="invalid-feedback fw-semibold">
                                You must agree before submitting.
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">Create Account</button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0 fw-semibold text-dark">Already have an account? <a href="login.php" class="text-primary fw-bold">Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms of Service Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms of Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Acceptance of Terms</h6>
                <p>By accessing and using MedOok, you agree to be bound by these Terms of Service.</p>
                
                <h6>2. Use of Service</h6>
                <p>You agree to use MedOok only for lawful purposes and in accordance with these Terms.</p>
                
                <h6>3. Account Registration</h6>
                <p>To use certain features of MedOok, you must register for an account. You agree to provide accurate information and to keep your information up-to-date.</p>
                
                <h6>4. Privacy</h6>
                <p>Your privacy is important to us. Please review our Privacy Policy to understand how we collect and use your information.</p>
                
                <h6>5. Limitation of Liability</h6>
                <p>MedOok is provided "as is" without warranties of any kind, either express or implied.</p>
                
                <h6>6. Changes to Terms</h6>
                <p>We reserve the right to modify these Terms at any time. Your continued use of MedOok after such modifications constitutes your acceptance of the new Terms.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Information We Collect</h6>
                <p>We collect information you provide directly to us when you register for an account, book an appointment, or contact us.</p>
                
                <h6>2. How We Use Your Information</h6>
                <p>We use the information we collect to provide, maintain, and improve MedOok, and to communicate with you.</p>
                
                <h6>3. Information Sharing</h6>
                <p>We do not share your personal information except as described in this Privacy Policy or with your consent.</p>
                
                <h6>4. Data Security</h6>
                <p>We take reasonable measures to help protect your personal information from loss, theft, misuse, and unauthorized access.</p>
                
                <h6>5. Your Choices</h6>
                <p>You can update your account information or delete your account at any time through your account settings.</p>
                
                <h6>6. Changes to Privacy Policy</h6>
                <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
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