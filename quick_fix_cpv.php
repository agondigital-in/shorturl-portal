<?php
// quick_fix_cpv.php - Quick fix for CPV tables
require_once 'db_connection.php';

echo "<h2>CPV Tables Quick Fix</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h3>Step 1: Checking existing tables...</h3>";
    
    // Check if tables exist
    $tables = $conn->query("SHOW TABLES LIKE 'cpv_%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Found tables: " . implode(', ', $tables) . "<br><br>";
    
    // Drop all CPV related tables
    echo "<h3>Step 2: Dropping old tables...</h3>";
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    $conn->exec("DROP TABLE IF EXISTS cpv_clicks");
    echo "✅ Dropped cpv_clicks<br>";
    $conn->exec("DROP TABLE IF EXISTS cpv_campaigns");
    echo "✅ Dropped cpv_campaigns<br>";
    $conn->exec("DROP TABLE IF EXISTS cpv_links");
    echo "✅ Dropped cpv_links<br>";
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Create new tables
    echo "<h3>Step 3: Creating new tables...</h3>";
    
    $sql1 = "CREATE TABLE cpv_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campaign_name VARCHAR(255) NOT NULL,
        original_url TEXT NOT NULL,
        short_code VARCHAR(20) UNIQUE NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        total_clicks INT DEFAULT 0,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_short_code (short_code),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql1);
    echo "✅ Created cpv_campaigns table<br>";
    
    $sql2 = "CREATE TABLE cpv_clicks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id INT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        referer TEXT,
        is_duplicate TINYINT(1) DEFAULT 0,
        clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_campaign_ip_date (campaign_id, ip_address, clicked_at),
        KEY idx_clicked_at (clicked_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql2);
    echo "✅ Created cpv_clicks table<br>";
    
    // Verify structure
    echo "<h3>Step 4: Verifying table structure...</h3>";
    
    echo "<h4>cpv_campaigns columns:</h4><ul>";
    $cols = $conn->query("SHOW COLUMNS FROM cpv_campaigns")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "<li>{$col['Field']} - {$col['Type']}</li>";
    }
    echo "</ul>";
    
    echo "<h4>cpv_clicks columns:</h4><ul>";
    $cols = $conn->query("SHOW COLUMNS FROM cpv_clicks")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "<li>{$col['Field']} - {$col['Type']}</li>";
    }
    echo "</ul>";
    
    echo "<h3 style='color: green;'>✅ All Done! Tables created successfully.</h3>";
    echo "<p><a href='super_admin/cpv.php' style='padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px;'>Go to CPV Page →</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
}
?>
