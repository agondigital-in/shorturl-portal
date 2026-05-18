<?php
// install_agencies.php - Install Agencies Feature
require_once 'db_connection.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Agencies Feature</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .success {
            color: #10b981;
            background: #f0fdf4;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #ef4444;
            background: #fef2f2;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #3b82f6;
            background: #eff6ff;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 10px 10px 0;
            font-weight: 600;
        }
        .btn:hover {
            opacity: 0.9;
        }
        pre {
            background: #1e293b;
            color: #10b981;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🚀 Installing Agencies Feature</h2>
        
<?php
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h3>Step 1: Creating Agencies Table</h3>";
    
    // Create agencies table
    $sql = "CREATE TABLE IF NOT EXISTS `agencies` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `email` varchar(255) DEFAULT NULL,
      `company` varchar(255) DEFAULT NULL,
      `phone` varchar(50) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql);
    echo "<div class='success'>✓ Agencies table created successfully!</div>";
    echo "<pre>$sql</pre>";
    
    echo "<h3>Step 2: Updating Campaigns Table</h3>";
    
    // Check if agency_id column exists
    $stmt = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'agency_id'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Adding agency_id column to campaigns table...</p>";
        $conn->exec("ALTER TABLE `campaigns` ADD COLUMN `agency_id` int(11) DEFAULT NULL AFTER `id`");
        echo "<div class='success'>✓ Agency_id column added successfully!</div>";
        echo "<pre>ALTER TABLE `campaigns` ADD COLUMN `agency_id` int(11) DEFAULT NULL AFTER `id`</pre>";
        
        // Add index
        $conn->exec("ALTER TABLE `campaigns` ADD KEY `agency_id` (`agency_id`)");
        echo "<div class='success'>✓ Index added on agency_id column!</div>";
    } else {
        echo "<div class='info'>ℹ Agency_id column already exists in campaigns table.</div>";
    }
    
    echo "<h3>Step 3: Verification</h3>";
    
    // Verify agencies table
    $stmt = $conn->query("SHOW TABLES LIKE 'agencies'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✓ Agencies table exists</div>";
        
        // Show table structure
        $stmt = $conn->query("DESCRIBE agencies");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><strong>Table Structure:</strong></p>";
        echo "<pre>";
        foreach ($columns as $col) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
        echo "</pre>";
    }
    
    // Verify campaigns table update
    $stmt = $conn->query("SHOW COLUMNS FROM campaigns LIKE 'agency_id'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✓ Campaigns table updated with agency_id column</div>";
    }
    
    echo "<h3 style='color: #10b981;'>✅ Installation Completed Successfully!</h3>";
    echo "<p>Agencies feature has been installed and is ready to use.</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Go to Super Admin Dashboard</li>";
    echo "<li>Click on 'Agencies' in the sidebar</li>";
    echo "<li>Add your first agency</li>";
    echo "<li>Create campaigns and assign agencies</li>";
    echo "</ol>";
    
    echo "<div style='margin-top: 30px;'>";
    echo "<a href='super_admin/dashboard.php' class='btn'>📊 Go to Dashboard</a>";
    echo "<a href='super_admin/agencies.php' class='btn'>🤝 Manage Agencies</a>";
    echo "<a href='super_admin/add_campaign.php' class='btn'>➕ Add Campaign</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li>Check database connection in db_connection.php</li>";
    echo "<li>Verify database user has CREATE and ALTER permissions</li>";
    echo "<li>Check if database exists</li>";
    echo "<li>Try running the SQL manually in phpMyAdmin</li>";
    echo "</ol>";
}
?>
    </div>
</body>
</html>
