<?php
require_once '../auth_check.php';
require_once '../config.php';

// Check if user is admin
if ($role != 'admin') {
    header("Location: ../super_admin/dashboard.php");
    exit();
}

// Fetch data for charts
// Get campaign statistics
$campaign_stats_sql = "SELECT 
                        COUNT(*) as total_campaigns,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
                        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_campaigns
                      FROM campaigns";
$campaign_stats_result = $conn->query($campaign_stats_sql);
$campaign_stats = $campaign_stats_result ? $campaign_stats_result->fetch_assoc() : ['total_campaigns' => 0, 'active_campaigns' => 0, 'inactive_campaigns' => 0];

// Get payment statistics
$payment_stats_sql = "SELECT 
                        SUM(CASE WHEN advertiser_payment_status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                        SUM(CASE WHEN advertiser_payment_status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                        COUNT(*) as total_campaigns
                      FROM campaigns";
$payment_stats_result = $conn->query($payment_stats_sql);
$payment_stats = $payment_stats_result ? $payment_stats_result->fetch_assoc() : ['pending_payments' => 0, 'completed_payments' => 0, 'total_campaigns' => 0];

// Get monthly campaign data for chart
$monthly_campaigns_sql = "SELECT 
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            COUNT(*) as campaign_count
                          FROM campaigns 
                          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                          ORDER BY month ASC
                          LIMIT 6";
$monthly_campaigns_result = $conn->query($monthly_campaigns_sql);
$monthly_data = [];
if ($monthly_campaigns_result) {
    while($row = $monthly_campaigns_result->fetch_assoc()) {
        $monthly_data[] = $row;
    }
}

// Get entity counts
$advertisers_count_sql = "SELECT COUNT(*) as count FROM advertisers";
$advertisers_count_result = $conn->query($advertisers_count_sql);
$advertisers_count = $advertisers_count_result ? $advertisers_count_result->fetch_assoc()['count'] : 0;

$publishers_count_sql = "SELECT COUNT(*) as count FROM publishers";
$publishers_count_result = $conn->query($publishers_count_sql);
$publishers_count = $publishers_count_result ? $publishers_count_result->fetch_assoc()['count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content" id="mainContent">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Admin Dashboard</h1>
                </div>

                <!-- Summary Cards -->
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card summary-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-megaphone fs-1 text-primary"></i>
                                <h5 class="card-title mt-3">Campaigns</h5>
                                <p class="card-text">Manage advertising campaigns and track performance.</p>
                                <a href="campaigns.php" class="btn btn-primary">View Campaigns</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card summary-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-people fs-1 text-success"></i>
                                <h5 class="card-title mt-3">Advertisers</h5>
                                <p class="card-text">Manage advertisers and their campaigns.</p>
                                <a href="advertisers.php" class="btn btn-success">View Advertisers</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card summary-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-people-fill fs-1 text-info"></i>
                                <h5 class="card-title mt-3">Publishers</h5>
                                <p class="card-text">Manage publishers and their campaigns.</p>
                                <a href="publishers.php" class="btn btn-info">View Publishers</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="row mb-4">
                    <!-- Campaign Statistics Chart -->
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="chart-title mb-0">Campaign Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="campaignChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Statistics Chart -->
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="chart-title mb-0">Payment Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="paymentChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Campaigns Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="chart-title mb-0">Monthly Campaign Growth</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="monthlyCampaignChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Entity Count Cards -->
                <div class="row mb-4">
                    <div class="col-lg-6 col-md-6 mb-4">
                        <div class="card text-white bg-primary summary-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Advertisers</h5>
                                <h2><?php echo $advertisers_count; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 mb-4">
                        <div class="card text-white bg-info summary-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Publishers</h5>
                                <h2><?php echo $publishers_count; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card quick-links-card">
                            <div class="card-header">
                                <h5>Quick Links</h5>
                            </div>
                            <div class="card-body">
                                <a href="view_campaigns.php" class="btn btn-outline-primary me-2 mb-2">View Advertiser Campaigns</a>
                                <a href="view_publisher_campaigns.php" class="btn btn-outline-info me-2 mb-2">View Publisher Campaigns</a>
                                <a href="payment_reports.php" class="btn btn-outline-success me-2 mb-2">Payment Reports</a>
                                <a href="../publisher_login.php" class="btn btn-outline-secondary mb-2" target="_blank">Publisher Login Page</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                        text: 'Campaign Status Distribution'
                    }
                }
            }
        });

        // Payment Statistics Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'bar',
            data: {
                labels: ['Pending Payments', 'Completed Payments'],
                datasets: [{
                    label: 'Number of Payments',
                    data: [<?php echo isset($payment_stats['pending_payments']) ? $payment_stats['pending_payments'] : 0; ?>, <?php echo isset($payment_stats['completed_payments']) ? $payment_stats['completed_payments'] : 0; ?>],
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(40, 167, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 193, 7, 1)',
                        'rgba(40, 167, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Payment Status Overview'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(179, 229, 252, 0.5)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(179, 229, 252, 0.5)'
                        }
                    }
                }
            }
        });

        // Monthly Campaigns Chart
        const monthlyCtx = document.getElementById('monthlyCampaignChart').getContext('2d');
        // Prepare data for the chart
        const months = <?php echo isset($monthly_data) ? json_encode(array_column($monthly_data, 'month')) : '[]'; ?>;
        const campaignCounts = <?php echo isset($monthly_data) ? json_encode(array_column($monthly_data, 'campaign_count')) : '[]'; ?>;
        
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Campaigns Created',
                    data: campaignCounts,
                    borderColor: 'rgba(2, 136, 209, 1)',
                    backgroundColor: 'rgba(2, 136, 209, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Campaign Creation Trend'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(179, 229, 252, 0.5)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(179, 229, 252, 0.5)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>