<?php
require_once 'config.php';

echo "<h1>Database Connection Test</h1>";

// Test connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "<p>✅ Database connection successful!</p>";

// Test users table
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>✅ Users table exists. Total users: " . $row['count'] . "</p>";
} else {
    echo "<p>❌ Error accessing users table: " . $conn->error . "</p>";
}

// Test advertisers table
$result = $conn->query("SELECT COUNT(*) as count FROM advertisers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>✅ Advertisers table exists. Total advertisers: " . $row['count'] . "</p>";
} else {
    echo "<p>❌ Error accessing advertisers table: " . $conn->error . "</p>";
}

// Test campaigns table
$result = $conn->query("SELECT COUNT(*) as count FROM campaigns");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>✅ Campaigns table exists. Total campaigns: " . $row['count'] . "</p>";
} else {
    echo "<p>❌ Error accessing campaigns table: " . $conn->error . "</p>";
}

// Test publishers table
$result = $conn->query("SELECT COUNT(*) as count FROM publishers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>✅ Publishers table exists. Total publishers: " . $row['count'] . "</p>";
} else {
    echo "<p>❌ Error accessing publishers table: " . $conn->error . "</p>";
}

// Test campaign_advertisers table
$result = $conn->query("SELECT COUNT(*) as count FROM campaign_advertisers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>✅ Campaign_Advertisers table exists. Total records: " . $row['count'] . "</p>";
} else {
    echo "<p>❌ Error accessing campaign_advertisers table: " . $conn->error . "</p>";
}

// Test campaign_publishers table
$result = $conn->query("SELECT COUNT(*) as count FROM campaign_publishers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>✅ Campaign_Publishers table exists. Total records: " . $row['count'] . "</p>";
} else {
    echo "<p>❌ Error accessing campaign_publishers table: " . $conn->error . "</p>";
}

echo "<h2>Sample Data</h2>";

// Show sample users
echo "<h3>Users</h3>";
$result = $conn->query("SELECT * FROM users LIMIT 5");
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Created At</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$row['id']."</td>";
        echo "<td>".$row['username']."</td>";
        echo "<td>".$row['role']."</td>";
        echo "<td>".$row['created_at']."</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found</p>";
}

$conn->close();
?>