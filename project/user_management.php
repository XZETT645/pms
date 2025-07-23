<?php
require_once 'config/database.php';
require_once 'config/session.php';

checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle user addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone_number = trim($_POST['phone_number']);
    $role = $_POST['role'];
    
    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $email);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $error_message = 'Email already exists';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (full_name, email, password, phone_number, role) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(1, $full_name);
        $insert_stmt->bindParam(2, $email);
        $insert_stmt->bindParam(3, $hashed_password);
        $insert_stmt->bindParam(4, $phone_number);
        $insert_stmt->bindParam(5, $role);
        
        if ($insert_stmt->execute()) {
            $success_message = 'User added successfully';
        } else {
            $error_message = 'Failed to add user';
        }
    }
}

// Handle user status toggle
if (isset($_GET['toggle_status']) && isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $toggle_query = "UPDATE users SET is_active = NOT is_active WHERE id = ?";
    $toggle_stmt = $db->prepare($toggle_query);
    $toggle_stmt->bindParam(1, $user_id);
    
    if ($toggle_stmt->execute()) {
        $success_message = 'User status updated successfully';
    } else {
        $error_message = 'Failed to update user status';
    }
}

// Get user statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users
    FROM users
";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'User Management - Program Management System';
include 'includes/header.php';
?>

<div class="container-fluid main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="page-title">User Management</h1>
            <button type="button" class="btn btn-kedah-blue" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
    </div>
    
    <div id="alertContainer">
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dashboard-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 text-kedah-blue"><?php echo number_format($stats['total_users']); ?></h3>
                        <p class="mb-0 text-muted">Total Users</p>
                    </div>
                    <div class="text-kedah-blue">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dashboard-card gold p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 text-kedah-blue"><?php echo number_format($stats['total_admins']); ?></h3>
                        <p class="mb-0 text-muted">Administrators</p>
                    </div>
                    <div class="text-kedah-gold">
                        <i class="fas fa-user-shield fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dashboard-card success p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 text-success"><?php echo number_format($stats['active_users']); ?></h3>
                        <p class="mb-0 text-muted">Active Users</p>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="dashboard-card danger p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 text-danger"><?php echo number_format($stats['inactive_users']); ?></h3>
                        <p class="mb-0 text-muted">Inactive Users</p>
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-user-times fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-container">
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-kedah-blue">
                            <i class="fas fa-users"></i> All Users
                        </h5>
                        <div class="d-flex gap-2">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search users..." style="width: 200px;">
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="usersTable">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <?php if ($user['profile_photo']): ?>
                                                <img src="<?php echo $user['profile_photo']; ?>" class="rounded-circle" width="40" height="40">
                                            <?php else: ?>
                                                <div class="bg-kedah-blue text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                <td>
                                    <span class="badge bg-kedah-blue">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="?toggle_status=1&user_id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>"
                                       onclick="return confirm('Are you sure you want to change this user\'s status?')">
                                        <i class="fas fa-<?php echo $user['is_active'] ? 'pause' : 'play'; ?>"></i>
                                        <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number">
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Choose role...</option>
                            <option value="admin">Administrator</option>
                            <option value="exco_user">EXCO User</option>
                            <option value="exco_pa">EXCO PA</option>
                            <option value="finance">Finance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-kedah-blue">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>