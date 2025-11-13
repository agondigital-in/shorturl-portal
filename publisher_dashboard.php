<?php
// publisher_dashboard.php - Publisher Dashboard
session_start();

// Check if user is logged in and is a publisher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'publisher') {
    header('Location: publisher_login.php');
    exit();
}

require_once 'db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get campaigns assigned to this specific publisher with their tracking links
    $stmt = $conn->prepare("
        SELECT c.id, c.name as campaign_name, p.name as publisher_name, c.start_date, c.end_date, 
               c.campaign_type, psc.short_code, COALESCE(psc.clicks, 0) as clicks, c.status
        FROM campaigns c
        JOIN campaign_publishers cp ON c.id = cp.campaign_id
        JOIN publisher_short_codes psc ON c.id = psc.campaign_id AND cp.publisher_id = psc.publisher_id
        JOIN publishers p ON cp.publisher_id = p.id
        WHERE cp.publisher_id = ? AND c.status = 'active'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $assigned_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Dashboard - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Ads Platform</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a class="nav-link btn btn-outline-light" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h2>Your Assigned Campaigns</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (empty($assigned_campaigns)): ?>
            <div class="alert alert-info">You have no campaigns assigned yet.</div>
        <?php else: ?>
            <div class="card">
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
<th>Tracking Link</th>
<th>Clicks</th>
<th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned_campaigns as $campaign): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($campaign['id']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['publisher_name']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['start_date']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['end_date']); ?></td>
                                        <td><?php echo htmlspecialchars($campaign['campaign_type']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <code>https://localhost/tracking/c/<?php echo htmlspecialchars($campaign['short_code']); ?></code>
                                                <button class="btn btn-outline-primary btn-sm ms-2 copy-btn" onclick="copyToClipboard('https://localhost/tracking/c/<?php echo htmlspecialchars($campaign['short_code']); ?>', this)">Copy</button>
                                            </div>
                                        </td>
                                        <td><?php echo $campaign['clicks']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($campaign['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text, button) {
            // Create a temporary input element
            const tempInput = document.createElement('input');
            tempInput.style.position = 'absolute';
            tempInput.style.left = '-1000px';
            tempInput.value = text;
            document.body.appendChild(tempInput);
            
            // Select and copy the text
            tempInput.select();
            document.execCommand('copy');
            
            // Remove the temporary input
            document.body.removeChild(tempInput);
            
            // Change button text to indicate success
            const originalText = button.textContent;
            button.textContent = 'Copied!';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-success');
            
            // Reset button after 2 seconds
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-primary');
            }, 2000);
        }
    </script>
</body>
</html>