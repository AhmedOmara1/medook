<?php
// Start output buffering to prevent header issues
ob_start();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../utils/auth.php';

// Simple debugging logs
error_log("Appointment confirmation page loaded");
error_log("URL parameters: " . print_r($_GET, true));

// Check for token-based validation (most reliable)
$token_valid = false;
$appointment_details = null;

if (isset($_GET['appointment_id']) && isset($_GET['token'])) {
    $appointment_id = intval($_GET['appointment_id']);
    $token = trim($_GET['token']);
    
    // Check if this is a valid appointment with matching token
    $stmt = $conn->prepare("SELECT a.*, d.specialty, u_doc.full_name as doctor_name 
                           FROM appointments a
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u_doc ON d.user_id = u_doc.id
                           WHERE a.id = ? AND a.token = ? AND a.patient_id = ?");
    $stmt->bind_param("isi", $appointment_id, $token, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $token_valid = true;
        $appointment_details = $result->fetch_assoc();
        error_log("Token validation successful for appointment ID: " . $appointment_id);
    } else {
        error_log("Token validation failed for appointment ID: " . $appointment_id);
    }
}

// Also check session and cookie as backup methods
$session_valid = isset($_SESSION['appointment_success']);
$cookie_valid = isset($_COOKIE['appointment_booked']) && $_COOKIE['appointment_booked'] === 'true';

// Log validation results
error_log("Validation results - Token: " . ($token_valid ? "Valid" : "Invalid") . 
          ", Session: " . ($session_valid ? "Valid" : "Invalid") . 
          ", Cookie: " . ($cookie_valid ? "Valid" : "Invalid"));

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: /medook/pages/login.php");
    exit();
}

// Consider the appointment booked if ANY validation method passes
$appointmentBooked = $token_valid || $session_valid || $cookie_valid;

// If no validation passes, redirect to home
if (!$appointmentBooked) {
    error_log("No appointment validation passed, redirecting to home");
    header("Location: /medook/pages/home.php");
    exit();
}

// Clear session flag if it exists
if (isset($_SESSION['appointment_success'])) {
    unset($_SESSION['appointment_success']);
}

// Clear cookie if it exists
if (isset($_COOKIE['appointment_booked'])) {
    setcookie('appointment_booked', '', time() - 3600, '/');
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8" data-aos="fade-up">
            <div class="card shadow-sm text-center p-4">
                <div class="py-4">
                    <div class="mb-4">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                            <i class="fas fa-check fa-3x"></i>
                        </div>
                    </div>
                    <h1 class="mb-4">Appointment Booked!</h1>
                    <p class="lead">Your appointment has been successfully scheduled. You will receive a confirmation once the doctor approves it.</p>
                    
                    <?php if ($appointment_details): ?>
                    <div class="alert alert-info">
                        <h5>Appointment Details</h5>
                        <p><strong>Doctor:</strong> <?php echo htmlspecialchars($appointment_details['doctor_name']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($appointment_details['appointment_date'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment_details['appointment_time'])); ?></p>
                        <p><strong>Status:</strong> <span class="badge bg-warning text-dark">Pending</span></p>
                    </div>
                    <?php endif; ?>
                    
                    <p class="text-muted mb-4">You can view and manage your appointments from your account.</p>
                    <div class="d-grid gap-2 d-md-block">
                        <a href="/medook/pages/appointments.php" class="btn btn-primary px-4 py-2 me-md-2">View My Appointments</a>
                        <a href="/medook/pages/home.php" class="btn btn-outline-secondary px-4 py-2">Return Home</a>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4 shadow-sm" data-aos="fade-up" data-aos-delay="100">
                <div class="card-body">
                    <h5 class="card-title">What Happens Next?</h5>
                    <ol class="mb-0">
                        <li class="mb-2">Your appointment is now in <span class="badge bg-warning text-dark">Pending</span> status</li>
                        <li class="mb-2">The doctor will review your appointment request</li>
                        <li class="mb-2">Once approved, the status will change to <span class="badge bg-success">Confirmed</span></li>
                        <li class="mb-2">You'll receive a confirmation notification</li>
                        <li>Arrive 15 minutes before your scheduled appointment time</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?> 