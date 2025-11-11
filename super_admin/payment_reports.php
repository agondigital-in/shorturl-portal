<?php
require_once '../auth_check.php';
require_once '../config.php';

// Check if user is super admin
if ($role != 'super_admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Handle form submission for updating target leads and validated leads
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_leads'])) {
    $campaign_id = $_POST['campaign_id'];
    $target_leads = intval($_POST['target_leads']);
    $validated_leads = intval($_POST['validated_leads']);
    $advertiser_payout = floatval($_POST['advertiser_payout']);
    
    // Calculate total amount
    $total_amount = $validated_leads * $advertiser_payout;
    
    // Update the campaign
    $stmt = $conn->prepare("UPDATE campaigns SET target_leads = ?, validated_leads = ?, total_amount = ? WHERE id = ?");
    $stmt->bind_param("iiii", $target_leads, $validated_leads, $total_amount, $campaign_id);
    
    if ($stmt->execute()) {
        $success = "Campaign leads updated successfully!";
    } else {
        $error = "Error updating campaign leads: " . $conn->error;
    }
    $stmt->close();
}

// Get filter parameters
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reports - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
  <?php include '../includes/header.php'; ?>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Payment Reports</h1>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Filter Campaigns</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="payment_status" class="form-label">Payment Status</label>
                                        <select class="form-control" id="payment_status" name="payment_status">
                                            <option value="">All Statuses</option>
                                            <option value="pending" <?php echo ($payment_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="completed" <?php echo ($payment_status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="payment_reports.php" class="btn btn-secondary">Clear</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <?php
                    // Get total counts
                    $total_sql = "SELECT 
                                    SUM(CASE WHEN advertiser_payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                                    SUM(CASE WHEN advertiser_payment_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                                    COUNT(*) as total_count
                                  FROM campaigns";
                    $total_result = $conn->query($total_sql);
                    $total_row = $total_result->fetch_assoc();
                    ?>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body">
                                <h5 class="card-title">Pending Payments</h5>
                                <h2><?php echo $total_row['pending_count']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body">
                                <h5 class="card-title">Completed Payments</h5>
                                <h2><?php echo $total_row['completed_count']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Campaigns</h5>
                                <h2><?php echo $total_row['total_count']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campaigns List -->
                <div class="card">
                    <div class="card-header">
                        <h5>
                            <?php 
                            if ($payment_status == 'pending') {
                                echo "Campaigns with Pending Payments";
                            } elseif ($payment_status == 'completed') {
                                echo "Campaigns with Completed Payments";
                            } else {
                                echo "All Campaigns";
                            }
                            ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Campaign Name</th>
                                        <th>Advertisers</th>
                                        <th>Publishers</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Target Leads</th>
                                        <th>Validated Leads</th>
                                        <th>Advertiser Payout ($)</th>
                                        <th>Total Amount ($)</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Build query based on filter
                                    $sql = "SELECT c.* FROM campaigns c";
                                    
                                    if (!empty($payment_status)) {
                                        $sql .= " WHERE c.advertiser_payment_status = '" . $conn->real_escape_string($payment_status) . "'";
                                    }
                                    
                                    $sql .= " ORDER BY c.id DESC";
                                    
                                    $result = $conn->query($sql);
                                    
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>".$row['id']."</td>";
                                            echo "<td>".$row['campaign_name']."</td>";
                                            
                                            // Get advertisers for this campaign
                                            $advertisers_sql = "SELECT a.name FROM advertisers a 
                                                               JOIN campaign_advertisers ca ON a.id = ca.advertiser_id 
                                                               WHERE ca.campaign_id = ".$row['id'];
                                            $advertisers_result = $conn->query($advertisers_sql);
                                            $advertisers = [];
                                            if ($advertisers_result->num_rows > 0) {
                                                while($advertiser = $advertisers_result->fetch_assoc()) {
                                                    $advertisers[] = $advertiser['name'];
                                                }
                                            }
                                            $advertisers_list = !empty($advertisers) ? implode(", ", $advertisers) : "N/A";
                                            
                                            echo "<td>".$advertisers_list."</td>";
                                            
                                            // Get publishers for this campaign
                                            $publishers_sql = "SELECT p.name FROM publishers p 
                                                              JOIN campaign_publishers cp ON p.id = cp.publisher_id 
                                                              WHERE cp.campaign_id = ".$row['id'];
                                            $publishers_result = $conn->query($publishers_sql);
                                            $publishers = [];
                                            if ($publishers_result->num_rows > 0) {
                                                while($publisher = $publishers_result->fetch_assoc()) {
                                                    $publishers[] = $publisher['name'];
                                                }
                                            }
                                            $publishers_list = !empty($publishers) ? implode(", ", $publishers) : "N/A";
                                            
                                            echo "<td>".$publishers_list."</td>";
                                            echo "<td>".$row['start_date']."</td>";
                                            echo "<td>".$row['end_date']."</td>";
                                            
                                            // Form for updating leads
                                            echo "<td>";
                                            echo "<form method='POST' class='d-inline'>";
                                            echo "<input type='hidden' name='campaign_id' value='".$row['id']."'>";
                                            echo "<input type='hidden' name='advertiser_payout' value='".$row['advertiser_payout']."'>";
                                            echo "<input type='number' name='target_leads' value='".$row['target_leads']."' class='form-control form-control-sm' style='width: 80px; display: inline-block;'>";
                                            echo "</td>";
                                            
                                            echo "<td>";
                                            echo "<input type='number' name='validated_leads' value='".$row['validated_leads']."' class='form-control form-control-sm' style='width: 80px; display: inline-block;'>";
                                            echo "</td>";
                                            
                                            echo "<td>".$row['advertiser_payout']."</td>";
                                            echo "<td>".number_format($row['total_amount'], 2)."</td>";
                                            echo "<td><span class='badge bg-".($row['advertiser_payment_status'] == 'completed' ? 'success' : 'warning')."'>".$row['advertiser_payment_status']."</span></td>";
                                            echo "<td>";
                                            echo "<button type='submit' name='update_leads' class='btn btn-sm btn-primary me-1'>Update</button>";
                                            echo "<a href='campaigns.php?toggle_payment_status=".$row['id']."' class='btn btn-sm btn-".($row['advertiser_payment_status'] == 'completed' ? 'warning' : 'success')."'>".($row['advertiser_payment_status'] == 'completed' ? 'Mark Pending' : 'Mark Completed')."</a>";
                                            echo "</form>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='12' class='text-center'>No campaigns found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calculate total amount when validated leads change
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const validatedInput = form.querySelector('input[name="validated_leads"]');
                const payoutInput = form.querySelector('input[name="advertiser_payout"]');
                
                if (validatedInput && payoutInput) {
                    validatedInput.addEventListener('change', function() {
                        // The calculation is done server-side, but we can show a preview
                    });
                }
            });
        });
    </script>
</body>
</html>