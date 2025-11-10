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
$dbname = $_ENV['DB_DATABASE'] ?? 'shorturl';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3012';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
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