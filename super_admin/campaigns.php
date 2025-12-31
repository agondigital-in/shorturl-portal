<?php
// super_admin/campaigns.php - Campaigns Management
$page_title = 'Campaigns';
require_once 'includes/header.php';
require_once '../db_connection.php';

// Handle campaign status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (!empty($campaign_id) && in_array($status, ['active', 'inactive'])) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
            $stmt->execute([$status, $campaign_id]);
            
            $success = "Campaign status updated successfully.";
        } catch (PDOException $e) {
            $error = "Error updating campaign status: " . $e->getMessage();
        }
    }
}

// Handle campaign deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    
    if (!empty($campaign_id)) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("DELETE FROM campaigns WHERE id = ?");
            $stmt->execute([$campaign_id]);
            
            $success = "Campaign deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting campaign: " . $e->getMessage();
        }
    }
}

// Get all campaigns with advertiser and publisher information
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT c.*, 
               c.enable_image_pixel,
               GROUP_CONCAT(DISTINCT a.name) as advertiser_names,
               GROUP_CONCAT(DISTINCT p.name) as publisher_names
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN advertisers a ON ca.advertiser_id = a.id
        LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
        LEFT JOIN publishers p ON cp.publisher_id = p.id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $active_count = count(array_filter($campaigns, fn($c) => $c['status'] === 'active'));
    $inactive_count = count(array_filter($campaigns, fn($c) => $c['status'] === 'inactive'));
    $total_clicks = array_sum(array_column($campaigns, 'click_count'));
    
} catch (PDOException $e) {
    $error = "Error loading campaigns: " . $e->getMessage();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Campaigns</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Campaigns</li>
            </ol>
        </nav>
    </div>
    <a href="add_campaign.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add New Campaign
    </a>
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
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="stat-card-value"><?php echo count($campaigns); ?></div>
            <div class="stat-card-label">Total Campaigns</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-card-value"><?php echo $active_count; ?></div>
            <div class="stat-card-label">Active</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-card-icon"><i class="fas fa-pause-circle"></i></div>
            <div class="stat-card-value"><?php echo $inactive_count; ?></div>
            <div class="stat-card-label">Inactive</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_clicks); ?></div>
            <div class="stat-card-label">Total Clicks</div>
        </div>
    </div>
</div>

<!-- Campaigns Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i>All Campaigns</h5>
        <span class="badge bg-primary"><?php echo count($campaigns); ?> Total</span>
    </div>
    <div class="card-body">
        <?php if (empty($campaigns)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                <p class="text-muted">No campaigns found. Create your first campaign!</p>
                <a href="add_campaign.php" class="btn btn-primary mt-2">
                    <i class="fas fa-plus me-2"></i>Create Campaign
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Campaign Name</th>
                            <th>Advertisers</th>
                            <th>Publishers</th>
                            <th>Dates</th>
                            <th>Type</th>
                            <th>Short Code</th>
                            <th>Clicks</th>
                            <th>Payouts</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><span class="badge badge-soft-primary">#<?php echo $campaign['id']; ?></span></td>
                                <td><strong><?php echo htmlspecialchars($campaign['name']); ?></strong></td>
                                <td>
                                    <div class="text-truncate" style="max-width: 120px;" title="<?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?>">
                                        <?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 120px;" title="<?php echo htmlspecialchars($campaign['publisher_names'] ?? 'N/A'); ?>">
                                        <?php echo htmlspecialchars($campaign['publisher_names'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('M d', strtotime($campaign['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($campaign['end_date'])); ?>
                                    </small>
                                </td>
                                <td><span class="badge badge-soft-primary"><?php echo htmlspecialchars($campaign['campaign_type']); ?></span></td>
                                <td><code><?php echo htmlspecialchars($campaign['shortcode']); ?></code></td>
                                <td><strong><?php echo number_format($campaign['click_count']); ?></strong></td>
                                <td>
                                    <small>
                                        <span class="text-success">A: ₹<?php echo number_format($campaign['advertiser_payout'], 2); ?></span><br>
                                        <span class="text-danger">P: ₹<?php echo number_format($campaign['publisher_payout'], 2); ?></span>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge <?php echo $campaign['status'] === 'active' ? 'badge-soft-success' : 'badge-soft-warning'; ?>">
                                        <?php echo ucfirst($campaign['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $campaign['payment_status'] === 'completed' ? 'badge-soft-success' : 'badge-soft-warning'; ?>">
                                        <?php echo ucfirst($campaign['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="campaign_tracking_stats.php?id=<?php echo $campaign['id']; ?>" class="btn btn-soft-info btn-sm" title="Stats">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                        <?php if (!empty($campaign['enable_image_pixel'])): ?>
                                        <a href="campaign_pixel_links.php?id=<?php echo $campaign['id']; ?>" class="btn btn-soft-primary btn-sm" title="Pixel Links">
                                            <i class="fas fa-image"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="edit_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-soft-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <?php if ($campaign['status'] === 'active'): ?>
                                                <button type="submit" name="status" value="inactive" class="btn btn-soft-secondary btn-sm" title="Deactivate">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="status" value="active" class="btn btn-soft-success btn-sm" title="Activate">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?')">
                                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
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

<?php require_once 'includes/footer.php'; ?>
