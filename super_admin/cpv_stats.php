<?php
// super_admin/cpv_stats.php - View detailed click stats for a CPV campaign
session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$campaign_id = $_GET['id'] ?? 0;

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM cpv_campaigns WHERE id = ? AND created_by = ?");
$stmt->execute([$campaign_id, $_SESSION['user_id']]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect BEFORE any HTML output
if (!$campaign) {
    header('Location: cpv.php');
    exit();
}

// Now include header after redirect check
$page_title = 'CPV Stats - ' . $campaign['campaign_name'];
require_once 'includes/header.php';

$stmt = $conn->prepare("SELECT COUNT(*) as total_clicks,
    SUM(CASE WHEN is_duplicate = 0 THEN 1 ELSE 0 END) as original_clicks,
    SUM(CASE WHEN is_duplicate = 1 THEN 1 ELSE 0 END) as duplicate_clicks
    FROM cpv_clicks WHERE campaign_id = ?");
$stmt->execute([$campaign_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT DATE(clicked_at) as click_date, COUNT(*) as total,
    SUM(CASE WHEN is_duplicate = 0 THEN 1 ELSE 0 END) as original,
    SUM(CASE WHEN is_duplicate = 1 THEN 1 ELSE 0 END) as duplicate
    FROM cpv_clicks WHERE campaign_id = ?
    GROUP BY DATE(clicked_at) ORDER BY click_date DESC LIMIT 30");
$stmt->execute([$campaign_id]);
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT ip_address, is_duplicate, clicked_at, user_agent, referer
    FROM cpv_clicks WHERE campaign_id = ? ORDER BY clicked_at DESC LIMIT 100");
$stmt->execute([$campaign_id]);
$recent_clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$base_url .= dirname(dirname($_SERVER['PHP_SELF'])) . '/cpv_redirect.php?c=' . $campaign['short_code'];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo htmlspecialchars($campaign['campaign_name']); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="cpv.php">CPV Campaigns</a></li>
                <li class="breadcrumb-item active">Stats</li>
            </ol>
        </nav>
    </div>
    <a href="cpv.php" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back to Campaigns</a>
</div>

<!-- Campaign Info -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Short URL:</strong></p>
                <code><?php echo $base_url; ?></code>
                <button class="btn btn-sm btn-link" onclick="navigator.clipboard.writeText('<?php echo $base_url; ?>'); alert('Copied!');">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Duration:</strong></p>
                <span><?php echo date('M d, Y', strtotime($campaign['start_date'])); ?> - <?php echo date('M d, Y', strtotime($campaign['end_date'])); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-card-value"><?php echo $stats['original_clicks'] ?? 0; ?></div>
            <div class="stat-card-label">Original Clicks</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="stat-card-icon"><i class="fas fa-copy"></i></div>
            <div class="stat-card-value"><?php echo $stats['duplicate_clicks'] ?? 0; ?></div>
            <div class="stat-card-label">Duplicate Clicks</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-card-value"><?php echo $stats['total_clicks'] ?? 0; ?></div>
            <div class="stat-card-label">Total Clicks</div>
        </div>
    </div>
</div>

<!-- Daily Breakdown -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Daily Breakdown (Last 30 Days)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($daily_stats)): ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                <p class="text-muted">No clicks recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
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
                                <td class="text-center"><span class="badge badge-soft-success"><?php echo $day['original']; ?></span></td>
                                <td class="text-center"><span class="badge badge-soft-warning"><?php echo $day['duplicate']; ?></span></td>
                                <td class="text-center"><span class="badge badge-soft-primary"><?php echo $day['total']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Clicks -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-mouse-pointer me-2"></i>Recent Clicks (Last 100)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recent_clicks)): ?>
            <div class="text-center py-5">
                <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
                <p class="text-muted">No clicks recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
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
                                        <span class="badge badge-soft-warning">Duplicate</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-success">Original</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y H:i:s', strtotime($click['clicked_at'])); ?></td>
                                <td class="text-truncate" style="max-width:250px;">
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

<?php require_once 'includes/footer.php'; ?>
