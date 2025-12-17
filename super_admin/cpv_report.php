<?php
// super_admin/cpv_report.php - CPV Campaigns Report
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get filter parameters
$filter_period = $_GET['period'] ?? 'all';
$filter_search = $_GET['search'] ?? '';

// Build date filter based on period
$date_filter = "";
switch ($filter_period) {
    case 'today':
        $date_filter = "AND DATE(cc.clicked_at) = CURDATE()";
        break;
    case 'yesterday':
        $date_filter = "AND DATE(cc.clicked_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'month':
        $date_filter = "AND MONTH(cc.clicked_at) = MONTH(CURDATE()) AND YEAR(cc.clicked_at) = YEAR(CURDATE())";
        break;
    case 'year':
        $date_filter = "AND YEAR(cc.clicked_at) = YEAR(CURDATE())";
        break;
    default:
        $date_filter = "";
}

// Search filter
$search_filter = "";
$search_params = [];
if (!empty($filter_search)) {
    $search_filter = "AND (c.campaign_name LIKE ? OR c.short_code LIKE ?)";
    $search_params = ["%$filter_search%", "%$filter_search%"];
}

// Get all CPV campaigns with click stats
try {
    $sql = "
        SELECT c.*, 
               COUNT(cc.id) as period_clicks,
               SUM(CASE WHEN cc.is_duplicate = 0 THEN 1 ELSE 0 END) as original_clicks,
               SUM(CASE WHEN cc.is_duplicate = 1 THEN 1 ELSE 0 END) as duplicate_clicks
        FROM cpv_campaigns c
        LEFT JOIN cpv_clicks cc ON c.id = cc.campaign_id $date_filter
        WHERE c.created_by = ? $search_filter
        GROUP BY c.id
        ORDER BY period_clicks DESC, c.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $params = array_merge([$_SESSION['user_id']], $search_params);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary stats for selected period
    $sql_summary = "
        SELECT 
            COUNT(DISTINCT c.id) as total_campaigns,
            COALESCE(COUNT(cc.id), 0) as total_clicks,
            COALESCE(SUM(CASE WHEN cc.is_duplicate = 0 THEN 1 ELSE 0 END), 0) as total_original,
            COALESCE(SUM(CASE WHEN cc.is_duplicate = 1 THEN 1 ELSE 0 END), 0) as total_duplicate
        FROM cpv_campaigns c
        LEFT JOIN cpv_clicks cc ON c.id = cc.campaign_id $date_filter
        WHERE c.created_by = ? $search_filter
    ";
    $stmt = $conn->prepare($sql_summary);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
    $campaigns = [];
    $summary = ['total_campaigns' => 0, 'total_clicks' => 0, 'total_original' => 0, 'total_duplicate' => 0];
}

// Period labels
$period_labels = [
    'all' => 'All Time',
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'month' => 'This Month',
    'year' => 'This Year'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPV Report - <?php echo $period_labels[$filter_period]; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        body { background: #f5f7fb; }
        .sidebar { min-height: calc(100vh - 56px); }
        .nav-link { color: #333; padding: 12px 20px; border-radius: 8px; margin: 2px 10px; }
        .nav-link:hover, .nav-link.active { background: var(--primary-gradient); color: white !important; }
        .card { border: none; border-radius: 15px; overflow: hidden; }
        .stat-card { 
            padding: 25px; 
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-number { font-size: 2.5rem; font-weight: 700; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }
        .period-btn { 
            padding: 12px 25px; 
            border-radius: 25px; 
            font-weight: 500;
            transition: all 0.3s;
        }
        .period-btn.active { 
            background: var(--primary-gradient); 
            color: white; 
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .period-btn:not(.active):hover {
            background: #e9ecef;
        }
        .table-container { 
            background: white; 
            border-radius: 15px; 
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .table { margin-bottom: 0; }
        .table thead th { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            padding: 15px;
            border: none;
        }
        .table tbody td { padding: 15px; vertical-align: middle; }
        .table tbody tr:hover { background: #f8f9ff; }
        .badge-clicks { 
            font-size: 1rem; 
            padding: 8px 15px; 
            border-radius: 20px;
        }
        .campaign-name { font-weight: 600; color: #333; }
        .campaign-url { font-size: 0.8rem; color: #888; }
        .search-box { 
            border-radius: 25px; 
            padding: 12px 20px;
            border: 2px solid #e9ecef;
        }
        .search-box:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2); }
        @media print {
            .no-print { display: none !important; }
            .stat-card { padding: 15px; }
            .stat-number { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark no-print" style="background: var(--primary-gradient);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-chart-line me-2"></i>Ads Platform</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3"><i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a class="btn btn-outline-light btn-sm" href="../logout.php"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </nav>

  <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            

            <main class="col-lg-10 ms-sm-auto px-4 py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><i class="fas fa-chart-bar me-2 text-primary"></i>CPV Report</h2>
                        <p class="text-muted mb-0">Showing: <strong><?php echo $period_labels[$filter_period]; ?></strong> | <?php echo date('d M Y'); ?></p>
                    </div>
                    <div class="no-print">
                        <a href="cpv.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left me-1"></i>Back</a>
                        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i>Print</button>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Period Filter Buttons -->
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="?period=today&search=<?php echo urlencode($filter_search); ?>" class="btn period-btn <?php echo $filter_period === 'today' ? 'active' : 'btn-outline-secondary'; ?>">
                                        <i class="fas fa-calendar-day me-1"></i>Today
                                    </a>
                                    <a href="?period=yesterday&search=<?php echo urlencode($filter_search); ?>" class="btn period-btn <?php echo $filter_period === 'yesterday' ? 'active' : 'btn-outline-secondary'; ?>">
                                        <i class="fas fa-history me-1"></i>Yesterday
                                    </a>
                                    <a href="?period=month&search=<?php echo urlencode($filter_search); ?>" class="btn period-btn <?php echo $filter_period === 'month' ? 'active' : 'btn-outline-secondary'; ?>">
                                        <i class="fas fa-calendar-alt me-1"></i>This Month
                                    </a>
                                    <a href="?period=year&search=<?php echo urlencode($filter_search); ?>" class="btn period-btn <?php echo $filter_period === 'year' ? 'active' : 'btn-outline-secondary'; ?>">
                                        <i class="fas fa-calendar me-1"></i>This Year
                                    </a>
                                    <a href="?period=all&search=<?php echo urlencode($filter_search); ?>" class="btn period-btn <?php echo $filter_period === 'all' ? 'active' : 'btn-outline-secondary'; ?>">
                                        <i class="fas fa-infinity me-1"></i>All Time
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <form method="GET" class="d-flex">
                                    <input type="hidden" name="period" value="<?php echo $filter_period; ?>">
                                    <input type="text" name="search" class="form-control search-box" placeholder="Search campaign..." value="<?php echo htmlspecialchars($filter_search); ?>">
                                    <button type="submit" class="btn btn-primary ms-2" style="border-radius: 25px;"><i class="fas fa-search"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card shadow stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="stat-number"><?php echo $summary['total_campaigns']; ?></div>
                            <div class="stat-label"><i class="fas fa-bullhorn me-1"></i>Total Campaigns</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                            <div class="stat-number"><?php echo $summary['total_clicks']; ?></div>
                            <div class="stat-label"><i class="fas fa-mouse-pointer me-1"></i>Total Clicks</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow stat-card" style="background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%); color: white;">
                            <div class="stat-number"><?php echo $summary['total_original']; ?></div>
                            <div class="stat-label"><i class="fas fa-check-circle me-1"></i>Original Clicks</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                            <div class="stat-number"><?php echo $summary['total_duplicate']; ?></div>
                            <div class="stat-label"><i class="fas fa-copy me-1"></i>Duplicate Clicks</div>
                        </div>
                    </div>
                </div>

                <!-- Report Table -->
                <div class="table-container shadow">
                    <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Campaign Details - <?php echo $period_labels[$filter_period]; ?></h5>
                        <span class="badge bg-primary"><?php echo count($campaigns); ?> campaigns</span>
                    </div>
                    <?php if (empty($campaigns)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <p class="text-muted">No campaigns found for this period.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                                <div class="campaign-name"><?php echo htmlspecialchars($camp['campaign_name']); ?></div>
                                                <div class="campaign-url"><?php echo htmlspecialchars(substr($camp['original_url'], 0, 50)); ?>...</div>
                                            </td>
                                            <td><code class="bg-light p-2 rounded"><?php echo $camp['short_code']; ?></code></td>
                                            <td class="text-center">
                                                <span class="badge badge-clicks bg-primary"><?php echo $camp['period_clicks'] ?? 0; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-clicks bg-success"><?php echo $camp['original_clicks'] ?? 0; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-clicks bg-danger"><?php echo $camp['duplicate_clicks'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo date('d M', strtotime($camp['start_date'])); ?> - <?php echo date('d M Y', strtotime($camp['end_date'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($is_expired): ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php elseif ($is_active): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="3" class="text-end">TOTAL:</td>
                                        <td class="text-center"><span class="badge badge-clicks bg-primary"><?php echo array_sum(array_column($campaigns, 'period_clicks')); ?></span></td>
                                        <td class="text-center"><span class="badge badge-clicks bg-success"><?php echo array_sum(array_column($campaigns, 'original_clicks')); ?></span></td>
                                        <td class="text-center"><span class="badge badge-clicks bg-danger"><?php echo array_sum(array_column($campaigns, 'duplicate_clicks')); ?></span></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
