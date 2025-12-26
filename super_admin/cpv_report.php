<?php
// super_admin/cpv_report.php - CPV Campaigns Report
$page_title = 'CPV Report';
require_once 'includes/header.php';
require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$filter_period = $_GET['period'] ?? 'all';
$filter_search = $_GET['search'] ?? '';

$date_filter = "";
switch ($filter_period) {
    case 'today': $date_filter = "AND DATE(cc.clicked_at) = CURDATE()"; break;
    case 'yesterday': $date_filter = "AND DATE(cc.clicked_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"; break;
    case 'month': $date_filter = "AND MONTH(cc.clicked_at) = MONTH(CURDATE()) AND YEAR(cc.clicked_at) = YEAR(CURDATE())"; break;
    case 'year': $date_filter = "AND YEAR(cc.clicked_at) = YEAR(CURDATE())"; break;
}

$search_filter = "";
$search_params = [];
if (!empty($filter_search)) {
    $search_filter = "AND (c.campaign_name LIKE ? OR c.short_code LIKE ?)";
    $search_params = ["%$filter_search%", "%$filter_search%"];
}

try {
    $sql = "SELECT c.*, COUNT(cc.id) as period_clicks,
            SUM(CASE WHEN cc.is_duplicate = 0 THEN 1 ELSE 0 END) as original_clicks,
            SUM(CASE WHEN cc.is_duplicate = 1 THEN 1 ELSE 0 END) as duplicate_clicks
            FROM cpv_campaigns c
            LEFT JOIN cpv_clicks cc ON c.id = cc.campaign_id $date_filter
            WHERE c.created_by = ? $search_filter
            GROUP BY c.id ORDER BY period_clicks DESC, c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $params = array_merge([$_SESSION['user_id']], $search_params);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_summary = "SELECT COUNT(DISTINCT c.id) as total_campaigns,
            COALESCE(COUNT(cc.id), 0) as total_clicks,
            COALESCE(SUM(CASE WHEN cc.is_duplicate = 0 THEN 1 ELSE 0 END), 0) as total_original,
            COALESCE(SUM(CASE WHEN cc.is_duplicate = 1 THEN 1 ELSE 0 END), 0) as total_duplicate
            FROM cpv_campaigns c
            LEFT JOIN cpv_clicks cc ON c.id = cc.campaign_id $date_filter
            WHERE c.created_by = ? $search_filter";
    $stmt = $conn->prepare($sql_summary);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
    $campaigns = [];
    $summary = ['total_campaigns' => 0, 'total_clicks' => 0, 'total_original' => 0, 'total_duplicate' => 0];
}

$period_labels = ['all' => 'All Time', 'today' => 'Today', 'yesterday' => 'Yesterday', 'month' => 'This Month', 'year' => 'This Year'];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">CPV Report</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="cpv.php">CPV Campaigns</a></li>
                <li class="breadcrumb-item active">Report</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="cpv.php" class="btn btn-light me-2"><i class="fas fa-arrow-left me-2"></i>Back</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i>Print</button>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Period Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($period_labels as $key => $label): ?>
                        <a href="?period=<?php echo $key; ?>&search=<?php echo urlencode($filter_search); ?>" 
                           class="btn <?php echo $filter_period === $key ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-4">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="period" value="<?php echo $filter_period; ?>">
                    <input type="text" name="search" class="form-control" placeholder="Search campaign..." value="<?php echo htmlspecialchars($filter_search); ?>">
                    <button type="submit" class="btn btn-primary ms-2"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="stat-card-value"><?php echo $summary['total_campaigns']; ?></div>
            <div class="stat-card-label">Total Campaigns</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-card-value"><?php echo number_format($summary['total_clicks']); ?></div>
            <div class="stat-card-label">Total Clicks</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-card-value"><?php echo number_format($summary['total_original']); ?></div>
            <div class="stat-card-label">Original Clicks</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-card-icon"><i class="fas fa-copy"></i></div>
            <div class="stat-card-value"><?php echo number_format($summary['total_duplicate']); ?></div>
            <div class="stat-card-label">Duplicate Clicks</div>
        </div>
    </div>
</div>

<!-- Report Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Campaign Details - <?php echo $period_labels[$filter_period]; ?></h5>
        <span class="badge bg-primary"><?php echo count($campaigns); ?> campaigns</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($campaigns)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">No campaigns found for this period.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Campaign</th>
                            <th>Short Code</th>
                            <th class="text-center">Clicks</th>
                            <th class="text-center">Original</th>
                            <th class="text-center">Duplicate</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($campaigns as $camp): ?>
                            <?php 
                            $today = date('Y-m-d');
                            $is_expired = $camp['end_date'] < $today;
                            $is_active = $camp['status'] === 'active' && !$is_expired;
                            ?>
                            <tr>
                                <td><strong><?php echo $i++; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($camp['campaign_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($camp['original_url'], 0, 50)); ?>...</small>
                                </td>
                                <td><code><?php echo $camp['short_code']; ?></code></td>
                                <td class="text-center"><span class="badge badge-soft-primary"><?php echo $camp['period_clicks'] ?? 0; ?></span></td>
                                <td class="text-center"><span class="badge badge-soft-success"><?php echo $camp['original_clicks'] ?? 0; ?></span></td>
                                <td class="text-center"><span class="badge badge-soft-danger"><?php echo $camp['duplicate_clicks'] ?? 0; ?></span></td>
                                <td><small><?php echo date('M d', strtotime($camp['start_date'])); ?> - <?php echo date('M d, Y', strtotime($camp['end_date'])); ?></small></td>
                                <td>
                                    <?php if ($is_expired): ?>
                                        <span class="badge badge-soft-danger">Expired</span>
                                    <?php elseif ($is_active): ?>
                                        <span class="badge badge-soft-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-warning">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                            <td class="text-center"><span class="badge badge-soft-primary"><?php echo array_sum(array_column($campaigns, 'period_clicks')); ?></span></td>
                            <td class="text-center"><span class="badge badge-soft-success"><?php echo array_sum(array_column($campaigns, 'original_clicks')); ?></span></td>
                            <td class="text-center"><span class="badge badge-soft-danger"><?php echo array_sum(array_column($campaigns, 'duplicate_clicks')); ?></span></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
