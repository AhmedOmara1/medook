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

// Add specific styles to completely disable all animations in this page
echo '<style>
    /* Aggressive animation disabling for appointments page */
    .modal, .modal *, .modal-backdrop {
        animation: none !important;
        transition: none !important;
        transform: none !important;
    }

    .modal.fade.show,
    .modal-backdrop.fade.show {
        opacity: 1 !important;
    }

    .modal-backdrop.show {
        opacity: 0.5 !important;
    }

    /* Disable all other animations */
    .row, .card, .table, .fade, .collapse, .collapsing {
        transition: none !important;
        animation: none !important;
    }

    /* Force instant display of elements */
    [style*="opacity"][style*="transition"] {
        transition: none !important;
        opacity: 1 !important;
    }
</style>';

// Add JavaScript to fix modal behavior
echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Remove fade class from all modals to prevent animation
        document.querySelectorAll(".modal").forEach(function(modal) {
            // Remove fade class
            modal.classList.remove("fade");
            
            // Force instant open/close without transitions
            var modalId = modal.id;
            var triggers = document.querySelectorAll("[data-bs-target=\'#" + modalId + "\'], [href=\'#" + modalId + "\']");
            
            triggers.forEach(function(trigger) {
                trigger.addEventListener("click", function(e) {
                    // Force modal to be immediately visible or hidden
                    var modalElem = document.getElementById(modalId);
                    modalElem.style.display = "block";
                    modalElem.classList.add("show");
                    document.body.classList.add("modal-open");
                    
                    // Create backdrop manually if needed
                    if (!document.querySelector(".modal-backdrop")) {
                        var backdrop = document.createElement("div");
                        backdrop.className = "modal-backdrop show";
                        document.body.appendChild(backdrop);
                    }
                });
            });
            
            // Handle close buttons
            modal.querySelectorAll("[data-bs-dismiss=\'modal\']").forEach(function(closeBtn) {
                closeBtn.addEventListener("click", function() {
                    modal.style.display = "none";
                    modal.classList.remove("show");
                    document.body.classList.remove("modal-open");
                    
                    // Remove backdrop
                    var backdrop = document.querySelector(".modal-backdrop");
                    if (backdrop) {
                        backdrop.parentNode.removeChild(backdrop);
                    }
                });
            });
        });
    });
</script>';

// Get filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$doctor_filter = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$patient_filter = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Handle appointment status updates
$success_message = '';
$error_message = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $appointment_id = intval($_GET['id']);
    
    if ($action === 'confirm' || $action === 'cancel' || $action === 'complete') {
        $new_status = '';
        
        switch ($action) {
            case 'confirm':
                $new_status = 'confirmed';
                break;
            case 'cancel':
                $new_status = 'cancelled';
                break;
            case 'complete':
                $new_status = 'completed';
                break;
        }
        
        if (!empty($new_status)) {
            $updateSql = "UPDATE appointments SET status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $new_status, $appointment_id);
            
            if ($updateStmt->execute()) {
                $success_message = "Appointment status updated successfully";
            } else {
                $error_message = "Error updating appointment status";
            }
        }
    } elseif ($action === 'delete') {
        $deleteSql = "DELETE FROM appointments WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $appointment_id);
        
        if ($deleteStmt->execute()) {
            $success_message = "Appointment deleted successfully";
        } else {
            $error_message = "Error deleting appointment";
        }
    }
}

// Build query with filters
$sql = "SELECT a.*, 
         d.specialty,
         u_pat.full_name as patient_name, 
         u_doc.full_name as doctor_name
         FROM appointments a
         JOIN doctors d ON a.doctor_id = d.id
         JOIN users u_pat ON a.patient_id = u_pat.id
         JOIN users u_doc ON d.user_id = u_doc.id
         WHERE 1=1";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($doctor_filter > 0) {
    $sql .= " AND a.doctor_id = ?";
    $params[] = $doctor_filter;
    $types .= "i";
}

if ($patient_filter > 0) {
    $sql .= " AND a.patient_id = ?";
    $params[] = $patient_filter;
    $types .= "i";
}

if (!empty($date_filter)) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if (!empty($search_term)) {
    $sql .= " AND (u_pat.full_name LIKE ? OR u_doc.full_name LIKE ? OR a.reason LIKE ?)";
    $search_pattern = "%{$search_term}%";
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $types .= "sss";
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);

// Get Doctors list for filter
$doctorsSql = "SELECT d.id, u.full_name, d.specialty 
              FROM doctors d 
              JOIN users u ON d.user_id = u.id 
              ORDER BY u.full_name";
$doctorsResult = $conn->query($doctorsSql);
$doctors = $doctorsResult->fetch_all(MYSQLI_ASSOC);

// Get appointment count statistics
$statsSql = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
              SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
              SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
           FROM appointments";
$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();
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
                    <div class="bg-primary-light p-3 rounded mb-3 d-flex align-items-center sidebar-item">
                        <i class="fas fa-user-md me-3 text-white-50"></i>
                        <span class="text-white">Doctors</span>
                    </div>
                </a>
                <a href="appointments.php" class="text-decoration-none menu-link">
                    <div class="bg-primary-light p-3 rounded mb-3 active d-flex align-items-center sidebar-item" style="background-color: rgba(255,255,255,0.2);">
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
                <h2 class="mb-0">Appointment Management</h2>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                    <button type="button" class="btn btn-outline-success" id="exportBtn">
                        <i class="fas fa-file-excel me-2"></i>Export
                    </button>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Appointment Stats -->
            <div class="row mb-4">
                <div class="col">
                    <div class="card bg-primary text-white">
                        <div class="card-body p-3 text-center">
                            <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                            <p class="mb-0">Total</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-warning text-dark">
                        <div class="card-body p-3 text-center">
                            <h3 class="mb-0"><?php echo $stats['pending']; ?></h3>
                            <p class="mb-0">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-success text-white">
                        <div class="card-body p-3 text-center">
                            <h3 class="mb-0"><?php echo $stats['confirmed']; ?></h3>
                            <p class="mb-0">Confirmed</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-info text-white">
                        <div class="card-body p-3 text-center">
                            <h3 class="mb-0"><?php echo $stats['completed']; ?></h3>
                            <p class="mb-0">Completed</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-danger text-white">
                        <div class="card-body p-3 text-center">
                            <h3 class="mb-0"><?php echo $stats['cancelled']; ?></h3>
                            <p class="mb-0">Cancelled</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <form action="" method="get" class="row gx-3 gy-2 align-items-center">
                        <div class="col-md-2">
                            <label for="search" class="visually-hidden">Search</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="visually-hidden">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="doctor_id" class="visually-hidden">Doctor</label>
                            <select class="form-select" id="doctor_id" name="doctor_id">
                                <option value="0">All Doctors</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['id']; ?>" <?php echo $doctor_filter === (int)$doctor['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($doctor['full_name']); ?> (<?php echo htmlspecialchars($doctor['specialty']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date" class="visually-hidden">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                        <div class="col-auto">
                            <a href="appointments.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Appointments Table -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($appointments) > 0): ?>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo $appointment['id']; ?></td>
                                            <td>
                                                <a href="view_user.php?id=<?php echo $appointment['patient_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="view_user.php?id=<?php echo $appointment['doctor_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                                    <small class="text-muted d-block"><?php echo htmlspecialchars($appointment['specialty']); ?></small>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                <small class="text-muted d-block"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                switch ($appointment['status']) {
                                                    case 'pending':
                                                        echo '<span class="badge bg-warning text-dark">Pending</span>';
                                                        break;
                                                    case 'confirmed':
                                                        echo '<span class="badge bg-success">Confirmed</span>';
                                                        break;
                                                    case 'completed':
                                                        echo '<span class="badge bg-info">Completed</span>';
                                                        break;
                                                    case 'cancelled':
                                                        echo '<span class="badge bg-danger">Cancelled</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Unknown</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($appointment['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $appointment['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($appointment['status'] === 'pending'): ?>
                                                        <a href="appointments.php?action=confirm&id=<?php echo $appointment['id']; ?>" class="btn btn-outline-success" onclick="return confirm('Confirm this appointment?');">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($appointment['status'] === 'confirmed'): ?>
                                                        <a href="appointments.php?action=complete&id=<?php echo $appointment['id']; ?>" class="btn btn-outline-info" onclick="return confirm('Mark this appointment as completed?');">
                                                            <i class="fas fa-check-double"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($appointment['status'] === 'pending' || $appointment['status'] === 'confirmed'): ?>
                                                        <a href="appointments.php?action=cancel&id=<?php echo $appointment['id']; ?>" class="btn btn-outline-warning" onclick="return confirm('Cancel this appointment?');">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="appointments.php?action=delete&id=<?php echo $appointment['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this appointment? This action cannot be undone.');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                                
                                                <!-- View Modal -->
                                                <div class="modal fade" id="viewModal<?php echo $appointment['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Appointment Details</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <h6 class="fw-bold text-primary">Appointment #<?php echo $appointment['id']; ?></h6>
                                                                    <p class="mb-0">
                                                                        <span class="badge bg-<?php 
                                                                            echo $appointment['status'] === 'pending' ? 'warning text-dark' : 
                                                                                 ($appointment['status'] === 'confirmed' ? 'success' : 
                                                                                 ($appointment['status'] === 'completed' ? 'info' : 'danger')); 
                                                                        ?>">
                                                                            <?php echo ucfirst($appointment['status']); ?>
                                                                        </span>
                                                                    </p>
                                                                </div>
                                                                
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1 fw-bold">Patient</p>
                                                                        <p class="mb-0"><?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1 fw-bold">Doctor</p>
                                                                        <p class="mb-0"><?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                                                        <p class="mb-0 text-muted"><?php echo htmlspecialchars($appointment['specialty']); ?></p>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1 fw-bold">Date</p>
                                                                        <p class="mb-0"><?php echo date('F d, Y', strtotime($appointment['appointment_date'])); ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1 fw-bold">Time</p>
                                                                        <p class="mb-0"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <p class="mb-1 fw-bold">Reason for Visit</p>
                                                                    <p class="mb-0"><?php echo htmlspecialchars($appointment['reason']); ?></p>
                                                                </div>
                                                                
                                                                <div class="row mb-0">
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1 fw-bold">Created</p>
                                                                        <p class="mb-0"><?php echo date('M d, Y g:i A', strtotime($appointment['created_at'])); ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1 fw-bold">Last Updated</p>
                                                                        <p class="mb-0"><?php 
                                                                            echo isset($appointment['updated_at']) && $appointment['updated_at'] 
                                                                                ? date('M d, Y g:i A', strtotime($appointment['updated_at'])) 
                                                                                : 'Not updated yet'; 
                                                                        ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <?php if ($appointment['status'] === 'pending'): ?>
                                                                    <a href="appointments.php?action=confirm&id=<?php echo $appointment['id']; ?>" class="btn btn-success">
                                                                        <i class="fas fa-check me-1"></i> Confirm
                                                                    </a>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($appointment['status'] === 'confirmed'): ?>
                                                                    <a href="appointments.php?action=complete&id=<?php echo $appointment['id']; ?>" class="btn btn-info text-white">
                                                                        <i class="fas fa-check-double me-1"></i> Mark Completed
                                                                    </a>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($appointment['status'] === 'pending' || $appointment['status'] === 'confirmed'): ?>
                                                                    <a href="appointments.php?action=cancel&id=<?php echo $appointment['id']; ?>" class="btn btn-warning">
                                                                        <i class="fas fa-times me-1"></i> Cancel
                                                                    </a>
                                                                <?php endif; ?>
                                                                
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                                <h5>No appointments found</h5>
                                                <p class="text-muted">No appointments match your criteria</p>
                                            </div>
                                        </td>
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
    
    /* Dark mode form controls */
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
        background-color: rgba(44, 51, 82, 0.8);
        border-color: rgba(58, 64, 100, 0.8);
        color: #e9ecef;
    }
    
    /* Dark mode modal styles */
    [data-theme="dark"] .modal-content {
        background-color: var(--card-bg);
        color: var(--text-color);
        border-color: var(--border-color);
    }
    
    [data-theme="dark"] .modal-header,
    [data-theme="dark"] .modal-footer {
        border-color: var(--border-color);
    }
</style>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?> 