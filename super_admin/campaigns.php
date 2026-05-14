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
            $success = "Status updated!";
        } catch (PDOException $e) { $error = "Error updating status."; }
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
            $success = "Campaign deleted!";
        } catch (PDOException $e) { $error = "Error deleting."; }
    }
}

// Get campaigns
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("
        SELECT c.*, c.enable_image_pixel,
               GROUP_CONCAT(DISTINCT a.name) as advertiser_names,
               GROUP_CONCAT(DISTINCT p.name) as publisher_names
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN advertisers a ON ca.advertiser_id = a.id
        LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
        LEFT JOIN publishers p ON cp.publisher_id = p.id
        GROUP BY c.id ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $active_count = count(array_filter($campaigns, fn($c) => $c['status'] === 'active'));
    $inactive_count = count(array_filter($campaigns, fn($c) => $c['status'] === 'inactive'));
    $total_clicks = array_sum(array_column($campaigns, 'click_count'));
} catch (PDOException $e) {
    $campaigns = []; $active_count = $inactive_count = $total_clicks = 0;
}
?>

<style>
/* Page Header */
.cp-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 32px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f5f9;
}
.cp-header h1 { 
    font-size: 2rem; 
    font-weight: 800; 
    color: #0f172a; 
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}
.cp-header h1 i { 
    color: #6366f1;
    font-size: 1.8rem;
}
.btn-add { 
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
.btn-add:hover { 
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}

/* Stats Row */
.stats-row { 
    display: grid; 
    grid-template-columns: repeat(4, 1fr); 
    gap: 20px; 
    margin-bottom: 32px; 
}
.stat-item { 
    background: #fff; 
    border-radius: 16px; 
    padding: 24px; 
    display: flex; 
    align-items: center; 
    gap: 18px; 
    border: 2px solid #f1f5f9;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.stat-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--stat-gradient);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}
.stat-item:hover::before { transform: scaleX(1); }
.stat-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    border-color: var(--stat-color);
}
.stat-item:nth-child(1) { --stat-gradient: linear-gradient(90deg, #8b5cf6, #6366f1); --stat-color: #8b5cf6; }
.stat-item:nth-child(2) { --stat-gradient: linear-gradient(90deg, #10b981, #059669); --stat-color: #10b981; }
.stat-item:nth-child(3) { --stat-gradient: linear-gradient(90deg, #f59e0b, #d97706); --stat-color: #f59e0b; }
.stat-item:nth-child(4) { --stat-gradient: linear-gradient(90deg, #06b6d4, #0891b2); --stat-color: #06b6d4; }

.stat-icon { 
    width: 56px; 
    height: 56px; 
    border-radius: 14px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 1.4rem;
    transition: all 0.3s ease;
}
.stat-item:hover .stat-icon {
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
.stat-icon.orange { 
    background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
    color: #ea580c;
    box-shadow: 0 4px 15px rgba(234, 88, 12, 0.2);
}
.stat-icon.blue { 
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
}
.stat-info h4 { 
    font-size: 1.8rem; 
    font-weight: 800; 
    margin: 0 0 4px 0; 
    color: #0f172a;
    line-height: 1;
}
.stat-info span { 
    font-size: 0.85rem; 
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Table Wrapper */
.cp-table-wrap { 
    background: #fff; 
    border-radius: 16px; 
    border: 2px solid #f1f5f9;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.cp-table-header { 
    padding: 20px 24px; 
    border-bottom: 2px solid #f1f5f9; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}
.cp-table-header h5 { 
    margin: 0; 
    font-weight: 700; 
    color: #0f172a; 
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.cp-table-header h5 i { 
    color: #6366f1;
    font-size: 1.2rem;
}
.badge-total { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff; 
    padding: 6px 16px; 
    border-radius: 20px; 
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.5px;
}

/* Table Scroll Container */
.table-scroll-container {
    overflow-x: auto;
    overflow-y: visible;
}

/* Custom Scrollbar */
.table-scroll-container::-webkit-scrollbar {
    height: 10px;
}

.table-scroll-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
    margin: 0 10px;
}

.table-scroll-container::-webkit-scrollbar-thumb {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    transition: all 0.3s ease;
}

.table-scroll-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(90deg, #764ba2 0%, #667eea 100%);
}

/* Firefox Scrollbar */
.table-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: #667eea #f1f5f9;
}

/* Table */
.cp-table { 
    width: 100%; 
    border-collapse: collapse; 
}
.cp-table th { 
    padding: 16px 20px; 
    text-align: left; 
    font-size: 0.8rem; 
    font-weight: 700; 
    color: #64748b; 
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #f8fafc; 
    border-bottom: 2px solid #e2e8f0;
}
.cp-table td { 
    padding: 18px 20px; 
    border-bottom: 1px solid #f1f5f9; 
    font-size: 0.9rem; 
    vertical-align: middle;
    color: #475569;
}
.cp-table tbody tr { 
    transition: all 0.2s ease; 
}
.cp-table tbody tr:hover { 
    background: #f8fafc;
    transform: scale(1.005);
}

.camp-name { 
    font-weight: 700; 
    color: #0f172a;
    font-size: 0.95rem;
}
.camp-adv, .camp-pub { 
    max-width: 120px; 
    white-space: nowrap; 
    overflow: hidden; 
    text-overflow: ellipsis; 
    color: #64748b; 
    font-size: 0.85rem;
}

/* Badges */
.badge-type { 
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    color: #7c3aed; 
    padding: 6px 14px; 
    border-radius: 8px; 
    font-size: 0.75rem; 
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
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
.badge-pending { 
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
.badge-completed { 
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

.payout-col { 
    font-size: 0.85rem;
    line-height: 1.6;
}
.payout-col .adv { 
    color: #16a34a;
    font-weight: 600;
}
.payout-col .pub { 
    color: #dc2626;
    font-weight: 600;
}

/* Action Buttons */
.action-btns { 
    display: flex; 
    gap: 6px; 
}
.action-btns a, .action-btns button { 
    width: 34px; 
    height: 34px; 
    border-radius: 8px; 
    border: none; 
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 0.85rem; 
    cursor: pointer; 
    text-decoration: none; 
    transition: all 0.3s ease;
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
.btn-pixel { 
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    color: #7c3aed;
}
.btn-pixel:hover {
    background: #7c3aed;
    color: #fff;
    transform: scale(1.1);
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
.btn-play { 
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #16a34a;
}
.btn-play:hover {
    background: #16a34a;
    color: #fff;
    transform: scale(1.1);
}
.btn-pause { 
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: #64748b;
}
.btn-pause:hover {
    background: #64748b;
    color: #fff;
    transform: scale(1.1);
}
.btn-del { 
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #dc2626;
}
.btn-del:hover {
    background: #dc2626;
    color: #fff;
    transform: scale(1.1);
}

/* Empty State */
.empty-box { 
    text-align: center; 
    padding: 80px 20px; 
    color: #94a3b8; 
}
.empty-box i { 
    font-size: 4rem; 
    margin-bottom: 20px;
    opacity: 0.5;
}
.empty-box p {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 20px;
}

/* Responsive */
@media(max-width:1200px) { 
    .stats-row { grid-template-columns: repeat(2, 1fr); } 
}
@media(max-width:768px) { 
    .stats-row { grid-template-columns: 1fr; }
    .cp-header { 
        flex-direction: column; 
        gap: 16px;
        align-items: flex-start;
    }
    .cp-header h1 { font-size: 1.5rem; }
    .cp-table { min-width: 1000px; }
}
@media(max-width:576px) {
    .stat-item {
        padding: 18px;
        gap: 14px;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        font-size: 1.2rem;
    }
    .stat-info h4 {
        font-size: 1.5rem;
    }
}
</style>

<div class="cp-header">
    <h1><i class="fas fa-rocket"></i>Campaigns Management</h1>
    <a href="add_campaign.php" class="btn-add">
        <i class="fas fa-plus-circle"></i>
        <span>Add Campaign</span>
    </a>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success py-2" style="border-radius:12px;font-size:0.9rem;"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
<div class="alert alert-danger py-2" style="border-radius:12px;font-size:0.9rem;"><i class="fas fa-times-circle me-2"></i><?php echo $error; ?></div>
<?php endif; ?>

<div class="stats-row">
    <div class="stat-item">
        <div class="stat-icon purple"><i class="fas fa-layer-group"></i></div>
        <div class="stat-info">
            <h4><?php echo count($campaigns); ?></h4>
            <span>Total Campaigns</span>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon green"><i class="fas fa-check-double"></i></div>
        <div class="stat-info">
            <h4><?php echo $active_count; ?></h4>
            <span>Active Now</span>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon orange"><i class="fas fa-pause-circle"></i></div>
        <div class="stat-info">
            <h4><?php echo $inactive_count; ?></h4>
            <span>Inactive</span>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon blue"><i class="fas fa-hand-pointer"></i></div>
        <div class="stat-info">
            <h4><?php echo number_format($total_clicks); ?></h4>
            <span>Total Clicks</span>
        </div>
    </div>
</div>

<div class="cp-table-wrap">
    <div class="cp-table-header">
        <h5><i class="fas fa-table"></i>All Campaigns</h5>
        <span class="badge-total"><?php echo count($campaigns); ?> Total</span>
    </div>
    <?php if (empty($campaigns)): ?>
    <div class="empty-box">
        <i class="fas fa-rocket"></i>
        <p>No campaigns created yet</p>
        <a href="add_campaign.php" class="btn-add">
            <i class="fas fa-plus-circle"></i>
            <span>Create First Campaign</span>
        </a>
    </div>
    <?php else: ?>
    <div class="table-scroll-container">
        <table class="cp-table">
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag"></i> ID</th>
                    <th><i class="fas fa-tag"></i> Campaign Name</th>
                    <th><i class="fas fa-briefcase"></i> Advertiser</th>
                    <th><i class="fas fa-users"></i> Publisher</th>
                    <th><i class="fas fa-layer-group"></i> Type</th>
                    <th><i class="fas fa-money-bill-wave"></i> Payout</th>
                    <th><i class="fas fa-signal"></i> Status</th>
                    <th><i class="fas fa-credit-card"></i> Payment</th>
                    <th><i class="fas fa-cog"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($campaigns as $c): ?>
                <tr>
                    <td><strong><?php echo $c['id']; ?></strong></td>
                    <td class="camp-name"><?php echo htmlspecialchars($c['name']); ?></td>
                    <td><div class="camp-adv" title="<?php echo htmlspecialchars($c['advertiser_names'] ?? '-'); ?>"><?php echo htmlspecialchars($c['advertiser_names'] ?? '-'); ?></div></td>
                    <td><div class="camp-pub" title="<?php echo htmlspecialchars($c['publisher_names'] ?? '-'); ?>"><?php echo htmlspecialchars($c['publisher_names'] ?? '-'); ?></div></td>
                    <td><span class="badge-type"><?php echo $c['campaign_type']; ?></span></td>
                    <td class="payout-col">
                        <span class="adv"><i class="fas fa-arrow-up"></i> ₹<?php echo number_format($c['advertiser_payout'],0); ?></span><br>
                        <span class="pub"><i class="fas fa-arrow-down"></i> ₹<?php echo number_format($c['publisher_payout'],0); ?></span>
                    </td>
                    <td><span class="<?php echo $c['status']==='active'?'badge-active':'badge-inactive'; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                    <td><span class="<?php echo $c['payment_status']==='completed'?'badge-completed':'badge-pending'; ?>"><?php echo ucfirst($c['payment_status']); ?></span></td>
                    <td>
                        <div class="action-btns">
                            <a href="campaign_tracking_stats.php?id=<?php echo $c['id']; ?>" class="btn-stats" title="View Statistics"><i class="fas fa-chart-bar"></i></a>
                            <?php if (!empty($c['enable_image_pixel'])): ?>
                            <a href="campaign_pixel_links.php?id=<?php echo $c['id']; ?>" class="btn-pixel" title="Pixel Links"><i class="fas fa-image"></i></a>
                            <?php endif; ?>
                            <a href="edit_campaign.php?id=<?php echo $c['id']; ?>" class="btn-edit" title="Edit Campaign"><i class="fas fa-pen"></i></a>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                <input type="hidden" name="action" value="update_status">
                                <?php if ($c['status']==='active'): ?>
                                <button type="submit" name="status" value="inactive" class="btn-pause" title="Pause Campaign"><i class="fas fa-pause"></i></button>
                                <?php else: ?>
                                <button type="submit" name="status" value="active" class="btn-play" title="Activate Campaign"><i class="fas fa-play"></i></button>
                                <?php endif; ?>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this campaign?')">
                                <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn-del" title="Delete Campaign"><i class="fas fa-trash-alt"></i></button>
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

<?php require_once 'includes/footer.php'; ?>
