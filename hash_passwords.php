<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

echo "<h2>Password Hash Update Tool</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get all users with plain text passwords (not starting with $2y$ which is bcrypt)
    $stmt = $conn->query("SELECT id, username, password FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    
    foreach ($users as $user) {
        // Check if password is already hashed (bcrypt starts with $2y$)
        if (strpos($user['password'], '$2y$') !== 0) {
            // Hash the plain text password
            $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
            
            // Update in database
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$hashedPassword, $user['id']]);
            
            echo "<p style='color:green'>✓ Updated password for user: <strong>" . htmlspecialchars($user['username']) . "</strong></p>";
            $updated++;
        } else {
            echo "<p style='color:blue'>→ Password already hashed for: <strong>" . htmlspecialchars($user['username']) . "</strong></p>";
        }
    }
    
    if ($updated > 0) {
        echo "<hr><p style='color:green; font-size:18px;'>✓ $updated password(s) hashed successfully!</p>";
    } else {
        echo "<hr><p style='color:blue;'>All passwords were already hashed.</p>";
    }
    
    echo "<p><a href='login.php'>→ Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
