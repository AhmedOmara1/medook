<?php
// Start output buffering to prevent header issues
ob_start();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/db_config.php';

// Check if user is admin
requireAdmin();

// Include animation disabling for admin area
require_once __DIR__ . '/../../includes/admin_animations.php';

// Get statistics
// Total users
$usersQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'patient'";
$usersResult = $conn->query($usersQuery);
$totalUsers = $usersResult->fetch_assoc()['total'];

// Total doctors
$doctorsQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'doctor'";
$doctorsResult = $conn->query($doctorsQuery);
$totalDoctors = $doctorsResult->fetch_assoc()['total'];

// Total appointments
$appointmentsQuery = "SELECT COUNT(*) as total FROM appointments";
$appointmentsResult = $conn->query($appointmentsQuery);
$totalAppointments = $appointmentsResult->fetch_assoc()['total'];

// Pending appointments
$pendingQuery = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
$pendingResult = $conn->query($pendingQuery);
$pendingAppointments = $pendingResult->fetch_assoc()['total'];

// Recent appointments
$recentQuery = "SELECT a.*, d.specialty, u_doc.full_name as doctor_name, u_pat.full_name as patient_name
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u_doc ON d.user_id = u_doc.id
                JOIN users u_pat ON a.patient_id = u_pat.id
                ORDER BY a.created_at DESC
                LIMIT 5";
$recentResult = $conn->query($recentQuery);

// Recent users
$newUsersQuery = "SELECT * FROM users WHERE role = 'patient' ORDER BY created_at DESC LIMIT 5";
$newUsersResult = $conn->query($newUsersQuery);
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-3 bg-primary text-white p-0 min-vh-100">
            <div class="p-4">
                <h4 class="mb-4">Admin Dashboard</h4>
            </div>
            <div class="px-3">
                <a href="dashboard.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 active d-flex align-items-center sidebar-item" style="background-color: rgba(255,255,255,0.2);">
                        <i class="fas fa-tachometer-alt me-3 text-white-50"></i>
                        <span class="text-white">Dashboard</span>
                    </div>
                </a>
                <a href="users.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item">
                        <i class="fas fa-users me-3 text-white-50"></i>
                        <span class="text-white">Patients</span>
                    </div>
                </a>
                <a href="doctors.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item">
                        <i class="fas fa-user-md me-3 text-white-50"></i>
                        <span class="text-white">Doctors</span>
                    </div>
                </a>
                <a href="appointments.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item">
                        <i class="fas fa-calendar-check me-3 text-white-50"></i>
                        <span class="text-white">Appointments</span>
                    </div>
                </a>
                <a href="settings.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item">
                        <i class="fas fa-cog me-3 text-white-50"></i>
                        <span class="text-white">Settings</span>
                    </div>
                </a>
                <a href="../logout.php" class="text-decoration-none mt-5 d-inline-block menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item sidebar-item-logout">
                        <i class="fas fa-sign-out-alt me-3 text-white-50"></i>
                        <span class="text-white">Logout</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-9 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="card stat-card primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Patients</h6>
                                    <h2 class="mb-0"><?php echo $totalUsers; ?></h2>
                                </div>
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="card stat-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Doctors</h6>
                                    <h2 class="mb-0"><?php echo $totalDoctors; ?></h2>
                                </div>
                                <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                    <i class="fas fa-user-md fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Appointments</h6>
                                    <h2 class="mb-0"><?php echo $totalAppointments; ?></h2>
                                </div>
                                <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                    <i class="fas fa-calendar-check fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="card stat-card danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Pending Appointments</h6>
                                    <h2 class="mb-0"><?php echo $pendingAppointments; ?></h2>
                                </div>
                                <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                                    <i class="fas fa-clock fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-8" data-aos="fade-up">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">Appointments Overview</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="appointmentsChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">Specialties Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="specialtiesChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Data Rows -->
            <div class="row g-4">
                <div class="col-12 col-lg-6" data-aos="fade-up">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Recent Appointments</h5>
                            <a href="appointments.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Patient</th>
                                            <th>Doctor</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recentResult && $recentResult->num_rows > 0): ?>
                                            <?php while ($appointment = $recentResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                                    <td>
                                                        <?php
                                                        switch ($appointment['status']) {
                                                            case 'pending':
                                                                echo '<span class="badge bg-warning text-dark">Pending</span>';
                                                                break;
                                                            case 'confirmed':
                                                                echo '<span class="badge bg-success">Confirmed</span>';
                                                                break;
                                                            case 'cancelled':
                                                                echo '<span class="badge bg-danger">Cancelled</span>';
                                                                break;
                                                            case 'completed':
                                                                echo '<span class="badge bg-info">Completed</span>';
                                                                break;
                                                            default:
                                                                echo '<span class="badge bg-secondary">Unknown</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4">No recent appointments found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">New Patients</h5>
                            <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($newUsersResult && $newUsersResult->num_rows > 0): ?>
                                            <?php while ($user = $newUsersResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-4">No new patients found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Animation Styles -->
<style>
    .sidebar-item {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .sidebar-item:not(.active):hover {
        background-color: rgba(255,255,255,0.1);
        transform: translateX(5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .sidebar-item:before {
        content: '';
        position: absolute;
        left: -20px;
        top: 0;
        height: 100%;
        width: 5px;
        background-color: #fff;
        opacity: 0;
        transition: all 0.3s ease;
    }
    
    .sidebar-item:not(.active):hover:before {
        left: 0;
        opacity: 0.6;
    }
    
    .menu-link:hover .fas {
        transform: scale(1.2);
        transition: transform 0.3s ease;
    }
    
    .sidebar-item-logout:hover {
        background-color: rgba(220, 53, 69, 0.2) !important;
    }
    
    .sidebar-item.active {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    /* Dark mode table styles */
    [data-theme="dark"] .table {
        color: var(--text-color);
    }
    
    [data-theme="dark"] .table-light, 
    [data-theme="dark"] .table-light>th, 
    [data-theme="dark"] .table-light>td {
        background-color: #2c3352;
        color: #e9ecef;
    }
    
    [data-theme="dark"] .table>:not(caption)>*>* {
        background-color: var(--card-bg);
        color: var(--text-color);
        border-color: var(--border-color);
    }
    
    [data-theme="dark"] .table-hover>tbody>tr:hover>* {
        background-color: rgba(255, 255, 255, 0.075);
        color: var(--text-color);
    }
    
    [data-theme="dark"] tr:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        position: relative;
        z-index: 1;
    }
    
    [data-theme="dark"] .table-responsive {
        border-color: var(--border-color);
    }
    
    /* Dark mode card styles */
    [data-theme="dark"] .card {
        background-color: var(--card-bg);
        border-color: var(--border-color);
    }
    
    [data-theme="dark"] .card-body {
        background-color: var(--card-bg);
        color: var(--text-color);
    }
    
    [data-theme="dark"] .card-header.bg-white {
        background-color: var(--card-bg) !important;
        border-color: var(--border-color);
    }
    
    /* Dark mode graph colors */
    [data-theme="dark"] canvas {
        filter: brightness(0.9) contrast(1.1);
    }
</style>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?> 