<?php
// super_admin/publishers.php - Manage Publishers
$page_title = 'Publishers';
require_once 'includes/header.php';
require_once '../db_connection.php';

// Handle publisher creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password are required.';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("INSERT INTO publishers (name, email, password, website, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $website, $phone]);
            
            $success = "Publisher created successfully.";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = "A publisher with this email already exists.";
            } else {
                $error = "Error creating publisher: " . $e->getMessage();
            }
        }
    }
}

// Handle publisher deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $publisher_id = $_POST['publisher_id'] ?? '';
    
    if (!empty($publisher_id)) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("DELETE FROM publishers WHERE id = ?");
            $stmt->execute([$publisher_id]);
            
            $success = "Publisher deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting publisher: " . $e->getMessage();
        }
    }
}

// Get all publishers
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM publishers ORDER BY name");
    $stmt->execute();
    $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading publishers: " . $e->getMessage();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Publishers</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Publishers</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPublisherModal">
        <i class="fas fa-plus me-2"></i>Add Publisher
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
            <div class="stat-card-icon"><i class="fas fa-globe"></i></div>
            <div class="stat-card-value"><?php echo count($publishers); ?></div>
            <div class="stat-card-label">Total Publishers</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-card-value"><?php echo count(array_filter($publishers, fn($p) => !empty($p['website']))); ?></div>
            <div class="stat-card-label">With Website</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-phone"></i></div>
            <div class="stat-card-value"><?php echo count(array_filter($publishers, fn($p) => !empty($p['phone']))); ?></div>
            <div class="stat-card-label">With Phone</div>
        </div>
    </div>
</div>

<!-- Publishers Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-globe me-2"></i>All Publishers</h5>
        <span class="badge bg-primary"><?php echo count($publishers); ?> Total</span>
    </div>
    <div class="card-body">
        <?php if (empty($publishers)): ?>
            <div class="text-center py-5">
                <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                <p class="text-muted">No publishers found. Add your first publisher!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Website</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($publishers as $publisher): ?>
                            <tr>
                                <td><span class="badge badge-soft-primary">#<?php echo htmlspecialchars($publisher['id']); ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width:32px;height:32px;font-size:12px;">
                                            <?php echo strtoupper(substr($publisher['name'], 0, 1)); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($publisher['name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($publisher['email']); ?></td>
                                <td>
                                    <?php if (!empty($publisher['website'])): ?>
                                        <a href="<?php echo htmlspecialchars($publisher['website']); ?>" target="_blank" class="text-primary">
                                            <i class="fas fa-external-link-alt me-1"></i><?php echo htmlspecialchars(parse_url($publisher['website'], PHP_URL_HOST) ?: $publisher['website']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($publisher['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="publisher_daily_clicks.php?publisher_id=<?php echo $publisher['id']; ?>" class="btn btn-soft-info btn-sm" title="View Stats">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this publisher?')">
                                            <input type="hidden" name="publisher_id" value="<?php echo $publisher['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-soft-danger btn-sm" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Publisher Modal -->
<div class="modal fade" id="createPublisherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Publisher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="Enter publisher name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required placeholder="Enter email address">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required placeholder="Enter password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Website</label>
                        <input type="url" class="form-control" name="website" placeholder="https://example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" placeholder="Enter phone number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Create Publisher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
