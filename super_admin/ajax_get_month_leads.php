<?php
// super_admin/ajax_get_month_leads.php - Get selected month leads data for a campaign
session_start();
require_once '../db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$campaign_id = $_GET['campaign_id'] ?? 0;
$month = $_GET['month'] ?? '';

if (!$campaign_id || !$month) {
    http_response_code(400);
    echo json_encode(['error' => 'Campaign ID and month required']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Parse the month (format: YYYY-MM)
    list($year, $monthNum) = explode('-', $month);
    
    // Get leads data for the selected month
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(lead_date, '%d %M %Y') as lead_date,
            leads_count
        FROM campaign_daily_leads
        WHERE campaign_id = ?
        AND YEAR(lead_date) = ?
        AND MONTH(lead_date) = ?
        ORDER BY lead_date ASC
    ");
    $stmt->execute([$campaign_id, $year, $monthNum]);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($leads);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
