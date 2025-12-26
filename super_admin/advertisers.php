<?php
// super_admin/advertisers.php - Manage Advertisers
$page_title = 'Advertisers';
require_once 'includes/header.php';
require_once '../db_connection.php';

// Handle advertiser creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("INSERT INTO advertisers (name, email, company, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $company, $phone]);
            
            $success = "Advertiser created successfully.";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = "An advertiser with this email already exists.";
            } else {
                $error = "Error creating advertiser: " . $e->getMessage();
            }
        }
    }
}

// Handle advertiser deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $advertiser_id = $_POST['advertiser_id'] ?? '';
    
    if (!empty($advertiser_id)) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("DELETE FROM advertisers WHERE id = ?");
            $stmt->execute([$advertiser_id]);
            
            $success = "Advertiser deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting advertiser: " . $e->getMessage();
        }
    }
}

// Get all advertisers
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM advertisers ORDER BY name");
    $stmt->execute();
    $advertisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading advertisers: " . $e->getMessage();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Advertisers</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Advertisers</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdvertiserModal">
        <i class="fas fa-plus me-2"></i>Add Advertiser
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
            <div class="stat-card-icon"><i class="fas fa-building"></i></div>
            <div class="stat-card-value"><?php echo count($advertisers); ?></div>
            <div class="stat-card-label">Total Advertisers</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-briefcase"></i></div>
            <div class="stat-card-value"><?php echo count(array_filter($advertisers, fn($a) => !empty($a['company']))); ?></div>
            <div class="stat-card-label">With Company</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-phone"></i></div>
            <div class="stat-card-value"><?php echo count(array_filter($advertisers, fn($a) => !empty($a['phone']))); ?></div>
            <div class="stat-card-label">With Phone</div>
        </div>
    </div>
</div>

<!-- Advertisers Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-building me-2"></i>All Advertisers</h5>
        <span class="badge bg-primary"><?php echo count($advertisers); ?> Total</span>
    </div>
    <div class="card-body">
        <?php if (empty($advertisers)): ?>
            <div class="text-center py-5">
                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                <p class="text-muted">No advertisers found. Add your first advertiser!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($advertisers as $advertiser): ?>
                            <tr>
                                <td><span class="badge badge-soft-primary">#<?php echo htmlspecialchars($advertiser['id']); ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width:32px;height:32px;font-size:12px;">
                                            <?php echo strtoupper(substr($advertiser['name'], 0, 1)); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($advertiser['name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($advertiser['email']); ?></td>
                                <td><?php echo htmlspecialchars($advertiser['company'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($advertiser['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="advertiser_campaigns.php?advertiser_id=<?php echo $advertiser['id']; ?>" class="btn btn-soft-info btn-sm" title="View Campaigns">
                                            <i class="fas fa-bullhorn"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this advertiser?')">
                                            <input type="hidden" name="advertiser_id" value="<?php echo $advertiser['id']; ?>">
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

<!-- Create Advertiser Modal -->
<div class="modal fade" id="createAdvertiserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Advertiser</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="Enter advertiser name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required placeholder="Enter email address">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <input type="text" class="form-control" name="company" placeholder="Enter company name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" placeholder="Enter phone number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Create Advertiser</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
