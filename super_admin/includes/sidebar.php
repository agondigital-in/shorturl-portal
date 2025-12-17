<?php
// super_admin/includes/sidebar.php - Common Sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Desktop Sidebar -->
<div class="col-lg-2 d-none d-lg-block bg-light sidebar p-0">
    <div class="sidebar-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home me-2"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'campaigns.php' ? 'active' : ''; ?>" href="campaigns.php">
                    <i class="fas fa-bullhorn me-2"></i><span>Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'advertisers.php' ? 'active' : ''; ?>" href="advertisers.php">
                    <i class="fas fa-users me-2"></i><span>Advertisers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'publishers.php' ? 'active' : ''; ?>" href="publishers.php">
                    <i class="fas fa-share-alt me-2"></i><span>Publishers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'admins.php' ? 'active' : ''; ?>" href="admins.php">
                    <i class="fas fa-user-shield me-2"></i><span>Admins</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'advertiser_campaigns.php' ? 'active' : ''; ?>" href="advertiser_campaigns.php">
                    <i class="fas fa-ad me-2"></i><span>Advertiser Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'publisher_campaigns.php' ? 'active' : ''; ?>" href="publisher_campaigns.php">
                    <i class="fas fa-link me-2"></i><span>Publisher Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'all_publishers_daily_clicks.php' ? 'active' : ''; ?>" href="all_publishers_daily_clicks.php">
                    <i class="fas fa-chart-bar me-2"></i><span>All Publishers Stats</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'cpv.php' ? 'active' : ''; ?>" href="cpv.php">
                    <i class="fas fa-compress-alt me-2"></i><span>CPV Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'cpv_report.php' ? 'active' : ''; ?>" href="cpv_report.php">
                    <i class="fas fa-chart-pie me-2"></i><span>CPV Report</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'payment_reports.php' ? 'active' : ''; ?>" href="payment_reports.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i><span>Payment Reports</span>
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
                <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home me-2"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'campaigns.php' ? 'active' : ''; ?>" href="campaigns.php">
                    <i class="fas fa-bullhorn me-2"></i><span>Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'advertisers.php' ? 'active' : ''; ?>" href="advertisers.php">
                    <i class="fas fa-users me-2"></i><span>Advertisers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'publishers.php' ? 'active' : ''; ?>" href="publishers.php">
                    <i class="fas fa-share-alt me-2"></i><span>Publishers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'admins.php' ? 'active' : ''; ?>" href="admins.php">
                    <i class="fas fa-user-shield me-2"></i><span>Admins</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'advertiser_campaigns.php' ? 'active' : ''; ?>" href="advertiser_campaigns.php">
                    <i class="fas fa-ad me-2"></i><span>Advertiser Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'publisher_campaigns.php' ? 'active' : ''; ?>" href="publisher_campaigns.php">
                    <i class="fas fa-link me-2"></i><span>Publisher Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'all_publishers_daily_clicks.php' ? 'active' : ''; ?>" href="all_publishers_daily_clicks.php">
                    <i class="fas fa-chart-bar me-2"></i><span>All Publishers Stats</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'cpv.php' ? 'active' : ''; ?>" href="cpv.php">
                    <i class="fas fa-compress-alt me-2"></i><span>CPV Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'cpv_report.php' ? 'active' : ''; ?>" href="cpv_report.php">
                    <i class="fas fa-chart-pie me-2"></i><span>CPV Report</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'payment_reports.php' ? 'active' : ''; ?>" href="payment_reports.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i><span>Payment Reports</span>
                </a>
            </li>
        </ul>
    </div>
</div>
