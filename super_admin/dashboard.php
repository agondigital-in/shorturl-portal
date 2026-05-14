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
/* Dashboard Header */
.dash-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 32px; 
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f5f9;
}
.dash-header h1 { 
    font-size: 2rem; 
    font-weight: 800; 
    color: #0f172a; 
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}
.dash-header h1 i { 
    color: #6366f1; 
    font-size: 1.8rem;
    animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
.btn-new { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff; 
    padding: 12px 24px; 
    border-radius: 10px; 
    text-decoration: none; 
    font-weight: 600; 
    font-size: 0.95rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-new:hover { 
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}
.btn-new i { font-size: 1rem; }

/* Stats Grid */
.stats-grid { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 20px; 
    margin-bottom: 32px; 
}
.stat-box { 
    background: #fff; 
    border-radius: 16px; 
    padding: 24px; 
    border: 2px solid #f1f5f9; 
    text-align: center; 
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.stat-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--box-color-1), var(--box-color-2));
    transform: scaleX(0);
    transition: transform 0.3s ease;
}
.stat-box:hover::before { transform: scaleX(1); }
.stat-box:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    border-color: var(--box-color-1);
}
.stat-box:nth-child(1) { --box-color-1: #8b5cf6; --box-color-2: #6366f1; }
.stat-box:nth-child(2) { --box-color-1: #10b981; --box-color-2: #059669; }
.stat-box:nth-child(3) { --box-color-1: #3b82f6; --box-color-2: #2563eb; }
.stat-box:nth-child(4) { --box-color-1: #f59e0b; --box-color-2: #d97706; }
.stat-box:nth-child(5) { --box-color-1: #ef4444; --box-color-2: #dc2626; }
.stat-box:nth-child(6) { --box-color-1: #06b6d4; --box-color-2: #0891b2; }

.stat-icon { 
    width: 64px; 
    height: 64px; 
    border-radius: 16px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    margin: 0 auto 16px; 
    font-size: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
}
.stat-box:hover .stat-icon {
    transform: scale(1.1) rotate(5deg);
}
.stat-icon.purple { 
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    color: #7c3aed;
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.2);
}
.stat-icon.green { 
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #16a34a;
    box-shadow: 0 4px 15px rgba(22, 163, 74, 0.2);
}
.stat-icon.blue { 
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
}
.stat-icon.orange { 
    background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
    color: #ea580c;
    box-shadow: 0 4px 15px rgba(234, 88, 12, 0.2);
}
.stat-icon.red { 
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #dc2626;
    box-shadow: 0 4px 15px rgba(220, 38, 38, 0.2);
}
.stat-icon.cyan { 
    background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);
    color: #0891b2;
    box-shadow: 0 4px 15px rgba(8, 145, 178, 0.2);
}
.stat-box h3 { 
    font-size: 2rem; 
    font-weight: 800; 
    color: #0f172a; 
    margin: 8px 0 0 0;
    line-height: 1;
}
.stat-box span { 
    font-size: 0.85rem; 
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 4px;
}

/* Charts Section */
.charts-row { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 24px; 
    margin-bottom: 32px; 
}
.chart-card { 
    background: #fff; 
    border-radius: 16px; 
    border: 2px solid #f1f5f9; 
    padding: 24px; 
    height: 320px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.chart-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}
.chart-card:hover::before {
    transform: scaleX(1);
}
.chart-card:hover {
    border-color: #e2e8f0;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}
.chart-card h5 { 
    font-size: 1.1rem; 
    font-weight: 700; 
    color: #0f172a; 
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.chart-card h5 i { 
    color: #6366f1;
    font-size: 1.2rem;
}
.chart-container { 
    height: 240px; 
    position: relative;
    touch-action: pan-y;
}

/* Recent Campaigns Card */
.recent-card { 
    background: #fff; 
    border-radius: 16px; 
    border: 2px solid #f1f5f9;
    overflow: hidden;
    transition: all 0.3s ease;
}
.recent-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.recent-header { 
    padding: 20px 24px; 
    border-bottom: 2px solid #f1f5f9; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}
.recent-header h5 { 
    margin: 0; 
    font-size: 1.1rem; 
    font-weight: 700; 
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}
.recent-header h5 i { 
    color: #6366f1;
    font-size: 1.2rem;
}
.btn-view { 
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    color: #7c3aed; 
    padding: 8px 18px; 
    border-radius: 8px; 
    text-decoration: none; 
    font-size: 0.85rem; 
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-view:hover { 
    background: #7c3aed; 
    color: #fff;
    transform: translateX(3px);
}

/* Recent Table */
.recent-table { 
    width: 100%; 
    border-collapse: collapse; 
}
.recent-table th { 
    padding: 16px 20px; 
    text-align: left; 
    font-size: 0.8rem; 
    font-weight: 700; 
    color: #64748b; 
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #f8fafc;
}
.recent-table td { 
    padding: 16px 20px; 
    border-bottom: 1px solid #f1f5f9; 
    font-size: 0.9rem;
}
.recent-table tr:last-child td { border-bottom: none; }
.recent-table tr { transition: all 0.2s ease; }
.recent-table tr:hover { 
    background: #f8fafc;
    transform: scale(1.01);
}
.camp-name { 
    font-weight: 700; 
    color: #0f172a;
    font-size: 0.95rem;
}
.badge-active { 
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #16a34a; 
    padding: 6px 14px; 
    border-radius: 8px; 
    font-size: 0.75rem; 
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}
.badge-inactive { 
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706; 
    padding: 6px 14px; 
    border-radius: 8px; 
    font-size: 0.75rem; 
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}
.action-btns { display: flex; gap: 6px; }
.action-btns a { 
    width: 32px; 
    height: 32px; 
    border-radius: 8px; 
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 0.85rem; 
    text-decoration: none;
    transition: all 0.3s ease;
}
.btn-edit { 
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
}
.btn-edit:hover {
    background: #d97706;
    color: #fff;
    transform: scale(1.1);
}
.btn-stats { 
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
}
.btn-stats:hover {
    background: #2563eb;
    color: #fff;
    transform: scale(1.1);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 16px;
    opacity: 0.5;
}
.empty-state p {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

/* Responsive Design */
@media(max-width:1200px) { 
    .stats-grid { grid-template-columns: repeat(3, 1fr); } 
}

/* Tablet Portrait */
@media(max-width:768px) {
    .dash-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 24px;
        padding-bottom: 16px;
    }
    .dash-header h1 {
        font-size: 1.5rem;
    }
    .dash-header h1 i {
        font-size: 1.4rem;
    }
    .btn-new {
        width: 100%;
        justify-content: center;
    }
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-box {
        padding: 20px;
    }
    .stat-icon {
        width: 52px;
        height: 52px;
        font-size: 1.3rem;
        margin-bottom: 14px;
    }
    .stat-box h3 {
        font-size: 1.6rem;
    }
    .stat-box span {
        font-size: 0.8rem;
    }
    .charts-row {
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    .chart-card {
        height: 340px;
        padding: 20px;
    }
    .chart-card h5 {
        font-size: 1rem;
        margin-bottom: 16px;
    }
    .chart-container {
        height: 260px;
    }
    .recent-header {
        padding: 14px 18px;
    }
    .recent-header h5 {
        font-size: 0.9rem;
    }
    .recent-table th {
        padding: 10px 14px;
        font-size: 0.7rem;
    }
    .recent-table td {
        padding: 10px 14px;
        font-size: 0.8rem;
    }
}

/* Mobile Landscape & Small Tablets */
@media(max-width:640px) {
    .dash-header {
        margin-bottom: 20px;
        padding-bottom: 14px;
    }
    .dash-header h1 {
        font-size: 1.4rem;
    }
    .btn-new {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
    .stats-grid {
        gap: 14px;
        margin-bottom: 20px;
    }
    .stat-box {
        padding: 18px;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        font-size: 1.2rem;
        margin-bottom: 12px;
    }
    .stat-box h3 {
        font-size: 1.5rem;
    }
    .stat-box span {
        font-size: 0.75rem;
    }
    .charts-row {
        gap: 14px;
        margin-bottom: 20px;
    }
    .chart-card {
        height: 320px;
        padding: 18px;
    }
    .chart-card h5 {
        font-size: 0.95rem;
        margin-bottom: 14px;
    }
    .chart-container {
        height: 240px;
    }
}

/* Mobile Portrait */
@media(max-width:576px) {
    .dash-header {
        margin-bottom: 18px;
        padding-bottom: 12px;
    }
    .dash-header h1 {
        font-size: 1.3rem;
        gap: 10px;
    }
    .dash-header h1 i {
        font-size: 1.2rem;
    }
    .btn-new {
        padding: 10px 18px;
        font-size: 0.85rem;
        gap: 6px;
    }
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
        margin-bottom: 18px;
    }
    .stat-box {
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
        text-align: left;
    }
    .stat-box::before {
        height: 3px;
    }
    .stat-icon {
        width: 56px;
        height: 56px;
        font-size: 1.3rem;
        margin: 0;
        flex-shrink: 0;
    }
    .stat-box h3 {
        font-size: 1.6rem;
        margin: 0 0 4px 0;
    }
    .stat-box span {
        font-size: 0.8rem;
        display: block;
    }
    .charts-row {
        gap: 12px;
        margin-bottom: 18px;
    }
    .chart-card {
        height: 300px;
        padding: 16px;
    }
    .chart-card h5 {
        font-size: 0.9rem;
        margin-bottom: 12px;
        gap: 8px;
    }
    .chart-card h5 i {
        font-size: 1rem;
    }
    .chart-container {
        height: 220px;
    }
    .recent-card {
        border-radius: 12px;
    }
    .recent-header {
        padding: 12px 16px;
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    .recent-header h5 {
        font-size: 0.85rem;
        gap: 8px;
    }
    .recent-header h5 i {
        font-size: 1rem;
    }
    .btn-view {
        padding: 6px 14px;
        font-size: 0.8rem;
        gap: 4px;
    }
    .recent-table {
        display: block;
        overflow-x: auto;
    }
    .recent-table thead {
        display: none;
    }
    .recent-table tbody {
        display: block;
    }
    .recent-table tr {
        display: block;
        margin-bottom: 12px;
        border: 1px solid #f1f5f9;
        border-radius: 8px;
        padding: 12px;
        background: #fff;
    }
    .recent-table tr:hover {
        background: #f8fafc;
    }
    .recent-table td {
        display: block;
        padding: 6px 0;
        border: none;
        text-align: left;
    }
    .recent-table td::before {
        content: attr(data-label);
        font-weight: 700;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        display: block;
        margin-bottom: 4px;
    }
    .camp-name {
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    .action-btns {
        margin-top: 8px;
        gap: 8px;
    }
    .action-btns a {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    .empty-state {
        padding: 40px 16px;
    }
    .empty-state i {
        font-size: 2.5rem;
    }
    .empty-state p {
        font-size: 0.95rem;
    }
}

/* Extra Small Mobile */
@media(max-width:400px) {
    .dash-header h1 {
        font-size: 1.2rem;
    }
    .btn-new {
        padding: 9px 16px;
        font-size: 0.8rem;
    }
    .stats-grid {
        gap: 10px;
        margin-bottom: 16px;
    }
    .stat-box {
        padding: 14px;
        gap: 14px;
    }
    .stat-icon {
        width: 52px;
        height: 52px;
        font-size: 1.2rem;
    }
    .stat-box h3 {
        font-size: 1.5rem;
    }
    .stat-box span {
        font-size: 0.75rem;
    }
    .charts-row {
        gap: 10px;
        margin-bottom: 16px;
    }
    .chart-card {
        height: 280px;
        padding: 14px;
    }
    .chart-card h5 {
        font-size: 0.85rem;
        margin-bottom: 10px;
    }
    .chart-container {
        height: 200px;
    }
    .recent-header {
        padding: 10px 14px;
    }
    .recent-table tr {
        padding: 10px;
        margin-bottom: 10px;
    }
}

/* Landscape Orientation for Phones */
@media(max-height: 600px) and (orientation: landscape) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 16px;
    }
    .stat-box {
        padding: 12px;
    }
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
        margin-bottom: 8px;
    }
    .stat-box h3 {
        font-size: 1.3rem;
    }
    .stat-box span {
        font-size: 0.7rem;
    }
    .charts-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .chart-card {
        height: 240px;
        padding: 12px;
    }
    .chart-card h5 {
        font-size: 0.85rem;
        margin-bottom: 8px;
    }
    .chart-container {
        height: 170px;
    }
}

/* Very Small Landscape Screens */
@media(max-height: 500px) and (orientation: landscape) {
    .charts-row {
        grid-template-columns: repeat(2, 1fr);
    }
    .chart-card {
        height: 200px;
        padding: 10px;
    }
    .chart-card h5 {
        font-size: 0.8rem;
        margin-bottom: 6px;
    }
    .chart-container {
        height: 140px;
    }
}

/* iPad and Tablet Specific */
@media(min-width: 768px) and (max-width: 1024px) {
    .charts-row {
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .chart-card {
        height: 360px;
        padding: 22px;
    }
    .chart-container {
        height: 280px;
    }
}

/* Large Tablets in Portrait */
@media(min-width: 768px) and (max-width: 991px) and (orientation: portrait) {
    .charts-row {
        grid-template-columns: 1fr;
        gap: 18px;
    }
    .chart-card {
        height: 380px;
    }
    .chart-container {
        height: 300px;
    }
}
</style>

<div class="dash-header">
    <h1><i class="fas fa-chart-line"></i>Super Admin Dashboard</h1>
    <a href="add_campaign.php" class="btn-new">
        <i class="fas fa-plus-circle"></i>
        <span>Create Campaign</span>
    </a>
</div>

<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-icon purple"><i class="fas fa-rocket"></i></div>
        <span>Total Campaigns</span>
        <h3><?php echo $campaigns_count; ?></h3>
    </div>
    <div class="stat-box">
        <div class="stat-icon green"><i class="fas fa-check-double"></i></div>
        <span>Active Now</span>
        <h3><?php echo $active_campaigns; ?></h3>
    </div>
    <div class="stat-box">
        <div class="stat-icon blue"><i class="fas fa-briefcase"></i></div>
        <span>Advertisers</span>
        <h3><?php echo $advertisers_count; ?></h3>
    </div>
    <div class="stat-box">
        <div class="stat-icon orange"><i class="fas fa-users"></i></div>
        <span>Publishers</span>
        <h3><?php echo $publishers_count; ?></h3>
    </div>
    <div class="stat-box">
        <div class="stat-icon red"><i class="fas fa-user-cog"></i></div>
        <span>Admin Users</span>
        <h3><?php echo $admins_count; ?></h3>
    </div>
    <div class="stat-box">
        <div class="stat-icon cyan"><i class="fas fa-hand-pointer"></i></div>
        <span>Total Clicks</span>
        <h3><?php echo number_format($total_clicks); ?></h3>
    </div>
</div>

<div class="charts-row">
    <div class="chart-card">
        <h5><i class="fas fa-chart-pie"></i>Campaign Status Distribution</h5>
        <div class="chart-container">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h5><i class="fas fa-layer-group"></i>Campaign Types Overview</h5>
        <div class="chart-container">
            <canvas id="typeChart"></canvas>
        </div>
    </div>
</div>

<div class="recent-card">
    <div class="recent-header">
        <h5><i class="fas fa-history"></i>Recent Campaigns</h5>
        <a href="campaigns.php" class="btn-view">
            <span>View All</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <?php if (empty($recent_campaigns)): ?>
    <div class="empty-state">
        <i class="fas fa-rocket"></i>
        <p>No campaigns created yet. Start by creating your first campaign!</p>
    </div>
    <?php else: ?>
    <table class="recent-table">
        <thead>
            <tr>
                <th><i class="fas fa-tag"></i> Campaign Name</th>
                <th><i class="fas fa-signal"></i> Status</th>
                <th><i class="fas fa-mouse-pointer"></i> Clicks</th>
                <th><i class="fas fa-cog"></i> Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_campaigns as $c): ?>
            <tr>
                <td class="camp-name" data-label="Campaign"><?php echo htmlspecialchars($c['name']); ?></td>
                <td data-label="Status"><span class="<?php echo $c['status']==='active'?'badge-active':'badge-inactive'; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                <td data-label="Clicks"><strong><?php echo number_format($c['click_count']); ?></strong></td>
                <td data-label="Actions">
                    <div class="action-btns">
                        <a href="edit_campaign.php?id=<?php echo $c['id']; ?>" class="btn-edit" title="Edit Campaign"><i class="fas fa-edit"></i></a>
                        <a href="campaign_tracking_stats.php?id=<?php echo $c['id']; ?>" class="btn-stats" title="View Statistics"><i class="fas fa-chart-bar"></i></a>
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
    // Responsive chart configuration
    const isMobile = window.innerWidth <= 576;
    const isTablet = window.innerWidth > 576 && window.innerWidth <= 768;
    
    // Status Chart
    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: [<?php echo implode(',', array_map(fn($i) => "'".ucfirst($i['status'])."'", $campaign_status_data)); ?>],
            datasets: [{ 
                data: [<?php echo implode(',', array_column($campaign_status_data, 'count')); ?>], 
                backgroundColor: ['#10b981','#f59e0b','#ef4444','#6366f1'], 
                borderWidth: 0,
                hoverOffset: isMobile ? 8 : 15
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    position: 'bottom',
                    labels: {
                        padding: isMobile ? 10 : 15,
                        font: {
                            size: isMobile ? 10 : (isTablet ? 11 : 12),
                            family: 'Inter'
                        },
                        boxWidth: isMobile ? 12 : 15,
                        boxHeight: isMobile ? 12 : 15
                    }
                },
                tooltip: {
                    padding: isMobile ? 8 : 12,
                    titleFont: {
                        size: isMobile ? 11 : 13
                    },
                    bodyFont: {
                        size: isMobile ? 10 : 12
                    }
                }
            },
            cutout: isMobile ? '55%' : '60%'
        }
    });
    
    // Type Chart
    new Chart(document.getElementById('typeChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: [<?php echo implode(',', array_map(fn($i) => "'".($i['campaign_type']?:'Not Set')."'", $campaign_type_data)); ?>],
            datasets: [{ 
                data: [<?php echo implode(',', array_column($campaign_type_data, 'count')); ?>], 
                backgroundColor: ['#667eea','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'], 
                borderWidth: 0,
                hoverOffset: isMobile ? 8 : 15
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    position: 'bottom',
                    labels: {
                        padding: isMobile ? 10 : 15,
                        font: {
                            size: isMobile ? 10 : (isTablet ? 11 : 12),
                            family: 'Inter'
                        },
                        boxWidth: isMobile ? 12 : 15,
                        boxHeight: isMobile ? 12 : 15
                    }
                },
                tooltip: {
                    padding: isMobile ? 8 : 12,
                    titleFont: {
                        size: isMobile ? 11 : 13
                    },
                    bodyFont: {
                        size: isMobile ? 10 : 12
                    }
                }
            }
        }
    });
    
    // Re-render charts on window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            location.reload();
        }, 500);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

