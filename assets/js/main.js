// Main JavaScript File

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Theme switcher functionality
    initThemeSwitcher();

    // Optimize AOS animations
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 400,
            easing: 'ease-out',
            once: true,
            mirror: false,
            disable: 'mobile',
            throttleDelay: 99,
            offset: 20,
            delay: 0
        });
    }

    // GSAP animations for hero section elements
    if (document.querySelector('.hero-title')) {
        gsap.from(".hero-title", {duration: 1, y: -50, opacity: 0, ease: "power3.out"});
        gsap.from(".hero-subtitle", {duration: 1, y: -30, opacity: 0, delay: 0.3, ease: "power3.out"});
        gsap.from(".hero-cta", {duration: 1, y: 30, opacity: 0, delay: 0.6, ease: "power3.out"});
    }

    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordInput = document.querySelector(this.getAttribute('data-target'));
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });

    // Bootstrap form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Date picker initialization
    const datePickers = document.querySelectorAll('.date-picker');
    if (datePickers.length > 0) {
        datePickers.forEach(picker => {
            picker.addEventListener('focus', (e) => {
                e.target.type = 'date';
                // Set min attribute to today's date to prevent past dates
                const today = new Date();
                const yyyy = today.getFullYear();
                let mm = today.getMonth() + 1; // Months start at 0
                let dd = today.getDate();
                
                if (dd < 10) dd = '0' + dd;
                if (mm < 10) mm = '0' + mm;
                
                const formattedToday = yyyy + '-' + mm + '-' + dd;
                e.target.min = formattedToday;
            });
            picker.addEventListener('blur', (e) => {
                if (!e.target.value) {
                    e.target.type = 'text';
                }
            });
            picker.addEventListener('change', (e) => {
                // Ensure we have a valid date format for the server
                if (e.target.value) {
                    const selectedDate = new Date(e.target.value);
                    const yyyy = selectedDate.getFullYear();
                    let mm = selectedDate.getMonth() + 1;
                    let dd = selectedDate.getDate();
                    
                    if (dd < 10) dd = '0' + dd;
                    if (mm < 10) mm = '0' + mm;
                    
                    e.target.setAttribute('data-formatted-date', yyyy + '-' + mm + '-' + dd);
                }
            });
        });
    }

    // Appointment modal functionality
    const appointmentModal = document.getElementById('appointmentModal');
    if (appointmentModal) {
        appointmentModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const doctorId = button.getAttribute('data-doctor-id');
            const doctorName = button.getAttribute('data-doctor-name');
            
            const modalTitle = appointmentModal.querySelector('.modal-title');
            const doctorIdInput = appointmentModal.querySelector('#doctor_id');
            
            modalTitle.textContent = `Book Appointment with ${doctorName}`;
            doctorIdInput.value = doctorId;
        });
    }

    // Tooltips initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Dashboard chart initialization (if charts exist)
    if (typeof Chart !== 'undefined' && document.getElementById('appointmentsChart')) {
        initDashboardCharts();
    }
});

// Theme switcher initialization
function initThemeSwitcher() {
    const themeToggle = document.getElementById('theme-toggle');
    
    // Check for saved theme preference or respect OS preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Set initial theme
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.documentElement.setAttribute('data-theme', 'dark');
        themeToggle.checked = true;
    }
    
    // Handle theme toggle changes
    themeToggle.addEventListener('change', function() {
        if (this.checked) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
        }
    });
}

// Dashboard charts initialization
function initDashboardCharts() {
    // Appointments chart
    const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
    const appointmentsChart = new Chart(appointmentsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            datasets: [{
                label: 'Appointments',
                data: [65, 59, 80, 81, 56, 55, 40],
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2,
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Specialties chart
    if (document.getElementById('specialtiesChart')) {
        const specialtiesCtx = document.getElementById('specialtiesChart').getContext('2d');
        const specialtiesChart = new Chart(specialtiesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Cardiology', 'Pediatrics', 'Dermatology', 'Neurology', 'Others'],
                datasets: [{
                    data: [35, 25, 20, 15, 5],
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                    ]
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
} 