<?php
// super_admin/all_publishers_daily_clicks.php - View all publishers' daily click statistics
$page_title = 'All Publishers Stats';
require_once 'includes/header.php';
require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Date Filter Logic
$filter_type = $_GET['filter'] ?? 'custom';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

if (isset($_GET['filter'])) {
    switch ($_GET['filter']) {
        case 'today':
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
            break;
        case 'yesterday':
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $end_date = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'this_month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            break;
        case 'previous_month':
            $start_date = date('Y-m-01', strtotime('first day of last month'));
            $end_date = date('Y-m-t', strtotime('last day of last month'));
            break;
    }
}

$stmt = $conn->prepare("
    SELECT 
        p.name as publisher_name,
        p.id as publisher_id,
        c.name as campaign_name,
        c.id as campaign_id,
        SUM(pdc.clicks) as total_clicks,
        COUNT(DISTINCT pdc.click_date) as active_days
    FROM publisher_daily_clicks pdc
    JOIN publishers p ON pdc.publisher_id = p.id
    JOIN campaigns c ON pdc.campaign_id = c.id
    WHERE pdc.click_date BETWEEN ? AND ?
    GROUP BY p.id, p.name, c.id, c.name
    ORDER BY p.name ASC, total_clicks DESC
");
$stmt->execute([$start_date, $end_date]);
$publisher_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_period_clicks = 0;
$total_active_publishers = 0;
foreach ($publisher_summary as $p) {
    $total_period_clicks += $p['total_clicks'];
    $total_active_publishers++;
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">All Publishers Daily Clicks</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Publishers Stats</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-end g-3">
            <div class="col-lg-5">
                <label class="form-label fw-semibold">Quick Filters</label>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?filter=today" class="btn <?php echo ($filter_type == 'today') ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">Today</a>
                    <a href="?filter=yesterday" class="btn <?php echo ($filter_type == 'yesterday') ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">Yesterday</a>
                    <a href="?filter=this_month" class="btn <?php echo ($filter_type == 'this_month') ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">This Month</a>
                    <a href="?filter=previous_month" class="btn <?php echo ($filter_type == 'previous_month') ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">Prev Month</a>
                </div>
            </div>
            <div class="col-lg-7">
                <form method="GET" class="row g-2">
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
            <div class="stat-card-value"><?php echo $total_active_publishers; ?></div>
            <div class="stat-card-label">Active Publishers</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="stat-card-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-card-value"><?php echo $total_active_publishers > 0 ? number_format($total_period_clicks / $total_active_publishers, 1) : '0'; ?></div>
            <div class="stat-card-label">Avg Per Publisher</div>
        </div>
    </div>
</div>

<!-- Publisher Summary Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Publisher Performance Summary</h5>
        <span class="badge bg-primary"><?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($publisher_summary)): ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                <p class="text-muted">No data available for the selected period</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Publisher</th>
                            <th class="text-end">Clicks</th>
                            <th class="text-center">Active Days</th>
                            <th class="text-end">Avg/Day</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($publisher_summary as $summary): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($summary['campaign_name']); ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width:28px;height:28px;font-size:10px;">
                                            <?php echo strtoupper(substr($summary['publisher_name'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($summary['publisher_name']); ?>
                                    </div>
                                </td>
                                <td class="text-end"><strong class="text-primary"><?php echo number_format($summary['total_clicks']); ?></strong></td>
                                <td class="text-center"><span class="badge badge-soft-success"><?php echo $summary['active_days']; ?> days</span></td>
                                <td class="text-end"><?php echo number_format($summary['total_clicks'] / max($summary['active_days'], 1), 1); ?></td>
                                <td class="text-center">
                                    <a href="publisher_daily_clicks.php?id=<?php echo $summary['campaign_id']; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-soft-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="2"><strong>Total</strong></td>
                            <td class="text-end"><strong class="text-primary"><?php echo number_format($total_period_clicks); ?></strong></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
