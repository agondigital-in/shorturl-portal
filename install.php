<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS shorturl");
    echo "Database 'shorturl' created successfully<br>";
    
    // Use the database
    $pdo->exec("USE shorturl");
    
    // Create table
    $pdo->exec("CREATE TABLE IF NOT EXISTS urls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_url TEXT NOT NULL,
        short_code VARCHAR(10) NOT NULL UNIQUE,
        click_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "Table 'urls' created successfully<br>";
    echo "<br>Installation completed successfully!<br>";
    echo "<a href='/'>Go to URL Shortener</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>