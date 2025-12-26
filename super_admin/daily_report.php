<?php
// super_admin/daily_report.php - Daily Report Dashboard
$page_title = 'Daily Report';
require_once 'includes/header.php';
require_once '../db_connection.php';

// Set default date to today
$report_date = date('Y-m-d');
$date_range = isset($_GET['range']) ? $_GET['range'] : '';
$report_start_date = $report_date;
$report_end_date = $report_date;

// Handle different date range options
if ($date_range === 'today') {
    $report_date = date('Y-m-d');
    $report_start_date = $report_date;
    $report_end_date = $report_date;
} elseif ($date_range === 'yesterday') {
    $report_date = date('Y-m-d', strtotime('-1 day'));
    $report_start_date = $report_date;
    $report_end_date = $report_date;
} elseif ($date_range === 'this_month') {
    $report_date = date('Y-m-01');
    $report_start_date = $report_date;
    $report_end_date = date('Y-m-t');
} elseif ($date_range === 'this_year') {
    $report_date = date('Y-01-01');
    $report_start_date = $report_date;
    $report_end_date = date('Y-12-31');
} elseif (isset($_GET['date'])) {
    $report_date = $_GET['date'];
    $report_start_date = $report_date;
    $report_end_date = $report_date;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch All Campaigns
$stmt = $conn->prepare("SELECT id, name, shortcode, click_count FROM campaigns ORDER BY name");
$stmt->execute();
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get advertiser and publisher information for each campaign
$campaign_details = [];
foreach ($campaigns as $campaign) {
    $campaign_id = $campaign['id'];
    
    $stmt = $conn->prepare("SELECT a.name FROM advertisers a JOIN campaign_advertisers ca ON a.id = ca.advertiser_id WHERE ca.campaign_id = ?");
    $stmt->execute([$campaign_id]);
    $advertisers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $conn->prepare("SELECT p.name FROM publishers p JOIN campaign_publishers cp ON p.id = cp.publisher_id WHERE cp.campaign_id = ?");
    $stmt->execute([$campaign_id]);
    $publishers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $campaign_details[] = [
        'id' => $campaign['id'],
        'name' => $campaign['name'],
        'shortcode' => $campaign['shortcode'],
        'total_clicks' => $campaign['click_count'],
        'advertisers' => $advertisers,
        'publishers' => $publishers
    ];
}

// Campaign Daily Click Summary
$stmt = $conn->prepare("
    SELECT psc.campaign_id, psc.publisher_id, SUM(psc.clicks) as total_clicks, p.name as publisher_name, c.name as campaign_name,
           GROUP_CONCAT(DISTINCT a.name) as advertiser_names
    FROM publisher_short_codes psc
    JOIN publishers p ON psc.publisher_id = p.id
    JOIN campaigns c ON psc.campaign_id = c.id
    LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
    LEFT JOIN advertisers a ON ca.advertiser_id = a.id
    WHERE DATE(psc.created_at) BETWEEN ? AND ?
    GROUP BY psc.campaign_id, psc.publisher_id, p.name, c.name
");
$stmt->execute([$report_start_date, $report_end_date]);
$daily_click_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize campaign clicks
$campaign_click_summary = [];
$publisher_click_summary = [];

foreach ($daily_click_data as $click_data) {
    $campaign_id = $click_data['campaign_id'];
    $publisher_id = $click_data['publisher_id'];
    $clicks = $click_data['total_clicks'];
    $campaign_name = $click_data['campaign_name'];
    $publisher_name = $click_data['publisher_name'];
    $advertiser_names = $click_data['advertiser_names'] ? explode(',', $click_data['advertiser_names']) : [];
    
    if (!isset($campaign_click_summary[$campaign_id])) {
        $campaign_click_summary[$campaign_id] = [
            'name' => $campaign_name,
            'total_clicks' => 0,
            'publishers' => [],
            'advertisers' => $advertiser_names
        ];
    }
    $campaign_click_summary[$campaign_id]['total_clicks'] += $clicks;
    $campaign_click_summary[$campaign_id]['publishers'][$publisher_id] = [
        'name' => $publisher_name,
        'clicks' => $clicks,
        'advertisers' => $advertiser_names
    ];
    
    if (!isset($publisher_click_summary[$publisher_id])) {
        $publisher_click_summary[$publisher_id] = [
            'name' => $publisher_name,
            'total_clicks' => 0,
            'campaigns' => []
        ];
    }
    $publisher_click_summary[$publisher_id]['total_clicks'] += $clicks;
    $publisher_click_summary[$publisher_id]['campaigns'][$campaign_id] = [
        'name' => $campaign_name,
        'clicks' => $clicks,
        'advertisers' => $advertiser_names
    ];
}

// Calculate totals
$total_campaigns = count($campaign_click_summary);
$total_publishers = count($publisher_click_summary);
$total_clicks = array_sum(array_column($campaign_click_summary, 'total_clicks'));
$avg_clicks = $total_campaigns > 0 ? round($total_clicks / $total_campaigns, 2) : 0;
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Daily Report</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Daily Report</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print me-2"></i>Print Report
    </button>
</div>

<!-- Date Display Badge -->
<div class="text-center mb-4">
    <span class="badge bg-primary fs-6 px-4 py-2">
        <i class="fas fa-calendar-alt me-2"></i>
        <?php 
        if ($report_start_date === $report_end_date) {
            echo 'Report Date: ' . date('F j, Y', strtotime($report_date));
        } else {
            echo 'Report Period: ' . date('F j, Y', strtotime($report_start_date)) . ' to ' . date('F j, Y', strtotime($report_end_date));
        }
        ?>
    </span>
</div>

<!-- Date Selection -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Select Report Date</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Custom Date</label>
                <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($report_date); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-7">
                <label class="form-label">Quick Select</label>
                <div>
                    <a href="?range=today" class="btn <?php echo $date_range === 'today' ? 'btn-primary' : 'btn-soft-primary'; ?> me-2 mb-2">Today</a>
                    <a href="?range=yesterday" class="btn <?php echo $date_range === 'yesterday' ? 'btn-primary' : 'btn-soft-primary'; ?> me-2 mb-2">Yesterday</a>
                    <a href="?range=this_month" class="btn <?php echo $date_range === 'this_month' ? 'btn-primary' : 'btn-soft-primary'; ?> me-2 mb-2">This Month</a>
                    <a href="?range=this_year" class="btn <?php echo $date_range === 'this_year' ? 'btn-primary' : 'btn-soft-primary'; ?> me-2 mb-2">This Year</a>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Generate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_campaigns); ?></div>
            <div class="stat-card-label">Total Campaigns</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-users"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_publishers); ?></div>
            <div class="stat-card-label">Total Publishers</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_clicks); ?></div>
            <div class="stat-card-label">Total Clicks</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-card-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-card-value"><?php echo number_format($avg_clicks, 2); ?></div>
            <div class="stat-card-label">Avg. Clicks/Campaign</div>
        </div>
    </div>
</div>

<!-- Daily Click Summary Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i>Daily Click Summary
            <span class="badge bg-light text-dark ms-2">
                <?php 
                if ($report_start_date === $report_end_date) {
                    echo date('F j, Y', strtotime($report_date));
                } else {
                    echo date('M j', strtotime($report_start_date)) . ' - ' . date('M j, Y', strtotime($report_end_date));
                }
                ?>
            </span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($campaign_click_summary)): ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                <p class="text-muted">No click data available for this date range.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><i class="fas fa-bullhorn me-1"></i>Campaign</th>
                            <th><i class="fas fa-building me-1"></i>Advertisers</th>
                            <th><i class="fas fa-globe me-1"></i>Publisher</th>
                            <th><i class="fas fa-mouse-pointer me-1"></i>Clicks</th>
                            <th><i class="fas fa-cog me-1"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaign_click_summary as $campaign_id => $summary): ?>
                            <?php if (!empty($summary['publishers'])): ?>
                                <?php foreach ($summary['publishers'] as $publisher_id => $publisher_data): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($summary['name']); ?></strong></td>
                                    <td>
                                        <?php if (!empty($summary['advertisers'])): ?>
                                            <?php echo htmlspecialchars(implode(', ', $summary['advertisers'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-soft-primary"><?php echo htmlspecialchars($publisher_data['name']); ?></span></td>
                                    <td><span class="badge bg-primary fs-6"><?php echo number_format($publisher_data['clicks']); ?></span></td>
                                    <td>
                                        <button class="btn btn-soft-primary btn-sm" onclick="printRow('<?php echo htmlspecialchars($summary['name']); ?>', '<?php echo htmlspecialchars(implode(', ', $summary['advertisers'] ?? ['None'])); ?>', '<?php echo htmlspecialchars($publisher_data['name']); ?>', '<?php echo $publisher_data['clicks']; ?>')">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($summary['name']); ?></strong></td>
                                    <td>
                                        <?php if (!empty($summary['advertisers'])): ?>
                                            <?php echo htmlspecialchars(implode(', ', $summary['advertisers'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-soft-warning">No publishers</span></td>
                                    <td><span class="badge bg-secondary fs-6">0</span></td>
                                    <td>
                                        <button class="btn btn-soft-primary btn-sm" onclick="printRow('<?php echo htmlspecialchars($summary['name']); ?>', '<?php echo htmlspecialchars(implode(', ', $summary['advertisers'] ?? ['None'])); ?>', 'No publishers', '0')">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function printRow(campaign, advertisers, publisher, clicks) {
    const printContent = `
        <div style="font-family: 'Inter', Arial, sans-serif; padding: 30px; max-width: 800px; margin: 0 auto;">
            <h2 style="color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 25px;">
                <i class="fas fa-chart-bar"></i> Campaign Report
            </h2>
            <div style="margin: 20px 0; padding: 20px; background: #f8fafc; border-radius: 12px; border-left: 4px solid #667eea;">
                <p style="margin: 0;"><strong>Report Date:</strong> <?php echo $report_start_date === $report_end_date ? date('F j, Y', strtotime($report_date)) : date('F j, Y', strtotime($report_start_date)) . ' to ' . date('F j, Y', strtotime($report_end_date)); ?></p>
            </div>
            <table style="width: 100%; border-collapse: collapse; margin: 25px 0; border-radius: 10px; overflow: hidden;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <th style="padding: 15px; text-align: left;">Campaign</th>
                        <th style="padding: 15px; text-align: left;">Advertisers</th>
                        <th style="padding: 15px; text-align: left;">Publisher</th>
                        <th style="padding: 15px; text-align: left;">Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: #fff;">
                        <td style="padding: 15px; border: 1px solid #e2e8f0;"><strong>${campaign}</strong></td>
                        <td style="padding: 15px; border: 1px solid #e2e8f0;">${advertisers}</td>
                        <td style="padding: 15px; border: 1px solid #e2e8f0;">${publisher}</td>
                        <td style="padding: 15px; border: 1px solid #e2e8f0; font-weight: bold; color: #667eea;">${clicks}</td>
                    </tr>
                </tbody>
            </table>
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 12px;">
                <p>Generated on: <?php echo date('F j, Y g:i A'); ?></p>
                <p>Ads Platform - Super Admin Report</p>
            </div>
        </div>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Campaign Report</title></head><body>' + printContent + '</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>

<style>
@media print {
    .sidebar, .top-navbar, .page-header button, .card-header, form, .btn { display: none !important; }
    .main-content { margin-left: 0 !important; padding-top: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
