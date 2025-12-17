<?php
// super_admin/advertisers.php - Manage Advertisers
session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

// Handle advertiser creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("INSERT INTO advertisers (name, email, company, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $company, $phone]);
            
            $success = "Advertiser created successfully.";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = "An advertiser with this email already exists.";
            } else {
                $error = "Error creating advertiser: " . $e->getMessage();
            }
        }
    }
}

// Handle advertiser deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $advertiser_id = $_POST['advertiser_id'] ?? '';
    
    if (!empty($advertiser_id)) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("DELETE FROM advertisers WHERE id = ?");
            $stmt->execute([$advertiser_id]);
            
            $success = "Advertiser deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting advertiser: " . $e->getMessage();
        }
    }
}

// Get all advertisers
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM advertisers ORDER BY name");
    $stmt->execute();
    $advertisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading advertisers: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisers - Ads Platform</title>
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
            
            <main class="col-lg-10 ms-sm-auto px-md-4 py-3">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h3 mb-0 text-dark">Advertisers</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Advertisers</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 mb-0">Advertisers</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdvertiserModal">
                        <i class="fas fa-plus me-1"></i>Add New Advertiser
                    </button>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($advertisers)): ?>
                            <p>No advertisers found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Company</th>
<th>Phone</th>
<th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($advertisers as $advertiser): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($advertiser['id']); ?></td>
                                                <td><?php echo htmlspecialchars($advertiser['name']); ?></td>
                                                <td><?php echo htmlspecialchars($advertiser['email']); ?></td>
                                                <td><?php echo htmlspecialchars($advertiser['company'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($advertiser['phone'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this advertiser?')">
                                                        <input type="hidden" name="advertiser_id" value="<?php echo $advertiser['id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
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
    
    <!-- Create Advertiser Modal -->
    <div class="modal fade" id="createAdvertiserModal" tabindex="-1" aria-labelledby="createAdvertiserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createAdvertiserModalLabel">Add New Advertiser</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="company" class="form-label">Company</label>
                            <input type="text" class="form-control" id="company" name="company">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Advertiser</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>