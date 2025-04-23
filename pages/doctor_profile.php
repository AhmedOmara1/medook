<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/path_config.php';

// Get doctor ID from URL parameter
$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no doctor ID provided, redirect to doctors listing
if ($doctor_id === 0) {
    redirect('pages.doctors');
    exit();
}

// Get doctor details
$sql = "SELECT d.*, u.full_name, u.email 
        FROM doctors d
        JOIN users u ON d.user_id = u.id
        WHERE d.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

// If doctor not found, redirect to doctors listing
if ($result->num_rows === 0) {
    redirect('pages.doctors');
    exit();
}

$doctor = $result->fetch_assoc();

// Get doctor's available appointment slots (simplified example)
$availableDays = [
    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'
];

// Available time slots with display format => database format
$availableTimeSlots = [
    '09:00 AM' => '09:00:00',
    '10:00 AM' => '10:00:00',
    '11:00 AM' => '11:00:00',
    '01:00 PM' => '13:00:00',
    '02:00 PM' => '14:00:00',
    '03:00 PM' => '15:00:00',
    '04:00 PM' => '16:00:00'
];

// Get errors from session if any
$appointmentErrors = isset($_SESSION['appointment_errors']) ? $_SESSION['appointment_errors'] : [];
$formData = isset($_SESSION['appointment_form_data']) ? $_SESSION['appointment_form_data'] : [];

// Clear the session data
if (isset($_SESSION['appointment_errors'])) {
    unset($_SESSION['appointment_errors']);
}
if (isset($_SESSION['appointment_form_data'])) {
    unset($_SESSION['appointment_form_data']);
}
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo getUrl('pages.home'); ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo getUrl('pages.doctors'); ?>">Doctors</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($doctor['full_name']); ?></li>
        </ol>
    </nav>
    
    <!-- Display errors if any -->
    <?php if (!empty($appointmentErrors)): ?>
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                <?php foreach ($appointmentErrors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Doctor Profile -->
        <div class="col-lg-8" data-aos="fade-up">
            <div class="card shadow-sm mb-4">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="/medook/assets/img/doctors/<?php echo $doctor['profile_image'] ? $doctor['profile_image'] : 'default.jpg'; ?>" class="img-fluid rounded-start h-100 object-fit-cover" alt="<?php echo htmlspecialchars($doctor['full_name']); ?>">
                    </div>
                    <div class="col-md-8">
                        <div class="card-body p-4">
                            <span class="specialty-pill"><?php echo htmlspecialchars($doctor['specialty']); ?></span>
                            <h2 class="card-title mb-2"><?php echo htmlspecialchars($doctor['full_name']); ?></h2>
                            <p class="text-muted mb-3">
                                <i class="fas fa-award me-2"></i>
                                <?php echo $doctor['experience']; ?> Years Experience
                            </p>
                            <div class="d-grid gap-2 d-md-block mb-4">
                                <a href="#" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#appointmentModal" data-doctor-id="<?php echo $doctor['id']; ?>" data-doctor-name="<?php echo $doctor['full_name']; ?>">
                                    <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                                </a>
                            </div>
                            <div class="mb-3">
                                <h5 class="border-bottom pb-2">About</h5>
                                <p><?php echo nl2br(htmlspecialchars($doctor['bio'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Doctor Details -->
            <div class="row g-4 mb-4">
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Specialization</h5>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="fas fa-stethoscope text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($doctor['specialty']); ?></h6>
                                    <small class="text-muted">Primary Specialty</small>
                                </div>
                            </div>
                            <p class="mb-0">Specialized in diagnosing and treating conditions related to <?php echo strtolower(htmlspecialchars($doctor['specialty'])); ?>.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Experience</h5>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="fas fa-user-md text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo $doctor['experience']; ?> Years</h6>
                                    <small class="text-muted">Professional Experience</small>
                                </div>
                            </div>
                            <p class="mb-0">Has been practicing medicine and helping patients for over <?php echo $doctor['experience']; ?> years.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Availability Section -->
            <div class="card shadow-sm mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Availability</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Available Days</h6>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($availableDays as $day): ?>
                                    <li class="list-group-item px-0">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <?php echo $day; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Time Slots</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($availableTimeSlots as $displayTime => $dbTime): ?>
                                    <span class="badge bg-light text-dark border"><?php echo $displayTime; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-center py-3">
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#appointmentModal" data-doctor-id="<?php echo $doctor['id']; ?>" data-doctor-name="<?php echo $doctor['full_name']; ?>">
                        Book an Appointment
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="400">
            <!-- Quick Info Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Quick Information</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-user-md text-primary me-2"></i> Specialty</span>
                                <span class="fw-bold"><?php echo htmlspecialchars($doctor['specialty']); ?></span>
                            </div>
                        </li>
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-calendar-alt text-primary me-2"></i> Experience</span>
                                <span class="fw-bold"><?php echo $doctor['experience']; ?> Years</span>
                            </div>
                        </li>
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-clock text-primary me-2"></i> Consultation</span>
                                <span class="fw-bold">30 Minutes</span>
                            </div>
                        </li>
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-language text-primary me-2"></i> Languages</span>
                                <span class="fw-bold">English</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Contact Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Contact</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="mailto:<?php echo htmlspecialchars($doctor['email']); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-2"></i>Send Message
                        </a>
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#appointmentModal" data-doctor-id="<?php echo $doctor['id']; ?>" data-doctor-name="<?php echo $doctor['full_name']; ?>">
                            <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Similar Doctors -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Similar Doctors</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Get similar doctors (same specialty)
                    $similarQuery = "SELECT d.id, d.specialty, u.full_name 
                                     FROM doctors d 
                                     JOIN users u ON d.user_id = u.id 
                                     WHERE d.specialty = ? AND d.id != ? 
                                     LIMIT 3";
                    $similarStmt = $conn->prepare($similarQuery);
                    $similarStmt->bind_param("si", $doctor['specialty'], $doctor_id);
                    $similarStmt->execute();
                    $similarResult = $similarStmt->get_result();
                    
                    if ($similarResult->num_rows > 0):
                    ?>
                        <ul class="list-group list-group-flush">
                            <?php while ($similarDoctor = $similarResult->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($similarDoctor['full_name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($similarDoctor['specialty']); ?></small>
                                        </div>
                                        <a href="<?php echo getUrl('pages.doctor_profile'); ?>?id=<?php echo $similarDoctor['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="p-3 text-center text-muted">
                            No similar doctors found
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Appointment Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="appointmentModalLabel">Book Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (isLoggedIn()): ?>
                    <form action="<?php echo getUrl('pages.process_appointment'); ?>" method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="doctor_id" id="doctor_id" value="<?php echo $doctor_id; ?>">
                        
                        <div class="mb-3">
                            <label for="appointment_date" class="form-label">Appointment Date</label>
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($formData['appointment_date']) ? $formData['appointment_date'] : ''; ?>">
                            <div class="invalid-feedback">Please select an appointment date.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="appointment_time" class="form-label">Preferred Time</label>
                            <select class="form-select" id="appointment_time" name="appointment_time" required>
                                <option value="" selected disabled>Select a time slot</option>
                                <?php foreach ($availableTimeSlots as $displayTime => $dbTime): ?>
                                    <option value="<?php echo $dbTime; ?>" <?php echo (isset($formData['appointment_time']) && $formData['appointment_time'] == $dbTime) ? 'selected' : ''; ?>>
                                        <?php echo $displayTime; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a time slot.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Visit</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required><?php echo isset($formData['reason']) ? htmlspecialchars($formData['reason']) : ''; ?></textarea>
                            <div class="invalid-feedback">Please provide a reason for your visit.</div>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Confirm Booking</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-user-lock fa-3x text-secondary mb-3"></i>
                        <h5>Please Login to Book an Appointment</h5>
                        <p class="text-muted mb-4">You need to be logged in to book appointments with our doctors.</p>
                        <a href="<?php echo getUrl('pages.login'); ?>" class="btn btn-primary">Login Now</a>
                        <p class="mt-3 small">Don't have an account? <a href="<?php echo getUrl('pages.register'); ?>">Register here</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?> 