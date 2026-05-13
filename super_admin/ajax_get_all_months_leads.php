<?php
// super_admin/ajax_get_all_months_leads.php - Get all months leads data for a campaign
session_start();
require_once '../db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$campaign_id = $_GET['campaign_id'] ?? 0;

if (!$campaign_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Campaign ID required']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Get all leads data for this campaign, ordered by date
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(lead_date, '%d %M %Y') as lead_date,
            leads_count
        FROM campaign_daily_leads
        WHERE campaign_id = ?
        ORDER BY lead_date ASC
    ");
    $stmt->execute([$campaign_id]);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($leads);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
