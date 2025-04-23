<?php
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Contact Header -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="fw-bold mb-3" data-aos="fade-up">Contact Us</h1>
                <p class="lead mb-0" data-aos="fade-up" data-aos-delay="100">Get in touch with the team behind MedOok</p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Information -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4" data-aos="fade-up">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="avatar-circle mb-3 mx-auto">
                                <span class="initials">AO</span>
                            </div>
                            <h3 class="fw-bold">Ahmed Omara</h3>
                            <p class="text-muted">Lead Developer & Designer</p>
                        </div>
                        
                        <div class="row g-4 mt-2">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="icon-wrapper bg-primary text-white rounded-circle me-3">
                                        <i class="fab fa-linkedin-in"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">LinkedIn</h6>
                                        <a href="https://www.linkedin.com/in/ahmed-omara-2805a8248?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=ios_app" class="text-decoration-none" target="_blank">Ahmed Omara</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="icon-wrapper bg-info text-white rounded-circle me-3">
                                        <i class="fas fa-at"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Email</h6>
                                        <a href="mailto:contact@medook.com" class="text-decoration-none">hamadayasser125@gmail.com</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm border-0" data-aos="fade-up" data-aos-delay="100">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Send a Message</h4>
                        <form id="contactForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" class="form-control" id="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Your Email</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                                <div class="col-12">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" required>
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">Your Message</label>
                                    <textarea class="form-control" id="message" rows="5" required></textarea>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary px-4 py-2">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.avatar-circle {
    width: 100px;
    height: 100px;
    background-color: var(--bs-primary);
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.initials {
    font-size: 42px;
    color: white;
    font-weight: bold;
}

.icon-wrapper {
    width: 40px;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show success alert
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Message sent!</strong> Thank you for contacting us. We'll get back to you soon.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        form.insertAdjacentHTML('beforebegin', alertHtml);
        form.reset();
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?> 