<?php
// super_admin/dashboard.php - Super Admin Dashboard
$page_title = 'Dashboard';
require_once 'includes/header.php';
require_once '../db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get counts for dashboard overview
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM campaigns");
    $stmt->execute();
    $campaigns_count = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM advertisers");
    $stmt->execute();
    $advertisers_count = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM publishers");
    $stmt->execute();
    $publishers_count = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'super_admin')");
    $stmt->execute();
    $admins_count = $stmt->fetch()['count'];
    
    // Get total clicks
    $stmt = $conn->prepare("SELECT SUM(click_count) as total FROM campaigns");
    $stmt->execute();
    $total_clicks = $stmt->fetch()['total'] ?? 0;
    
    // Get active campaigns count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM campaigns WHERE status = 'active'");
    $stmt->execute();
    $active_campaigns = $stmt->fetch()['count'];
    
    // Get recent campaigns
    $stmt = $conn->prepare("SELECT * FROM campaigns ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get campaign status distribution
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM campaigns GROUP BY status");
    $stmt->execute();
    $campaign_status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent campaign clicks (last 7 days)
    $stmt = $conn->prepare("SELECT DATE(created_at) as date, SUM(click_count) as clicks FROM campaigns WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date");
    $stmt->execute();
    $clicks_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get campaign type distribution
    $stmt = $conn->prepare("SELECT campaign_type, COUNT(*) as count FROM campaigns GROUP BY campaign_type");
    $stmt->execute();
    $campaign_type_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="add_campaign.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>New Campaign
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-2">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="stat-card-value"><?php echo $campaigns_count; ?></div>
            <div class="stat-card-label">Campaigns</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-card-value"><?php echo $active_campaigns; ?></div>
            <div class="stat-card-label">Active</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-building"></i></div>
            <div class="stat-card-value"><?php echo $advertisers_count; ?></div>
            <div class="stat-card-label">Advertisers</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card warning">
            <div class="stat-card-icon"><i class="fas fa-globe"></i></div>
            <div class="stat-card-value"><?php echo $publishers_count; ?></div>
            <div class="stat-card-label">Publishers</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card danger">
            <div class="stat-card-icon"><i class="fas fa-user-shield"></i></div>
            <div class="stat-card-value"><?php echo $admins_count; ?></div>
            <div class="stat-card-label">Admins</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_clicks); ?></div>
            <div class="stat-card-label">Total Clicks</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Campaign Status</h5>
            </div>
            <div class="card-body">
                <canvas id="campaignStatusChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Campaign Types</h5>
            </div>
            <div class="card-body">
                <canvas id="campaignTypeChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Clicks Chart -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Recent Clicks (Last 7 Days)</h5>
    </div>
    <div class="card-body">
        <canvas id="clicksChart" height="100"></canvas>
    </div>
</div>

<!-- Recent Campaigns -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Campaigns</h5>
        <a href="campaigns.php" class="btn btn-soft-primary btn-sm">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($recent_campaigns)): ?>
            <div class="text-center py-4">
                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                <p class="text-muted">No campaigns found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Short Code</th>
                            <th>Status</th>
                            <th>Clicks</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_campaigns as $campaign): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($campaign['name']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($campaign['shortcode']); ?></code></td>
                                <td>
                                    <span class="badge <?php echo $campaign['status'] === 'active' ? 'badge-soft-success' : 'badge-soft-warning'; ?>">
                                        <?php echo ucfirst($campaign['status']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo number_format($campaign['click_count']); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($campaign['start_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($campaign['end_date'])); ?></td>
                                <td>
                                    <a href="edit_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-soft-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="campaign_tracking_stats.php?id=<?php echo $campaign['id']; ?>" class="btn btn-soft-info btn-sm">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Campaign Status Chart
    var ctx1 = document.getElementById('campaignStatusChart').getContext('2d');
    new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: [<?php echo implode(',', array_map(function($item) { return "'" . ucfirst($item['status']) . "'"; }, $campaign_status_data)); ?>],
            datasets: [{
                data: [<?php echo implode(',', array_column($campaign_status_data, 'count')); ?>],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#6366f1'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    
    // Campaign Type Chart
    var ctx2 = document.getElementById('campaignTypeChart').getContext('2d');
    new Chart(ctx2, {
        type: 'pie',
        data: {
            labels: [<?php echo implode(',', array_map(function($item) { return "'" . ($item['campaign_type'] === 'None' ? 'Not Set' : $item['campaign_type']) . "'"; }, $campaign_type_data)); ?>],
            datasets: [{
                data: [<?php echo implode(',', array_column($campaign_type_data, 'count')); ?>],
                backgroundColor: ['#667eea', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    
    // Clicks Chart
    var ctx3 = document.getElementById('clicksChart').getContext('2d');
    new Chart(ctx3, {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M d', strtotime($item['date'])) . "'"; }, $clicks_data)); ?>],
            datasets: [{
                label: 'Total Clicks',
                data: [<?php echo implode(',', array_column($clicks_data, 'clicks')); ?>],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
