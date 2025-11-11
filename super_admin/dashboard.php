<?php
require_once '../auth_check.php';
require_once '../config.php';

// Check if user is super admin
if ($role != 'super_admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Set username for sidebar
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';

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

$admins_count_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
$admins_count_result = $conn->query($admins_count_sql);
$admins_count = $admins_count_result ? $admins_count_result->fetch_assoc()['count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Super Admin specific styles */
        .super-admin-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e3f2fd;
        }
        
        .super-admin-header {
            background: linear-gradient(to right, #e3f2fd, #bbdefb);
            color: #0d47a1;
        }
        
        .super-admin-btn {
            background: linear-gradient(to right, #64b5f6, #90caf9);
            border: none;
            color: #0d47a1;
            font-weight: 500;
        }
        
        .super-admin-btn:hover {
            background: linear-gradient(to right, #42a5f5, #64b5f6);
            color: #0d47a1;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .super-admin-panel {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.15);
        }
    </style>
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
                    <h1 class="h2">Super Admin Dashboard</h1>
                </div>

                <!-- Summary Cards -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card super-admin-card summary-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-megaphone fs-1 text-primary"></i>
                                <h5 class="card-title mt-3">Campaigns</h5>
                                <p class="card-text">Manage all advertising campaigns.</p>
                                <a href="campaigns.php" class="btn super-admin-btn">View Campaigns</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card super-admin-card summary-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-people fs-1 text-success"></i>
                                <h5 class="card-title mt-3">Advertisers</h5>
                                <p class="card-text">Manage all advertisers.</p>
                                <a href="advertisers.php" class="btn super-admin-btn">View Advertisers</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card super-admin-card summary-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-people-fill fs-1 text-info"></i>
                                <h5 class="card-title mt-3">Publishers</h5>
                                <p class="card-text">Manage all publishers.</p>
                                <a href="publishers.php" class="btn super-admin-btn">View Publishers</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card super-admin-card summary-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-person-badge fs-1 text-warning"></i>
                                <h5 class="card-title mt-3">Admins</h5>
                                <p class="card-text">Manage admin users.</p>
                                <a href="admins.php" class="btn super-admin-btn">View Admins</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="row mb-4">
                    <!-- Campaign Statistics Chart -->
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card super-admin-panel">
                            <div class="card-header super-admin-header">
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
                        <div class="card super-admin-panel">
                            <div class="card-header super-admin-header">
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
                        <div class="card super-admin-panel">
                            <div class="card-header super-admin-header">
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
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card super-admin-panel h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                                    <i class="bi bi-people text-white fs-3"></i>
                                </div>
                                <h5 class="card-title">Total Advertisers</h5>
                                <h2 class="text-primary fw-bold"><?php echo $advertisers_count; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card super-admin-panel h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                                    <i class="bi bi-people-fill text-white fs-3"></i>
                                </div>
                                <h5 class="card-title">Total Publishers</h5>
                                <h2 class="text-success fw-bold"><?php echo $publishers_count; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card super-admin-panel h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-info d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                                    <i class="bi bi-person-badge text-white fs-3"></i>
                                </div>
                                <h5 class="card-title">Total Admins</h5>
                                <h2 class="text-info fw-bold"><?php echo $admins_count; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card super-admin-panel h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                                    <i class="bi bi-megaphone text-white fs-3"></i>
                                </div>
                                <h5 class="card-title">Total Campaigns</h5>
                                <h2 class="text-warning fw-bold"><?php echo $campaign_stats['total_campaigns']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card super-admin-panel">
                            <div class="card-header super-admin-header">
                                <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Quick Links</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="view_campaigns.php" class="btn super-admin-btn me-2 mb-2">
                                        <i class="bi bi-eye me-1"></i>View Advertiser Campaigns
                                    </a>
                                    <a href="view_publisher_campaigns.php" class="btn btn-outline-info me-2 mb-2">
                                        <i class="bi bi-eye-fill me-1"></i>View Publisher Campaigns
                                    </a>
                                    <a href="payment_reports.php" class="btn btn-outline-success me-2 mb-2">
                                        <i class="bi bi-currency-dollar me-1"></i>Payment Reports
                                    </a>
                                    <a href="../publisher_login.php" class="btn btn-outline-secondary mb-2" target="_blank">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Publisher Login Page
                                    </a>
                                </div>
                            </div>
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
                        'rgba(25, 118, 210, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(25, 118, 210, 1)',
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
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Campaigns Created',
                    data: campaignCounts,
                    backgroundColor: 'rgba(25, 118, 210, 0.8)',
                    borderColor: 'rgba(25, 118, 210, 1)',
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
                        text: 'Monthly Campaign Growth'
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