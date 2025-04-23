<?php
// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check for backup cookie authentication if session is lost
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    // Restore session from cookies
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    if (isset($_COOKIE['user_role'])) {
        $_SESSION['role'] = $_COOKIE['user_role'];
    }
    if (isset($_COOKIE['user_name'])) {
        $_SESSION['full_name'] = $_COOKIE['user_name'];
    }
}

require_once __DIR__ . '/../utils/auth.php';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedOok - Medical Appointment Booking</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/medook/assets/css/style.css" rel="stylesheet">
    <!-- Custom Modal Fix JS -->
    <script src="/medook/assets/js/custom-modal.js"></script>
    <style>
        /* Custom header styles */
        .navbar {
            padding: 8px 0;
            transition: all 0.3s ease;
            background-color: var(--navbar-bg) !important;
            box-shadow: var(--header-shadow);
        }
        
        .navbar-brand {
            margin-left: -15px;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .navbar-brand i {
            color: #0d6efd;
            transform: scale(1.2);
        }
        
        .navbar-brand:hover {
            transform: translateY(-2px);
        }
        
        .nav-link {
            margin: 0 5px;
            position: relative;
            transition: all 0.3s ease;
            font-weight: 500;
            color: var(--navbar-text) !important;
        }
        
        .nav-link:not(.btn)::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: #0d6efd;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .nav-link:not(.btn):hover::after {
            width: 100%;
        }
        
        .nav-link.btn {
            border-radius: 30px;
            padding: 8px 22px;
            margin-left: 10px;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.2);
            border: none;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .nav-link.btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(13, 110, 253, 0.3);
        }
        
        .nav-link.btn:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        
        /* Animation for regular nav links */
        .nav-item:not(:last-child) .nav-link:hover {
            color: #0d6efd;
            transform: translateY(-2px);
        }
        
        /* Special styling for logout button */
        .nav-link[href*="logout"] {
            color: #dc3545;
            transition: all 0.3s ease;
        }
        
        .nav-link[href*="logout"]:hover {
            color: #c82333;
        }
        
        @media (max-width: 992px) {
            .navbar-brand {
                margin-left: 0;
            }
            
            .nav-link.btn {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand text-primary" href="<?php echo getUrl('pages.home'); ?>">
                <i class="fas fa-heartbeat me-2"></i>MedOok
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo getUrl('pages.home'); ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo getUrl('pages.doctors'); ?>">Find Doctors</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasRole('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getUrl('admin.dashboard'); ?>">Admin Dashboard</a>
                            </li>
                        <?php elseif (hasRole('doctor')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getUrl('doctor.dashboard'); ?>">Doctor Dashboard</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getUrl('pages.appointments'); ?>">My Appointments</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getUrl('pages.profile'); ?>">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getUrl('pages.logout'); ?>">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getUrl('pages.login'); ?>">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white" href="<?php echo getUrl('pages.register'); ?>">Register</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item ms-2 d-flex align-items-center">
                        <div class="theme-toggle-wrapper d-flex align-items-center">
                            <i class="fas fa-sun theme-icon-light me-1"></i>
                            <label class="theme-switch mb-0" for="theme-toggle">
                                <input type="checkbox" id="theme-toggle">
                                <span class="slider round"></span>
                            </label>
                            <i class="fas fa-moon theme-icon-dark ms-1"></i>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Main Content -->
    <main class="py-3"> 