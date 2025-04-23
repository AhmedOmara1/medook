    </main>
    <!-- Footer -->
    <footer class="py-2 mt-3" style="background-color: var(--footer-bg); color: var(--footer-text);">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="mb-3"><i class="fas fa-heartbeat me-2"></i>MedOok</h5>
                    <p>Your trusted platform for booking medical appointments online.</p>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo getUrl('pages.doctors'); ?>" class="text-white-50">Find Doctors</a></li>
                        <li><a href="<?php echo getUrl('pages.appointments'); ?>" class="text-white-50">Appointments</a></li>
                        <li><a href="<?php echo getUrl('pages.contact'); ?>" class="text-white-50">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Connect With Us</h5>
                    <div class="d-flex">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="https://www.linkedin.com/in/ahmed-omara-2805a8248/" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> MedOok (Ahmed Omara). All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo getUrl('assets.js'); ?>/main.js"></script>
</body>
</html> 