<?php
// super_admin/advertiser_campaigns.php - View Advertiser Campaigns
session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Get all advertisers with their campaigns
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT 
            a.id as advertiser_id,
            a.name as advertiser_name,
            a.email as advertiser_email,
            c.id as campaign_id,
            c.name as campaign_name,
            c.shortcode,
            c.start_date,
            c.end_date,
            c.campaign_type,
            c.click_count,
            c.status
        FROM advertisers a
        LEFT JOIN campaign_advertisers ca ON a.id = ca.advertiser_id
        LEFT JOIN campaigns c ON ca.campaign_id = c.id
        ORDER BY a.name, c.name
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group results by advertiser
    $advertiser_campaigns = [];
    foreach ($results as $row) {
        $advertiser_id = $row['advertiser_id'];
        if (!isset($advertiser_campaigns[$advertiser_id])) {
            $advertiser_campaigns[$advertiser_id] = [
                'id' => $row['advertiser_id'],
                'name' => $row['advertiser_name'],
                'email' => $row['advertiser_email'],
                'campaigns' => []
            ];
        }
        
        if ($row['campaign_id']) {
            $advertiser_campaigns[$advertiser_id]['campaigns'][] = [
                'id' => $row['campaign_id'],
                'name' => $row['campaign_name'],
                'shortcode' => $row['shortcode'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'type' => $row['campaign_type'],
                'clicks' => $row['click_count'],
                'status' => $row['status']
            ];
        }
    }
    
} catch (PDOException $e) {
    $error = "Error loading advertiser campaigns: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Advertiser Campaigns - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Ads Platform</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Super Admin)</span>
                <a class="nav-link btn btn-outline-light" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Navigation</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">Home Dashboard</a>
                        <a href="campaigns.php" class="list-group-item list-group-item-action">Campaigns</a>
                        <a href="advertisers.php" class="list-group-item list-group-item-action">Advertisers</a>
                        <a href="publishers.php" class="list-group-item list-group-item-action">Publishers</a>
                        <a href="admins.php" class="list-group-item list-group-item-action">Admins</a>
                        <a href="advertiser_campaigns.php" class="list-group-item list-group-item-action active">View Advertiser Campaigns</a>
                        <a href="publisher_campaigns.php" class="list-group-item list-group-item-action">View Publisher Campaigns</a>
                        <a href="payment_reports.php" class="list-group-item list-group-item-action">Payment Reports</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2>View Advertiser Campaigns</h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (empty($advertiser_campaigns)): ?>
                    <div class="alert alert-info">No advertisers or campaigns found.</div>
                <?php else: ?>
                    <?php foreach ($advertiser_campaigns as $advertiser): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><?php echo htmlspecialchars($advertiser['name']); ?></h5>
                                <p class="mb-0"><?php echo htmlspecialchars($advertiser['email']); ?></p>
                            </div>
                            <div class="card-body">
                                <?php if (empty($advertiser['campaigns'])): ?>
                                    <p class="text-muted">No campaigns assigned to this advertiser.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Campaign Name</th>
                                                    <th>Short Code</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Type</th>
                                                    <th>Clicks</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($advertiser['campaigns'] as $campaign): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($campaign['shortcode']); ?></td>
                                                        <td><?php echo htmlspecialchars($campaign['start_date']); ?></td>
                                                        <td><?php echo htmlspecialchars($campaign['end_date']); ?></td>
                                                        <td><?php echo htmlspecialchars($campaign['type']); ?></td>
                                                        <td><?php echo $campaign['clicks']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                                <?php echo ucfirst($campaign['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>