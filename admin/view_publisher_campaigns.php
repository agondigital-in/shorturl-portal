<?php
require_once '../auth_check.php';
require_once '../config.php';

// Check if user is admin
if ($role != 'admin') {
    header("Location: ../super_admin/dashboard.php");
    exit();
}

// Get publisher ID from URL parameter
$publisher_id = isset($_GET['publisher_id']) ? intval($_GET['publisher_id']) : 0;

// Fetch publisher details
$publisher_name = "";
if ($publisher_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM publishers WHERE id = ?");
    // Check if prepare succeeded
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $publisher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $publisher = $result->fetch_assoc();
        $publisher_name = $publisher['name'];
    } else {
        $publisher_id = 0; // Reset if invalid ID
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Publisher Campaigns - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">View Publisher Campaigns</h1>
                </div>

                <!-- Select Publisher Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Select Publisher</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="publisher_id" class="form-label">Publisher</label>
                                        <select class="form-control" id="publisher_id" name="publisher_id" required>
                                            <option value="">Select Publisher</option>
                                            <?php
                                            $sql = "SELECT id, name FROM publishers ORDER BY name";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                while($row = $result->fetch_assoc()) {
                                                    $selected = ($publisher_id == $row['id']) ? "selected" : "";
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

                <?php if ($publisher_id > 0): ?>
                <!-- Campaigns List for Selected Publisher -->
                <div class="card">
                    <div class="card-header">
                        <h5>Campaigns for <?php echo htmlspecialchars($publisher_name); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Campaign Name</th>
                                        <th>Advertisers</th>
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
                                    // Updated query to get advertisers through the campaign_advertisers junction table
                                    $sql = "SELECT c.* FROM campaigns c 
                                            JOIN campaign_publishers cp ON c.id = cp.campaign_id
                                            WHERE cp.publisher_id = ? 
                                            ORDER BY c.id DESC";
                                    $stmt = $conn->prepare($sql);
                                    // Check if prepare succeeded
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
                                            
                                            // Get advertisers for this campaign
                                            $advertisers_sql = "SELECT a.name FROM advertisers a 
                                                               JOIN campaign_advertisers ca ON a.id = ca.advertiser_id 
                                                               WHERE ca.campaign_id = ".$row['id'];
                                            $advertisers_result = $conn->query($advertisers_sql);
                                            $advertisers = [];
                                            if ($advertisers_result && $advertisers_result->num_rows > 0) {
                                                while($advertiser = $advertisers_result->fetch_assoc()) {
                                                    $advertisers[] = $advertiser['name'];
                                                }
                                            }
                                            $advertisers_list = !empty($advertisers) ? implode(", ", $advertisers) : "N/A";
                                            
                                            echo "<td>".$advertisers_list."</td>";
                                            echo "<td>".$row['start_date']."</td>";
                                            echo "<td>".$row['end_date']."</td>";
                                            echo "<td>".$row['type']."</td>";
                                            echo "<td><a href='../".$row['short_code']."' target='_blank'>".$row['short_code']."</a></td>";
                                            echo "<td>".$row['clicks']."</td>";
                                            echo "<td><span class='badge bg-".($row['status'] == 'active' ? 'success' : 'danger')."'>".$row['status']."</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center'>No campaigns found for this publisher</td></tr>";
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