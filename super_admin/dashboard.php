<?php
// super_admin/dashboard.php - Super Admin Dashboard
$page_title = 'Dashboard';
require_once 'includes/header.php';
require_once '../db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
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
    
    $stmt = $conn->prepare("SELECT SUM(click_count) as total FROM campaigns");
    $stmt->execute();
    $total_clicks = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM campaigns WHERE status = 'active'");
    $stmt->execute();
    $active_campaigns = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT * FROM campaigns ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM campaigns GROUP BY status");
    $stmt->execute();
    $campaign_status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT campaign_type, COUNT(*) as count FROM campaigns GROUP BY campaign_type");
    $stmt->execute();
    $campaign_type_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading data";
}
?>

<style>
.dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.dash-header h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
.dash-header h1 i { color: #6366f1; }
.btn-new { background: #6366f1; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
.btn-new:hover { background: #4f46e5; color: #fff; }

.stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-box { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; text-align: center; transition: 0.3s; }
.stat-box:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 1.2rem; }
.stat-icon.purple { background: #ede9fe; color: #7c3aed; }
.stat-icon.green { background: #dcfce7; color: #16a34a; }
.stat-icon.blue { background: #dbeafe; color: #2563eb; }
.stat-icon.orange { background: #ffedd5; color: #ea580c; }
.stat-icon.red { background: #fee2e2; color: #dc2626; }
.stat-icon.cyan { background: #cffafe; color: #0891b2; }
.stat-box h3 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; }
.stat-box span { font-size: 0.8rem; color: #64748b; }

.charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
.chart-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; height: 280px; }
.chart-card h5 { font-size: 0.95rem; font-weight: 600; color: #1e293b; margin: 0 0 16px 0; }
.chart-card h5 i { color: #6366f1; }
.chart-container { height: 200px; position: relative; }

.recent-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; }
.recent-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.recent-header h5 { margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b; }
.recent-header h5 i { color: #6366f1; }
.btn-view { background: #ede9fe; color: #7c3aed; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: 500; }
.btn-view:hover { background: #7c3aed; color: #fff; }

.recent-table { width: 100%; border-collapse: collapse; }
.recent-table th { padding: 12px 16px; text-align: left; font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; background: #f8fafc; }
.recent-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; }
.recent-table tr:hover { background: #f8fafc; }
.camp-name { font-weight: 600; color: #1e293b; }
.badge-active { background: #dcfce7; color: #16a34a; padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
.badge-inactive { background: #fef3c7; color: #d97706; padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
.action-btns a { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; text-decoration: none; margin-right: 4px; }
.btn-edit { background: #fef3c7; color: #d97706; }
.btn-stats { background: #dbeafe; color: #2563eb; }

@media(max-width:1200px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media(max-width:768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .charts-row { grid-template-columns: 1fr; } }
</style>

<div class="dash-header">
    <h1><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
    <a href="add_campaign.php" class="btn-new"><i class="fas fa-plus me-1"></i> New Campaign</a>
</div>

<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-icon purple"><i class="fas fa-bullhorn"></i></div>
        <h3><?php echo $campaigns_count; ?></h3>
        <span>Campaigns</span>
    </div>
    <div class="stat-box">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <h3><?php echo $active_campaigns; ?></h3>
        <span>Active</span>
    </div>
    <div class="stat-box">
        <div class="stat-icon blue"><i class="fas fa-building"></i></div>
        <h3><?php echo $advertisers_count; ?></h3>
        <span>Advertisers</span>
    </div>
    <div class="stat-box">
        <div class="stat-icon orange"><i class="fas fa-globe"></i></div>
        <h3><?php echo $publishers_count; ?></h3>
        <span>Publishers</span>
    </div>
    <div class="stat-box">
        <div class="stat-icon red"><i class="fas fa-user-shield"></i></div>
        <h3><?php echo $admins_count; ?></h3>
        <span>Admins</span>
    </div>
    <div class="stat-box">
        <div class="stat-icon cyan"><i class="fas fa-mouse-pointer"></i></div>
        <h3><?php echo number_format($total_clicks); ?></h3>
        <span>Total Clicks</span>
    </div>
</div>

<div class="charts-row">
    <div class="chart-card">
        <h5><i class="fas fa-chart-pie me-2"></i>Campaign Status</h5>
        <div class="chart-container">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h5><i class="fas fa-chart-pie me-2"></i>Campaign Types</h5>
        <div class="chart-container">
            <canvas id="typeChart"></canvas>
        </div>
    </div>
</div>

<div class="recent-card">
    <div class="recent-header">
        <h5><i class="fas fa-clock me-2"></i>Recent Campaigns</h5>
        <a href="campaigns.php" class="btn-view">View All</a>
    </div>
    <?php if (empty($recent_campaigns)): ?>
    <div style="text-align:center;padding:40px;color:#94a3b8;">
        <i class="fas fa-bullhorn" style="font-size:2rem;margin-bottom:10px;"></i>
        <p>No campaigns yet</p>
    </div>
    <?php else: ?>
    <table class="recent-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th>Clicks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_campaigns as $c): ?>
            <tr>
                <td class="camp-name"><?php echo htmlspecialchars($c['name']); ?></td>
                <td><span class="<?php echo $c['status']==='active'?'badge-active':'badge-inactive'; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                <td><strong><?php echo number_format($c['click_count']); ?></strong></td>
                <td>
                    <div class="action-btns">
                        <a href="edit_campaign.php?id=<?php echo $c['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                        <a href="campaign_tracking_stats.php?id=<?php echo $c['id']; ?>" class="btn-stats"><i class="fas fa-chart-line"></i></a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: [<?php echo implode(',', array_map(fn($i) => "'".ucfirst($i['status'])."'", $campaign_status_data)); ?>],
            datasets: [{ data: [<?php echo implode(',', array_column($campaign_status_data, 'count')); ?>], backgroundColor: ['#10b981','#f59e0b','#ef4444','#6366f1'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
    
    new Chart(document.getElementById('typeChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: [<?php echo implode(',', array_map(fn($i) => "'".($i['campaign_type']?:'Not Set')."'", $campaign_type_data)); ?>],
            datasets: [{ data: [<?php echo implode(',', array_column($campaign_type_data, 'count')); ?>], backgroundColor: ['#667eea','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
