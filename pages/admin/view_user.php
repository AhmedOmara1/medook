<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/db_config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../pages/unauthorized.php");
    exit();
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id === 0) {
    header("Location: users.php");
    exit();
}

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: users.php");
    exit();
}

$user = $result->fetch_assoc();

// Get appointment stats if user is a patient
$appointmentStats = [];
if ($user['role'] === 'patient') {
    $statsSql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                 FROM appointments 
                 WHERE patient_id = ?";
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->bind_param("i", $user_id);
    $statsStmt->execute();
    $appointmentStats = $statsStmt->get_result()->fetch_assoc();
}

// Get doctor details if user is a doctor
$doctorDetails = null;
if ($user['role'] === 'doctor') {
    $doctorSql = "SELECT * FROM doctors WHERE user_id = ?";
    $doctorStmt = $conn->prepare($doctorSql);
    $doctorStmt->bind_param("i", $user_id);
    $doctorStmt->execute();
    $doctorResult = $doctorStmt->get_result();
    
    if ($doctorResult->num_rows > 0) {
        $doctorDetails = $doctorResult->fetch_assoc();
        
        // Get appointment stats for doctor
        $doctorStatsSql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                        FROM appointments 
                        WHERE doctor_id = ?";
        $doctorStatsStmt = $conn->prepare($doctorStatsSql);
        $doctorStatsStmt->bind_param("i", $doctorDetails['id']);
        $doctorStatsStmt->execute();
        $appointmentStats = $doctorStatsStmt->get_result()->fetch_assoc();
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 bg-primary text-white p-0 min-vh-100">
            <div class="p-3">
                <h5 class="mb-0">Admin Dashboard</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="dashboard.php" class="list-group-item list-group-item-action bg-primary text-white border-0">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="users.php" class="list-group-item list-group-item-action bg-primary text-white border-0 active" style="background-color: rgba(255,255,255,0.2) !important;">
                    <i class="fas fa-users me-2"></i> Users
                </a>
                <a href="doctors.php" class="list-group-item list-group-item-action bg-primary text-white border-0">
                    <i class="fas fa-user-md me-2"></i> Doctors
                </a>
                <a href="appointments.php" class="list-group-item list-group-item-action bg-primary text-white border-0">
                    <i class="fas fa-calendar-check me-2"></i> Appointments
                </a>
                <a href="settings.php" class="list-group-item list-group-item-action bg-primary text-white border-0">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
                <a href="../logout.php" class="list-group-item list-group-item-action bg-primary text-white border-0 mt-4">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                    <li class="breadcrumb-item active" aria-current="page">User Details</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">User Details</h2>
                <div>
                    <a href="edit_user.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit User
                    </a>
                    <a href="users.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Users
                    </a>
                </div>
            </div>
            
            <div class="row">
                <!-- User Profile Card -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php
                                $initials = strtoupper(substr($user['full_name'], 0, 1));
                                $bgClass = '';
                                
                                switch ($user['role']) {
                                    case 'admin':
                                        $bgClass = 'bg-warning';
                                        break;
                                    case 'doctor':
                                        $bgClass = 'bg-info';
                                        break;
                                    case 'patient':
                                        $bgClass = 'bg-success';
                                        break;
                                    default:
                                        $bgClass = 'bg-secondary';
                                }
                                ?>
                                <div class="rounded-circle <?php echo $bgClass; ?> text-white d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 3rem;">
                                    <?php echo $initials; ?>
                                </div>
                            </div>
                            <h4 class="card-title mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                            
                            <?php
                            switch ($user['role']) {
                                case 'admin':
                                    echo '<span class="badge bg-warning">Administrator</span>';
                                    break;
                                case 'doctor':
                                    echo '<span class="badge bg-info">Doctor</span>';
                                    break;
                                case 'patient':
                                    echo '<span class="badge bg-success">Patient</span>';
                                    break;
                                default:
                                    echo '<span class="badge bg-secondary">Unknown</span>';
                            }
                            ?>
                            
                            <hr>
                            
                            <div class="text-start">
                                <p class="mb-2">
                                    <strong><i class="fas fa-user me-2"></i>Username:</strong>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </p>
                                <p class="mb-2">
                                    <strong><i class="fas fa-calendar me-2"></i>Joined:</strong>
                                    <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                                </p>
                                <?php if ($user['role'] === 'doctor' && $doctorDetails): ?>
                                    <p class="mb-2">
                                        <strong><i class="fas fa-stethoscope me-2"></i>Specialty:</strong>
                                        <?php echo htmlspecialchars($doctorDetails['specialty']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong><i class="fas fa-briefcase me-2"></i>Experience:</strong>
                                        <?php echo $doctorDetails['experience']; ?> years
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Actions Card -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Account Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="reset_password.php?id=<?php echo $user_id; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-key me-2"></i>Reset Password
                                </a>
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <a href="users.php?delete=<?php echo $user_id; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <i class="fas fa-trash-alt me-2"></i>Delete Account
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Details Card -->
                <div class="col-lg-8">
                    <?php if ($user['role'] === 'doctor' || $user['role'] === 'patient'): ?>
                        <!-- Stats Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Appointment Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center mb-3 mb-md-0">
                                        <div class="p-3 rounded bg-light mb-2">
                                            <h2 class="mb-0"><?php echo $appointmentStats['total'] ?? 0; ?></h2>
                                        </div>
                                        <p class="mb-0">Total Appointments</p>
                                    </div>
                                    <div class="col-md-3 text-center mb-3 mb-md-0">
                                        <div class="p-3 rounded bg-warning bg-opacity-10 mb-2">
                                            <h2 class="mb-0 text-warning"><?php echo $appointmentStats['pending'] ?? 0; ?></h2>
                                        </div>
                                        <p class="mb-0">Pending</p>
                                    </div>
                                    <div class="col-md-3 text-center mb-3 mb-md-0">
                                        <div class="p-3 rounded bg-success bg-opacity-10 mb-2">
                                            <h2 class="mb-0 text-success"><?php echo $appointmentStats['confirmed'] + ($appointmentStats['completed'] ?? 0); ?></h2>
                                        </div>
                                        <p class="mb-0">Completed</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <div class="p-3 rounded bg-danger bg-opacity-10 mb-2">
                                            <h2 class="mb-0 text-danger"><?php echo $appointmentStats['cancelled'] ?? 0; ?></h2>
                                        </div>
                                        <p class="mb-0">Cancelled</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($user['role'] === 'doctor' && $doctorDetails): ?>
                        <!-- Doctor Bio -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Doctor Profile</h5>
                            </div>
                            <div class="card-body">
                                <h6>Biography</h6>
                                <p><?php echo nl2br(htmlspecialchars($doctorDetails['bio'] ?? 'No biography provided.')); ?></p>
                            </div>
                        </div>
                        
                        <!-- Recent Appointments -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Recent Appointments</h5>
                                <a href="appointments.php?doctor_id=<?php echo $doctorDetails['id']; ?>" class="btn btn-sm btn-primary">
                                    View All
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <?php
                                $recentAppSql = "SELECT a.*, u.full_name as patient_name
                                                 FROM appointments a
                                                 JOIN users u ON a.patient_id = u.id
                                                 WHERE a.doctor_id = ?
                                                 ORDER BY a.appointment_date DESC, a.appointment_time DESC
                                                 LIMIT 5";
                                $recentAppStmt = $conn->prepare($recentAppSql);
                                $recentAppStmt->bind_param("i", $doctorDetails['id']);
                                $recentAppStmt->execute();
                                $recentAppointments = $recentAppStmt->get_result();
                                
                                if ($recentAppointments->num_rows > 0):
                                ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Patient</th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($app = $recentAppointments->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($app['patient_name']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($app['appointment_date'])); ?></td>
                                                        <td><?php echo date('h:i A', strtotime($app['appointment_time'])); ?></td>
                                                        <td>
                                                            <?php
                                                            switch ($app['status']) {
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
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <p>No appointments found for this doctor.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($user['role'] === 'patient'): ?>
                        <!-- Recent Appointments -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Recent Appointments</h5>
                                <a href="appointments.php?patient_id=<?php echo $user_id; ?>" class="btn btn-sm btn-primary">
                                    View All
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <?php
                                $recentAppSql = "SELECT a.*, d.specialty, u.full_name as doctor_name
                                                 FROM appointments a
                                                 JOIN doctors d ON a.doctor_id = d.id
                                                 JOIN users u ON d.user_id = u.id
                                                 WHERE a.patient_id = ?
                                                 ORDER BY a.appointment_date DESC, a.appointment_time DESC
                                                 LIMIT 5";
                                $recentAppStmt = $conn->prepare($recentAppSql);
                                $recentAppStmt->bind_param("i", $user_id);
                                $recentAppStmt->execute();
                                $recentAppointments = $recentAppStmt->get_result();
                                
                                if ($recentAppointments->num_rows > 0):
                                ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Doctor</th>
                                                    <th>Specialty</th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($app = $recentAppointments->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($app['doctor_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($app['specialty']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($app['appointment_date'])); ?></td>
                                                        <td><?php echo date('h:i A', strtotime($app['appointment_time'])); ?></td>
                                                        <td>
                                                            <?php
                                                            switch ($app['status']) {
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
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <p>No appointments found for this patient.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($user['role'] === 'admin'): ?>
                        <!-- Activity Log Placeholder -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Admin Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center py-4">
                                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                    <h5>Admin Activity Tracking</h5>
                                    <p class="text-muted">Activity tracking is not available in this version.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?> 