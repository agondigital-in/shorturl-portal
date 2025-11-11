<?php
// Installation script for Ad Campaign Platform

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ads_platform');

echo "<h1>Ad Campaign Platform Installation</h1>";

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "<p>✅ Database connection successful!</p>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Database '" . DB_NAME . "' created or already exists!</p>";
} else {
    echo "<p>❌ Error creating database: " . $conn->error . "</p>";
    $conn->close();
    exit();
}

// Select database
$conn->select_db(DB_NAME);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Users table created or already exists!</p>";
} else {
    echo "<p>❌ Error creating users table: " . $conn->error . "</p>";
}

// Create advertisers table
$sql = "CREATE TABLE IF NOT EXISTS advertisers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Advertisers table created or already exists!</p>";
} else {
    echo "<p>❌ Error creating advertisers table: " . $conn->error . "</p>";
}

// Create campaigns table
$sql = "CREATE TABLE IF NOT EXISTS campaigns (
    id INT(11) NOT NULL AUTO_INCREMENT,
    campaign_name VARCHAR(100) NOT NULL,
    advertiser_id INT(11) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    advertiser_payout DECIMAL(10,2) NOT NULL,
    publisher_payout DECIMAL(10,2) NOT NULL,
    type ENUM('CPR', 'CPL', 'CPC', 'CPM', 'CPS', 'None') NOT NULL DEFAULT 'None',
    website_url TEXT NOT NULL,
    short_code VARCHAR(20) NOT NULL UNIQUE,
    clicks INT(11) NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Campaigns table created or already exists!</p>";
} else {
    echo "<p>❌ Error creating campaigns table: " . $conn->error . "</p>";
}

// Check if super admin user exists
$result = $conn->query("SELECT id FROM users WHERE username = 'superadmin'");
if ($result->num_rows == 0) {
    // Insert default super admin user (password: admin123)
    $password_hash = '$2y$10$e0vJZ5mNmK0GkHQpHEaVye5IWQvz0HBAIqJ7KNvL4q/UH5B9u9JjO'; // Hash for 'admin123'
    $sql = "INSERT INTO users (username, password, role) VALUES ('superadmin', '$password_hash', 'super_admin')";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Default super admin user created!</p>";
        echo "<p><strong>Default Login:</strong></p>";
        echo "<ul>";
        echo "<li>Username: superadmin</li>";
        echo "<li>Password: admin123</li>";
        echo "<li><strong>IMPORTANT:</strong> Change this password immediately after first login!</li>";
        echo "</ul>";
    } else {
        echo "<p>❌ Error creating super admin user: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ Super admin user already exists!</p>";
}

$conn->close();

echo "<h2>Installation Complete!</h2>";
echo "<p>You can now <a href='login.php'>login to the platform</a>.</p>";
echo "<p>For security reasons, please delete this install.php file after installation.</p>";
?>