<?php
// super_admin/includes/header.php - Common Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? 'Super Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin-theme.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 70px;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }
        
        * { font-family: 'Inter', sans-serif; }
        
        body {
            background: #f1f5f9;
            min-height: 100vh;
        }
        
        /* Top Navbar */
        .top-navbar {
            height: var(--header-height);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            z-index: 1000;
            transition: left 0.3s ease;
        }
        
        .top-navbar .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: #1e293b;
        }
        
        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            border-radius: 50px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .user-dropdown .dropdown-toggle:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        .user-dropdown .dropdown-toggle::after { display: none; }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: var(--sidebar-bg);
            z-index: 1001;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar-header {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .sidebar-logo-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .sidebar-logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
        }
        
        .sidebar-nav {
            padding: 20px 16px;
        }
        
        .nav-section {
            margin-bottom: 24px;
        }
        
        .nav-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            padding: 0 12px;
            margin-bottom: 8px;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s;
            margin-bottom: 4px;
        }
        
        .sidebar-link:hover {
            background: rgba(255,255,255,0.08);
            color: #e2e8f0;
        }
        
        .sidebar-link.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .sidebar-link i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding-top: var(--header-height);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .content-wrapper {
            padding: 24px;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-item a {
            color: #64748b;
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #1e293b;
            font-weight: 500;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            background: white;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            padding: 20px 24px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .card-body { padding: 24px; }
        
        /* Stats Cards */
        .stat-card {
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            color: white;
        }
        
        .stat-card.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-card.info { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
        .stat-card.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-card.danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        
        .stat-card-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 48px;
            opacity: 0.3;
        }
        
        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-card-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Tables */
        .table { margin-bottom: 0; }
        
        .table th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 16px;
        }
        
        .table td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }
        
        .table tbody tr:hover { background: #f8fafc; }
        
        /* Buttons */
        .btn { border-radius: 8px; font-weight: 500; padding: 10px 20px; }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-1px);
        }
        
        .btn-soft-primary { background: #eff6ff; color: #3b82f6; border: none; }
        .btn-soft-primary:hover { background: #3b82f6; color: white; }
        
        .btn-soft-danger { background: #fef2f2; color: #ef4444; border: none; }
        .btn-soft-danger:hover { background: #ef4444; color: white; }
        
        .btn-soft-success { background: #f0fdf4; color: #10b981; border: none; }
        .btn-soft-success:hover { background: #10b981; color: white; }
        
        .btn-soft-warning { background: #fffbeb; color: #f59e0b; border: none; }
        .btn-soft-warning:hover { background: #f59e0b; color: white; }
        
        /* Badges */
        .badge { font-weight: 500; padding: 6px 12px; border-radius: 6px; }
        .badge-soft-success { background: #f0fdf4; color: #10b981; }
        .badge-soft-danger { background: #fef2f2; color: #ef4444; }
        .badge-soft-warning { background: #fffbeb; color: #f59e0b; }
        .badge-soft-primary { background: #eff6ff; color: #3b82f6; }
        
        /* Forms */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
        
        .form-label {
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
        }
        
        .alert-success { background: #f0fdf4; color: #166534; }
        .alert-danger { background: #fef2f2; color: #991b1b; }
        .alert-warning { background: #fffbeb; color: #92400e; }
        .alert-info { background: #eff6ff; color: #1e40af; }
        
        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .top-navbar { left: 0; }
            .main-content { margin-left: 0; }
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1000;
                display: none;
            }
            .sidebar-overlay.show { display: block; }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="sidebar-logo-text">Ads Platform</span>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="campaigns.php" class="sidebar-link <?php echo $current_page === 'campaigns.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Campaigns</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Users</div>
                <a href="advertisers.php" class="sidebar-link <?php echo $current_page === 'advertisers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span>Advertisers</span>
                </a>
                <a href="publishers.php" class="sidebar-link <?php echo $current_page === 'publishers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i>
                    <span>Publishers</span>
                </a>
                <a href="admins.php" class="sidebar-link <?php echo $current_page === 'admins.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i>
                    <span>Admins</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Campaigns</div>
                <a href="advertiser_campaigns.php" class="sidebar-link <?php echo $current_page === 'advertiser_campaigns.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ad"></i>
                    <span>Advertiser Campaigns</span>
                </a>
                <a href="publisher_campaigns.php" class="sidebar-link <?php echo $current_page === 'publisher_campaigns.php' ? 'active' : ''; ?>">
                    <i class="fas fa-link"></i>
                    <span>Publisher Campaigns</span>
                </a>
                <a href="add_campaign.php" class="sidebar-link <?php echo $current_page === 'add_campaign.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Campaign</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Analytics</div>
                <a href="all_publishers_daily_clicks.php" class="sidebar-link <?php echo $current_page === 'all_publishers_daily_clicks.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Publishers Stats</span>
                </a>
                <a href="publisher_daily_clicks.php" class="sidebar-link <?php echo $current_page === 'publisher_daily_clicks.php' ? 'active' : ''; ?>">
                    <i class="fas fa-mouse-pointer"></i>
                    <span>Daily Clicks</span>
                </a>
                <a href="campaign_tracking_stats.php" class="sidebar-link <?php echo $current_page === 'campaign_tracking_stats.php' ? 'active' : ''; ?>">
                    <i class="fas fa-crosshairs"></i>
                    <span>Tracking Stats</span>
                </a>
                <a href="daily_report.php" class="sidebar-link <?php echo $current_page === 'daily_report.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-day"></i>
                    <span>Daily Report</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">CPV</div>
                <a href="cpv.php" class="sidebar-link <?php echo $current_page === 'cpv.php' ? 'active' : ''; ?>">
                    <i class="fas fa-eye"></i>
                    <span>CPV Campaigns</span>
                </a>
                <a href="cpv_report.php" class="sidebar-link <?php echo $current_page === 'cpv_report.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>CPV Report</span>
                </a>
                <a href="cpv_stats.php" class="sidebar-link <?php echo $current_page === 'cpv_stats.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-area"></i>
                    <span>CPV Stats</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Finance</div>
                <a href="payment_reports.php" class="sidebar-link <?php echo $current_page === 'payment_reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Payment Reports</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Settings</div>
                <a href="manage_admins.php" class="sidebar-link <?php echo $current_page === 'manage_admins.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Admins</span>
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Top Navbar -->
    <nav class="top-navbar">
        <div class="container-fluid h-100">
            <div class="d-flex align-items-center justify-content-between h-100 px-3">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-dark d-lg-none p-0" id="sidebarToggle">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                    <nav aria-label="breadcrumb" class="d-none d-md-block">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($page_title); ?></li>
                        </ol>
                    </nav>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown user-dropdown">
                        <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                            </div>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                            <i class="fas fa-chevron-down ms-2" style="font-size: 10px;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
