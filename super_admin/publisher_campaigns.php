<?php
// super_admin/publisher_campaigns.php - View Publisher Campaigns
session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Get all publishers with their campaigns and publisher-specific tracking info
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
        
    // Get publishers with their campaigns and publisher-specific short codes
    $stmt = $conn->prepare("
        SELECT 
            p.id as publisher_id,
            p.name as publisher_name,
            p.email as publisher_email,
            p.website as publisher_website,
            c.id as campaign_id,
            c.name as campaign_name,
            c.shortcode as base_shortcode,
            c.start_date,
            c.end_date,
            c.campaign_type,
            c.click_count as total_campaign_clicks,
            c.status,
            psc.short_code as publisher_shortcode,
            COALESCE(psc.clicks, 0) as publisher_clicks
        FROM publishers p
        LEFT JOIN campaign_publishers cp ON p.id = cp.publisher_id
        LEFT JOIN campaigns c ON cp.campaign_id = c.id
        LEFT JOIN publisher_short_codes psc ON c.id = psc.campaign_id AND p.id = psc.publisher_id
        ORDER BY p.name, c.name
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    // Group results by publisher
    $publisher_campaigns = [];
    foreach ($results as $row) {
        $publisher_id = $row['publisher_id'];
        if (!isset($publisher_campaigns[$publisher_id])) {
            $publisher_campaigns[$publisher_id] = [
                'id' => $row['publisher_id'],
                'name' => $row['publisher_name'],
                'email' => $row['publisher_email'],
                'website' => $row['publisher_website'],
                'campaigns' => []
            ];
        }
            
        if ($row['campaign_id']) {
            $publisher_campaigns[$publisher_id]['campaigns'][] = [
                'id' => $row['campaign_id'],
                'name' => $row['campaign_name'],
                'base_shortcode' => $row['base_shortcode'],
                'publisher_shortcode' => $row['publisher_shortcode'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'type' => $row['campaign_type'],
                'total_clicks' => $row['total_campaign_clicks'],
                'publisher_clicks' => $row['publisher_clicks'],
                'status' => $row['status']
            ];
        }
    }
        
} catch (PDOException $e) {
    $error = "Error loading publisher campaigns: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Publisher Campaigns - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .card {
            border: none;
            border-radius: 10px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }
        .shadow {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: calc(100vh - 56px);
            position: sticky;
            top: 56px;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 56px);
            overflow-x: hidden;
            overflow-y: auto;
        }
        .nav-link {
            color: #495057;
            padding: 0.75rem 1rem;
            border-left: 3px solid transparent;
        }
        .nav-link:hover {
            color: #0d6efd;
            background-color: #e9ecef;
        }
        .nav-link.active {
            color: #0d6efd;
            background-color: #e9ecef;
            border-left: 3px solid #0d6efd;
        }
        .publisher-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .publisher-card .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <i class="fas fa-chart-line me-2 text-primary"></i>
                <span class="fw-bold text-dark">Ads Platform</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <span class="navbar-text">
                            <i class="fas fa-user-circle me-1"></i>
                            Welcome, <span class="fw-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 d-none d-lg-block bg-light sidebar p-0">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'campaigns.php' ? 'active' : ''; ?>" href="campaigns.php">
                                <i class="fas fa-bullhorn me-2"></i>
                                <span>Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'advertisers.php' ? 'active' : ''; ?>" href="advertisers.php">
                                <i class="fas fa-users me-2"></i>
                                <span>Advertisers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'publishers.php' ? 'active' : ''; ?>" href="publishers.php">
                                <i class="fas fa-share-alt me-2"></i>
                                <span>Publishers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admins.php' ? 'active' : ''; ?>" href="admins.php">
                                <i class="fas fa-user-shield me-2"></i>
                                <span>Admins</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'advertiser_campaigns.php' ? 'active' : ''; ?>" href="advertiser_campaigns.php">
                                <i class="fas fa-ad me-2"></i>
                                <span>Advertiser Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'publisher_campaigns.php' ? 'active' : ''; ?>" href="publisher_campaigns.php">
                                <i class="fas fa-link me-2"></i>
                                <span>Publisher Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'all_publishers_daily_clicks.php' ? 'active' : ''; ?>" href="all_publishers_daily_clicks.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                <span>All Publishers Stats</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'payment_reports.php' ? 'active' : ''; ?>" href="payment_reports.php">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                <span>Payment Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Mobile Sidebar Toggle -->
            <div class="col-12 d-lg-none bg-light p-2">
                <button class="btn btn-primary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <i class="fas fa-bars me-2"></i>Menu
                </button>
            </div>
            
            <!-- Mobile Offcanvas Sidebar -->
            <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
                <div class="offcanvas-header bg-light">
                    <h5 class="offcanvas-title">Navigation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'campaigns.php' ? 'active' : ''; ?>" href="campaigns.php">
                                <i class="fas fa-bullhorn me-2"></i>
                                <span>Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'advertisers.php' ? 'active' : ''; ?>" href="advertisers.php">
                                <i class="fas fa-users me-2"></i>
                                <span>Advertisers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'publishers.php' ? 'active' : ''; ?>" href="publishers.php">
                                <i class="fas fa-share-alt me-2"></i>
                                <span>Publishers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admins.php' ? 'active' : ''; ?>" href="admins.php">
                                <i class="fas fa-user-shield me-2"></i>
                                <span>Admins</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'advertiser_campaigns.php' ? 'active' : ''; ?>" href="advertiser_campaigns.php">
                                <i class="fas fa-ad me-2"></i>
                                <span>Advertiser Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'publisher_campaigns.php' ? 'active' : ''; ?>" href="publisher_campaigns.php">
                                <i class="fas fa-link me-2"></i>
                                <span>Publisher Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'payment_reports.php' ? 'active' : ''; ?>" href="payment_reports.php">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                <span>Payment Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <main class="col-lg-10 ms-sm-auto px-md-4 py-3">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h3 mb-0 text-dark">View All Publisher Reports</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Publisher Campaigns</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                
                <p class="text-muted">This page shows all publishers and their assigned campaigns with publisher-specific tracking information.</p>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($publisher_campaigns)): ?>
                    <!-- Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="publisherFilter" class="form-control" placeholder="Filter by publisher name...">
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="campaignFilter" class="form-control" placeholder="Filter by campaign name...">
                        </div>
                    </div>
                    
                    <!-- Summary Statistics -->
                    <?php 
                    $total_publishers = count($publisher_campaigns);
                    $total_campaigns = 0;
                    $total_publisher_clicks = 0;
                    $total_campaign_clicks = 0;
                    
                    foreach ($publisher_campaigns as $publisher) {
                        $total_campaigns += count($publisher['campaigns']);
                        foreach ($publisher['campaigns'] as $campaign) {
                            $total_publisher_clicks += $campaign['publisher_clicks'];
                            $total_campaign_clicks += $campaign['total_clicks'];
                        }
                    }
                    ?>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-primary shadow">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $total_publishers; ?></h5>
                                    <p class="card-text">Total Publishers</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-success shadow">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $total_campaigns; ?></h5>
                                    <p class="card-text">Total Campaigns</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-info shadow">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $total_publisher_clicks; ?></h5>
                                    <p class="card-text">Total Publisher Clicks</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-warning shadow">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $total_campaign_clicks; ?></h5>
                                    <p class="card-text">Total Campaign Clicks</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($publisher_campaigns)): ?>
                    <div class="alert alert-info">No publishers or campaigns found.</div>
                <?php else: ?>
                    <?php foreach ($publisher_campaigns as $publisher): ?>
                        <div class="card mb-4 publisher-card">
                            <div class="card-header">
                                <h5><?php echo htmlspecialchars($publisher['name']); ?></h5>
                                <p class="mb-0"><?php echo htmlspecialchars($publisher['email']); ?></p>
                                <?php if (!empty($publisher['website'])): ?>
                                    <p class="mb-0"><a href="<?php echo htmlspecialchars($publisher['website']); ?>" target="_blank"><?php echo htmlspecialchars($publisher['website']); ?></a></p>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (empty($publisher['campaigns'])): ?>
                                    <p class="text-muted">No campaigns assigned to this publisher.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Campaign Name</th>
                                                    <th>Base Short Code</th>
                                                    <th>Publisher Short Code</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Type</th>
                                                    <th>Total Clicks</th>
                                                    <th>Publisher Clicks</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($publisher['campaigns'] as $campaign): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($campaign['base_shortcode']); ?></td>
                                                        <td><?php echo htmlspecialchars($campaign['publisher_shortcode'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($campaign['start_date']); ?></td>
                                                        <td><?php echo htmlspecialchars($campaign['end_date']); ?></td>
                                                        <td><?php echo htmlspecialchars($campaign['type']); ?></td>
                                                        <td><?php echo $campaign['total_clicks']; ?></td>
                                                        <td><?php echo $campaign['publisher_clicks']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                                <?php echo ucfirst($campaign['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add collapsible functionality to publisher cards
        document.addEventListener('DOMContentLoaded', function() {
            const cardHeaders = document.querySelectorAll('.publisher-card .card-header');
            
            cardHeaders.forEach(function(header) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    const cardBody = this.nextElementSibling;
                    const icon = this.querySelector('.toggle-icon') || this.appendChild(createToggleIcon());
                    
                    if (cardBody.style.display === 'none') {
                        cardBody.style.display = 'block';
                        icon.textContent = '▼';
                    } else {
                        cardBody.style.display = 'none';
                        icon.textContent = '▶';
                    }
                });
            });
            
            function createToggleIcon() {
                const icon = document.createElement('span');
                icon.className = 'toggle-icon ms-2';
                icon.textContent = '▼';
                icon.style.float = 'right';
                return icon;
            }
            
            // Add toggle all functionality
            const toggleAll = document.createElement('button');
            toggleAll.className = 'btn btn-sm btn-outline-secondary mb-3';
            toggleAll.textContent = 'Toggle All Campaigns';
            toggleAll.addEventListener('click', function() {
                const cardBodies = document.querySelectorAll('.publisher-card .card-body');
                const icons = document.querySelectorAll('.toggle-icon');
                
                let shouldExpand = false;
                // Check if any are currently collapsed
                for (let body of cardBodies) {
                    if (body.style.display === 'none') {
                        shouldExpand = true;
                        break;
                    }
                }
                
                cardBodies.forEach(function(body, index) {
                    if (shouldExpand) {
                        body.style.display = 'block';
                        if (icons[index]) icons[index].textContent = '▼';
                    } else {
                        body.style.display = 'none';
                        if (icons[index]) icons[index].textContent = '▶';
                    }
                });
                
                this.textContent = shouldExpand ? 'Collapse All' : 'Expand All';
            });
            
            if (document.querySelector('main')) {
                document.querySelector('main').insertBefore(toggleAll, document.querySelector('main').firstChild);
            }
            
            // Add filtering functionality
            const publisherFilter = document.getElementById('publisherFilter');
            const campaignFilter = document.getElementById('campaignFilter');
            
            if (publisherFilter && campaignFilter) {
                publisherFilter.addEventListener('input', filterPublishers);
                campaignFilter.addEventListener('input', filterPublishers);
            }
            
            function filterPublishers() {
                const publisherText = publisherFilter.value.toLowerCase();
                const campaignText = campaignFilter.value.toLowerCase();
                
                const publisherCards = document.querySelectorAll('.publisher-card');
                
                publisherCards.forEach(function(card) {
                    const publisherName = card.querySelector('.card-header h5').textContent.toLowerCase();
                    const campaignRows = card.querySelectorAll('tbody tr');
                    
                    let publisherMatch = publisherText === '' || publisherName.includes(publisherText);
                    let campaignMatch = campaignText === '';
                    
                    // Check if any campaign matches
                    let showCard = publisherMatch;
                    
                    if (campaignText !== '') {
                        campaignRows.forEach(function(row) {
                            const campaignName = row.cells[0].textContent.toLowerCase();
                            if (campaignName.includes(campaignText)) {
                                campaignMatch = true;
                                showCard = true;
                            }
                        });
                    } else if (publisherMatch) {
                        showCard = true;
                    }
                    
                    card.style.display = showCard ? 'block' : 'none';
                    
                    // Filter individual campaign rows if needed
                    if (showCard && campaignText !== '') {
                        campaignRows.forEach(function(row) {
                            const campaignName = row.cells[0].textContent.toLowerCase();
                            row.style.display = campaignName.includes(campaignText) ? 'table-row' : 'none';
                        });
                    } else if (showCard) {
                        // Show all rows if no campaign filter
                        campaignRows.forEach(function(row) {
                            row.style.display = 'table-row';
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>