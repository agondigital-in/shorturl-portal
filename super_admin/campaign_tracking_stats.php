<?php
// super_admin/campaign_tracking_stats.php - Campaign Tracking Statistics
$page_title = 'Campaign Stats';
require_once 'includes/header.php';
require_once '../db_connection.php';

$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$base_url = $is_localhost ? 'http://localhost/shorturl/c/' : 'https://tracking.agondigital.in/c/';

$campaign_id = $_GET['id'] ?? '';

if (empty($campaign_id)) {
    header('Location: campaigns.php');
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT c.*, GROUP_CONCAT(DISTINCT a.name) as advertiser_names
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN advertisers a ON ca.advertiser_id = a.id
        WHERE c.id = ? GROUP BY c.id");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        header('Location: campaigns.php');
        exit();
    }
    
    $stmt = $conn->prepare("SELECT p.name as publisher_name, psc.short_code, COALESCE(psc.clicks, 0) as clicks
        FROM publishers p
        JOIN campaign_publishers cp ON p.id = cp.publisher_id
        JOIN publisher_short_codes psc ON cp.campaign_id = psc.campaign_id AND cp.publisher_id = psc.publisher_id
        WHERE cp.campaign_id = ? ORDER BY p.name");
    $stmt->execute([$campaign_id]);
    $publisher_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading campaign data: " . $e->getMessage();
}

$total_clicks = array_sum(array_column($publisher_stats, 'clicks'));
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Campaign Statistics</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="campaigns.php">Campaigns</a></li>
                <li class="breadcrumb-item active">Stats</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="publisher_daily_clicks.php?id=<?php echo $campaign_id; ?>" class="btn btn-soft-info me-2"><i class="fas fa-calendar-alt me-2"></i>Daily Clicks</a>
        <a href="campaigns.php" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Campaign Details -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i><?php echo htmlspecialchars($campaign['name']); ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2"><strong>Base Short Code:</strong> <code><?php echo htmlspecialchars($campaign['shortcode']); ?></code></p>
                <p class="mb-2"><strong>Advertisers:</strong> <?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?></p>
                <p class="mb-0"><strong>Duration:</strong> <?php echo date('M d, Y', strtotime($campaign['start_date'])); ?> - <?php echo date('M d, Y', strtotime($campaign['end_date'])); ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><strong>Campaign Type:</strong> <span class="badge badge-soft-primary"><?php echo htmlspecialchars($campaign['campaign_type']); ?></span></p>
                <p class="mb-0"><strong>Target URL:</strong> <a href="<?php echo htmlspecialchars($campaign['target_url']); ?>" target="_blank"><?php echo htmlspecialchars(substr($campaign['target_url'], 0, 50)); ?>...</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-users"></i></div>
            <div class="stat-card-value"><?php echo count($publisher_stats); ?></div>
            <div class="stat-card-label">Publishers</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_clicks); ?></div>
            <div class="stat-card-label">Total Clicks</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-card-value"><?php echo count($publisher_stats) > 0 ? number_format($total_clicks / count($publisher_stats), 1) : 0; ?></div>
            <div class="stat-card-label">Avg Per Publisher</div>
        </div>
    </div>
</div>

<!-- Publisher Stats Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Publisher Tracking Statistics</h5>
        <span class="badge bg-primary"><?php echo count($publisher_stats); ?> Publishers</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($publisher_stats)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">No publishers assigned to this campaign.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Publisher</th>
                            <th>Short Code</th>
                            <th>Tracking Link</th>
                            <th class="text-end">Clicks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($publisher_stats as $stats): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width:32px;height:32px;font-size:12px;">
                                            <?php echo strtoupper(substr($stats['publisher_name'], 0, 1)); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($stats['publisher_name']); ?></strong>
                                    </div>
                                </td>
                                <td><code><?php echo htmlspecialchars($stats['short_code']); ?></code></td>
                                <td>
                                    <code class="small"><?php echo $base_url . htmlspecialchars($stats['short_code']); ?></code>
                                    <button class="btn btn-sm btn-link p-0 ms-2" onclick="navigator.clipboard.writeText('<?php echo $base_url . htmlspecialchars($stats['short_code']); ?>'); alert('Copied!');">
                                        <i class="fas fa-copy text-primary"></i>
                                    </button>
                                </td>
                                <td class="text-end"><strong class="text-primary"><?php echo number_format($stats['clicks']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total Clicks</strong></td>
                            <td class="text-end"><strong class="text-primary"><?php echo number_format($total_clicks); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
