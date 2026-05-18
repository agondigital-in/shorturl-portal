<?php
// super_admin/ajax_manage_lead.php - Edit/Delete lead entries
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../db_connection.php';

$action = $_POST['action'] ?? '';
$campaign_id = $_POST['campaign_id'] ?? 0;
$lead_date = $_POST['lead_date'] ?? '';

if (!$campaign_id || !$lead_date) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    if ($action === 'edit') {
        $leads_count = $_POST['leads_count'] ?? 0;
        
        if ($leads_count < 0) {
            echo json_encode(['success' => false, 'message' => 'Leads count cannot be negative']);
            exit();
        }
        
        // Update lead entry
        $stmt = $conn->prepare("UPDATE campaign_daily_leads SET leads_count = ? WHERE campaign_id = ? AND lead_date = ?");
        $stmt->execute([$leads_count, $campaign_id, $lead_date]);
        
        // Update campaign total
        $stmt = $conn->prepare("UPDATE campaigns SET validated_leads = 
                                (SELECT COALESCE(SUM(leads_count), 0) FROM campaign_daily_leads WHERE campaign_id = ?) 
                                WHERE id = ?");
        $stmt->execute([$campaign_id, $campaign_id]);
        
        echo json_encode(['success' => true, 'message' => 'Lead updated successfully']);
        
    } elseif ($action === 'delete') {
        // Delete lead entry
        $stmt = $conn->prepare("DELETE FROM campaign_daily_leads WHERE campaign_id = ? AND lead_date = ?");
        $stmt->execute([$campaign_id, $lead_date]);
        
        // Update campaign total
        $stmt = $conn->prepare("UPDATE campaigns SET validated_leads = 
                                (SELECT COALESCE(SUM(leads_count), 0) FROM campaign_daily_leads WHERE campaign_id = ?) 
                                WHERE id = ?");
        $stmt->execute([$campaign_id, $campaign_id]);
        
        echo json_encode(['success' => true, 'message' => 'Lead deleted successfully']);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
