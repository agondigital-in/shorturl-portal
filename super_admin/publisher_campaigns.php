<?php
// super_admin/publisher_campaigns.php - View Publisher Campaigns
$page_title = 'Publisher Campaigns';
require_once 'includes/header.php';
require_once '../db_connection.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
        
    $stmt = $conn->prepare("
        SELECT 
            p.id as publisher_id,
            p.name as publisher_name,
            p.email as publisher_email,
            p.website as publisher_website,
            c.id as campaign_id,
            c.name as campaign_name,
            c.shortcode as base_shortcode,
            c.start_date,
            c.end_date,
            c.campaign_type,
            c.click_count as total_campaign_clicks,
            c.status,
            psc.short_code as publisher_shortcode,
            COALESCE(psc.clicks, 0) as publisher_clicks
        FROM publishers p
        LEFT JOIN campaign_publishers cp ON p.id = cp.publisher_id
        LEFT JOIN campaigns c ON cp.campaign_id = c.id
        LEFT JOIN publisher_short_codes psc ON c.id = psc.campaign_id AND p.id = psc.publisher_id
        ORDER BY p.name, c.name
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    $publisher_campaigns = [];
    foreach ($results as $row) {
        $publisher_id = $row['publisher_id'];
        if (!isset($publisher_campaigns[$publisher_id])) {
            $publisher_campaigns[$publisher_id] = [
                'id' => $row['publisher_id'],
                'name' => $row['publisher_name'],
                'email' => $row['publisher_email'],
                'website' => $row['publisher_website'],
                'campaigns' => []
            ];
        }
            
        if ($row['campaign_id']) {
            $publisher_campaigns[$publisher_id]['campaigns'][] = [
                'id' => $row['campaign_id'],
                'name' => $row['campaign_name'],
                'base_shortcode' => $row['base_shortcode'],
                'publisher_shortcode' => $row['publisher_shortcode'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'type' => $row['campaign_type'],
                'total_clicks' => $row['total_campaign_clicks'],
                'publisher_clicks' => $row['publisher_clicks'],
                'status' => $row['status']
            ];
        }
    }
} catch (PDOException $e) {
    $error = "Error loading publisher campaigns: " . $e->getMessage();
}

$total_publishers = count($publisher_campaigns);
$total_campaigns = 0;
$total_publisher_clicks = 0;
foreach ($publisher_campaigns as $pub) {
    $total_campaigns += count($pub['campaigns']);
    foreach ($pub['campaigns'] as $c) {
        $total_publisher_clicks += $c['publisher_clicks'];
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Publisher Campaigns</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Publisher Campaigns</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-globe"></i></div>
            <div class="stat-card-value"><?php echo $total_publishers; ?></div>
            <div class="stat-card-label">Total Publishers</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-link"></i></div>
            <div class="stat-card-value"><?php echo $total_campaigns; ?></div>
            <div class="stat-card-label">Campaign Assignments</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_publisher_clicks); ?></div>
            <div class="stat-card-label">Publisher Clicks</div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <input type="text" id="publisherFilter" class="form-control" placeholder="🔍 Filter by publisher name...">
            </div>
            <div class="col-md-6">
                <input type="text" id="campaignFilter" class="form-control" placeholder="🔍 Filter by campaign name...">
            </div>
        </div>
    </div>
</div>

<?php if (empty($publisher_campaigns)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-globe fa-3x text-muted mb-3"></i>
            <p class="text-muted">No publishers or campaigns found.</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($publisher_campaigns as $publisher): ?>
        <div class="card mb-4 publisher-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="user-avatar me-3" style="width:45px;height:45px;">
                        <?php echo strtoupper(substr($publisher['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($publisher['name']); ?></h5>
                        <small class="text-muted"><?php echo htmlspecialchars($publisher['email']); ?></small>
                        <?php if (!empty($publisher['website'])): ?>
                            <br><a href="<?php echo htmlspecialchars($publisher['website']); ?>" target="_blank" class="small"><i class="fas fa-external-link-alt me-1"></i>Website</a>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge bg-primary"><?php echo count($publisher['campaigns']); ?> Campaigns</span>
            </div>
            <div class="card-body">
                <?php if (empty($publisher['campaigns'])): ?>
                    <p class="text-muted text-center py-3"><i class="fas fa-info-circle me-2"></i>No campaigns assigned</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Publisher Code</th>
                                    <th>Duration</th>
                                    <th>Type</th>
                                    <th>Publisher Clicks</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($publisher['campaigns'] as $campaign): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($campaign['name']); ?></strong></td>
                                        <td><code><?php echo htmlspecialchars($campaign['publisher_shortcode'] ?? $campaign['base_shortcode']); ?></code></td>
                                        <td><small><?php echo date('M d', strtotime($campaign['start_date'])); ?> - <?php echo date('M d, Y', strtotime($campaign['end_date'])); ?></small></td>
                                        <td><span class="badge badge-soft-primary"><?php echo htmlspecialchars($campaign['type']); ?></span></td>
                                        <td><strong class="text-primary"><?php echo number_format($campaign['publisher_clicks']); ?></strong></td>
                                        <td>
                                            <?php if ($campaign['status'] === 'active'): ?>
                                                <span class="badge badge-soft-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-soft-warning"><?php echo ucfirst($campaign['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const publisherFilter = document.getElementById('publisherFilter');
    const campaignFilter = document.getElementById('campaignFilter');
    
    function filterCards() {
        const pubText = publisherFilter.value.toLowerCase();
        const campText = campaignFilter.value.toLowerCase();
        
        document.querySelectorAll('.publisher-card').forEach(card => {
            const pubName = card.querySelector('.card-header h5').textContent.toLowerCase();
            const rows = card.querySelectorAll('tbody tr');
            let showCard = pubText === '' || pubName.includes(pubText);
            
            if (campText !== '') {
                let hasMatch = false;
                rows.forEach(row => {
                    const campName = row.cells[0].textContent.toLowerCase();
                    if (campName.includes(campText)) hasMatch = true;
                });
                showCard = showCard && hasMatch;
            }
            
            card.style.display = showCard ? 'block' : 'none';
        });
    }
    
    publisherFilter.addEventListener('input', filterCards);
    campaignFilter.addEventListener('input', filterCards);
});
</script>

<?php require_once 'includes/footer.php'; ?>
