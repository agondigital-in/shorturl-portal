<?php
// install_cpv.php - Install CPV tables
session_start();

// Only super admin can run this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    die('Access denied! Please login as super admin first.');
}

require_once 'db_connection.php';

$messages = [];
$errors = [];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create cpv_campaigns table
    $sql1 = "CREATE TABLE IF NOT EXISTS `cpv_campaigns` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `campaign_name` VARCHAR(255) NOT NULL,
        `original_url` TEXT NOT NULL,
        `short_code` VARCHAR(20) UNIQUE NOT NULL,
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `total_clicks` INT DEFAULT 0,
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_short_code` (`short_code`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql1);
    $messages[] = "✅ Table 'cpv_campaigns' created successfully!";
    
    // Create cpv_clicks table
    $sql2 = "CREATE TABLE IF NOT EXISTS `cpv_clicks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `campaign_id` INT NOT NULL,
        `ip_address` VARCHAR(45) NOT NULL,
        `user_agent` TEXT,
        `referer` TEXT,
        `is_duplicate` TINYINT(1) DEFAULT 0 COMMENT '0=Original, 1=Duplicate (same IP same day)',
        `clicked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_campaign_ip_date` (`campaign_id`, `ip_address`, `clicked_at`),
        INDEX `idx_clicked_at` (`clicked_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql2);
    $messages[] = "✅ Table 'cpv_clicks' created successfully!";
    
    $messages[] = "🎉 CPV feature installed successfully!";
    
} catch (PDOException $e) {
    $errors[] = "❌ Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install CPV Feature</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">CPV Feature Installation</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($messages as $msg): ?>
                            <div class="alert alert-success"><?php echo $msg; ?></div>
                        <?php endforeach; ?>
                        
                        <?php foreach ($errors as $err): ?>
                            <div class="alert alert-danger"><?php echo $err; ?></div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($errors)): ?>
                            <a href="super_admin/cpv.php" class="btn btn-primary w-100">
                                Go to CPV Campaigns →
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
