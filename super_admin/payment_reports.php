<?php
// super_admin/payment_reports.php - Payment Reports
session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Handle lead updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_leads') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $target_leads = $_POST['target_leads'] ?? 0;
    $validated_leads = $_POST['validated_leads'] ?? 0;
    
    // Validate inputs
    if (is_numeric($target_leads) && is_numeric($validated_leads) && $target_leads >= 0 && $validated_leads >= 0 && $validated_leads <= $target_leads) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("UPDATE campaigns SET target_leads = ?, validated_leads = ? WHERE id = ?");
            $stmt->execute([$target_leads, $validated_leads, $campaign_id]);
            
            $success = "Lead information updated successfully.";
        } catch (PDOException $e) {
            $error = "Error updating lead information: " . $e->getMessage();
        }
    } else {
        if (!is_numeric($target_leads) || !is_numeric($validated_leads) || $target_leads < 0 || $validated_leads < 0) {
            $error = "Invalid lead values. Please enter valid non-negative numbers.";
        } else {
            $error = "Validated leads cannot exceed target leads.";
        }
    }
}

// Handle payment status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_payment_status') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $current_status = $_POST['current_status'] ?? '';
    
    // Toggle status
    $new_status = ($current_status === 'pending') ? 'completed' : 'pending';
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("UPDATE campaigns SET payment_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $campaign_id]);
        
        $success = "Payment status updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating payment status: " . $e->getMessage();
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_date_type = $_GET['date_type'] ?? '';
$filter_date_value = $_GET['date_value'] ?? '';

// Handle quick date filters
$quick_filter = $_GET['quick_filter'] ?? '';
if (!empty($quick_filter)) {
    $filter_date_type = $quick_filter;
    switch ($quick_filter) {
        case 'today':
            $filter_date_value = date('Y-m-d');
            break;
        case 'yesterday':
            $filter_date_value = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'month':
            $filter_date_value = date('Y-m');
            break;
        case 'year':
            $filter_date_value = date('Y');
            break;
    }
}

// Get all campaigns with advertiser and publisher info
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "
        SELECT 
            c.id,
            c.name as campaign_name,
            c.shortcode,
            c.advertiser_payout,
            c.publisher_payout,
            c.campaign_type,
            c.click_count,
            c.target_leads,
            c.validated_leads,
            c.payment_status,
            c.start_date,
            c.end_date,
            c.created_at,
            GROUP_CONCAT(DISTINCT a.name) as advertiser_names,
            GROUP_CONCAT(DISTINCT p.name) as publisher_names
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN advertisers a ON ca.advertiser_id = a.id
        LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
        LEFT JOIN publishers p ON cp.publisher_id = p.id
        ";
    
    // Add filter conditions
    $where_conditions = [];
    $params = [];
    
    if ($filter_status !== 'all') {
        $where_conditions[] = "c.payment_status = ?";
        $params[] = $filter_status;
    }
    
    // Add date filter conditions
    if (!empty($filter_date_type) && !empty($filter_date_value)) {
        switch ($filter_date_type) {
            case 'day':
            case 'today':
            case 'yesterday':
                $where_conditions[] = "DATE(c.created_at) = ?";
                $params[] = $filter_date_value;
                break;
            case 'month':
                $where_conditions[] = "DATE_FORMAT(c.created_at, '%Y-%m') = ?";
                $params[] = $filter_date_value;
                break;
            case 'year':
                $where_conditions[] = "YEAR(c.created_at) = ?";
                $params[] = $filter_date_value;
                break;
        }
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    
    $sql .= " GROUP BY c.id ORDER BY c.created_at DESC ";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_advertiser_payout = 0;
    $total_publisher_payout = 0;
    $pending_payments = 0;
    $completed_payments = 0;
    
    foreach ($campaigns as $campaign) {
        if ($campaign['payment_status'] === 'pending') {
            $pending_payments++;
        } else {
            $completed_payments++;
        }
        
        $total_advertiser_payout += $campaign['advertiser_payout'];
        $total_publisher_payout += $campaign['publisher_payout'];
    }
    
} catch (PDOException $e) {
    $error = "Error loading payment reports: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reports - Ads Platform</title>
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
        .summary-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .filter-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .modal-content {
            border: 1px solid rgba(0, 0, 0, 0.2);
        }
        .quick-filter-btn {
            margin-right: 5px;
            margin-bottom: 5px;
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
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'payment_reports.php' ? 'active' : ''; ?>" href="payment_reports.php">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                <span>Payment Reports</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'all_publishers_daily_clicks.php' ? 'active' : ''; ?>" href="all_publishers_daily_clicks.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                <span>All Publishers Stats</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'cpv.php' ? 'active' : ''; ?>" href="cpv.php">
                                <i class="fas fa-compress-alt me-2"></i>
                                <span>CPV Campaigns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'cpv_report.php' ? 'active' : ''; ?>" href="cpv_report.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                <span>CPV Report</span>
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
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'all_publishers_daily_clicks.php' ? 'active' : ''; ?>" href="all_publishers_daily_clicks.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                <span>All Publishers Stats</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <main class="col-lg-10 ms-sm-auto px-md-4 py-3">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h3 mb-0 text-dark">Payment Reports</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Payment Reports</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <!-- Quick Filter Section -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card filter-card shadow">
                            <div class="card-body">
                                <h5 class="card-title">Quick Filters</h5>
                                <div class="mb-3">
                                    <a href="?quick_filter=today" class="btn btn-outline-primary quick-filter-btn <?php echo ($filter_date_type === 'today') ? 'active' : ''; ?>">
                                        <i class="fas fa-calendar-day me-1"></i>Today
                                    </a>
                                    <a href="?quick_filter=yesterday" class="btn btn-outline-primary quick-filter-btn <?php echo ($filter_date_type === 'yesterday') ? 'active' : ''; ?>">
                                        <i class="fas fa-calendar-day me-1"></i>Yesterday
                                    </a>
                                    <a href="?quick_filter=month" class="btn btn-outline-primary quick-filter-btn <?php echo ($filter_date_type === 'month') ? 'active' : ''; ?>">
                                        <i class="fas fa-calendar-week me-1"></i>This Month
                                    </a>
                                    <a href="?quick_filter=year" class="btn btn-outline-primary quick-filter-btn <?php echo ($filter_date_type === 'year') ? 'active' : ''; ?>">
                                        <i class="fas fa-calendar-alt me-1"></i>This Year
                                    </a>
                                    <a href="payment_reports.php" class="btn btn-outline-secondary quick-filter-btn">
                                        <i class="fas fa-times me-1"></i>Clear All
                                    </a>
                                </div>
                                
                                <!-- Custom Filter Section -->
                                <h5 class="card-title mt-3">Custom Filters</h5>
                                <form method="GET" class="row" id="customFilterForm">
                                    <div class="col-md-3 mb-3">
                                        <select name="status" class="form-select" onchange="this.form.submit()">
                                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending Payments</option>
                                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed Payments</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <select name="date_type" class="form-select" onchange="updateDateInput()">
                                            <option value="" <?php echo empty($filter_date_type) ? 'selected' : ''; ?>>Select Date Filter</option>
                                            <option value="day" <?php echo ($filter_date_type === 'day' || $filter_date_type === 'today' || $filter_date_type === 'yesterday') ? 'selected' : ''; ?>>Day-wise</option>
                                            <option value="month" <?php echo $filter_date_type === 'month' ? 'selected' : ''; ?>>Month-wise</option>
                                            <option value="year" <?php echo $filter_date_type === 'year' ? 'selected' : ''; ?>>Year-wise</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <input type="text" name="date_value" id="dateValueInput" class="form-control" placeholder="Enter date (YYYY-MM-DD, YYYY-MM, or YYYY)" value="<?php echo htmlspecialchars($filter_date_value); ?>">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter me-1"></i>Apply Filter
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary summary-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo count($campaigns); ?></h5>
                                <p class="card-text">Total Campaigns</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning summary-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $pending_payments; ?></h5>
                                <p class="card-text">Pending Payments</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success summary-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $completed_payments; ?></h5>
                                <p class="card-text">Completed Payments</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card summary-card">
                            <div class="card-body">
                                <h5 class="card-title">₹<?php echo number_format($total_advertiser_payout, 2); ?></h5>
                                <p class="card-text">Total Advertiser Payout</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card summary-card">
                            <div class="card-body">
                                <h5 class="card-title">₹<?php echo number_format($total_publisher_payout, 2); ?></h5>
                                <p class="card-text">Total Publisher Payout</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card summary-card">
                            <div class="card-body">
                                <h5 class="card-title">₹<?php echo number_format($total_advertiser_payout - $total_publisher_payout, 2); ?></h5>
                                <p class="card-text">Platform Profit</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Campaigns Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Campaign Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($campaigns)): ?>
                            <p class="text-muted">No campaigns found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Campaign Name</th>
                                            <th>Advertisers</th>
                                            <th>Publishers</th>
                                            <th>Type</th>
                                            <th>Target Leads</th>
                                            <th>Validated Leads</th>
                                            <th>Total Amount</th>
                                            <th>Clicks</th>
                                            <th>Advertiser Payout</th>
                                            <th>Publisher Payout</th>
                                            <th>Payment Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($campaigns as $campaign): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($campaign['id']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['publisher_names'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['campaign_type']); ?></td>
                                                <td><?php echo $campaign['target_leads']; ?></td>
                                                <td><?php echo $campaign['validated_leads']; ?></td>
                                                <td>₹<?php echo number_format($campaign['validated_leads'] * $campaign['advertiser_payout'], 2); ?></td>
                                                <td><?php echo $campaign['click_count']; ?></td>
                                                <td>₹<?php echo number_format($campaign['advertiser_payout'], 2); ?></td>
                                                <td>₹<?php echo number_format($campaign['publisher_payout'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $campaign['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($campaign['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <!-- Lead Update Modal Trigger -->
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#leadModal<?php echo $campaign['id']; ?>">
                                                        Update Leads
                                                    </button>
                                                    
                                                    <!-- Payment Status Toggle -->
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to change the payment status?');">
                                                        <input type="hidden" name="action" value="toggle_payment_status">
                                                        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                        <input type="hidden" name="current_status" value="<?php echo $campaign['payment_status']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-<?php echo $campaign['payment_status'] === 'completed' ? 'warning' : 'success'; ?>">
                                                            <?php echo $campaign['payment_status'] === 'completed' ? 'Mark Pending' : 'Mark Completed'; ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            
                                            <!-- Lead Update Modal -->
                                            <div class="modal fade" id="leadModal<?php echo $campaign['id']; ?>" tabindex="-1" aria-labelledby="leadModalLabel<?php echo $campaign['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="leadModalLabel<?php echo $campaign['id']; ?>">Update Lead Information</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="update_leads">
                                                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label for="target_leads_<?php echo $campaign['id']; ?>" class="form-label">Target Leads</label>
                                                                    <input type="number" class="form-control target-leads-input" id="target_leads_<?php echo $campaign['id']; ?>" name="target_leads" value="<?php echo $campaign['target_leads']; ?>" min="0" step="1" data-campaign-id="<?php echo $campaign['id']; ?>">
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="validated_leads_<?php echo $campaign['id']; ?>" class="form-label">Validated Leads</label>
                                                                    <input type="number" class="form-control validated-leads-input" id="validated_leads_<?php echo $campaign['id']; ?>" name="validated_leads" value="<?php echo $campaign['validated_leads']; ?>" min="0" max="<?php echo $campaign['target_leads']; ?>" step="1" data-advertiser-payout="<?php echo $campaign['advertiser_payout']; ?>" data-campaign-id="<?php echo $campaign['id']; ?>">
                                                                </div>
                                                                
                                                                <div class="alert alert-info">
                                                                    <strong>Total Amount:</strong> ₹<span class="total-amount-display"><?php echo number_format($campaign['validated_leads'] * $campaign['advertiser_payout'], 2); ?></span>
                                                                    <br>(Validated Leads × Advertiser Payout)
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update date input placeholder and format based on selected date type
        function updateDateInput() {
            const dateType = document.querySelector('[name="date_type"]').value;
            const dateInput = document.getElementById('dateValueInput');
            
            switch(dateType) {
                case 'day':
                    dateInput.placeholder = 'Enter date (YYYY-MM-DD)';
                    dateInput.type = 'text';
                    break;
                case 'month':
                    dateInput.placeholder = 'Enter month (YYYY-MM)';
                    dateInput.type = 'text';
                    break;
                case 'year':
                    dateInput.placeholder = 'Enter year (YYYY)';
                    dateInput.type = 'text';
                    break;
                default:
                    dateInput.placeholder = 'Enter date (YYYY-MM-DD, YYYY-MM, or YYYY)';
                    dateInput.type = 'text';
            }
        }
        
        // Initialize date input based on current selection
        document.addEventListener('DOMContentLoaded', function() {
            updateDateInput();
            
            // Add event listeners to all validated leads inputs
            const validatedLeadsInputs = document.querySelectorAll('.validated-leads-input');
            const targetLeadsInputs = document.querySelectorAll('.target-leads-input');
            
            // Add event listener for target leads inputs
            targetLeadsInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    const campaignId = this.dataset.campaignId;
                    const targetLeads = parseInt(this.value) || 0;
                    
                    // Update the max attribute of the corresponding validated leads input
                    const validatedLeadsInput = document.querySelector(`#validated_leads_${campaignId}`);
                    if (validatedLeadsInput) {
                        validatedLeadsInput.max = targetLeads;
                        
                        // Check if current validated leads exceed new target leads
                        const currentValidatedLeads = parseInt(validatedLeadsInput.value) || 0;
                        if (currentValidatedLeads > targetLeads) {
                            validatedLeadsInput.value = targetLeads;
                            // Trigger input event to update total amount
                            validatedLeadsInput.dispatchEvent(new Event('input'));
                        }
                    }
                });
            });
            
            validatedLeadsInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    const advertiserPayout = parseFloat(this.dataset.advertiserPayout);
                    const validatedLeads = parseFloat(this.value) || 0;
                    const targetLeadsInput = document.querySelector(`#target_leads_${this.dataset.campaignId}`);
                    const targetLeads = targetLeadsInput ? parseInt(targetLeadsInput.value) || 0 : Infinity;
                    
                    // Ensure validated leads don't exceed target leads
                    if (validatedLeads > targetLeads) {
                        this.value = targetLeads;
                    }
                    
                    const totalAmount = (parseFloat(this.value) || 0) * advertiserPayout;
                    
                    // Update the total amount display
                    const totalAmountDisplay = this.closest('.modal-body').querySelector('.total-amount-display');
                    if (totalAmountDisplay) {
                        totalAmountDisplay.textContent = totalAmount.toFixed(2);
                    }
                });
            });
        });
    </script>
</body>
</html>