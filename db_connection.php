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
$dbname = $_ENV['DB_DATABASE'] ?? 'default';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3308';

// PDO connection
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // If database doesn't exist, show a friendly message
    if ($e->getCode() == 1049) {
        die("Database not found. Please run install.php first or create the database manually.");
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

// MySQLi connection (for backward compatibility with existing code)
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>