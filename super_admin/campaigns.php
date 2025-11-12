<?php
require_once '../auth_check.php';
require_once '../config.php';

// Check if user is super admin
if ($role != 'super_admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Handle form submission for adding new campaign
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_campaign'])) {
    $campaign_name = $_POST['campaign_name'];
    $advertiser_ids = $_POST['advertiser_id'] ?? []; // Array of advertiser IDs
    $publisher_ids = $_POST['publisher_id'] ?? []; // Array of publisher IDs
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $advertiser_payout = $_POST['advertiser_payout'];
    $publisher_payout = $_POST['publisher_payout'];
    $type = $_POST['type'];
    $website_url = $_POST['website_url'];
    
    // Validate that at least one advertiser and one publisher is selected
    if (empty($advertiser_ids)) {
        $error = "Please select at least one advertiser.";
    } elseif (empty($publisher_ids)) {
        $error = "Please select at least one publisher.";
    } else {
        // Generate short code (p + campaign id)
        // First, insert the campaign to get the ID
        $stmt = $conn->prepare("INSERT INTO campaigns (campaign_name, start_date, end_date, advertiser_payout, publisher_payout, type, website_url, short_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Temporary short code, will be updated after getting the ID
        $temp_short_code = "temp_" . time();
        $stmt->bind_param("ssssssss", $campaign_name, $start_date, $end_date, $advertiser_payout, $publisher_payout, $type, $website_url, $temp_short_code);
        
        if ($stmt->execute()) {
            // Get the inserted campaign ID
            $campaign_id = $conn->insert_id;
            
            // Insert advertiser associations
            foreach ($advertiser_ids as $advertiser_id) {
                $stmt = $conn->prepare("INSERT INTO campaign_advertisers (campaign_id, advertiser_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $campaign_id, $advertiser_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Insert publisher associations
            foreach ($publisher_ids as $publisher_id) {
                $stmt = $conn->prepare("INSERT INTO campaign_publishers (campaign_id, publisher_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $campaign_id, $publisher_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Generate the proper short code
            $short_code = "p" . $campaign_id;
            
            // Update the campaign with the proper short code
            $stmt = $conn->prepare("UPDATE campaigns SET short_code = ? WHERE id = ?");
            $stmt->bind_param("si", $short_code, $campaign_id);
            
            if ($stmt->execute()) {
                // Redirect to dashboard after successful campaign creation
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Error updating campaign short code: " . $conn->error;
            }
        } else {
            $error = "Error adding campaign: " . $conn->error;
        }
        $stmt->close();
    }
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
}

// Handle advertiser payment status toggle
if (isset($_GET['toggle_payment_status'])) {
    $id = $_GET['toggle_payment_status'];
    $stmt = $conn->prepare("SELECT advertiser_payment_status FROM campaigns WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $campaign = $result->fetch_assoc();
    
    if ($campaign) {
        $new_status = $campaign['advertiser_payment_status'] == 'completed' ? 'pending' : 'completed';
        $stmt = $conn->prepare("UPDATE campaigns SET advertiser_payment_status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        
        if ($stmt->execute()) {
            $success = "Advertiser payment status updated successfully!";
        } else {
            $error = "Error updating advertiser payment status: " . $conn->error;
        }
    }
    $stmt->close();
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Campaigns - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
      <?php include '../includes/header.php'; ?>
    <style>
        .selection-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            background-color: #f8f9fa;
        }
        .selection-box:hover {
            background-color: #e9ecef;
        }
        .selection-box.active {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .dropdown-container {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 100%;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
        }
        .dropdown-content.show {
            display: block;
        }
        .dropdown-item {
            padding: 8px 12px;
            cursor: pointer;
        }
        .dropdown-item:hover {
            background-color: #f1f1f1;
        }
        .selected-items {
            margin-top: 5px;
            min-height: 20px;
        }
        .selected-item {
            display: inline-block;
            background-color: #0d6efd;
            color: white;
            padding: 2px 8px;
            margin: 2px;
            border-radius: 10px;
            font-size: 12px;
        }
        .remove-item {
            cursor: pointer;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Campaigns</h1>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Add Campaign Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Campaign</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="campaign_name" class="form-label">Campaign Name</label>
                                        <input type="text" class="form-control" id="campaign_name" name="campaign_name" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Advertisers (Required)</label>
                                        <div class="dropdown-container">
                                            <div class="selection-box" onclick="toggleDropdown('advertiser')">
                                                <span id="advertiser-placeholder">Select Advertisers</span>
                                                <div class="selected-items" id="advertiser-selected"></div>
                                            </div>
                                            <div class="dropdown-content" id="advertiser-dropdown">
                                                <?php
                                                $sql = "SELECT id, name FROM advertisers ORDER BY name";
                                                $result = $conn->query($sql);
                                                if ($result->num_rows > 0) {
                                                    while($row = $result->fetch_assoc()) {
                                                        echo "<div class='dropdown-item' data-id='".$row['id']."' data-name='".$row['name']."' onclick='selectItem(\"advertiser\", ".$row['id'].", \"".$row['name']."\")'>".$row['name']."</div>";
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <input type="hidden" name="advertiser_id[]" id="advertiser-ids" value="">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Publishers (Required)</label>
                                        <div class="dropdown-container">
                                            <div class="selection-box" onclick="toggleDropdown('publisher')">
                                                <span id="publisher-placeholder">Select Publishers</span>
                                                <div class="selected-items" id="publisher-selected"></div>
                                            </div>
                                            <div class="dropdown-content" id="publisher-dropdown">
                                                <?php
                                                $sql = "SELECT id, name FROM publishers ORDER BY name";
                                                $result = $conn->query($sql);
                                                if ($result->num_rows > 0) {
                                                    while($row = $result->fetch_assoc()) {
                                                        echo "<div class='dropdown-item' data-id='".$row['id']."' data-name='".$row['name']."' onclick='selectItem(\"publisher\", ".$row['id'].", \"".$row['name']."\")'>".$row['name']."</div>";
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <input type="hidden" name="publisher_id[]" id="publisher-ids" value="">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="advertiser_payout" class="form-label">Advertiser Payout ($)</label>
                                        <input type="number" step="0.01" class="form-control" id="advertiser_payout" name="advertiser_payout" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="publisher_payout" class="form-label">Publisher Payout ($)</label>
                                        <input type="number" step="0.01" class="form-control" id="publisher_payout" name="publisher_payout" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="type" class="form-label">Campaign Type</label>
                                        <select class="form-control" id="type" name="type" required>
                                            <option value="CPR">CPR</option>
                                            <option value="CPL">CPL</option>
                                            <option value="CPC">CPC</option>
                                            <option value="CPM">CPM</option>
                                            <option value="CPS">CPS</option>
                                            <option value="None">None</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="website_url" class="form-label">Website URL</label>
                                        <input type="url" class="form-control" id="website_url" name="website_url" placeholder="https://example.com" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" name="add_campaign" class="btn btn-primary">Add Campaign</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Campaigns List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Campaigns</h5>
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
                                        <th>Type</th>
                                        <th>Short Code</th>
                                        <th>Clicks</th>
                                        <th>Status</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT c.* FROM campaigns c ORDER BY c.id DESC";
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
                                            echo "<td>".$row['type']."</td>";
                                            echo "<td><a href='../".$row['short_code']."' target='_blank'>".$row['short_code']."</a></td>";
                                            echo "<td>".$row['clicks']."</td>";
                                            echo "<td><span class='badge bg-".($row['status'] == 'active' ? 'success' : 'danger')."'>".$row['status']."</span></td>";
                                            echo "<td><span class='badge bg-".($row['advertiser_payment_status'] == 'completed' ? 'success' : 'warning')."'>".$row['advertiser_payment_status']."</span></td>";
                                            echo "<td>";
                                            echo "<a href='?toggle_status=".$row['id']."' class='btn btn-sm btn-".($row['status'] == 'active' ? 'warning' : 'success')."'>".($row['status'] == 'active' ? 'Deactivate' : 'Activate')."</a> ";
                                            echo "<a href='?toggle_payment_status=".$row['id']."' class='btn btn-sm btn-".($row['advertiser_payment_status'] == 'completed' ? 'warning' : 'success')."'>".($row['advertiser_payment_status'] == 'completed' ? 'Mark Pending' : 'Mark Completed')."</a> ";
                                            echo "<a href='?delete=".$row['id']."' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this campaign?\")'>Delete</a>";
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
    // Set default dates to today and 30 days from now
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        const thirtyDays = new Date();
        thirtyDays.setDate(thirtyDays.getDate() + 30);
        const thirtyDaysStr = thirtyDays.toISOString().split('T')[0];
        
        document.getElementById('start_date').value = today;
        document.getElementById('end_date').value = thirtyDaysStr;
    });
    
    // Close dropdowns when clicking outside
    window.onclick = function(event) {
        if (!event.target.matches('.selection-box')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
    
    // Toggle dropdown visibility
    function toggleDropdown(type) {
        document.getElementById(type + '-dropdown').classList.toggle('show');
    }
    
    // Store selected items
    var selectedAdvertisers = [];
    var selectedPublishers = [];
    
    // Select item function
    function selectItem(type, id, name) {
        if (type === 'advertiser') {
            // Check if already selected
            if (!selectedAdvertisers.some(item => item.id === id)) {
                selectedAdvertisers.push({id: id, name: name});
                updateSelectedItems(type);
            }
        } else if (type === 'publisher') {
            // Check if already selected
            if (!selectedPublishers.some(item => item.id === id)) {
                selectedPublishers.push({id: id, name: name});
                updateSelectedItems(type);
            }
        }
        // Close dropdown after selection
        document.getElementById(type + '-dropdown').classList.remove('show');
    }
    
    // Update selected items display
    function updateSelectedItems(type) {
        var selectedContainer = document.getElementById(type + '-selected');
        var placeholder = document.getElementById(type + '-placeholder');
        var hiddenInput = document.getElementById(type + '-ids');
        
        if (type === 'advertiser') {
            selectedContainer.innerHTML = '';
            var ids = [];
            selectedAdvertisers.forEach(function(item) {
                var itemElement = document.createElement('span');
                itemElement.className = 'selected-item';
                itemElement.innerHTML = item.name + ' <span class="remove-item" onclick="removeItem(\'advertiser\', ' + item.id + ')">&times;</span>';
                selectedContainer.appendChild(itemElement);
                ids.push(item.id);
            });
            hiddenInput.value = ids.join(',');
            placeholder.style.display = selectedAdvertisers.length > 0 ? 'none' : 'inline';
        } else if (type === 'publisher') {
            selectedContainer.innerHTML = '';
            var ids = [];
            selectedPublishers.forEach(function(item) {
                var itemElement = document.createElement('span');
                itemElement.className = 'selected-item';
                itemElement.innerHTML = item.name + ' <span class="remove-item" onclick="removeItem(\'publisher\', ' + item.id + ')">&times;</span>';
                selectedContainer.appendChild(itemElement);
                ids.push(item.id);
            });
            hiddenInput.value = ids.join(',');
            placeholder.style.display = selectedPublishers.length > 0 ? 'none' : 'inline';
        }
    }
    
    // Remove item function
    function removeItem(type, id) {
        if (type === 'advertiser') {
            selectedAdvertisers = selectedAdvertisers.filter(item => item.id !== id);
            updateSelectedItems(type);
        } else if (type === 'publisher') {
            selectedPublishers = selectedPublishers.filter(item => item.id !== id);
            updateSelectedItems(type);
        }
    }
    </script>
</body>
</html>