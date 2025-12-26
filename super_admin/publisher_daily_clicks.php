<?php
// super_admin/publisher_daily_clicks.php - View daily click statistics
$page_title = 'Daily Clicks';
require_once 'includes/header.php';
require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$campaign_id = $_GET['id'] ?? null;

if (!$campaign_id) {
    header('Location: campaigns.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    header('Location: campaigns.php');
    exit();
}

$filter_type = $_GET['filter'] ?? 'custom';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

if (isset($_GET['filter'])) {
    switch ($_GET['filter']) {
        case 'today': $start_date = $end_date = date('Y-m-d'); break;
        case 'yesterday': $start_date = $end_date = date('Y-m-d', strtotime('-1 day')); break;
        case 'this_month': $start_date = date('Y-m-01'); $end_date = date('Y-m-t'); break;
        case 'previous_month': $start_date = date('Y-m-01', strtotime('first day of last month')); $end_date = date('Y-m-t', strtotime('last day of last month')); break;
    }
}

$stmt = $conn->prepare("SELECT pdc.click_date, p.name as publisher_name, p.id as publisher_id, pdc.clicks
    FROM publisher_daily_clicks pdc
    JOIN publishers p ON pdc.publisher_id = p.id
    WHERE pdc.campaign_id = ? AND pdc.click_date BETWEEN ? AND ?
    ORDER BY pdc.click_date DESC, p.name ASC");
$stmt->execute([$campaign_id, $start_date, $end_date]);
$daily_clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT p.name as publisher_name, p.id as publisher_id, SUM(pdc.clicks) as total_clicks, COUNT(DISTINCT pdc.click_date) as active_days
    FROM publisher_daily_clicks pdc
    JOIN publishers p ON pdc.publisher_id = p.id
    WHERE pdc.campaign_id = ? AND pdc.click_date BETWEEN ? AND ?
    GROUP BY p.id, p.name ORDER BY total_clicks DESC");
$stmt->execute([$campaign_id, $start_date, $end_date]);
$publisher_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_period_clicks = array_sum(array_column($publisher_summary, 'total_clicks'));
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Daily Click Statistics</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="campaigns.php">Campaigns</a></li>
                <li class="breadcrumb-item active">Daily Clicks</li>
            </ol>
        </nav>
    </div>
    <a href="campaign_tracking_stats.php?id=<?php echo $campaign_id; ?>" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back to Stats</a>
</div>

<!-- Campaign Info -->
<div class="alert alert-info mb-4">
    <strong><i class="fas fa-bullhorn me-2"></i><?php echo htmlspecialchars($campaign['name']); ?></strong>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-end g-3">
            <div class="col-lg-5">
                <label class="form-label fw-semibold">Quick Filters</label>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?id=<?php echo $campaign_id; ?>&filter=today" class="btn <?php echo ($filter_type == 'today') ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">Today</a>
                    <a href="?id=<?php echo $campaign_id; ?>&filter=yesterday" class="btn <?php echo ($filter_type == 'yesterday') ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">Yesterday</a>
                    <a href="?id=<?php echo $campaign_id; ?>&filter=this_month" class="btn <?php echo ($filter_type == 'this_month') ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">This Month</a>
                    <a href="?id=<?php echo $campaign_id; ?>&filter=previous_month" class="btn <?php echo ($filter_type == 'previous_month') ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">Prev Month</a>
                </div>
            </div>
            <div class="col-lg-7">
                <form method="GET" class="row g-2">
                    <input type="hidden" name="id" value="<?php echo $campaign_id; ?>">
                    <input type="hidden" name="filter" value="custom">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_period_clicks); ?></div>
            <div class="stat-card-label">Total Clicks</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-users"></i></div>
            <div class="stat-card-value"><?php echo count($publisher_summary); ?></div>
            <div class="stat-card-label">Active Publishers</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="stat-card-icon"><i class="fas fa-trophy"></i></div>
            <div class="stat-card-value"><?php echo !empty($publisher_summary) ? htmlspecialchars($publisher_summary[0]['publisher_name']) : 'N/A'; ?></div>
            <div class="stat-card-label">Top Performer</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Publisher Summary -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Publisher Performance</h5></div>
            <div class="card-body p-0">
                <?php if (empty($publisher_summary)): ?>
                    <div class="text-center py-5"><p class="text-muted">No data available</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Publisher</th><th class="text-end">Clicks</th><th class="text-center">Days</th><th class="text-end">Avg/Day</th></tr></thead>
                            <tbody>
                                <?php foreach ($publisher_summary as $summary): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($summary['publisher_name']); ?></strong></td>
                                        <td class="text-end"><span class="badge badge-soft-primary"><?php echo number_format($summary['total_clicks']); ?></span></td>
                                        <td class="text-center"><?php echo $summary['active_days']; ?></td>
                                        <td class="text-end"><?php echo number_format($summary['total_clicks'] / max($summary['active_days'], 1), 1); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Daily Breakdown -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Daily Breakdown</h5></div>
            <div class="card-body p-0">
                <?php if (empty($daily_clicks)): ?>
                    <div class="text-center py-5"><p class="text-muted">No daily records found</p></div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Date</th><th>Publisher</th><th class="text-end">Clicks</th></tr></thead>
                            <tbody>
                                <?php foreach ($daily_clicks as $click): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($click['click_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($click['publisher_name']); ?></td>
                                        <td class="text-end"><strong><?php echo number_format($click['clicks']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
