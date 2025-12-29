<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

echo "<h2>Create Super Admin User</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $username = 'agone@gmail.com';
    $password = 'agone@5185';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'super_admin';
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing user
        $stmt = $conn->prepare("UPDATE users SET password = ?, role = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $role, $username]);
        echo "<p style='color:green'>✓ User <strong>$username</strong> updated successfully!</p>";
    } else {
        // Create new user
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $role]);
        echo "<p style='color:green'>✓ Super Admin created successfully!</p>";
    }
    
    echo "<hr>";
    echo "<h3>Login Details:</h3>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>Role:</strong> Super Admin</p>";
    echo "<hr>";
    echo "<p><a href='login.php' style='background:#6366f1;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;'>→ Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
