<?php
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <h1 class="display-4 fw-bold mb-4 hero-title">Your Health, Our Priority</h1>
                <p class="lead mb-4 hero-subtitle">Book appointments with top doctors online, manage your health records, and get the care you deserve.</p>
                <div class="hero-cta">
                    <a href="doctors.php" class="btn btn-light btn-lg px-4 me-2">Find Doctors</a>
                    <a href="register.php" class="btn btn-outline-light btn-lg px-4">Sign Up</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block" data-aos="fade-left" data-aos-delay="300">
                <img src="/medook/photos/home.png" class="img-fluid" alt="Medical Services">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold" data-aos="fade-up">Why Choose MedOok?</h2>
                <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">Experience healthcare booking like never before</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-4 mx-auto">
                            <i class="fas fa-user-md fa-2x p-3"></i>
                        </div>
                        <h4 class="mb-3">Expert Doctors</h4>
                        <p class="text-muted">Access to a wide range of experienced medical professionals across various specialties.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success bg-gradient text-white rounded-circle mb-4 mx-auto">
                            <i class="fas fa-calendar-check fa-2x p-3"></i>
                        </div>
                        <h4 class="mb-3">Easy Booking</h4>
                        <p class="text-muted">Simple and intuitive appointment booking system that saves your time and energy.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="400">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-info bg-gradient text-white rounded-circle mb-4 mx-auto">
                            <i class="fas fa-mobile-alt fa-2x p-3"></i>
                        </div>
                        <h4 class="mb-3">24/7 Availability</h4>
                        <p class="text-muted">Book your appointments anytime, anywhere with our responsive platform.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Doctors Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8">
                <h2 class="fw-bold" data-aos="fade-up">Featured Doctors</h2>
                <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">Meet our top healthcare professionals</p>
            </div>
            <div class="col-lg-4 text-lg-end" data-aos="fade-up" data-aos-delay="200">
                <a href="doctors.php" class="btn btn-primary px-4">View All Doctors</a>
            </div>
        </div>
        
        <div class="row g-4">
            <?php
            // Connect to database
            require_once __DIR__ . '/../config/db_config.php';
            
            // Get featured doctors (limit to 3)
            $sql = "SELECT d.*, u.full_name, u.email 
                    FROM doctors d
                    JOIN users u ON d.user_id = u.id
                    LIMIT 3";
                    
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($doctor = $result->fetch_assoc()) {
                    ?>
                    <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="card card-doctor">
                            <img src="/medook/assets/img/doctors/<?php echo $doctor['profile_image']; ?>" class="card-img-top" alt="<?php echo $doctor['full_name']; ?>">
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
                echo '<div class="col-12"><p class="text-center">No featured doctors available at the moment. Please check back later.</p></div>';
            }
            ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold" data-aos="fade-up">What Our Patients Say</h2>
                <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">Discover why people love using MedOok for their healthcare needs</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-4">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text mb-4">"MedOok made it so easy to find a great cardiologist. I was able to book an appointment within minutes and get the care I needed without any hassle."</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">JD</div>
                            <div class="ms-3">
                                <h6 class="mb-0">John Doe</h6>
                                <small class="text-muted">Patient</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-4">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text mb-4">"As a busy professional, I appreciate how MedOok has simplified the way I manage my healthcare appointments. The reminders are especially helpful!"</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">SJ</div>
                            <div class="ms-3">
                                <h6 class="mb-0">Sarah Johnson</h6>
                                <small class="text-muted">Patient</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="400">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex mb-4">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star-half-alt text-warning"></i>
                        </div>
                        <p class="card-text mb-4">"The platform is intuitive and the doctors are exceptional. I've recommended MedOok to all my family members for their healthcare needs."</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">RP</div>
                            <div class="ms-3">
                                <h6 class="mb-0">Robert Peters</h6>
                                <small class="text-muted">Patient</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                <h2 class="fw-bold mb-4">Ready to take control of your health?</h2>
                <p class="lead mb-4">Join thousands of satisfied patients who have simplified their healthcare journey with MedOok.</p>
                <div>
                    <a href="register.php" class="btn btn-light btn-lg px-5 me-2">Get Started</a>
                    <a href="doctors.php" class="btn btn-outline-light btn-lg px-5">Find Doctors</a>
                </div>
            </div>
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