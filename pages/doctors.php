<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db_config.php';

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$specialty = isset($_GET['specialty']) ? trim($_GET['specialty']) : '';

// Build the query
$sql = "SELECT d.*, u.full_name, u.email 
        FROM doctors d
        JOIN users u ON d.user_id = u.id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR d.specialty LIKE ? OR d.bio LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if (!empty($specialty)) {
    $sql .= " AND d.specialty = ?";
    $params[] = $specialty;
    $types .= "s";
}

$sql .= " ORDER BY d.experience DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get all specialties for filter dropdown
$specialtiesQuery = "SELECT DISTINCT specialty FROM doctors ORDER BY specialty";
$specialtiesResult = $conn->query($specialtiesQuery);
$specialties = [];
if ($specialtiesResult->num_rows > 0) {
    while ($row = $specialtiesResult->fetch_assoc()) {
        $specialties[] = $row['specialty'];
    }
}
?>

<!-- Hero Section -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <h1 class="fw-bold mb-4">Find the Right Doctor</h1>
                <p class="lead mb-4">Browse our network of experienced healthcare professionals and book your appointment today.</p>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form action="" method="get">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Doctor name, specialty, etc." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="specialty" class="form-label">Specialty</label>
                                    <select class="form-select" id="specialty" name="specialty">
                                        <option value="">All Specialties</option>
                                        <?php foreach ($specialties as $s): ?>
                                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $specialty === $s ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary w-100">Search Doctors</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Doctors List Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="fw-bold" data-aos="fade-up">
                    <?php 
                    if (!empty($search) || !empty($specialty)) {
                        echo 'Search Results';
                        if (!empty($specialty)) {
                            echo ' for ' . htmlspecialchars($specialty);
                        }
                    } else {
                        echo 'Our Doctors';
                    }
                    ?>
                </h2>
                <p class="text-muted" data-aos="fade-up" data-aos-delay="100">
                    <?php echo $result->num_rows; ?> doctors found
                </p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php
            if ($result->num_rows > 0) {
                while ($doctor = $result->fetch_assoc()) {
                    ?>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up">
                        <div class="card card-doctor h-100">
                            <img src="/medook/assets/img/doctors/<?php echo $doctor['profile_image'] ? $doctor['profile_image'] : 'default.jpg'; ?>" class="card-img-top" alt="<?php echo $doctor['full_name']; ?>">
                            <div class="card-body">
                                <span class="specialty-pill"><?php echo $doctor['specialty']; ?></span>
                                <h5 class="card-title mb-1"><?php echo $doctor['full_name']; ?></h5>
                                <p class="text-muted mb-3"><?php echo $doctor['experience']; ?> Years Experience</p>
                                <p class="card-text"><?php echo substr($doctor['bio'], 0, 100); ?>...</p>
                            </div>
                            <div class="card-footer bg-white border-0 pb-3">
                                <a href="doctor_profile.php?id=<?php echo $doctor['id']; ?>" class="btn btn-outline-primary me-2">View Profile</a>
                                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#appointmentModal" data-doctor-id="<?php echo $doctor['id']; ?>" data-doctor-name="<?php echo $doctor['full_name']; ?>">Book Now</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="col-12 text-center py-5">
                    <div data-aos="fade-up">
                        <i class="fas fa-user-md fa-4x text-muted mb-4"></i>
                        <h4>No doctors found</h4>
                        <p class="text-muted">Try adjusting your search criteria or browse all our doctors.</p>
                        <a href="doctors.php" class="btn btn-primary mt-3">View All Doctors</a>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</section>

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
                    <form action="process_appointment.php" method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="doctor_id" id="doctor_id">
                        
                        <div class="mb-3">
                            <label for="appointment_date" class="form-label">Appointment Date</label>
                            <input type="text" class="form-control date-picker" id="appointment_date" name="appointment_date" placeholder="Select a date" required>
                            <div class="invalid-feedback">Please select an appointment date.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="appointment_time" class="form-label">Preferred Time</label>
                            <select class="form-select" id="appointment_time" name="appointment_time" required>
                                <option value="" selected disabled>Select a time slot</option>
                                <option value="09:00:00">09:00 AM</option>
                                <option value="10:00:00">10:00 AM</option>
                                <option value="11:00:00">11:00 AM</option>
                                <option value="13:00:00">01:00 PM</option>
                                <option value="14:00:00">02:00 PM</option>
                                <option value="15:00:00">03:00 PM</option>
                                <option value="16:00:00">04:00 PM</option>
                            </select>
                            <div class="invalid-feedback">Please select a time slot.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Visit</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
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
                        <a href="login.php" class="btn btn-primary">Login Now</a>
                        <p class="mt-3 small">Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?> 