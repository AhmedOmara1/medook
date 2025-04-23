<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../config/path_config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('pages.login');
}

// Only patients can access this page
if ($_SESSION['role'] !== 'patient') {
    redirect('pages.unauthorized');
}

$patient_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle appointment cancellation
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    
    // Verify the appointment belongs to this patient
    $checkSql = "SELECT * FROM appointments WHERE id = ? AND patient_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $appointment_id, $patient_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $appointment = $checkResult->fetch_assoc();
        // Only allow cancellation if appointment is pending or confirmed
        if ($appointment['status'] === 'pending' || $appointment['status'] === 'confirmed') {
            $updateSql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("i", $appointment_id);
            
            if ($updateStmt->execute()) {
                $success_message = "Your appointment has been cancelled successfully.";
            } else {
                $error_message = "There was an error cancelling your appointment. Please try again.";
            }
        } else {
            $error_message = "This appointment cannot be cancelled due to its current status.";
        }
    } else {
        $error_message = "Invalid appointment or you don't have permission to cancel it.";
    }
}

// Get filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters
$sql = "SELECT a.*, 
         d.specialty,
         u.full_name as doctor_name
         FROM appointments a
         JOIN doctors d ON a.doctor_id = d.id
         JOIN users u ON d.user_id = u.id
         WHERE a.patient_id = ?";
$params = [$patient_id];
$types = "i";

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_filter)) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);

// Get appointment count statistics
$statsSql = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
              SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
              SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
           FROM appointments 
           WHERE patient_id = ?";
$statsStmt = $conn->prepare($statsSql);
$statsStmt->bind_param("i", $patient_id);
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$stats = $statsResult->fetch_assoc();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Appointments</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo getUrl('pages.doctors'); ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Book New Appointment
                    </a>
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
            
            <!-- Appointment Statistics -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="card bg-primary text-white h-100 shadow-sm" style="transition: all 0.3s ease;">
                        <div class="card-body py-3">
                            <h5 class="card-title h3 mb-0"><?php echo $stats['total']; ?></h5>
                            <p class="card-text">Total Appointments</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="card bg-warning text-dark h-100 shadow-sm" style="transition: all 0.3s ease;">
                        <div class="card-body py-3">
                            <h5 class="card-title h3 mb-0"><?php echo $stats['pending']; ?></h5>
                            <p class="card-text">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="card bg-success text-white h-100 shadow-sm" style="transition: all 0.3s ease;">
                        <div class="card-body py-3">
                            <h5 class="card-title h3 mb-0"><?php echo $stats['confirmed']; ?></h5>
                            <p class="card-text">Confirmed</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="card bg-info text-white h-100 shadow-sm" style="transition: all 0.3s ease;">
                        <div class="card-body py-3">
                            <h5 class="card-title h3 mb-0"><?php echo $stats['completed']; ?></h5>
                            <p class="card-text">Completed</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card shadow-sm mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <a href="appointments.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Appointments List -->
            <div class="card shadow-sm" data-aos="fade-up" data-aos-delay="200">
                <div class="card-body p-0">
                    <?php if (count($appointments) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Doctor</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $index => $appointment): ?>
                                        <tr data-aos="fade-up" data-aos-delay="<?php echo 100 + ($index * 50); ?>" class="appointment-row" style="transition: all 0.3s ease;">
                                            <td><?php echo $appointment['id']; ?></td>
                                            <td>
                                                <a href="<?php echo getUrl('pages.doctor_profile', ['id' => $appointment['doctor_id']]); ?>" class="text-decoration-none">
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
                                            <td><?php echo date('M d, Y', strtotime($appointment['created_at'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info mb-1 view-appointment" 
                                                        data-id="<?php echo $appointment['id']; ?>"
                                                        data-doctor="<?php echo htmlspecialchars($appointment['doctor_name']); ?>"
                                                        data-specialty="<?php echo htmlspecialchars($appointment['specialty']); ?>"
                                                        data-date="<?php echo date('F d, Y', strtotime($appointment['appointment_date'])); ?>"
                                                        data-time="<?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>"
                                                        data-reason="<?php echo htmlspecialchars($appointment['reason']); ?>"
                                                        data-status="<?php echo $appointment['status']; ?>"
                                                        data-created="<?php echo date('F d, Y', strtotime($appointment['created_at'])); ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($appointment['status'] === 'pending' || $appointment['status'] === 'confirmed'): ?>
                                                    <a href="appointments.php?action=cancel&id=<?php echo $appointment['id']; ?>" 
                                                        class="btn btn-sm btn-danger mb-1" 
                                                        onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-calendar-times fa-4x text-muted"></i>
                            </div>
                            <h4 class="mb-3">No Appointments Found</h4>
                            <p class="text-muted mb-4">You don't have any appointments matching your filters.</p>
                            <a href="<?php echo getUrl('pages.doctors'); ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Book New Appointment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Appointment View Modal -->
<div class="modal fade" id="viewAppointmentModal" tabindex="-1" aria-labelledby="viewAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewAppointmentModalLabel">Appointment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Doctor</p>
                        <p id="modal-doctor" class="mb-0"></p>
                        <p id="modal-specialty" class="mb-0 text-muted"></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-1 fw-bold">Status</p>
                        <p id="modal-status" class="mb-0"></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Date</p>
                        <p id="modal-date" class="mb-0"></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Time</p>
                        <p id="modal-time" class="mb-0"></p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <p class="mb-1 fw-bold">Reason for Visit</p>
                    <p id="modal-reason" class="mb-0"></p>
                </div>
                
                <div class="mb-3">
                    <p class="mb-1 fw-bold">Created On</p>
                    <p id="modal-created" class="mb-0"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="cancel-appointment-btn" class="btn btn-danger d-none">Cancel Appointment</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle view appointment modal
    const viewAppointmentButtons = document.querySelectorAll('.view-appointment');
    const viewAppointmentModal = document.getElementById('viewAppointmentModal');
    const cancelAppointmentBtn = document.getElementById('cancel-appointment-btn');
    
    viewAppointmentButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const doctor = this.getAttribute('data-doctor');
            const specialty = this.getAttribute('data-specialty');
            const date = this.getAttribute('data-date');
            const time = this.getAttribute('data-time');
            const reason = this.getAttribute('data-reason');
            const status = this.getAttribute('data-status');
            const created = this.getAttribute('data-created');
            
            document.getElementById('modal-doctor').textContent = doctor;
            document.getElementById('modal-specialty').textContent = specialty;
            document.getElementById('modal-date').textContent = date;
            document.getElementById('modal-time').textContent = time;
            document.getElementById('modal-reason').textContent = reason;
            document.getElementById('modal-created').textContent = created;
            
            // Set status with badge
            let statusHtml = '';
            switch (status) {
                case 'pending':
                    statusHtml = '<span class="badge bg-warning text-dark">Pending</span>';
                    break;
                case 'confirmed':
                    statusHtml = '<span class="badge bg-success">Confirmed</span>';
                    break;
                case 'cancelled':
                    statusHtml = '<span class="badge bg-danger">Cancelled</span>';
                    break;
                case 'completed':
                    statusHtml = '<span class="badge bg-info">Completed</span>';
                    break;
                default:
                    statusHtml = '<span class="badge bg-secondary">Unknown</span>';
            }
            document.getElementById('modal-status').innerHTML = statusHtml;
            
            // Show cancel button only for pending or confirmed appointments
            if (status === 'pending' || status === 'confirmed') {
                cancelAppointmentBtn.classList.remove('d-none');
                cancelAppointmentBtn.href = `appointments.php?action=cancel&id=${id}`;
            } else {
                cancelAppointmentBtn.classList.add('d-none');
            }
            
            // Show modal
            const bsModal = new bootstrap.Modal(viewAppointmentModal);
            bsModal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<style>
    /* Appointment row hover effect */
    .appointment-row {
        transition: all 0.3s ease;
    }
    
    .appointment-row:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        z-index: 1;
        position: relative;
    }
    
    /* Card hover animations */
    .card {
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
    }
    
    /* Button animations */
    .btn {
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }
    
    /* Badge animations */
    .badge {
        transition: all 0.3s ease;
    }
    
    .badge:hover {
        transform: scale(1.1);
    }
    
    /* Dark mode styling for filter inputs */
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
        background-color: rgba(44, 51, 82, 0.8);
        border-color: rgba(58, 64, 100, 0.8);
        color: #e9ecef;
    }
    
    /* Enhance the Book New Appointment button */
    .btn-primary {
        background: linear-gradient(45deg, #4e73df, #36b9cc);
        border: none;
        box-shadow: 0 4px 15px rgba(78, 115, 223, 0.2);
    }
    
    .btn-primary:hover {
        background: linear-gradient(45deg, #36b9cc, #4e73df);
        box-shadow: 0 6px 20px rgba(78, 115, 223, 0.3);
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
    
    [data-theme="dark"] .appointment-row:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }
    
    [data-theme="dark"] .table-responsive {
        border-color: var(--border-color);
    }
</style> 