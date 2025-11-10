<?php
$host = 'localhost';
$dbname = 'shorturl';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // If database doesn't exist, show a friendly message
    if ($e->getCode() == 1049) {
        die("Database not found. Please run install.php first or create the database manually.");
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}
?>