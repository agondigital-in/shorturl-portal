<?php
// check_cpv_tables.php - Check CPV table structure
require_once 'db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>Checking CPV Tables Structure</h2>";
    
    // Check cpv_campaigns table
    echo "<h3>cpv_campaigns table:</h3>";
    $stmt = $conn->query("DESCRIBE cpv_campaigns");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    // Check cpv_clicks table
    echo "<h3>cpv_clicks table:</h3>";
    $stmt = $conn->query("DESCRIBE cpv_clicks");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
