<?php
// super_admin/publisher_daily_clicks.php - View daily click statistics
session_start();
require_once '../db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get campaign ID from URL
$campaign_id = $_GET['id'] ?? null;

if (!$campaign_id) {
    header('Location: campaigns.php');
    exit();
}

// Get campaign details
$stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    header('Location: campaigns.php');
    exit();
}

// Get date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get daily clicks data
$stmt = $conn->prepare("
    SELECT 
        pdc.click_date,
        p.name as publisher_name,
        p.id as publisher_id,
        pdc.clicks,
        pdc.created_at
    FROM publisher_daily_clicks pdc
    JOIN publishers p ON pdc.publisher_id = p.id
    WHERE pdc.campaign_id = ? 
    AND pdc.click_date BETWEEN ? AND ?
    ORDER BY pdc.click_date DESC, p.name ASC
");
$stmt->execute([$campaign_id, $start_date, $end_date]);
$daily_clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary by publisher
$stmt = $conn->prepare("
    SELECT 
        p.name as publisher_name,
        p.id as publisher_id,
        SUM(pdc.clicks) as total_clicks,
        COUNT(DISTINCT pdc.click_date) as active_days
    FROM publisher_daily_clicks pdc
    JOIN publishers p ON pdc.publisher_id = p.id
    WHERE pdc.campaign_id = ? 
    AND pdc.click_date BETWEEN ? AND ?
    GROUP BY p.id, p.name
    ORDER BY total_clicks DESC
");
$stmt->execute([$campaign_id, $start_date, $end_date]);
$publisher_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Click Statistics - <?php echo htmlspecialchars($campaign['name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; }
        .campaign-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .filter-form { margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px; }
        .filter-form input { padding: 8px; margin-right: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-form button { padding: 8px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-form button:hover { background: #0056b3; }
        .summary-section { margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .back-btn { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; margin-bottom: 20px; }
        .back-btn:hover { background: #5a6268; }
        .stats-card { display: inline-block; padding: 15px 25px; background: #e7f3ff; border-radius: 5px; margin-right: 15px; margin-bottom: 15px; }
        .stats-card strong { display: block; font-size: 24px; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <a href="campaign_tracking_stats.php?id=<?php echo $campaign_id; ?>" class="back-btn">← Back to Campaign Stats</a>
        
        <h1>Daily Click Statistics</h1>
        
        <div class="campaign-info">
            <strong>Campaign:</strong> <?php echo htmlspecialchars($campaign['name']); ?> (<?php echo htmlspecialchars($campaign['shortcode']); ?>)<br>
            <strong>Campaign Period:</strong> <?php echo $campaign['start_date']; ?> to <?php echo $campaign['end_date']; ?>
        </div>

        <div class="filter-form">
            <form method="GET">
                <input type="hidden" name="id" value="<?php echo $campaign_id; ?>">
                <label>Start Date:</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                <label>End Date:</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                <button type="submit">Filter</button>
            </form>
        </div>

        <div class="summary-section">
            <h2>Publisher Summary (<?php echo $start_date; ?> to <?php echo $end_date; ?>)</h2>
            <?php if (count($publisher_summary) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Publisher</th>
                            <th>Total Clicks</th>
                            <th>Active Days</th>
                            <th>Avg Clicks/Day</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($publisher_summary as $summary): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($summary['publisher_name']); ?></td>
                                <td><?php echo number_format($summary['total_clicks']); ?></td>
                                <td><?php echo $summary['active_days']; ?></td>
                                <td><?php echo number_format($summary['total_clicks'] / max($summary['active_days'], 1), 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No data available for the selected date range.</p>
            <?php endif; ?>
        </div>

        <div class="daily-section">
            <h2>Daily Click Details</h2>
            <?php if (count($daily_clicks) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Publisher</th>
                            <th>Clicks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_clicks as $click): ?>
                            <tr>
                                <td><?php echo $click['click_date']; ?></td>
                                <td><?php echo htmlspecialchars($click['publisher_name']); ?></td>
                                <td><?php echo number_format($click['clicks']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No daily click data available for the selected date range.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
