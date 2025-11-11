<?php
require_once 'config.php';

echo "<h1>Short URL System Test</h1>";

// Test creating a sample campaign
echo "<h2>Creating Test Campaign</h2>";

// First, create a test advertiser if not exists
$stmt = $conn->prepare("INSERT IGNORE INTO advertisers (id, name, email) VALUES (1, 'Test Advertiser', 'test@example.com')");
$stmt->execute();
$stmt->close();

// Create a test campaign
$stmt = $conn->prepare("INSERT IGNORE INTO campaigns (id, campaign_name, advertiser_id, start_date, end_date, advertiser_payout, publisher_payout, type, website_url, short_code) VALUES (1, 'Test Campaign', 1, '2025-01-01', '2025-12-31', 1.00, 0.50, 'CPC', 'https://example.com', 'p1')");
$stmt->execute();
$stmt->close();

echo "<p>✅ Test campaign created with short code 'p1'</p>";

// Test the redirect functionality
echo "<h2>Testing Redirect</h2>";
echo "<p>Click on the link below to test the redirect:</p>";
echo "<p><a href='p1' target='_blank'>Test Short URL (p1)</a></p>";
echo "<p>This should redirect to https://example.com and increment the click count.</p>";

// Show current click count
echo "<h2>Current Click Count</h2>";
$stmt = $conn->prepare("SELECT clicks FROM campaigns WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();
$campaign = $result->fetch_assoc();
$stmt->close();

echo "<p>Current clicks for test campaign: " . $campaign['clicks'] . "</p>";

$conn->close();

echo "<h2>Test Complete</h2>";
echo "<p>If the redirect worked, the click count should increment each time you click the link.</p>";
?>