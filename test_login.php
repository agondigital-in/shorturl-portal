<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

echo "<h2>Login Debug Test</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p style='color:green'>✓ Database connection successful!</p>";
    
    // Check if users table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ Users table exists</p>";
        
        // Get all users
        $stmt = $conn->query("SELECT id, username, role, password FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Users in database:</h3>";
        if (count($users) > 0) {
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Password Hash</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['username'] . "</td>";
                echo "<td>" . $user['role'] . "</td>";
                echo "<td>" . substr($user['password'], 0, 30) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Test password verification
            echo "<h3>Test Login:</h3>";
            echo "<p>Try logging in with one of the above usernames.</p>";
            echo "<p>If password is hashed with password_hash(), it should work.</p>";
            echo "<p>If password is plain text, login will fail because auth.php uses password_verify().</p>";
        } else {
            echo "<p style='color:red'>✗ No users found in database!</p>";
            echo "<p>You need to create a user first. Run this SQL:</p>";
            echo "<pre>INSERT INTO users (username, password, role) VALUES ('admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'super_admin');</pre>";
        }
    } else {
        echo "<p style='color:red'>✗ Users table does not exist!</p>";
        echo "<p>Run the database_schema.sql file first.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
