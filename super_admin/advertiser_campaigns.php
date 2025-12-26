<?php
// super_admin/advertiser_campaigns.php - View Advertiser Campaigns
$page_title = 'Advertiser Campaigns';
require_once 'includes/header.php';
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

$total_advertisers = count($advertiser_campaigns);
$total_campaigns = 0;
$total_clicks = 0;
foreach ($advertiser_campaigns as $adv) {
    $total_campaigns += count($adv['campaigns']);
    foreach ($adv['campaigns'] as $c) {
        $total_clicks += $c['clicks'];
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Advertiser Campaigns</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Advertiser Campaigns</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-building"></i></div>
            <div class="stat-card-value"><?php echo $total_advertisers; ?></div>
            <div class="stat-card-label">Total Advertisers</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="stat-card-value"><?php echo $total_campaigns; ?></div>
            <div class="stat-card-label">Total Campaigns</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_clicks); ?></div>
            <div class="stat-card-label">Total Clicks</div>
        </div>
    </div>
</div>

<?php if (empty($advertiser_campaigns)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-building fa-3x text-muted mb-3"></i>
            <p class="text-muted">No advertisers or campaigns found.</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($advertiser_campaigns as $advertiser): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="user-avatar me-3" style="width:45px;height:45px;">
                        <?php echo strtoupper(substr($advertiser['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($advertiser['name']); ?></h5>
                        <small class="text-muted"><?php echo htmlspecialchars($advertiser['email']); ?></small>
                    </div>
                </div>
                <span class="badge bg-primary"><?php echo count($advertiser['campaigns']); ?> Campaigns</span>
            </div>
            <div class="card-body">
                <?php if (empty($advertiser['campaigns'])): ?>
                    <p class="text-muted text-center py-3"><i class="fas fa-info-circle me-2"></i>No campaigns assigned</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Short Code</th>
                                    <th>Duration</th>
                                    <th>Type</th>
                                    <th>Clicks</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($advertiser['campaigns'] as $campaign): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($campaign['name']); ?></strong></td>
                                        <td><code><?php echo htmlspecialchars($campaign['shortcode']); ?></code></td>
                                        <td>
                                            <small><?php echo date('M d', strtotime($campaign['start_date'])); ?> - <?php echo date('M d, Y', strtotime($campaign['end_date'])); ?></small>
                                        </td>
                                        <td><span class="badge badge-soft-primary"><?php echo htmlspecialchars($campaign['type']); ?></span></td>
                                        <td><strong class="text-primary"><?php echo number_format($campaign['clicks']); ?></strong></td>
                                        <td>
                                            <?php if ($campaign['status'] === 'active'): ?>
                                                <span class="badge badge-soft-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-soft-warning"><?php echo ucfirst($campaign['status']); ?></span>
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
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
