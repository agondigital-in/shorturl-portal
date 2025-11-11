<?php
require_once 'config.php';

// Test campaign statistics query
$campaign_stats_sql = "SELECT 
                        COUNT(*) as total_campaigns,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
                        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_campaigns
                      FROM campaigns";
$campaign_stats_result = $conn->query($campaign_stats_sql);
$campaign_stats = $campaign_stats_result->fetch_assoc();

echo "Campaign Statistics:\n";
print_r($campaign_stats);

// Test payment statistics query
$payment_stats_sql = "SELECT 
                        SUM(CASE WHEN advertiser_payment_status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                        SUM(CASE WHEN advertiser_payment_status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                        COUNT(*) as total_campaigns
                      FROM campaigns";
$payment_stats_result = $conn->query($payment_stats_sql);
$payment_stats = $payment_stats_result->fetch_assoc();

echo "\nPayment Statistics:\n";
print_r($payment_stats);

// Test monthly campaign data query
$monthly_campaigns_sql = "SELECT 
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            COUNT(*) as campaign_count
                          FROM campaigns 
                          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                          ORDER BY month ASC
                          LIMIT 6";
$monthly_campaigns_result = $conn->query($monthly_campaigns_sql);
$monthly_data = [];
while($row = $monthly_campaigns_result->fetch_assoc()) {
    $monthly_data[] = $row;
}

echo "\nMonthly Campaign Data:\n";
print_r($monthly_data);

// Test entity counts
$advertisers_count_sql = "SELECT COUNT(*) as count FROM advertisers";
$advertisers_count_result = $conn->query($advertisers_count_sql);
$advertisers_count = $advertisers_count_result->fetch_assoc()['count'];

echo "\nAdvertisers Count: " . $advertisers_count . "\n";

$publishers_count_sql = "SELECT COUNT(*) as count FROM publishers";
$publishers_count_result = $conn->query($publishers_count_sql);
$publishers_count = $publishers_count_result->fetch_assoc()['count'];

echo "Publishers Count: " . $publishers_count . "\n";

$admins_count_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
$admins_count_result = $conn->query($admins_count_sql);
$admins_count = $admins_count_result->fetch_assoc()['count'];

echo "Admins Count: " . $admins_count . "\n";
?>