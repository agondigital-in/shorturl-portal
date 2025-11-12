<?php
require_once 'config.php';

echo "<h1>Environment Variables Test</h1>";

echo "<h2>Environment Configuration</h2>";
echo "<p><strong>APP_ENV:</strong> " . $app_env . "</p>";
echo "<p><strong>APP_DEBUG:</strong> " . $app_debug . "</p>";
echo "<p><strong>APP_URL:</strong> " . $app_url . "</p>";

echo "<h2>Database Configuration</h2>";
echo "<p><strong>DB_HOST:</strong> " . $host . "</p>";
echo "<p><strong>DB_DATABASE:</strong> " . $dbname . "</p>";
echo "<p><strong>DB_USERNAME:</strong> " . $username . "</p>";
echo "<p><strong>DB_PASSWORD:</strong> " . (empty($password) ? "(empty)" : "(set)") . "</p>";
echo "<p><strong>DB_PORT:</strong> " . $port . "</p>";

echo "<h2>Database Connection Test</h2>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Connected successfully to database '" . $dbname . "' on host '" . $host . ":" . $port . "'</p>";
}

echo "<h2>PDO Connection Test</h2>";
try {
    $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✅ PDO connection successful</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ PDO connection failed: " . $e->getMessage() . "</p>";
}

$conn->close();
?>