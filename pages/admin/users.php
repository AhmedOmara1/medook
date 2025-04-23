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

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Check if not deleting admin user
    $checkSql = "SELECT role FROM users WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $userToDelete = $checkResult->fetch_assoc();
    
    if ($userToDelete && $userToDelete['role'] === 'admin') {
        $deleteError = "Cannot delete admin user";
    } else {
        // Delete user
        $deleteSql = "DELETE FROM users WHERE id = ? AND role != 'admin'";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $user_id);
        
        if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
            $deleteSuccess = "User deleted successfully";
        } else {
            $deleteError = "Error deleting user";
        }
    }
}

// Get users by role filter
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if (!empty($roleFilter)) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
    $types .= "s";
}

if (!empty($searchTerm)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $searchPattern = "%{$searchTerm}%";
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $types .= "sss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

// Count users by role
$countSql = "SELECT 
                SUM(CASE WHEN role = 'patient' THEN 1 ELSE 0 END) as patients,
                SUM(CASE WHEN role = 'doctor' THEN 1 ELSE 0 END) as doctors,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                COUNT(*) as total
             FROM users";
$countResult = $conn->query($countSql);
$counts = $countResult->fetch_assoc();
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
                    <div class="bg-primary-light p-3 rounded mb-3 active d-flex align-items-center sidebar-item" style="background-color: rgba(255,255,255,0.2);">
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
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-9 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">User Management</h2>
                <a href="add_user.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New User
                </a>
            </div>
            
            <?php if (isset($deleteSuccess)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $deleteSuccess; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($deleteError)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $deleteError; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- User Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total Users</h6>
                                    <h2 class="mb-0"><?php echo $counts['total']; ?></h2>
                                </div>
                                <div class="rounded-circle bg-white p-3">
                                    <i class="fas fa-users text-primary"></i>
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
                                    <h6 class="mb-0">Patients</h6>
                                    <h2 class="mb-0"><?php echo $counts['patients']; ?></h2>
                                </div>
                                <div class="rounded-circle bg-white p-3">
                                    <i class="fas fa-user text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Doctors</h6>
                                    <h2 class="mb-0"><?php echo $counts['doctors']; ?></h2>
                                </div>
                                <div class="rounded-circle bg-white p-3">
                                    <i class="fas fa-user-md text-info"></i>
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
                                    <h6 class="mb-0">Admins</h6>
                                    <h2 class="mb-0"><?php echo $counts['admins']; ?></h2>
                                </div>
                                <div class="rounded-circle bg-white p-3">
                                    <i class="fas fa-user-shield text-warning"></i>
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
                                <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, email, username" value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="visually-hidden">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">All Roles</option>
                                <option value="patient" <?php echo $roleFilter === 'patient' ? 'selected' : ''; ?>>Patients</option>
                                <option value="doctor" <?php echo $roleFilter === 'doctor' ? 'selected' : ''; ?>>Doctors</option>
                                <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                        <div class="col-auto">
                            <a href="users.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Username</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Created</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $index => $user): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php
                                                switch ($user['role']) {
                                                    case 'admin':
                                                        echo '<span class="badge bg-warning">Admin</span>';
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
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($user['role'] !== 'admin'): ?>
                                                        <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-outline-danger" title="Delete" 
                                                           onclick="return confirm('Are you sure you want to delete this user?');">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                <h5>No users found</h5>
                                                <p class="text-muted">No users match your criteria</p>
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
</style>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?> 