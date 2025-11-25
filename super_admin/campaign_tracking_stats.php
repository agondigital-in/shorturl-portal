<?php
// super_admin/campaign_tracking_stats.php - Campaign Tracking Statistics
session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Detect environment and set base URL
$is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
if ($is_localhost) {
    $base_url = 'http://localhost/shorturl/c/';
} else {
    $base_url = 'https://tracking.agondigital.in/c/';
}

// Get campaign ID from URL parameter
$campaign_id = $_GET['id'] ?? '';

if (empty($campaign_id)) {
    header('Location: campaigns.php');
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get campaign details
    $stmt = $conn->prepare("
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT a.name) as advertiser_names
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN advertisers a ON ca.advertiser_id = a.id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        header('Location: campaigns.php');
        exit();
    }
    
    // Get publisher tracking statistics
    $stmt = $conn->prepare("
        SELECT p.name as publisher_name, 
               psc.short_code,
               COALESCE(psc.clicks, 0) as clicks
        FROM publishers p
        JOIN campaign_publishers cp ON p.id = cp.publisher_id
        JOIN publisher_short_codes psc ON cp.campaign_id = psc.campaign_id AND cp.publisher_id = psc.publisher_id
        WHERE cp.campaign_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$campaign_id]);
    $publisher_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading campaign data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Tracking Statistics - Ads Platform</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Theme -->
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#"><i class="fas fa-chart-line me-2"></i>Ads Platform</a>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid" style="margin-top: 80px;">
        <div class="row g-4 p-4">
            <!-- Sidebar -->
            <div class="col-md-2">
                <div class="sidebar-nav">
                    <a href="dashboard.php" class="nav-link-custom"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="campaigns.php" class="nav-link-custom"><i class="fas fa-bullhorn"></i> Campaigns</a>
                    <a href="advertisers.php" class="nav-link-custom"><i class="fas fa-users"></i> Advertisers</a>
                    <a href="publishers.php" class="nav-link-custom"><i class="fas fa-network-wired"></i> Publishers</a>
                    <a href="admins.php" class="nav-link-custom"><i class="fas fa-user-shield"></i> Admins</a>
                    <a href="payment_reports.php" class="nav-link-custom"><i class="fas fa-file-invoice-dollar"></i> Reports</a>
                    <a href="all_publishers_daily_clicks.php" class="nav-link-custom"><i class="fas fa-chart-bar"></i> All Publishers Stats</a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1 text-dark">Campaign Statistics</h2>
                        <p class="text-secondary mb-0">Detailed performance tracking for <?php echo htmlspecialchars($campaign['name']); ?></p>
                    </div>
                    <a href="campaigns.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Campaigns
                    </a>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <!-- Campaign Details Card -->
                <div class="modern-card mb-4">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h5 class="mb-0 fw-bold">Campaign Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="small text-secondary fw-bold text-uppercase">Base Short Code</label>
                                    <div class="d-flex align-items-center mt-1">
                                        <code class="bg-light px-2 py-1 rounded border me-2"><?php echo htmlspecialchars($campaign['shortcode']); ?></code>
                                        <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyToClipboard('<?php echo rtrim($base_url, '/c/') . '/' . htmlspecialchars($campaign['shortcode']); ?>', this)">
                                            <i class="fas fa-copy me-1"></i>Copy Link
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-secondary fw-bold text-uppercase">Advertisers</label>
                                    <div class="fw-medium text-dark mt-1"><?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?></div>
                                </div>
                                <div>
                                    <label class="small text-secondary fw-bold text-uppercase">Start Date</label>
                                    <div class="fw-medium text-dark mt-1"><?php echo htmlspecialchars($campaign['start_date']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="small text-secondary fw-bold text-uppercase">Campaign Type</label>
                                    <div class="mt-1"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($campaign['campaign_type']); ?></span></div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-secondary fw-bold text-uppercase">Target URL</label>
                                    <div class="mt-1">
                                        <a href="<?php echo htmlspecialchars($campaign['target_url']); ?>" target="_blank" class="text-decoration-none text-truncate d-block">
                                            <?php echo htmlspecialchars($campaign['target_url']); ?> <i class="fas fa-external-link-alt small ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                <div>
                                    <label class="small text-secondary fw-bold text-uppercase">End Date</label>
                                    <div class="fw-medium text-dark mt-1"><?php echo htmlspecialchars($campaign['end_date']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Publisher Stats -->
                <div class="modern-card">
                    <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Publisher Tracking Statistics</h5>
                        <a href="publisher_daily_clicks.php?id=<?php echo $campaign_id; ?>" class="btn btn-sm btn-info text-white">
                            <i class="fas fa-calendar-alt me-2"></i>View Daily Clicks
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($publisher_stats)): ?>
                            <div class="p-5 text-center">
                                <div class="mb-3">
                                    <i class="fas fa-users-slash fa-3x text-secondary opacity-50"></i>
                                </div>
                                <h5 class="text-secondary">No publishers assigned</h5>
                                <p class="text-muted">Assign publishers to this campaign to see tracking stats.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Publisher</th>
                                            <th>Short Code</th>
                                            <th>Tracking Link</th>
                                            <th class="text-end">Clicks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_clicks = 0;
                                        foreach ($publisher_stats as $stats): 
                                            $total_clicks += $stats['clicks'];
                                        ?>
                                            <tr>
                                                <td class="fw-medium text-dark"><?php echo htmlspecialchars($stats['publisher_name']); ?></td>
                                                <td class="font-monospace text-secondary"><?php echo htmlspecialchars($stats['short_code']); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <code class="small bg-light px-2 py-1 rounded border text-truncate" style="max-width: 300px;"><?php echo $base_url . htmlspecialchars($stats['short_code']); ?></code>
                                                        <button class="btn btn-sm btn-link text-primary ms-2 p-0 copy-btn" onclick="copyToClipboard('<?php echo $base_url . htmlspecialchars($stats['short_code']); ?>', this)">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="text-end fw-bold"><?php echo number_format($stats['clicks']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light border-top">
                                            <td colspan="3" class="fw-bold text-end">Total Clicks</td>
                                            <td class="fw-bold text-end text-primary"><?php echo number_format($total_clicks); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text, button) {
            // Create a temporary input element
            const tempInput = document.createElement('input');
            tempInput.style.position = 'absolute';
            tempInput.style.left = '-1000px';
            tempInput.value = text;
            document.body.appendChild(tempInput);
            
            // Select and copy the text
            tempInput.select();
            document.execCommand('copy');
            
            // Remove the temporary input
            document.body.removeChild(tempInput);
            
            // Visual feedback
            const originalHtml = button.innerHTML;
            const isIconBtn = button.classList.contains('btn-link');
            
            if (isIconBtn) {
                button.innerHTML = '<i class="fas fa-check text-success"></i>';
            } else {
                button.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-success', 'text-white');
            }
            
            // Reset button after 2 seconds
            setTimeout(() => {
                button.innerHTML = originalHtml;
                if (!isIconBtn) {
                    button.classList.remove('btn-success', 'text-white');
                    button.classList.add('btn-outline-primary');
                }
            }, 2000);
        }
    </script>
</body>
</html>