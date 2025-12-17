<?php
// cpv_redirect.php - Handles CPV short URL redirects with IP tracking
require_once 'db_connection.php';

$short_code = $_GET['c'] ?? '';

if (empty($short_code)) {
    http_response_code(400);
    die('Invalid link!');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Find the CPV campaign
    $stmt = $conn->prepare("SELECT id, original_url, start_date, end_date, status FROM cpv_campaigns WHERE short_code = ?");
    $stmt->execute([$short_code]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        http_response_code(404);
        die('Link not found!');
    }
    
    // Check if campaign is active
    if ($campaign['status'] !== 'active') {
        http_response_code(404);
        die('This campaign is inactive!');
    }
    
    // Check date range
    $today = date('Y-m-d');
    if ($today < $campaign['start_date'] || $today > $campaign['end_date']) {
        http_response_code(404);
        die('This campaign is not active on this date!');
    }
    
    // Get visitor IP
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // Handle multiple IPs in X-Forwarded-For
    if (strpos($ip_address, ',') !== false) {
        $ip_address = trim(explode(',', $ip_address)[0]);
    }
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Check if this IP already clicked today (duplicate check)
    $stmt = $conn->prepare("SELECT id FROM cpv_clicks WHERE campaign_id = ? AND ip_address = ? AND DATE(clicked_at) = ?");
    $stmt->execute([$campaign['id'], $ip_address, $today]);
    $existing_click = $stmt->fetch();
    
    $is_duplicate = $existing_click ? 1 : 0;
    
    // Record the click with IP
    $stmt = $conn->prepare("INSERT INTO cpv_clicks (campaign_id, ip_address, user_agent, referer, is_duplicate, clicked_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$campaign['id'], $ip_address, $user_agent, $referer, $is_duplicate]);
    
    // Update total click count
    $stmt = $conn->prepare("UPDATE cpv_campaigns SET total_clicks = total_clicks + 1 WHERE id = ?");
    $stmt->execute([$campaign['id']]);
    
    // Redirect to original URL
    header("Location: " . $campaign['original_url'], true, 302);
    exit();
    
} catch (PDOException $e) {
    http_response_code(500);
    die("Server Error");
}
?>
