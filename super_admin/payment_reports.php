<?php
// super_admin/payment_reports.php - Payment Reports
$page_title = 'Payment Reports';
require_once 'includes/header.php';
require_once '../db_connection.php';

// Handle lead updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_leads') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $target_leads = $_POST['target_leads'] ?? 0;
    $validated_leads = $_POST['validated_leads'] ?? 0;
    
    if (is_numeric($target_leads) && is_numeric($validated_leads) && $target_leads >= 0 && $validated_leads >= 0 && $validated_leads <= $target_leads) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("UPDATE campaigns SET target_leads = ?, validated_leads = ? WHERE id = ?");
            $stmt->execute([$target_leads, $validated_leads, $campaign_id]);
            
            $success = "Lead information updated successfully.";
        } catch (PDOException $e) {
            $error = "Error updating lead information: " . $e->getMessage();
        }
    } else {
        if (!is_numeric($target_leads) || !is_numeric($validated_leads) || $target_leads < 0 || $validated_leads < 0) {
            $error = "Invalid lead values. Please enter valid non-negative numbers.";
        } else {
            $error = "Validated leads cannot exceed target leads.";
        }
    }
}

// Handle payment status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_payment_status') {
    $campaign_id = $_POST['campaign_id'] ?? '';
    $current_status = $_POST['current_status'] ?? '';
    $new_status = ($current_status === 'pending') ? 'completed' : 'pending';
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("UPDATE campaigns SET payment_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $campaign_id]);
        
        $success = "Payment status updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating payment status: " . $e->getMessage();
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_date_type = $_GET['date_type'] ?? '';
$filter_date_value = $_GET['date_value'] ?? '';

// Handle quick date filters
$quick_filter = $_GET['quick_filter'] ?? '';
if (!empty($quick_filter)) {
    $filter_date_type = $quick_filter;
    switch ($quick_filter) {
        case 'today':
            $filter_date_value = date('Y-m-d');
            break;
        case 'yesterday':
            $filter_date_value = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'month':
            $filter_date_value = date('Y-m');
            break;
        case 'year':
            $filter_date_value = date('Y');
            break;
    }
}

// Get all campaigns with advertiser and publisher info
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "
        SELECT 
            c.id, c.name as campaign_name, c.shortcode, c.advertiser_payout, c.publisher_payout,
            c.campaign_type, c.click_count, c.target_leads, c.validated_leads, c.payment_status,
            c.start_date, c.end_date, c.created_at,
            GROUP_CONCAT(DISTINCT a.name) as advertiser_names,
            GROUP_CONCAT(DISTINCT p.name) as publisher_names
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN advertisers a ON ca.advertiser_id = a.id
        LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
        LEFT JOIN publishers p ON cp.publisher_id = p.id
    ";
    
    $where_conditions = [];
    $params = [];
    
    if ($filter_status !== 'all') {
        $where_conditions[] = "c.payment_status = ?";
        $params[] = $filter_status;
    }
    
    if (!empty($filter_date_type) && !empty($filter_date_value)) {
        switch ($filter_date_type) {
            case 'day':
            case 'today':
            case 'yesterday':
                $where_conditions[] = "DATE(c.created_at) = ?";
                $params[] = $filter_date_value;
                break;
            case 'month':
                $where_conditions[] = "DATE_FORMAT(c.created_at, '%Y-%m') = ?";
                $params[] = $filter_date_value;
                break;
            case 'year':
                $where_conditions[] = "YEAR(c.created_at) = ?";
                $params[] = $filter_date_value;
                break;
        }
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    
    $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_advertiser_payout = 0;
    $total_publisher_payout = 0;
    $pending_payments = 0;
    $completed_payments = 0;
    
    foreach ($campaigns as $campaign) {
        if ($campaign['payment_status'] === 'pending') {
            $pending_payments++;
        } else {
            $completed_payments++;
        }
        $total_advertiser_payout += $campaign['advertiser_payout'];
        $total_publisher_payout += $campaign['publisher_payout'];
    }
    
} catch (PDOException $e) {
    $error = "Error loading payment reports: " . $e->getMessage();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Payment Reports</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Payment Reports</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Quick Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Quick Filters</h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <a href="?quick_filter=today" class="btn <?php echo ($filter_date_type === 'today') ? 'btn-primary' : 'btn-soft-primary'; ?> me-2 mb-2">
                <i class="fas fa-calendar-day me-1"></i>Today
            </a>
            <a href="?quick_filter=yesterday" class="btn <?php echo ($filter_date_type === 'yesterday') ? 'btn-primary' : 'btn-soft-primary'; ?> me-2 mb-2">
                <i class="fas fa-calendar-day me-1"></i>Yesterday
            </a>
            <a href="?quick_filter=month" class="btn <?php echo ($filter_date_type === 'month') ? 'btn-primary' : 'btn-soft-primary'; ?> me-2 mb-2">
                <i class="fas fa-calendar-week me-1"></i>This Month
            </a>
            <a href="?quick_filter=year" class="btn <?php echo ($filter_date_type === 'year') ? 'btn-primary' : 'btn-soft-primary'; ?> me-2 mb-2">
                <i class="fas fa-calendar-alt me-1"></i>This Year
            </a>
            <a href="payment_reports.php" class="btn btn-soft-danger mb-2">
                <i class="fas fa-times me-1"></i>Clear All
            </a>
        </div>
        
        <h6 class="mt-3 mb-3">Custom Filters</h6>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending Payments</option>
                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed Payments</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="date_type" class="form-select" id="dateTypeSelect">
                    <option value="">Select Date Filter</option>
                    <option value="day" <?php echo in_array($filter_date_type, ['day', 'today', 'yesterday']) ? 'selected' : ''; ?>>Day-wise</option>
                    <option value="month" <?php echo $filter_date_type === 'month' ? 'selected' : ''; ?>>Month-wise</option>
                    <option value="year" <?php echo $filter_date_type === 'year' ? 'selected' : ''; ?>>Year-wise</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="date_value" class="form-control" placeholder="YYYY-MM-DD / YYYY-MM / YYYY" value="<?php echo htmlspecialchars($filter_date_value); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Apply Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-2">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="stat-card-value"><?php echo count($campaigns); ?></div>
            <div class="stat-card-label">Total Campaigns</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card warning">
            <div class="stat-card-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-card-value"><?php echo $pending_payments; ?></div>
            <div class="stat-card-label">Pending</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-card-value"><?php echo $completed_payments; ?></div>
            <div class="stat-card-label">Completed</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-card-value">₹<?php echo number_format($total_advertiser_payout, 0); ?></div>
            <div class="stat-card-label">Adv. Payout</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card danger">
            <div class="stat-card-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-card-value">₹<?php echo number_format($total_publisher_payout, 0); ?></div>
            <div class="stat-card-label">Pub. Payout</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-card-value">₹<?php echo number_format($total_advertiser_payout - $total_publisher_payout, 0); ?></div>
            <div class="stat-card-label">Profit</div>
        </div>
    </div>
</div>

<!-- Campaigns Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Campaign Payment Details</h5>
        <span class="badge bg-primary"><?php echo count($campaigns); ?> Campaigns</span>
    </div>
    <div class="card-body">
        <?php if (empty($campaigns)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                <p class="text-muted">No campaigns found matching your filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Campaign</th>
                            <th>Advertisers</th>
                            <th>Publishers</th>
                            <th>Type</th>
                            <th>Target</th>
                            <th>Validated</th>
                            <th>Amount</th>
                            <th>Clicks</th>
                            <th>Adv. Payout</th>
                            <th>Pub. Payout</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><span class="badge badge-soft-primary">#<?php echo $campaign['id']; ?></span></td>
                                <td><strong><?php echo htmlspecialchars($campaign['campaign_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($campaign['advertiser_names'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($campaign['publisher_names'] ?? 'N/A'); ?></td>
                                <td><span class="badge badge-soft-primary"><?php echo htmlspecialchars($campaign['campaign_type']); ?></span></td>
                                <td><?php echo $campaign['target_leads']; ?></td>
                                <td><?php echo $campaign['validated_leads']; ?></td>
                                <td><strong>₹<?php echo number_format($campaign['validated_leads'] * $campaign['advertiser_payout'], 2); ?></strong></td>
                                <td><?php echo number_format($campaign['click_count']); ?></td>
                                <td class="text-success">₹<?php echo number_format($campaign['advertiser_payout'], 2); ?></td>
                                <td class="text-danger">₹<?php echo number_format($campaign['publisher_payout'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $campaign['payment_status'] === 'completed' ? 'badge-soft-success' : 'badge-soft-warning'; ?>">
                                        <?php echo ucfirst($campaign['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-soft-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leadModal<?php echo $campaign['id']; ?>" title="Update Leads">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Change payment status?');">
                                            <input type="hidden" name="action" value="toggle_payment_status">
                                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $campaign['payment_status']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $campaign['payment_status'] === 'completed' ? 'btn-soft-warning' : 'btn-soft-success'; ?>" title="<?php echo $campaign['payment_status'] === 'completed' ? 'Mark Pending' : 'Mark Completed'; ?>">
                                                <i class="fas <?php echo $campaign['payment_status'] === 'completed' ? 'fa-undo' : 'fa-check'; ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Lead Update Modal -->
                            <div class="modal fade" id="leadModal<?php echo $campaign['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_leads">
                                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Lead Information</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Target Leads</label>
                                                    <input type="number" class="form-control target-leads-input" name="target_leads" value="<?php echo $campaign['target_leads']; ?>" min="0" data-campaign-id="<?php echo $campaign['id']; ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Validated Leads</label>
                                                    <input type="number" class="form-control validated-leads-input" name="validated_leads" value="<?php echo $campaign['validated_leads']; ?>" min="0" max="<?php echo $campaign['target_leads']; ?>" data-advertiser-payout="<?php echo $campaign['advertiser_payout']; ?>" data-campaign-id="<?php echo $campaign['id']; ?>">
                                                </div>
                                                <div class="alert alert-info">
                                                    <strong>Total Amount:</strong> ₹<span class="total-amount-display"><?php echo number_format($campaign['validated_leads'] * $campaign['advertiser_payout'], 2); ?></span>
                                                    <br><small>(Validated Leads × Advertiser Payout)</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update total amount when validated leads change
    document.querySelectorAll('.validated-leads-input').forEach(function(input) {
        input.addEventListener('input', function() {
            const payout = parseFloat(this.dataset.advertiserPayout) || 0;
            const leads = parseFloat(this.value) || 0;
            const total = leads * payout;
            const display = this.closest('.modal-body').querySelector('.total-amount-display');
            if (display) display.textContent = total.toFixed(2);
        });
    });
    
    // Update max validated leads when target changes
    document.querySelectorAll('.target-leads-input').forEach(function(input) {
        input.addEventListener('input', function() {
            const campaignId = this.dataset.campaignId;
            const validatedInput = this.closest('.modal-body').querySelector('.validated-leads-input');
            if (validatedInput) {
                validatedInput.max = this.value;
                if (parseInt(validatedInput.value) > parseInt(this.value)) {
                    validatedInput.value = this.value;
                    validatedInput.dispatchEvent(new Event('input'));
                }
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
