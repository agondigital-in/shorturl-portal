<?php
session_start();
require_once 'config.php';

// Check if publisher is logged in
if (!isset($_SESSION['publisher_id']) || $_SESSION['role'] != 'publisher') {
    header("Location: publisher_login.php");
    exit();
}

$publisher_id = $_SESSION['publisher_id'];
$publisher_name = $_SESSION['publisher_name'];

// Fetch data for charts
// Get campaign statistics for this publisher
$campaign_stats_sql = "SELECT 
                        COUNT(*) as total_campaigns,
                        SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
                        SUM(CASE WHEN c.status = 'inactive' THEN 1 ELSE 0 END) as inactive_campaigns
                      FROM campaigns c 
                      JOIN campaign_publishers cp ON c.id = cp.campaign_id
                      WHERE cp.publisher_id = ?";
$stmt = $conn->prepare($campaign_stats_sql);
if ($stmt) {
    $stmt->bind_param("i", $publisher_id);
    $stmt->execute();
    $campaign_stats_result = $stmt->get_result();
    $campaign_stats = $campaign_stats_result ? $campaign_stats_result->fetch_assoc() : ['total_campaigns' => 0, 'active_campaigns' => 0, 'inactive_campaigns' => 0];
    $stmt->close();
} else {
    $campaign_stats = ['total_campaigns' => 0, 'active_campaigns' => 0, 'inactive_campaigns' => 0];
}

// Get total clicks for this publisher
$clicks_sql = "SELECT SUM(c.clicks) as total_clicks
               FROM campaigns c 
               JOIN campaign_publishers cp ON c.id = cp.campaign_id
               WHERE cp.publisher_id = ?";
$stmt = $conn->prepare($clicks_sql);
if ($stmt) {
    $stmt->bind_param("i", $publisher_id);
    $stmt->execute();
    $clicks_result = $stmt->get_result();
    $total_clicks = $clicks_result ? $clicks_result->fetch_assoc()['total_clicks'] ?? 0 : 0;
    $stmt->close();
} else {
    $total_clicks = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Add Bootstrap JavaScript bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar" id="sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="publisher_dashboard.php">
                                <i class="bi bi-house-door"></i> Dashboard
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="position-absolute bottom-0 w-100 p-3 text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-primary">Publisher: <?php echo $publisher_name; ?></span>
                        <a href="publisher_logout.php" class="btn btn-sm btn-outline-light">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content" id="mainContent">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Publisher Dashboard</h1>
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-house"></i> Main Page
                        </a>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card text-white bg-primary summary-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Campaigns</h5>
                                <h2><?php echo $campaign_stats['total_campaigns']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card text-white bg-success summary-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Active Campaigns</h5>
                                <h2><?php echo $campaign_stats['active_campaigns']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card text-white bg-info summary-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Clicks</h5>
                                <h2><?php echo $total_clicks; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mb-4">
                    <!-- Campaign Statistics Chart -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="chart-title mb-0">My Campaign Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="campaignChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campaigns List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All My Campaigns</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Campaign Name</th>
                                        <th>Publishers</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Type</th>
                                        <th>Short Code</th>
                                        <th>Clicks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get campaigns for this publisher
                                    $sql = "SELECT c.* FROM campaigns c 
                                            JOIN campaign_publishers cp ON c.id = cp.campaign_id
                                            WHERE cp.publisher_id = ? 
                                            ORDER BY c.id DESC";
                                    $stmt = $conn->prepare($sql);
                                    if ($stmt === false) {
                                        die("Prepare failed: " . $conn->error);
                                    }
                                    $stmt->bind_param("i", $publisher_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>".$row['id']."</td>";
                                            echo "<td>".$row['campaign_name']."</td>";
                                            
                                            // Get publishers for this campaign
                                            $publishers_sql = "SELECT p.name FROM publishers p 
                                                              JOIN campaign_publishers cp ON p.id = cp.publisher_id 
                                                              WHERE cp.campaign_id = ".$row['id'];
                                            $publishers_result = $conn->query($publishers_sql);
                                            $publishers = [];
                                            if ($publishers_result && $publishers_result->num_rows > 0) {
                                                while($publisher = $publishers_result->fetch_assoc()) {
                                                    $publishers[] = $publisher['name'];
                                                }
                                            }
                                            $publishers_list = !empty($publishers) ? implode(", ", $publishers) : "N/A";
                                            
                                            echo "<td>".$publishers_list."</td>";
                                            echo "<td>".$row['start_date']."</td>";
                                            echo "<td>".$row['end_date']."</td>";
                                            echo "<td>".$row['type']."</td>";
                                            echo "<td><a href='".$row['short_code']."' target='_blank'>".$row['short_code']."</a></td>";
                                            echo "<td>".$row['clicks']."</td>";
                                            echo "<td><span class='badge bg-".($row['status'] == 'active' ? 'success' : 'danger')."'>".$row['status']."</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center'>No campaigns found</td></tr>";
                                    }
                                    $stmt->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Simplified Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('newSidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            // Initialize sidebar as collapsed on mobile
            if (window.innerWidth <= 991) {
                if (sidebar) {
                    sidebar.classList.add('collapsed');
                }
            }
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('collapsed');
                    sidebar.classList.toggle('expanded');
                });
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 991 && 
                        sidebar.classList.contains('expanded') && 
                        !sidebar.contains(e.target) && 
                        e.target !== sidebarToggle &&
                        !sidebarToggle.contains(e.target)) {
                        sidebar.classList.remove('expanded');
                        sidebar.classList.add('collapsed');
                    }
                });
            }
        });

        // Campaign Statistics Chart
        const campaignCtx = document.getElementById('campaignChart').getContext('2d');
        const campaignChart = new Chart(campaignCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active Campaigns', 'Inactive Campaigns'],
                datasets: [{
                    data: [<?php echo isset($campaign_stats['active_campaigns']) ? $campaign_stats['active_campaigns'] : 0; ?>, <?php echo isset($campaign_stats['inactive_campaigns']) ? $campaign_stats['inactive_campaigns'] : 0; ?>],
                    backgroundColor: [
                        'rgba(2, 136, 209, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(2, 136, 209, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'My Campaign Status Distribution'
                    }
                }
            }
        });
    </script>
</body>
</html>