<?php
// pixel.php - Image Pixel Tracking Endpoint
// Returns a 1x1 transparent GIF and logs the impression

require_once 'db_connection.php';

// Get pixel code from URL
$pixel_code = $_GET['p'] ?? '';

if (empty($pixel_code)) {
    // Return transparent 1x1 GIF anyway
    outputPixel();
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Find pixel link
    $stmt = $conn->prepare("
        SELECT ipl.id, ipl.campaign_id, ipl.publisher_id 
        FROM image_pixel_links ipl
        JOIN campaigns c ON ipl.campaign_id = c.id
        WHERE ipl.pixel_code = ? 
        AND c.status = 'active'
        AND c.enable_image_pixel = 1
        AND CURDATE() BETWEEN c.start_date AND c.end_date
    ");
    $stmt->execute([$pixel_code]);
    $pixel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pixel) {
        // Log impression
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Insert impression log
        $stmt = $conn->prepare("
            INSERT INTO image_pixel_impressions 
            (pixel_id, campaign_id, publisher_id, ip_address, user_agent, referer) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $pixel['id'],
            $pixel['campaign_id'],
            $pixel['publisher_id'],
            $ip,
            $user_agent,
            $referer
        ]);
        
        // Update impression count
        $stmt = $conn->prepare("UPDATE image_pixel_links SET impressions = impressions + 1 WHERE id = ?");
        $stmt->execute([$pixel['id']]);
    }
} catch (Exception $e) {
    // Silently fail - don't break the pixel
    error_log("Pixel tracking error: " . $e->getMessage());
}

outputPixel();

function outputPixel() {
    // Prevent caching
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: image/gif');
    
    // 1x1 transparent GIF
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}
?>
