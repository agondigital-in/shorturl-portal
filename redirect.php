<?php
// redirect.php - Handles short URL redirects and tracks clicks
require_once 'db_connection.php';
require_once 'auth.php'; // For authentication functions if needed

// Get the shortcode and publisher ID from the URL parameters
$short_code = $_GET['code'] ?? '';
$publisher_id = $_GET['pub'] ?? '';

// If no shortcode, show error
if (empty($short_code)) {
    http_response_code(400);
    die('Bad Request: Missing shortcode parameter');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Step 3: Validate Campaign Shortcode
    $stmt = $conn->prepare("
        SELECT id, target_url, start_date, end_date, status 
        FROM campaigns 
        WHERE shortcode = ?
    ");
    $stmt->execute([$short_code]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        http_response_code(404);
        die('Invalid link!');
    }
    
    $campaign_id = $campaign['id'];
    
    // Step 4: Validate Date Range and Status
    $current_date = date('Y-m-d');
    if ($current_date < $campaign['start_date'] || $current_date > $campaign['end_date'] || $campaign['status'] != 'active') {
        http_response_code(404);
        die('Campaign is not active on this date');
    }
    
    // If we have a publisher ID, validate publisher assignment
    if (!empty($publisher_id)) {
        // Step 5: Validate Publisher Assigned to Campaign
        $stmt = $conn->prepare("
            SELECT id 
            FROM campaign_publishers 
            WHERE campaign_id = ? AND publisher_id = ?
        ");
        $stmt->execute([$campaign_id, $publisher_id]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$assignment) {
            // Check if this is a publisher-specific short code
            $stmt = $conn->prepare("
                SELECT id 
                FROM publisher_short_codes 
                WHERE short_code = ? AND publisher_id = ?
            ");
            $stmt->execute([$short_code, $publisher_id]);
            $publisher_shortcode = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$publisher_shortcode) {
                // Publisher not assigned to this campaign
                http_response_code(403);
                die('Publisher not authorized for this campaign');
            }
        }
        
        // Step 6: Increase Click Counts for all three tables
        
        // 1️⃣ publisher_short_codes
        $stmt = $conn->prepare("
            UPDATE publisher_short_codes 
            SET clicks = clicks + 1 
            WHERE short_code = ? AND publisher_id = ?
        ");
        $stmt->execute([$short_code, $publisher_id]);
        
        // 2️⃣ campaign_publishers
        $stmt = $conn->prepare("
            UPDATE campaign_publishers 
            SET clicks = clicks + 1 
            WHERE campaign_id = ? AND publisher_id = ?
        ");
        $stmt->execute([$campaign_id, $publisher_id]);
    }
    
    // 3️⃣ campaigns (total clicks)
    $stmt = $conn->prepare("
        UPDATE campaigns 
        SET click_count = click_count + 1 
        WHERE id = ?
    ");
    $stmt->execute([$campaign_id]);
    
    // Step 7: Redirect to Target URL
    header("Location: " . $campaign['target_url'], true, 302);
    exit();
    
} catch (PDOException $e) {
    http_response_code(500);
    die("Server Error: " . $e->getMessage());
}
?>