<?php
// super_admin/dashboard.php - Super Admin Dashboard
session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get counts for dashboard overview
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM campaigns");
    $stmt->execute();
    $campaigns_count = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM advertisers");
    $stmt->execute();
    $advertisers_count = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM publishers");
    $stmt->execute();
    $publishers_count = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'super_admin')");
    $stmt->execute();
    $admins_count = $stmt->fetch()['count'];
    
    // Get recent campaigns
    $stmt = $conn->prepare("SELECT * FROM campaigns ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get campaign status distribution
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM campaigns GROUP BY status");
    $stmt->execute();
    $campaign_status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent campaign clicks (last 7 days)
    $stmt = $conn->prepare("SELECT DATE(created_at) as date, SUM(click_count) as clicks FROM campaigns WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date");
    $stmt->execute();
    $clicks_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get campaign type distribution
    $stmt = $conn->prepare("SELECT campaign_type, COUNT(*) as count FROM campaigns GROUP BY campaign_type");
    $stmt->execute();
    $campaign_type_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <?php include 'includes/sidebar.php'; ?>
            
            
            <main class="col-lg-10 ms-sm-auto px-md-4 py-3">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h3 mb-0 text-dark">Dashboard</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <h2>Dashboard Overview</h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title"><?php echo $campaigns_count; ?></h5>
                                        <p class="card-text">Campaigns</p>
                                    </div>
                                    <div class="icon-circle bg-white text-primary">
                                        <i class="fas fa-bullhorn"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title"><?php echo $advertisers_count; ?></h5>
                                        <p class="card-text">Advertisers</p>
                                    </div>
                                    <div class="icon-circle bg-white text-success">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title"><?php echo $publishers_count; ?></h5>
                                        <p class="card-text">Publishers</p>
                                    </div>
                                    <div class="icon-circle bg-white text-info">
                                        <i class="fas fa-share-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title"><?php echo $admins_count; ?></h5>
                                        <p class="card-text">Admins</p>
                                    </div>
                                    <div class="icon-circle bg-white text-warning">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5>Campaign Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="campaignStatusChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5>Campaign Type Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="campaignTypeChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5>Recent Campaign Clicks (Last 7 Days)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="clicksChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Campaigns</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_campaigns)): ?>
                            <p>No campaigns found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Short Code</th>
                                            <th>Status</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_campaigns as $campaign): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['shortcode']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($campaign['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($campaign['start_date']); ?></td>
                                                <td><?php echo htmlspecialchars($campaign['end_date']); ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Campaign Status Chart
        document.addEventListener('DOMContentLoaded', function() {
            var ctx1 = document.getElementById('campaignStatusChart').getContext('2d');
            var campaignStatusChart = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . ucfirst($item['status']) . "'"; }, $campaign_status_data)); ?>],
                    datasets: [{
                        data: [<?php echo implode(',', array_column($campaign_status_data, 'count')); ?>],
                        backgroundColor: [
                            '#28a745',
                            '#dc3545',
                            '#ffc107'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
            
            // Campaign Type Chart
            var ctx2 = document.getElementById('campaignTypeChart').getContext('2d');
            var campaignTypeChart = new Chart(ctx2, {
                type: 'pie',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . ($item['campaign_type'] === 'None' ? 'Not Set' : $item['campaign_type']) . "'"; }, $campaign_type_data)); ?>],
                    datasets: [{
                        data: [<?php echo implode(',', array_column($campaign_type_data, 'count')); ?>],
                        backgroundColor: [
                            '#007bff',
                            '#28a745',
                            '#ffc107',
                            '#dc3545',
                            '#6f42c1',
                            '#17a2b8'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
            
            // Clicks Chart
            var ctx3 = document.getElementById('clicksChart').getContext('2d');
            var clicksChart = new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M d', strtotime($item['date'])) . "'"; }, $clicks_data)); ?>],
                    datasets: [{
                        label: 'Total Clicks',
                        data: [<?php echo implode(',', array_column($clicks_data, 'clicks')); ?>],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>