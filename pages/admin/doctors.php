<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/db_config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../pages/unauthorized.php");
    exit();
}

// Include animation disabling for admin area
require_once __DIR__ . '/../../includes/admin_animations.php';

// Get filter values
$specialty_filter = isset($_GET['specialty']) ? $_GET['specialty'] : '';
$experience_filter = isset($_GET['experience']) ? intval($_GET['experience']) : 0;
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$sql = "SELECT d.*, u.full_name, u.email, u.username, u.created_at 
        FROM doctors d
        JOIN users u ON d.user_id = u.id
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($specialty_filter)) {
    $sql .= " AND d.specialty = ?";
    $params[] = $specialty_filter;
    $types .= "s";
}

if ($experience_filter > 0) {
    $sql .= " AND d.experience >= ?";
    $params[] = $experience_filter;
    $types .= "i";
}

if (!empty($search_term)) {
    $sql .= " AND (u.full_name LIKE ? OR d.specialty LIKE ? OR u.email LIKE ?)";
    $search_pattern = "%{$search_term}%";
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $types .= "sss";
}

$sql .= " ORDER BY u.full_name ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$doctors = $result->fetch_all(MYSQLI_ASSOC);

// Get available specialties for filter
$specialtySql = "SELECT DISTINCT specialty FROM doctors ORDER BY specialty";
$specialtyResult = $conn->query($specialtySql);
$specialties = [];
while ($row = $specialtyResult->fetch_assoc()) {
    $specialties[] = $row['specialty'];
}

// Count doctors
$countSql = "SELECT COUNT(*) as total FROM doctors";
$countResult = $conn->query($countSql);
$doctorCount = $countResult->fetch_assoc()['total'];

// Get appointment counts by doctor
$appointmentStats = [];
$statsSql = "SELECT 
              d.id as doctor_id,
              COUNT(a.id) as total_appointments,
              SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN a.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
              SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
              SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM doctors d
            LEFT JOIN appointments a ON d.id = a.doctor_id
            GROUP BY d.id";
$statsResult = $conn->query($statsSql);
while ($row = $statsResult->fetch_assoc()) {
    $appointmentStats[$row['doctor_id']] = $row;
}
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
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item">
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
                    <div class="bg-primary-light p-3 rounded mb-3 active d-flex align-items-center sidebar-item" style="background-color: rgba(255,255,255,0.2);">
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
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-9 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Doctor Management</h2>
                <a href="add_user.php?role=doctor" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Doctor
                </a>
            </div>
            
            <!-- Doctor Stats Summary -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total Doctors</h6>
                                    <h2 class="mb-0"><?php echo $doctorCount; ?></h2>
                                </div>
                                <div class="rounded-circle bg-white p-3">
                                    <i class="fas fa-user-md text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Specialties</h6>
                                    <h2 class="mb-0"><?php echo count($specialties); ?></h2>
                                </div>
                                <div class="rounded-circle bg-white p-3">
                                    <i class="fas fa-stethoscope text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Avg. Experience</h6>
                                    <?php
                                    $totalExperience = 0;
                                    foreach ($doctors as $doctor) {
                                        $totalExperience += $doctor['experience'];
                                    }
                                    $avgExperience = $doctorCount > 0 ? round($totalExperience / $doctorCount, 1) : 0;
                                    ?>
                                    <h2 class="mb-0"><?php echo $avgExperience; ?> <small>years</small></h2>
                                </div>
                                <div class="rounded-circle bg-white p-3">
                                    <i class="fas fa-briefcase text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total Appointments</h6>
                                    <?php
                                    $totalAppointments = 0;
                                    foreach ($appointmentStats as $stat) {
                                        $totalAppointments += $stat['total_appointments'];
                                    }
                                    ?>
                                    <h2 class="mb-0"><?php echo $totalAppointments; ?></h2>
                                </div>
                                <div class="rounded-circle bg-white p-3">
                                    <i class="fas fa-calendar-check text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <form action="" method="get" class="row gx-3 gy-2 align-items-center">
                        <div class="col-md-4">
                            <label for="search" class="visually-hidden">Search</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, specialty, email" value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="specialty" class="visually-hidden">Specialty</label>
                            <select class="form-select" id="specialty" name="specialty">
                                <option value="">All Specialties</option>
                                <?php foreach ($specialties as $specialty): ?>
                                    <option value="<?php echo htmlspecialchars($specialty); ?>" <?php echo $specialty_filter === $specialty ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($specialty); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="experience" class="visually-hidden">Min. Experience</label>
                            <select class="form-select" id="experience" name="experience">
                                <option value="0">Any Experience</option>
                                <option value="1" <?php echo $experience_filter === 1 ? 'selected' : ''; ?>>1+ years</option>
                                <option value="3" <?php echo $experience_filter === 3 ? 'selected' : ''; ?>>3+ years</option>
                                <option value="5" <?php echo $experience_filter === 5 ? 'selected' : ''; ?>>5+ years</option>
                                <option value="10" <?php echo $experience_filter === 10 ? 'selected' : ''; ?>>10+ years</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                        <div class="col-auto">
                            <a href="doctors.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Doctors Section -->
            <div class="row">
                <?php if (count($doctors) > 0): ?>
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                            <?php echo strtoupper(substr($doctor['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($doctor['full_name']); ?></h5>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center">
                                                    <div class="text-primary fw-bold"><?php echo $doctor['experience']; ?></div>
                                                    <small class="text-muted">Years Exp.</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center">
                                                    <div class="text-primary fw-bold">
                                                        <?php echo isset($appointmentStats[$doctor['id']]) ? $appointmentStats[$doctor['id']]['total_appointments'] : 0; ?>
                                                    </div>
                                                    <small class="text-muted">Appointments</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="small text-truncate-3" style="max-height: 4.5em; overflow: hidden;">
                                            <?php echo !empty($doctor['bio']) ? htmlspecialchars($doctor['bio']) : 'No biography provided.'; ?>
                                        </p>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="row mb-2">
                                        <div class="col-6 small">
                                            <i class="fas fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($doctor['email']); ?>
                                        </div>
                                        <div class="col-6 small text-end">
                                            <i class="fas fa-calendar text-muted me-1"></i> Joined <?php echo date('M Y', strtotime($doctor['created_at'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <a href="view_user.php?id=<?php echo $doctor['user_id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="edit_user.php?id=<?php echo $doctor['user_id']; ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="users.php?delete=<?php echo $doctor['user_id']; ?>" class="btn btn-outline-danger" title="Delete" 
                                               onclick="return confirm('Are you sure you want to delete this doctor?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body py-5 text-center">
                                <i class="fas fa-user-md fa-4x text-muted mb-3"></i>
                                <h4>No Doctors Found</h4>
                                <p class="text-muted">No doctors match your search criteria.</p>
                                <a href="doctors.php" class="btn btn-primary mt-2">View All Doctors</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.text-truncate-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Sidebar Animation Styles */
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

/* Dark mode form controls */
[data-theme="dark"] .form-control,
[data-theme="dark"] .form-select {
    background-color: rgba(44, 51, 82, 0.8);
    border-color: rgba(58, 64, 100, 0.8);
    color: #e9ecef;
}

/* Dark mode borders */
[data-theme="dark"] .border {
    border-color: var(--border-color) !important;
}
</style>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?> 