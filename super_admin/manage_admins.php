<?php
// super_admin/manage_admins.php - Manage Admins
$page_title = 'Manage Admins';
require_once 'includes/header.php';
require_once '../db_connection.php';

// Handle admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("An admin with this username already exists.");
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $role]);
            
            $success = "Admin user created successfully.";
        } catch (Exception $e) {
            $error = "Error creating admin: " . $e->getMessage();
        }
    }
}

// Handle admin deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id = $_POST['user_id'] ?? '';
    
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } elseif (!empty($user_id)) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role IN ('admin', 'super_admin')");
            $stmt->execute([$user_id]);
            
            $success = "Admin user deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting admin: " . $e->getMessage();
        }
    }
}

// Get all admin users
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE role IN ('admin', 'super_admin') ORDER BY username");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading admins: " . $e->getMessage();
}

$super_admin_count = count(array_filter($admins, fn($a) => $a['role'] === 'super_admin'));
$admin_count = count(array_filter($admins, fn($a) => $a['role'] === 'admin'));
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Manage Admins</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Manage Admins</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
        <i class="fas fa-plus me-2"></i>Add New Admin
    </button>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-users-cog"></i></div>
            <div class="stat-card-value"><?php echo count($admins); ?></div>
            <div class="stat-card-label">Total Admins</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card danger">
            <div class="stat-card-icon"><i class="fas fa-user-shield"></i></div>
            <div class="stat-card-value"><?php echo $super_admin_count; ?></div>
            <div class="stat-card-label">Super Admins</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-user-tie"></i></div>
            <div class="stat-card-value"><?php echo $admin_count; ?></div>
            <div class="stat-card-label">Regular Admins</div>
        </div>
    </div>
</div>

<!-- Admins Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>All Admin Users</h5>
        <span class="badge bg-primary"><?php echo count($admins); ?> Total</span>
    </div>
    <div class="card-body">
        <?php if (empty($admins)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users-cog fa-3x text-muted mb-3"></i>
                <p class="text-muted">No admin users found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width:36px;height:36px;font-size:14px;">
                                            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                            <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge badge-soft-success ms-2">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($admin['role'] === 'super_admin'): ?>
                                        <span class="badge badge-soft-danger"><i class="fas fa-crown me-1"></i>Super Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-primary"><i class="fas fa-user-tie me-1"></i>Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas fa-calendar-alt text-muted me-1"></i>
                                    <?php echo date('M d, Y', strtotime($admin['created_at'])); ?>
                                </td>
                                <td>
                                    <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this admin user?')">
                                            <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-soft-danger btn-sm">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-lock me-1"></i>Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required placeholder="Enter username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required placeholder="Enter password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
