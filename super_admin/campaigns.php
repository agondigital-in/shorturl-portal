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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .card {
            border: none;
            border-radius: 10px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }
        .shadow {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: calc(100vh - 56px);
            position: sticky;
            top: 56px;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 56px);
            overflow-x: hidden;
            overflow-y: auto;
        }
        .nav-link {
            color: #495057;
            padding: 0.75rem 1rem;
            border-left: 3px solid transparent;
        }
        .nav-link:hover {
            color: #0d6efd;
            background-color: #e9ecef;
        }
        .nav-link.active {
            color: #0d6efd;
            background-color: #e9ecef;
            border-left: 3px solid #0d6efd;
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <i class="fas fa-chart-line me-2 text-primary"></i>
                <span class="fw-bold text-dark">Ads Platform</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <span class="navbar-text">
                            <i class="fas fa-user-circle me-1"></i>
                            Welcome, <span class="fw-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 d-none d-lg-block bg-light sidebar p-0">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'campaigns.php' ? 'active' : ''; ?>" href="campaigns.php">
                                <i class="fas fa-bullhorn me-2"></i>
                                <span>Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'advertisers.php' ? 'active' : ''; ?>" href="advertisers.php">
                                <i class="fas fa-users me-2"></i>
                                <span>Advertisers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'publishers.php' ? 'active' : ''; ?>" href="publishers.php">
                                <i class="fas fa-share-alt me-2"></i>
                                <span>Publishers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admins.php' ? 'active' : ''; ?>" href="admins.php">
                                <i class="fas fa-user-shield me-2"></i>
                                <span>Admins</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'advertiser_campaigns.php' ? 'active' : ''; ?>" href="advertiser_campaigns.php">
                                <i class="fas fa-ad me-2"></i>
                                <span>Advertiser Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'publisher_campaigns.php' ? 'active' : ''; ?>" href="publisher_campaigns.php">
                                <i class="fas fa-link me-2"></i>
                                <span>Publisher Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'payment_reports.php' ? 'active' : ''; ?>" href="payment_reports.php">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                <span>Payment Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Mobile Sidebar Toggle -->
            <div class="col-12 d-lg-none bg-light p-2">
                <button class="btn btn-primary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <i class="fas fa-bars me-2"></i>Menu
                </button>
            </div>
            
            <!-- Mobile Offcanvas Sidebar -->
            <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
                <div class="offcanvas-header bg-light">
                    <h5 class="offcanvas-title">Navigation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'campaigns.php' ? 'active' : ''; ?>" href="campaigns.php">
                                <i class="fas fa-bullhorn me-2"></i>
                                <span>Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'advertisers.php' ? 'active' : ''; ?>" href="advertisers.php">
                                <i class="fas fa-users me-2"></i>
                                <span>Advertisers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'publishers.php' ? 'active' : ''; ?>" href="publishers.php">
                                <i class="fas fa-share-alt me-2"></i>
                                <span>Publishers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admins.php' ? 'active' : ''; ?>" href="admins.php">
                                <i class="fas fa-user-shield me-2"></i>
                                <span>Admins</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'advertiser_campaigns.php' ? 'active' : ''; ?>" href="advertiser_campaigns.php">
                                <i class="fas fa-ad me-2"></i>
                                <span>Advertiser Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'publisher_campaigns.php' ? 'active' : ''; ?>" href="publisher_campaigns.php">
                                <i class="fas fa-link me-2"></i>
                                <span>Publisher Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'payment_reports.php' ? 'active' : ''; ?>" href="payment_reports.php">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                <span>Payment Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <main class="col-lg-10 ms-sm-auto px-md-4 py-3">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h3 mb-0 text-dark">Campaigns</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Campaigns</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 mb-0">All Campaigns</h2>
                    <a href="add_campaign.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add New Campaign
                    </a>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($campaigns)): ?>
                            <p>No campaigns found. <a href="add_campaign.php">Create your first campaign</a>.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
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
                                            <th>Clicks</th>
                                            <th>Adv. Payout</th>
                                            <th>Pub. Payout</th>
                                            <th>Status</th>
                                            <th>Payment Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($campaigns as $campaign): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($campaign['id']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['publisher_names'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['start_date']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['end_date']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['campaign_type']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['shortcode']); ?></td>
                                                <td><?php echo $campaign['click_count']; ?></td>
                                                <td>₹<?php echo number_format($campaign['advertiser_payout'], 2); ?></td>
                                                <td>₹<?php echo number_format($campaign['publisher_payout'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($campaign['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $campaign['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($campaign['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="campaign_tracking_stats.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-info">Tracking Stats</a>
                                                    <a href="edit_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                                            <option value="active" <?php echo $campaign['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="inactive" <?php echo $campaign['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                        </select>
                                                    </form>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this campaign?')">
                                                        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>