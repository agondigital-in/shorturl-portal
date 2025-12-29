<?php
// super_admin/ajax_get_leads.php - Get leads data for a campaign
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo '<div class="alert alert-danger">Unauthorized</div>';
    exit();
}

require_once '../db_connection.php';

$campaign_id = $_GET['campaign_id'] ?? 0;
$month = $_GET['month'] ?? date('Y-m');

if (!$campaign_id) {
    echo '<div class="alert alert-danger">Invalid campaign</div>';
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get leads data for the month
$stmt = $conn->prepare("
    SELECT lead_date, leads_count 
    FROM campaign_daily_leads 
    WHERE campaign_id = ? AND DATE_FORMAT(lead_date, '%Y-%m') = ?
    ORDER BY lead_date DESC
");
$stmt->execute([$campaign_id, $month]);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = array_sum(array_column($leads, 'leads_count'));

// Get campaign target
$stmt = $conn->prepare("SELECT target_leads FROM campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);
$target = $campaign['target_leads'] ?? 0;
?>

<div class="row mb-3">
    <div class="col-md-6">
        <span class="badge bg-primary fs-6">Month Total: <?php echo number_format($total); ?></span>
    </div>
    <div class="col-md-6 text-end">
        <span class="badge bg-secondary fs-6">Target: <?php echo number_format($target); ?></span>
    </div>
</div>

<?php if (count($leads) > 0): ?>
<div class="table-responsive">
    <table class="table table-sm table-striped">
        <thead class="table-dark">
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th class="text-end">Leads</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leads as $row): ?>
            <tr>
                <td><?php echo date('d M Y', strtotime($row['lead_date'])); ?></td>
                <td><?php echo date('l', strtotime($row['lead_date'])); ?></td>
                <td class="text-end"><strong><?php echo number_format($row['leads_count']); ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-primary">
            <tr>
                <th colspan="2">Total</th>
                <th class="text-end"><?php echo number_format($total); ?></th>
            </tr>
        </tfoot>
    </table>
</div>
<?php else: ?>
<div class="alert alert-warning text-center">
    <i class="fas fa-info-circle me-2"></i>No leads data for this month
</div>
<?php endif; ?>
