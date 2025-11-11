<?php
require_once 'config.php';

// Get the short code from the URL
$short_code = $_GET['p'] ?? '';

// If no short code from query parameter, try to get it from the path
if (empty($short_code)) {
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path_parts = explode('/', trim($path, '/'));
    $last_part = end($path_parts);
    
    // Check if it matches our pattern (p followed by numbers)
    if (preg_match('/^[pP]\d+$/', $last_part)) {
        $short_code = $last_part;
    }
}

if (empty($short_code)) {
    die("Invalid link");
}

// Get the campaign with the short code
$stmt = $conn->prepare("SELECT * FROM campaigns WHERE short_code = ?");
$stmt->bind_param("s", $short_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Campaign not found");
}

$campaign = $result->fetch_assoc();

// Check if campaign is active
if ($campaign['status'] != 'active') {
    die("This campaign is inactive.");
}

// Check if today's date is between start and end dates
$today = date('Y-m-d');
if ($today < $campaign['start_date'] || $today > $campaign['end_date']) {
    // Update campaign status to inactive if it's expired
    $stmt = $conn->prepare("UPDATE campaigns SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param("i", $campaign['id']);
    $stmt->execute();
    $stmt->close();
    
    die("This campaign has expired.");
}

// Increment click count
$stmt = $conn->prepare("UPDATE campaigns SET clicks = clicks + 1 WHERE id = ?");
$stmt->bind_param("i", $campaign['id']);
$stmt->execute();
$stmt->close();

// Get advertiser ID from query parameter if provided
$advertiser_id = $_GET['aid'] ?? null;

// Redirect to the original URL
header("Location: " . $campaign['website_url']);
exit();
?>