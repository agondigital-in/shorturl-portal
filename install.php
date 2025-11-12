<?php
// Load environment variables from .env file
$env_file = __DIR__ . '/.env';

if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Application environment
$app_env = $_ENV['APP_ENV'] ?? 'local';
$app_debug = $_ENV['APP_DEBUG'] ?? 'true';
$app_url = $_ENV['APP_URL'] ?? 'http://localhost';

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_DATABASE'] ?? 'ads_platform'; // Updated to match database.sql
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3308'; // Updated default port

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "Database '$dbname' created successfully<br>";
    
    // Use the database
    $pdo->exec("USE `$dbname`");
    
    // Create tables based on database.sql
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS advertisers (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS publishers (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS campaigns (
        id INT(11) NOT NULL AUTO_INCREMENT,
        campaign_name VARCHAR(100) NOT NULL,
        advertiser_id INT(11) NOT NULL,
        publisher_id INT(11),
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        advertiser_payout DECIMAL(10,2) NOT NULL,
        publisher_payout DECIMAL(10,2) NOT NULL,
        type ENUM('CPR', 'CPL', 'CPC', 'CPM', 'CPS', 'None') NOT NULL DEFAULT 'None',
        website_url TEXT NOT NULL,
        short_code VARCHAR(20) NOT NULL UNIQUE,
        clicks INT(11) NOT NULL DEFAULT 0,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        target_leads INT(11) DEFAULT 0,
        validated_leads INT(11) DEFAULT 0,
        total_amount DECIMAL(10,2) DEFAULT 0.00,
        advertiser_payment_status ENUM('pending', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
        FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE SET NULL
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS campaign_advertisers (
        id INT(11) NOT NULL AUTO_INCREMENT,
        campaign_id INT(11) NOT NULL,
        advertiser_id INT(11) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
        FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
        UNIQUE KEY unique_campaign_advertiser (campaign_id, advertiser_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS campaign_publishers (
        id INT(11) NOT NULL AUTO_INCREMENT,
        campaign_id INT(11) NOT NULL,
        publisher_id INT(11) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
        FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE CASCADE,
        UNIQUE KEY unique_campaign_publisher (campaign_id, publisher_id)
    )");
    
    // Insert default admin user
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role) VALUES (?, ?, ?)");
    $hashedPassword = password_hash('Agondigital@2020', PASSWORD_DEFAULT);
    $stmt->execute(['admin', $hashedPassword, 'super_admin']);
    
    echo "Tables created successfully<br>";
    echo "<br>Installation completed successfully!<br>";
    echo "<a href='/'>Go to Ad Campaign Platform</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>