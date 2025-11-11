<nav class="col-md-3 col-lg-2 d-md-block sidebar" id="sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : 'text-white'; ?>" href="dashboard.php">
                    <i class="bi bi-house-door"></i> Home
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'campaigns.php' ? 'active' : 'text-white'; ?>" href="campaigns.php">
                    <i class="bi bi-megaphone"></i> Campaigns
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'advertisers.php' ? 'active' : 'text-white'; ?>" href="advertisers.php">
                    <i class="bi bi-people"></i> Advertisers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'publishers.php' ? 'active' : 'text-white'; ?>" href="publishers.php">
                    <i class="bi bi-people-fill"></i> Publishers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : 'text-white'; ?>" href="admins.php">
                    <i class="bi bi-person-badge"></i> Admins
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_campaigns.php' ? 'active' : 'text-white'; ?>" href="view_campaigns.php">
                    <i class="bi bi-eye"></i> View Advertiser Campaigns
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_publisher_campaigns.php' ? 'active' : 'text-white'; ?>" href="view_publisher_campaigns.php">
                    <i class="bi bi-eye-fill"></i> View Publisher Campaigns
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payment_reports.php' ? 'active' : 'text-white'; ?>" href="payment_reports.php">
                    <i class="bi bi-currency-dollar"></i> Payment Reports
                </a>
            </li>
        </ul>
    </div>
</nav>