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
$filter = $_GET['filter'] ?? 'all';
$month_filter = $_GET['month'] ?? date('Y-m');
$year_filter = $_GET['year'] ?? date('Y');

$label = 'All Campaigns';
if ($filter === 'today') $label = 'Today';
elseif ($filter === 'yesterday') $label = 'Yesterday';
elseif ($filter === 'this_month') $label = 'This Month';
elseif ($filter === 'this_year') $label = 'This Year';

// Get all campaigns with leads filtered by month/year
$where_clause = "c.status = 'active'";
$params = [];

if ($filter === 'today') {
    $where_clause .= " AND EXISTS (SELECT 1 FROM campaign_daily_leads cdl WHERE cdl.campaign_id = c.id AND cdl.lead_date = CURDATE())";
} elseif ($filter === 'yesterday') {
    $where_clause .= " AND EXISTS (SELECT 1 FROM campaign_daily_leads cdl WHERE cdl.campaign_id = c.id AND cdl.lead_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY))";
} elseif ($filter === 'this_month') {
    $where_clause .= " AND EXISTS (SELECT 1 FROM campaign_daily_leads cdl WHERE cdl.campaign_id = c.id AND YEAR(cdl.lead_date) = ? AND MONTH(cdl.lead_date) = ?)";
    list($y, $m) = explode('-', $month_filter);
    $params[] = $y;
    $params[] = $m;
} elseif ($filter === 'this_year') {
    $where_clause .= " AND EXISTS (SELECT 1 FROM campaign_daily_leads cdl WHERE cdl.campaign_id = c.id AND YEAR(cdl.lead_date) = ?)";
    $params[] = $year_filter;
}

// For "All" filter, get campaigns grouped by month and year
if ($filter === 'all') {
    $stmt = $conn->prepare("
        SELECT 
            c.id, 
            c.name, 
            c.shortcode,
            COALESCE((SELECT SUM(leads_count) FROM campaign_daily_leads WHERE campaign_id = c.id), 0) as total_leads,
            COALESCE(
                (SELECT MAX(lead_date) FROM campaign_daily_leads WHERE campaign_id = c.id),
                CURDATE()
            ) as last_lead_date,
            YEAR(COALESCE(
                (SELECT MAX(lead_date) FROM campaign_daily_leads WHERE campaign_id = c.id),
                CURDATE()
            )) as lead_year,
            MONTH(COALESCE(
                (SELECT MAX(lead_date) FROM campaign_daily_leads WHERE campaign_id = c.id),
                CURDATE()
            )) as lead_month
        FROM campaigns c
        WHERE c.status = 'active'
        ORDER BY lead_year DESC, lead_month DESC, c.name ASC
    ");
    $stmt->execute();
    $all_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group campaigns by year and month
    $campaigns_by_period = [];
    foreach ($all_campaigns as $campaign) {
        $year = $campaign['lead_year'];
        $month = $campaign['lead_month'];
        $period_key = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        
        if (!isset($campaigns_by_period[$period_key])) {
            $campaigns_by_period[$period_key] = [
                'year' => $year,
                'month' => $month,
                'campaigns' => []
            ];
        }
        $campaigns_by_period[$period_key]['campaigns'][] = $campaign;
    }
    
    $campaigns = $all_campaigns;
    $total_leads = array_sum(array_column($campaigns, 'total_leads'));
} else {
    $stmt = $conn->prepare("
        SELECT c.id, c.name, c.shortcode,
               COALESCE((SELECT SUM(leads_count) FROM campaign_daily_leads WHERE campaign_id = c.id), 0) as total_leads
        FROM campaigns c
        WHERE $where_clause
        ORDER BY c.name
    ");
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_leads = array_sum(array_column($campaigns, 'total_leads'));
    $campaigns_by_period = null;
}
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
.btn-print {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
    <div class="d-flex flex-wrap align-items-center mb-3">
        <span class="me-3 text-muted"><i class="fas fa-filter me-1"></i>Filter:</span>
        <a href="?filter=all" class="btn filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
            <i class="fas fa-list me-1"></i>All
        </a>
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
    
    <!-- Month and Year Selectors -->
    <div class="row">
        <?php if ($filter === 'this_month'): ?>
        <div class="col-md-6">
            <label class="form-label fw-bold"><i class="fas fa-calendar-alt me-1"></i>Select Month:</label>
            <input type="month" id="monthSelector" class="form-control form-control-lg" style="border-radius: 12px;" 
                   value="<?php echo $month_filter; ?>" onchange="filterByMonth(this.value)">
        </div>
        <?php endif; ?>
        
        <?php if ($filter === 'this_year'): ?>
        <div class="col-md-6">
            <label class="form-label fw-bold"><i class="fas fa-calendar me-1"></i>Select Year:</label>
            <select id="yearSelector" class="form-control form-control-lg" style="border-radius: 12px;" onchange="filterByYear(this.value)">
                <?php 
                $currentYear = date('Y');
                for ($y = $currentYear; $y >= $currentYear - 5; $y--): 
                ?>
                <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Campaigns Table -->
<?php if ($filter === 'all' && !empty($campaigns_by_period)): ?>
    <!-- Month-wise grouped view in single table for "All" filter -->
    <div class="campaign-table-card">
        <div class="campaign-table-header">
            <h5><i class="fas fa-list me-2"></i>Campaigns - <?php echo $label; ?></h5>
            <span class="badge bg-light text-dark px-3 py-2" style="border-radius: 20px;">
                <?php echo count($campaigns); ?> Campaigns | <?php echo number_format($total_leads); ?> Total Leads
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
                    <?php 
                    $monthNames = [
                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                    ];
                    
                    $global_index = 1;
                    foreach ($campaigns_by_period as $period_key => $period_data): 
                        $year = $period_data['year'];
                        $month = $period_data['month'];
                        $month_name = $monthNames[$month];
                        $period_campaigns = $period_data['campaigns'];
                        $period_total = array_sum(array_column($period_campaigns, 'total_leads'));
                    ?>
                    <!-- Month Header Row -->
                    <tr style="background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%);">
                        <td colspan="5" style="padding: 15px 20px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="color: white; font-weight: 600; font-size: 1.1rem;">
                                    <i class="fas fa-calendar-alt me-2"></i><?php echo $month_name . ' ' . $year; ?>
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2" style="border-radius: 20px; font-size: 0.9rem;">
                                    <?php echo count($period_campaigns); ?> Campaigns | <?php echo number_format($period_total); ?> Leads
                                </span>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Campaigns for this month -->
                    <?php foreach ($period_campaigns as $c): ?>
                    <tr>
                        <td><div class="row-number"><?php echo $global_index++; ?></div></td>
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
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
<?php else: ?>
    <!-- Regular table view for other filters -->
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
<?php endif; ?>

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
                <button type="button" class="btn btn-lg px-4" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 12px;" onclick="downloadExcel()">
                    <i class="fas fa-file-excel me-1"></i>Download Excel
                </button>
                <button type="button" class="btn btn-lg px-4 btn-print" style="border-radius: 12px;" onclick="printSelectedMonthLeads()">
                    <i class="fas fa-print me-1"></i>Print Selected Month
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentCampaignId = 0;
let currentCampaignName = '';

function viewLeads(campaignId, campaignName) {
    currentCampaignId = campaignId;
    currentCampaignName = campaignName;
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

function filterByMonth(month) {
    window.location.href = '?filter=this_month&month=' + month;
}

function filterByYear(year) {
    window.location.href = '?filter=this_year&year=' + year;
}

function downloadExcel() {
    const selectedMonth = document.getElementById('viewMonth').value;
    
    if (!selectedMonth) {
        alert('कृपया पहले महीना चुनें / Please select a month first');
        return;
    }
    
    // Open download URL in new window
    window.location.href = 'download_leads_excel.php?campaign_id=' + currentCampaignId + '&month=' + selectedMonth + '&campaign_name=' + encodeURIComponent(currentCampaignName);
}

function printSelectedMonthLeads() {
    const selectedMonth = document.getElementById('viewMonth').value;
    
    if (!selectedMonth) {
        alert('Please select a month first');
        return;
    }
    
    // Create print window
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    if (!printWindow) {
        alert('Please allow popups to print the report');
        return;
    }
    
    // Fetch selected month data for this campaign
    fetch('ajax_get_month_leads.php?campaign_id=' + currentCampaignId + '&month=' + selectedMonth)
        .then(response => response.json())
        .then(data => {
            let totalLeads = 0;
            let tableRows = '';
            
            if (data.length === 0) {
                tableRows = `
                    <tr>
                        <td colspan="3" style="padding: 20px; text-align: center; color: #999;">
                            No leads data found for this month
                        </td>
                    </tr>
                `;
            } else {
                data.forEach((row, index) => {
                    totalLeads += parseInt(row.leads_count);
                    tableRows += `
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">${index + 1}</td>
                            <td style="padding: 12px; border: 1px solid #ddd;">${row.lead_date}</td>
                            <td style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold;">${row.leads_count}</td>
                        </tr>
                    `;
                });
            }
            
            // Format month for display
            const [year, month] = selectedMonth.split('-');
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                              'July', 'August', 'September', 'October', 'November', 'December'];
            const monthDisplay = monthNames[parseInt(month) - 1] + ' ' + year;
            
            const printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Campaign Leads Report - ${currentCampaignName} - ${monthDisplay}</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            padding: 30px;
                            color: #333;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 30px;
                            border-bottom: 3px solid #667eea;
                            padding-bottom: 20px;
                        }
                        .header h1 {
                            color: #667eea;
                            margin: 0 0 10px 0;
                            font-size: 28px;
                        }
                        .header h2 {
                            color: #764ba2;
                            margin: 0;
                            font-size: 20px;
                        }
                        .header h3 {
                            color: #06b6d4;
                            margin: 10px 0 0 0;
                            font-size: 18px;
                        }
                        .info-box {
                            background: #f8f9fa;
                            padding: 15px;
                            border-radius: 8px;
                            margin-bottom: 20px;
                        }
                        .info-box p {
                            margin: 5px 0;
                            font-size: 14px;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                        }
                        th {
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            color: white;
                            padding: 15px;
                            text-align: left;
                            font-weight: bold;
                            border: 1px solid #667eea;
                        }
                        th:first-child, td:first-child {
                            text-align: center;
                        }
                        th:last-child, td:last-child {
                            text-align: center;
                        }
                        tr:nth-child(even) {
                            background: #f8f9fa;
                        }
                        .total-row {
                            background: #667eea !important;
                            color: white;
                            font-weight: bold;
                            font-size: 16px;
                        }
                        .total-row td {
                            padding: 15px;
                            border: 1px solid #667eea;
                        }
                        .footer {
                            margin-top: 30px;
                            text-align: center;
                            font-size: 12px;
                            color: #666;
                            border-top: 2px solid #ddd;
                            padding-top: 15px;
                        }
                        @media print {
                            body { padding: 20px; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>📊 Campaign Leads Report</h1>
                        <h2>${currentCampaignName}</h2>
                        <h3>${monthDisplay}</h3>
                    </div>
                    
                    <div class="info-box">
                        <p><strong>Report Generated:</strong> ${new Date().toLocaleString()}</p>
                        <p><strong>Month:</strong> ${monthDisplay}</p>
                        <p><strong>Total Records:</strong> ${data.length}</p>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 80px;">#</th>
                                <th>Date</th>
                                <th style="width: 150px;">Leads Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                            ${data.length > 0 ? `
                            <tr class="total-row">
                                <td colspan="2" style="text-align: right; padding-right: 20px;">TOTAL LEADS:</td>
                                <td style="text-align: center; font-size: 18px;">${totalLeads}</td>
                            </tr>
                            ` : ''}
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <p>This is a computer-generated report. No signature required.</p>
                        <p>© ${new Date().getFullYear()} - Campaign Management System</p>
                    </div>
                    
                    <div class="no-print" style="text-align: center; margin-top: 20px;">
                        <button onclick="window.print()" style="padding: 10px 30px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                            🖨️ Print Report
                        </button>
                        <button onclick="window.close()" style="padding: 10px 30px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-left: 10px;">
                            ✖️ Close
                        </button>
                    </div>
                </body>
                </html>
            `;
            
            printWindow.document.write(printContent);
            printWindow.document.close();
        })
        .catch(error => {
            printWindow.close();
            alert('Error loading data for print');
        });
}
</script>

<?php require_once 'includes/footer.php'; ?>
