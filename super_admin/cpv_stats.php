<?php
// super_admin/cpv_stats.php - View detailed click stats for a CPV campaign
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$campaign_id = $_GET['id'] ?? 0;

$db = Database::getInstance();
$conn = $db->getConnection();

// Get campaign details
$stmt = $conn->prepare("SELECT * FROM cpv_campaigns WHERE id = ? AND created_by = ?");
$stmt->execute([$campaign_id, $_SESSION['user_id']]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    header('Location: cpv.php');
    exit();
}

// Get click stats
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_clicks,
        SUM(CASE WHEN is_duplicate = 0 THEN 1 ELSE 0 END) as original_clicks,
        SUM(CASE WHEN is_duplicate = 1 THEN 1 ELSE 0 END) as duplicate_clicks
    FROM cpv_clicks WHERE campaign_id = ?
");
$stmt->execute([$campaign_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get daily breakdown
$stmt = $conn->prepare("
    SELECT 
        DATE(clicked_at) as click_date,
        COUNT(*) as total,
        SUM(CASE WHEN is_duplicate = 0 THEN 1 ELSE 0 END) as original,
        SUM(CASE WHEN is_duplicate = 1 THEN 1 ELSE 0 END) as duplicate
    FROM cpv_clicks 
    WHERE campaign_id = ?
    GROUP BY DATE(clicked_at)
    ORDER BY click_date DESC
    LIMIT 30
");
$stmt->execute([$campaign_id]);
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent clicks with IP
$stmt = $conn->prepare("
    SELECT ip_address, is_duplicate, clicked_at, user_agent, referer
    FROM cpv_clicks 
    WHERE campaign_id = ?
    ORDER BY clicked_at DESC
    LIMIT 100
");
$stmt->execute([$campaign_id]);
$recent_clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$base_url .= dirname(dirname($_SERVER['PHP_SELF'])) . '/cpv_redirect.php?c=' . $campaign['short_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPV Stats - <?php echo htmlspecialchars($campaign['campaign_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card { border: none; border-radius: 10px; }
        .card-header { background-color: #f8f9fa; font-weight: 600; }
        .stat-card { text-align: center; padding: 20px; }
        .stat-number { font-size: 2.5rem; font-weight: bold; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-chart-line me-2"></i>Ads Platform</a>
            <a class="btn btn-outline-light btn-sm" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="cpv.php" class="btn btn-outline-secondary mb-2"><i class="fas fa-arrow-left me-1"></i>Back to Campaigns</a>
                <h2><?php echo htmlspecialchars($campaign['campaign_name']); ?></h2>
                <p class="text-muted mb-0">
                    Short URL: <code><?php echo $base_url; ?></code>
                    <br>Duration: <?php echo date('d M Y', strtotime($campaign['start_date'])); ?> - <?php echo date('d M Y', strtotime($campaign['end_date'])); ?>
                </p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card shadow stat-card bg-success text-white">
                    <div class="stat-number"><?php echo $stats['original_clicks'] ?? 0; ?></div>
                    <div>Original Clicks</div>
                    <small class="opacity-75">Unique IPs per day</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow stat-card bg-warning">
                    <div class="stat-number"><?php echo $stats['duplicate_clicks'] ?? 0; ?></div>
                    <div>Duplicate Clicks</div>
                    <small class="opacity-75">Same IP same day</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow stat-card bg-primary text-white">
                    <div class="stat-number"><?php echo $stats['total_clicks'] ?? 0; ?></div>
                    <div>Total Clicks</div>
                    <small class="opacity-75">All clicks combined</small>
                </div>
            </div>
        </div>

        <!-- Daily Breakdown -->
        <div class="card shadow mb-4">
            <div class="card-header"><i class="fas fa-calendar-alt me-2"></i>Daily Breakdown (Last 30 Days)</div>
            <div class="card-body">
                <?php if (empty($daily_stats)): ?>
                    <p class="text-muted text-center py-4">No clicks recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th class="text-center">Original</th>
                                    <th class="text-center">Duplicate</th>
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daily_stats as $day): ?>
                                    <tr>
                                        <td><?php echo date('d M Y (D)', strtotime($day['click_date'])); ?></td>
                                        <td class="text-center"><span class="badge bg-success"><?php echo $day['original']; ?></span></td>
                                        <td class="text-center"><span class="badge bg-warning text-dark"><?php echo $day['duplicate']; ?></span></td>
                                        <td class="text-center"><span class="badge bg-primary"><?php echo $day['total']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Clicks with IP -->
        <div class="card shadow">
            <div class="card-header"><i class="fas fa-mouse-pointer me-2"></i>Recent Clicks (Last 100)</div>
            <div class="card-body">
                <?php if (empty($recent_clicks)): ?>
                    <p class="text-muted text-center py-4">No clicks recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>IP Address</th>
                                    <th>Type</th>
                                    <th>Date & Time</th>
                                    <th>Referer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_clicks as $click): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($click['ip_address']); ?></code></td>
                                        <td>
                                            <?php if ($click['is_duplicate']): ?>
                                                <span class="badge bg-warning text-dark">Duplicate</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Original</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d M Y H:i:s', strtotime($click['clicked_at'])); ?></td>
                                        <td class="text-truncate" style="max-width:250px;" title="<?php echo htmlspecialchars($click['referer']); ?>">
                                            <?php echo htmlspecialchars($click['referer'] ?: '-'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
