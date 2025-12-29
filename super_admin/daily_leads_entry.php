<?php
// super_admin/daily_leads_entry.php - Daily Leads Entry
$page_title = 'Daily Leads Entry';
require_once 'includes/header.php';
require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$success = '';
$error = '';

// Create table if not exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS campaign_daily_leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id INT NOT NULL,
        lead_date DATE NOT NULL,
        leads_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_campaign_date (campaign_id, lead_date)
    )");
} catch (PDOException $e) {}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_leads') {
        $campaign_id = $_POST['campaign_id'] ?? 0;
        $lead_date = $_POST['lead_date'] ?? date('Y-m-d');
        $leads_count = $_POST['leads_count'] ?? 0;
        
        try {
            $stmt = $conn->prepare("INSERT INTO campaign_daily_leads (campaign_id, lead_date, leads_count) 
                                    VALUES (?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE leads_count = ?");
            $stmt->execute([$campaign_id, $lead_date, $leads_count, $leads_count]);
            
            $stmt = $conn->prepare("UPDATE campaigns SET validated_leads = 
                                    (SELECT COALESCE(SUM(leads_count), 0) FROM campaign_daily_leads WHERE campaign_id = ?) 
                                    WHERE id = ?");
            $stmt->execute([$campaign_id, $campaign_id]);
            
            $success = "Leads saved successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'today';
$label = 'Today';
if ($filter === 'yesterday') $label = 'Yesterday';
elseif ($filter === 'this_month') $label = 'This Month';
elseif ($filter === 'this_year') $label = 'This Year';

// Get all campaigns with total leads
$stmt = $conn->prepare("
    SELECT c.id, c.name, c.shortcode,
           COALESCE((SELECT SUM(leads_count) FROM campaign_daily_leads WHERE campaign_id = c.id), 0) as total_leads
    FROM campaigns c
    WHERE c.status = 'active'
    ORDER BY c.name
");
$stmt->execute();
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_leads = array_sum(array_column($campaigns, 'total_leads'));
?>

<style>
.leads-page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 25px;
    color: white;
    position: relative;
    overflow: hidden;
}
.leads-page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}
.leads-page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}
.leads-page-header p {
    opacity: 0.9;
    margin: 0;
}

.stat-box {
    border-radius: 16px;
    padding: 25px;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}
.stat-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.stat-box .icon {
    font-size: 2.5rem;
    opacity: 0.3;
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
}
.stat-box h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
}
.stat-box p {
    margin: 5px 0 0;
    font-size: 0.95rem;
    opacity: 0.9;
}
.stat-brown { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); }
.stat-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }

.filter-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
}
.filter-btn {
    border-radius: 25px;
    padding: 10px 20px;
    font-weight: 500;
    border: 2px solid #667eea;
    margin-right: 8px;
    margin-bottom: 8px;
    transition: all 0.3s;
}
.filter-btn:hover {
    transform: translateY(-2px);
}
.filter-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: white;
}
.filter-btn:not(.active) {
    background: white;
    color: #667eea;
}

.campaign-table-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
}
.campaign-table-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%);
    color: white;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.campaign-table-header h5 {
    margin: 0;
    font-weight: 600;
}

.campaign-table {
    margin: 0;
}
.campaign-table thead th {
    background: #f8fafc;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 1px;
    color: #64748b;
    padding: 15px 20px;
    border: none;
}
.campaign-table tbody td {
    padding: 18px 20px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.campaign-table tbody tr:hover {
    background: linear-gradient(90deg, #f8fafc 0%, #fff 100%);
}
.campaign-table tbody tr:last-child td {
    border-bottom: none;
}

.campaign-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.95rem;
}
.shortcode-badge {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}
.leads-badge {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
}

.action-btn {
    border-radius: 10px;
    padding: 8px 16px;
    font-weight: 500;
    font-size: 0.85rem;
    margin-right: 5px;
    transition: all 0.3s;
}
.action-btn:hover {
    transform: translateY(-2px);
}
.btn-enter {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
}
.btn-view {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    border: none;
    color: white;
}

.row-number {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #475569;
}
</style>

<!-- Page Header -->
<div class="leads-page-header">
    <h1><i class="fas fa-edit me-2"></i>Daily Leads Entry</h1>
    <p>Manually enter and track daily leads for your campaigns</p>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px; border: none;">
    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px; border: none;">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="stat-box stat-brown">
            <i class="fas fa-bullhorn icon"></i>
            <h2><?php echo count($campaigns); ?></h2>
            <p><i class="fas fa-chart-line me-1"></i>Active Campaigns</p>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="stat-box stat-green">
            <i class="fas fa-users icon"></i>
            <h2><?php echo number_format($total_leads); ?></h2>
            <p><i class="fas fa-trophy me-1"></i>Total Leads</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filter-card">
    <div class="d-flex flex-wrap align-items-center">
        <span class="me-3 text-muted"><i class="fas fa-filter me-1"></i>Filter:</span>
        <a href="?filter=today" class="btn filter-btn <?php echo $filter === 'today' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-day me-1"></i>Today
        </a>
        <a href="?filter=yesterday" class="btn filter-btn <?php echo $filter === 'yesterday' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-minus me-1"></i>Yesterday
        </a>
        <a href="?filter=this_month" class="btn filter-btn <?php echo $filter === 'this_month' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt me-1"></i>This Month
        </a>
        <a href="?filter=this_year" class="btn filter-btn <?php echo $filter === 'this_year' ? 'active' : ''; ?>">
            <i class="fas fa-calendar me-1"></i>This Year
        </a>
    </div>
</div>

<!-- Campaigns Table -->
<div class="campaign-table-card">
    <div class="campaign-table-header">
        <h5><i class="fas fa-list me-2"></i>Campaigns - <?php echo $label; ?></h5>
        <span class="badge bg-light text-dark px-3 py-2" style="border-radius: 20px;">
            <?php echo count($campaigns); ?> Campaigns
        </span>
    </div>
    <div class="table-responsive">
        <table class="table campaign-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Campaign Name</th>
                    <th>Shortcode</th>
                    <th>Leads</th>
                    <th style="width: 220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">No active campaigns found</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($campaigns as $i => $c): ?>
                <tr>
                    <td><div class="row-number"><?php echo $i + 1; ?></div></td>
                    <td><span class="campaign-name"><?php echo htmlspecialchars($c['name']); ?></span></td>
                    <td><span class="shortcode-badge"><?php echo htmlspecialchars($c['shortcode']); ?></span></td>
                    <td><span class="leads-badge"><?php echo number_format($c['total_leads']); ?></span></td>
                    <td>
                        <button class="btn action-btn btn-enter" data-bs-toggle="modal" data-bs-target="#addModal<?php echo $c['id']; ?>">
                            <i class="fas fa-plus me-1"></i>Enter
                        </button>
                        <button class="btn action-btn btn-view" onclick="viewLeads(<?php echo $c['id']; ?>, '<?php echo addslashes($c['name']); ?>')">
                            <i class="fas fa-eye me-1"></i>View
                        </button>
                    </td>
                </tr>
                
                <!-- Add Leads Modal -->
                <div class="modal fade" id="addModal<?php echo $c['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 20px; border: none;">
                            <form method="POST">
                                <input type="hidden" name="action" value="add_leads">
                                <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                                    <h5 class="modal-title text-white"><i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($c['name']); ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Date</label>
                                        <input type="date" name="lead_date" class="form-control form-control-lg" style="border-radius: 12px;" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Leads Count</label>
                                        <input type="number" name="leads_count" class="form-control form-control-lg" style="border-radius: 12px;" min="0" value="0" required>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 px-4 pb-4">
                                    <button type="button" class="btn btn-light btn-lg px-4" style="border-radius: 12px;" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-lg px-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px;">
                                        <i class="fas fa-save me-1"></i>Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Leads Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); border-radius: 20px 20px 0 0;">
                <h5 class="modal-title text-white"><i class="fas fa-calendar-alt me-2"></i>Leads History - <span id="viewCampaignName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <input type="month" id="viewMonth" class="form-control form-control-lg" style="border-radius: 12px;" value="<?php echo date('Y-m'); ?>" onchange="loadLeadsData()">
                </div>
                <div id="leadsDataContainer">
                    <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light btn-lg px-4" style="border-radius: 12px;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentCampaignId = 0;

function viewLeads(campaignId, campaignName) {
    currentCampaignId = campaignId;
    document.getElementById('viewCampaignName').textContent = campaignName;
    loadLeadsData();
    new bootstrap.Modal(document.getElementById('viewModal')).show();
}

function loadLeadsData() {
    const month = document.getElementById('viewMonth').value;
    const container = document.getElementById('leadsDataContainer');
    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    
    fetch('ajax_get_leads.php?campaign_id=' + currentCampaignId + '&month=' + month)
        .then(response => response.text())
        .then(data => { container.innerHTML = data; })
        .catch(error => { container.innerHTML = '<div class="alert alert-danger">Error loading data</div>'; });
}
</script>

<?php require_once 'includes/footer.php'; ?>
