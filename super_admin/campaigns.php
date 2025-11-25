<?php
// super_admin/campaigns.php - Campaigns Management
session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

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
            
            // Delete campaign (cascading will remove related records)
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
    
} catch (PDOException $e) {
    $error = "Error loading campaigns: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaigns - Ads Platform</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Theme -->
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#"><i class="fas fa-chart-line me-2"></i>Ads Platform</a>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid" style="margin-top: 80px;">
        <div class="row g-4 p-4">
            <!-- Sidebar -->
            <div class="col-md-2">
                <div class="sidebar-nav">
                    <a href="dashboard.php" class="nav-link-custom"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="campaigns.php" class="nav-link-custom active"><i class="fas fa-bullhorn"></i> Campaigns</a>
                    <a href="advertisers.php" class="nav-link-custom"><i class="fas fa-users"></i> Advertisers</a>
                    <a href="publishers.php" class="nav-link-custom"><i class="fas fa-network-wired"></i> Publishers</a>
                    <a href="admins.php" class="nav-link-custom"><i class="fas fa-user-shield"></i> Admins</a>
                    <a href="payment_reports.php" class="nav-link-custom"><i class="fas fa-file-invoice-dollar"></i> Reports</a>
                    <a href="all_publishers_daily_clicks.php" class="nav-link-custom"><i class="fas fa-file-invoice-dollar"></i>All Publishers Stats</a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1 text-dark">Campaigns</h2>
                        <p class="text-secondary mb-0">Manage and track all your marketing campaigns</p>
                    </div>
                    <a href="add_campaign.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Campaign
                    </a>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <div class="modern-card">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h5 class="mb-0 fw-bold">All Campaigns</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($campaigns)): ?>
                            <div class="p-5 text-center">
                                <div class="mb-3">
                                    <i class="fas fa-bullhorn fa-3x text-secondary opacity-50"></i>
                                </div>
                                <h5 class="text-secondary">No campaigns found</h5>
                                <p class="text-muted">Get started by creating your first campaign.</p>
                                <a href="add_campaign.php" class="btn btn-outline-primary mt-2">Create Campaign</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Campaign Name</th>
                                            <th>Advertisers</th>
                                            <th>Publishers</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Type</th>
                                            <th>Base Short Code</th>
                                            <th class="text-end">Clicks</th>
                                            <th class="text-end">Adv. Payout</th>
                                            <th class="text-end">Pub. Payout</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($campaigns as $campaign): ?>
                                            <tr>
                                                <td class="text-secondary">#<?php echo htmlspecialchars($campaign['id']); ?></td>
                                                <td class="fw-medium text-dark"><?php echo htmlspecialchars($campaign['name']); ?></td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?>">
                                                        <?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($campaign['publisher_names'] ?? 'N/A'); ?>">
                                                        <?php echo htmlspecialchars($campaign['publisher_names'] ?? 'N/A'); ?>
                                                    </div>
                                                </td>
                                                <td class="small"><?php echo htmlspecialchars($campaign['start_date']); ?></td>
                                                <td class="small"><?php echo htmlspecialchars($campaign['end_date']); ?></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($campaign['campaign_type']); ?></span></td>
                                                <td class="font-monospace small"><?php echo htmlspecialchars($campaign['shortcode']); ?></td>
                                                <td class="text-end fw-bold"><?php echo number_format($campaign['click_count']); ?></td>
                                                <td class="text-end text-success">₹<?php echo number_format($campaign['advertiser_payout'], 2); ?></td>
                                                <td class="text-end text-danger">₹<?php echo number_format($campaign['publisher_payout'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $campaign['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                                        <?php echo ucfirst($campaign['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $campaign['payment_status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?> rounded-pill">
                                                        <?php echo ucfirst($campaign['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <a href="campaign_tracking_stats.php?id=<?php echo $campaign['id']; ?>" class="btn btn-soft-info" title="Tracking Stats" data-bs-toggle="tooltip">
                                                            <i class="fas fa-chart-line fa-lg"></i>
                                                        </a>
                                                        <a href="edit_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-soft-warning" title="Edit Campaign" data-bs-toggle="tooltip">
                                                            <i class="fas fa-edit fa-lg"></i>
                                                        </a>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <?php if ($campaign['status'] === 'active'): ?>
                                                                <button type="submit" name="status" value="inactive" class="btn btn-soft-secondary" title="Deactivate" data-bs-toggle="tooltip">
                                                                    <i class="fas fa-pause fa-lg"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="submit" name="status" value="active" class="btn btn-soft-success" title="Activate" data-bs-toggle="tooltip">
                                                                    <i class="fas fa-play fa-lg"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </form>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this campaign?')">
                                                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" class="btn btn-soft-danger" title="Delete" data-bs-toggle="tooltip">
                                                                <i class="fas fa-trash fa-lg"></i>
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>