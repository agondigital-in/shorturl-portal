<?php
require_once '../auth_check.php';
require_once '../config.php';

// Check if user is super admin
if ($role != 'super_admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Handle campaign status toggle
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $stmt = $conn->prepare("SELECT status FROM campaigns WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $campaign = $result->fetch_assoc();
    
    if ($campaign) {
        $new_status = $campaign['status'] == 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        
        if ($stmt->execute()) {
            $success = "Campaign status updated successfully!";
        } else {
            $error = "Error updating campaign status: " . $conn->error;
        }
    }
    $stmt->close();
    
    // Redirect to avoid resubmission
    $advertiser_id = isset($_GET['advertiser_id']) ? intval($_GET['advertiser_id']) : 0;
    if ($advertiser_id > 0) {
        header("Location: view_campaigns.php?advertiser_id=" . $advertiser_id);
    } else {
        header("Location: view_campaigns.php");
    }
    exit();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM campaigns WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "Campaign deleted successfully!";
    } else {
        $error = "Error deleting campaign: " . $conn->error;
    }
    $stmt->close();
    
    // Redirect to avoid resubmission
    $advertiser_id = isset($_GET['advertiser_id']) ? intval($_GET['advertiser_id']) : 0;
    if ($advertiser_id > 0) {
        header("Location: view_campaigns.php?advertiser_id=" . $advertiser_id);
    } else {
        header("Location: view_campaigns.php");
    }
    exit();
}

// Get advertiser ID from URL parameter
$advertiser_id = isset($_GET['advertiser_id']) ? intval($_GET['advertiser_id']) : 0;

// Fetch advertiser details
$advertiser_name = "";
if ($advertiser_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM advertisers WHERE id = ?");
    $stmt->bind_param("i", $advertiser_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $advertiser = $result->fetch_assoc();
        $advertiser_name = $advertiser['name'];
    } else {
        $advertiser_id = 0; // Reset if invalid ID
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Advertiser Campaigns - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
      <?php include '../includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">View Advertiser Campaigns</h1>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Select Advertiser Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Select Advertiser</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="advertiser_id" class="form-label">Advertiser</label>
                                        <select class="form-control" id="advertiser_id" name="advertiser_id" required>
                                            <option value="">Select Advertiser</option>
                                            <?php
                                            $sql = "SELECT id, name FROM advertisers ORDER BY name";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                while($row = $result->fetch_assoc()) {
                                                    $selected = ($advertiser_id == $row['id']) ? "selected" : "";
                                                    echo "<option value='".$row['id']."' ".$selected.">".$row['name']."</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">View Campaigns</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($advertiser_id > 0): ?>
                <!-- Campaigns List for Selected Advertiser -->
                <div class="card">
                    <div class="card-header">
                        <h5>Campaigns for <?php echo htmlspecialchars($advertiser_name); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Campaign Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Type</th>
                                        <th>Short Code</th>
                                        <th>Clicks</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT c.* FROM campaigns c 
                                            JOIN campaign_advertisers ca ON c.id = ca.campaign_id
                                            WHERE ca.advertiser_id = ? 
                                            ORDER BY c.id DESC";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $advertiser_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>".$row['id']."</td>";
                                            echo "<td>".$row['campaign_name']."</td>";
                                            echo "<td>".$row['start_date']."</td>";
                                            echo "<td>".$row['end_date']."</td>";
                                            echo "<td>".$row['type']."</td>";
                                            echo "<td><a href='../".$row['short_code']."' target='_blank'>".$row['short_code']."</a></td>";
                                            echo "<td>".$row['clicks']."</td>";
                                            echo "<td><span class='badge bg-".($row['status'] == 'active' ? 'success' : 'danger')."'>".$row['status']."</span></td>";
                                            echo "<td>";
                                            echo "<a href='?toggle_status=".$row['id']."&advertiser_id=".$advertiser_id."' class='btn btn-sm btn-".($row['status'] == 'active' ? 'warning' : 'success')."'>".($row['status'] == 'active' ? 'Deactivate' : 'Activate')."</a> ";
                                            echo "<a href='?delete=".$row['id']."&advertiser_id=".$advertiser_id."' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this campaign?\")'>Delete</a>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center'>No campaigns found for this advertiser</td></tr>";
                                    }
                                    $stmt->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>