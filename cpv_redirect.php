<?php
// cpv_redirect.php - Handles CPV short URL redirects with optimized IP tracking
session_start();

require_once 'db_connection.php';

$short_code = $_GET['c'] ?? '';

if (empty($short_code)) {
    http_response_code(400);
    die('Invalid link!');
}

// Prevent double counting from browser prefetch/double requests
$request_key = 'cpv_' . $short_code . '_' . time();
if (isset($_SESSION['last_cpv_click']) && $_SESSION['last_cpv_click'] === $short_code) {
    $last_time = $_SESSION['last_cpv_time'] ?? 0;
    if (time() - $last_time < 3) { // 3 second cooldown
        // Skip counting, just redirect
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT original_url FROM cpv_campaigns WHERE short_code = ? AND status = 'active'");
            $stmt->execute([$short_code]);
            $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($campaign) {
                header("Location: " . $campaign['original_url'], true, 302);
                exit();
            }
        } catch (Exception $e) {}
        die('Link not found!');
    }
}

$_SESSION['last_cpv_click'] = $short_code;
$_SESSION['last_cpv_time'] = time();

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
    if (strpos($ip_address, ',') !== false) {
        $ip_address = trim(explode(',', $ip_address)[0]);
    }
    
    // Check if this IP already clicked today
    $stmt = $conn->prepare("SELECT id FROM cpv_clicks WHERE campaign_id = ? AND ip_address = ? AND DATE(clicked_at) = ?");
    $stmt->execute([$campaign['id'], $ip_address, $today]);
    $existing_click = $stmt->fetch();
    
    $is_duplicate = $existing_click ? true : false;
    
    // Only store unique IPs in cpv_clicks
    if (!$is_duplicate) {
        $stmt = $conn->prepare("INSERT INTO cpv_clicks (campaign_id, ip_address, clicked_at) VALUES (?, ?, NOW())");
        $stmt->execute([$campaign['id'], $ip_address]);
    }
    
    // Update daily stats
    $stmt = $conn->prepare("
        INSERT INTO cpv_daily_stats (campaign_id, stat_date, total_clicks, original_clicks, duplicate_clicks) 
        VALUES (?, ?, 1, ?, ?)
        ON DUPLICATE KEY UPDATE 
            total_clicks = total_clicks + 1,
            original_clicks = original_clicks + ?,
            duplicate_clicks = duplicate_clicks + ?
    ");
    $original_inc = $is_duplicate ? 0 : 1;
    $duplicate_inc = $is_duplicate ? 1 : 0;
    $stmt->execute([$campaign['id'], $today, $original_inc, $duplicate_inc, $original_inc, $duplicate_inc]);
    
    // Auto-cleanup: Delete IP records older than 1 month
    if (rand(1, 100) <= 5) {
        $stmt = $conn->prepare("DELETE FROM cpv_clicks WHERE clicked_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
        $stmt->execute();
    }
    
    // Redirect to original URL
    header("Location: " . $campaign['original_url'], true, 302);
    exit();
    
} catch (PDOException $e) {
    http_response_code(500);
    die("Server Error");
}
?>
