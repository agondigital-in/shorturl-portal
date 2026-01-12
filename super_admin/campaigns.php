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
.cp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.cp-header h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
.cp-header h1 i { color: #6366f1; }
.btn-add { background: #6366f1; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
.btn-add:hover { background: #4f46e5; color: #fff; }

.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
.stat-item { background: #fff; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 15px; border: 1px solid #e2e8f0; }
.stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.stat-icon.purple { background: #ede9fe; color: #7c3aed; }
.stat-icon.green { background: #dcfce7; color: #16a34a; }
.stat-icon.orange { background: #ffedd5; color: #ea580c; }
.stat-icon.blue { background: #dbeafe; color: #2563eb; }
.stat-info h4 { font-size: 1.4rem; font-weight: 700; margin: 0; color: #1e293b; }
.stat-info span { font-size: 0.8rem; color: #64748b; }

.cp-table-wrap { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; }
.cp-table-header { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.cp-table-header h5 { margin: 0; font-weight: 600; color: #1e293b; font-size: 1rem; }
.cp-table-header h5 i { color: #6366f1; }
.badge-total { background: #6366f1; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; }

.cp-table { width: 100%; border-collapse: collapse; }
.cp-table th { padding: 12px 15px; text-align: left; font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.cp-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; vertical-align: middle; }
.cp-table tr:hover { background: #f8fafc; }

.camp-name { font-weight: 600; color: #1e293b; }
.camp-adv, .camp-pub { max-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #64748b; font-size: 0.8rem; }
.badge-type { background: #ede9fe; color: #7c3aed; padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 500; }
.badge-active { background: #dcfce7; color: #16a34a; padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
.badge-inactive { background: #fef3c7; color: #d97706; padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
.badge-pending { background: #fef3c7; color: #d97706; padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; }
.badge-completed { background: #dcfce7; color: #16a34a; padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; }

.payout-col { font-size: 0.8rem; }
.payout-col .adv { color: #16a34a; }
.payout-col .pub { color: #dc2626; }

.action-btns { display: flex; gap: 5px; }
.action-btns a, .action-btns button { width: 30px; height: 30px; border-radius: 6px; border: none; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; cursor: pointer; text-decoration: none; transition: 0.2s; }
.btn-stats { background: #dbeafe; color: #2563eb; }
.btn-pixel { background: #ede9fe; color: #7c3aed; }
.btn-edit { background: #fef3c7; color: #d97706; }
.btn-play { background: #dcfce7; color: #16a34a; }
.btn-pause { background: #f1f5f9; color: #64748b; }
.btn-del { background: #fee2e2; color: #dc2626; }
.action-btns a:hover, .action-btns button:hover { opacity: 0.8; transform: scale(1.05); }

.empty-box { text-align: center; padding: 50px; color: #94a3b8; }
.empty-box i { font-size: 2.5rem; margin-bottom: 15px; }

@media(max-width:992px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
@media(max-width:576px) { .stats-row { grid-template-columns: 1fr; } .cp-header { flex-direction: column; gap: 10px; } }
</style>

<div class="cp-header">
    <h1><i class="fas fa-bullhorn me-2"></i>Campaigns</h1>
    <a href="add_campaign.php" class="btn-add"><i class="fas fa-plus me-1"></i> Add Campaign</a>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success py-2" style="border-radius:8px;font-size:0.9rem;"><i class="fas fa-check-circle me-1"></i><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
<div class="alert alert-danger py-2" style="border-radius:8px;font-size:0.9rem;"><i class="fas fa-times-circle me-1"></i><?php echo $error; ?></div>
<?php endif; ?>

<div class="stats-row">
    <div class="stat-item">
        <div class="stat-icon purple"><i class="fas fa-bullhorn"></i></div>
        <div class="stat-info"><h4><?php echo count($campaigns); ?></h4><span>Total</span></div>
    </div>
    <div class="stat-item">
        <div class="stat-icon green"><i class="fas fa-check"></i></div>
        <div class="stat-info"><h4><?php echo $active_count; ?></h4><span>Active</span></div>
    </div>
    <div class="stat-item">
        <div class="stat-icon orange"><i class="fas fa-pause"></i></div>
        <div class="stat-info"><h4><?php echo $inactive_count; ?></h4><span>Inactive</span></div>
    </div>
    <div class="stat-item">
        <div class="stat-icon blue"><i class="fas fa-mouse-pointer"></i></div>
        <div class="stat-info"><h4><?php echo number_format($total_clicks); ?></h4><span>Clicks</span></div>
    </div>
</div>

<div class="cp-table-wrap">
    <div class="cp-table-header">
        <h5><i class="fas fa-list me-2"></i>All Campaigns</h5>
        <span class="badge-total"><?php echo count($campaigns); ?> Total</span>
    </div>
    <?php if (empty($campaigns)): ?>
    <div class="empty-box">
        <i class="fas fa-bullhorn"></i>
        <p>No campaigns yet</p>
        <a href="add_campaign.php" class="btn-add"><i class="fas fa-plus me-1"></i> Create</a>
    </div>
    <?php else: ?>
    <table class="cp-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Advertiser</th>
                <th>Publisher</th>
                <th>Type</th>
                <th>Clicks</th>
                <th>Payout</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($campaigns as $c): ?>
            <tr>
                <td><?php echo $c['id']; ?></td>
                <td class="camp-name"><?php echo htmlspecialchars($c['name']); ?></td>
                <td><div class="camp-adv" title="<?php echo htmlspecialchars($c['advertiser_names'] ?? '-'); ?>"><?php echo htmlspecialchars($c['advertiser_names'] ?? '-'); ?></div></td>
                <td><div class="camp-pub" title="<?php echo htmlspecialchars($c['publisher_names'] ?? '-'); ?>"><?php echo htmlspecialchars($c['publisher_names'] ?? '-'); ?></div></td>
                <td><span class="badge-type"><?php echo $c['campaign_type']; ?></span></td>
                <td><strong><?php echo number_format($c['click_count']); ?></strong></td>
                <td class="payout-col">
                    <span class="adv">A:₹<?php echo number_format($c['advertiser_payout'],0); ?></span><br>
                    <span class="pub">P:₹<?php echo number_format($c['publisher_payout'],0); ?></span>
                </td>
                <td><span class="<?php echo $c['status']==='active'?'badge-active':'badge-inactive'; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                <td><span class="<?php echo $c['payment_status']==='completed'?'badge-completed':'badge-pending'; ?>"><?php echo ucfirst($c['payment_status']); ?></span></td>
                <td>
                    <div class="action-btns">
                        <a href="campaign_tracking_stats.php?id=<?php echo $c['id']; ?>" class="btn-stats" title="Stats"><i class="fas fa-chart-line"></i></a>
                        <?php if (!empty($c['enable_image_pixel'])): ?>
                        <a href="campaign_pixel_links.php?id=<?php echo $c['id']; ?>" class="btn-pixel" title="Pixel"><i class="fas fa-image"></i></a>
                        <?php endif; ?>
                        <a href="edit_campaign.php?id=<?php echo $c['id']; ?>" class="btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                            <input type="hidden" name="action" value="update_status">
                            <?php if ($c['status']==='active'): ?>
                            <button type="submit" name="status" value="inactive" class="btn-pause" title="Pause"><i class="fas fa-pause"></i></button>
                            <?php else: ?>
                            <button type="submit" name="status" value="active" class="btn-play" title="Activate"><i class="fas fa-play"></i></button>
                            <?php endif; ?>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn-del" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
