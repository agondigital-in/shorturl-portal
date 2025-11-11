<?php
// Determine user role and name for header display
$role_display = '';
$user_name = '';
$logout_url = '../logout.php';

if (isset($role)) {
    if ($role == 'super_admin') {
        $role_display = 'Super Admin';
        $user_name = isset($username) ? $username : 'Unknown';
    } elseif ($role == 'admin') {
        $role_display = 'Admin';
        $user_name = isset($username) ? $username : 'Unknown';
        $logout_url = '../logout.php';
    }
} elseif (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'publisher') {
        $role_display = 'Publisher';
        $user_name = isset($_SESSION['publisher_name']) ? $_SESSION['publisher_name'] : 'Unknown';
        $logout_url = 'publisher_logout.php';
    }
} else {
    $role_display = 'Dashboard';
    $user_name = 'User';
}
?>

<!-- Top Navbar -->
<nav class="top-navbar">
    <div class="d-flex align-items-center">
        <!-- New Toggle Button -->
        <button class="navbar-toggler sidebar-toggle me-3" type="button" id="newSidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand mb-0" href="#">
            <?php echo $role_display; ?>
        </a>
    </div>
    
    <div class="dropdown">
        <button class="btn btn-outline-light dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i> <?php echo $user_name; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo $logout_url; ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
    </div>
</nav>