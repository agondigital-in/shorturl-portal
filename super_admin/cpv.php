<?php
// super_admin/cpv.php - CPV Campaign Manager with IP Tracking
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$message = '';
$error = '';

// Check if campaign was just created
if (isset($_GET['created']) && $_GET['created'] == 1) {
    $message = 'CPV Campaign created successfully!';
}

// Check if campaign was just updated
if (isset($_GET['updated']) && $_GET['updated'] == 1) {
    $message = 'Campaign updated successfully!';
}

// Check if campaign was just deleted
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $message = 'Campaign deleted successfully!';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Create new campaign
    if ($_POST['action'] === 'create') {
        $campaign_name = trim($_POST['campaign_name'] ?? '');
        $original_url = trim($_POST['original_url'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if (empty($campaign_name) || empty($original_url) || empty($start_date) || empty($end_date)) {
            $error = 'All fields are required!';
        } elseif (!filter_var($original_url, FILTER_VALIDATE_URL)) {
            $error = 'Please enter a valid URL!';
        } elseif ($start_date > $end_date) {
            $error = 'End date must be after start date!';
        } else {
            // Generate auto-increment short code like camp1, camp2, camp3
            $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(short_code, 5) AS UNSIGNED)) as max_num FROM cpv_campaigns WHERE short_code LIKE 'camp%' AND short_code REGEXP '^camp[0-9]+$'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $next_num = ($result['max_num'] ?? 0) + 1;
            $short_code = 'camp' . $next_num;
            
            if (empty($error)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO cpv_campaigns (campaign_name, original_url, short_code, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$campaign_name, $original_url, $short_code, $start_date, $end_date, $_SESSION['user_id']]);
                    // Redirect to refresh page after successful creation
                    header('Location: cpv.php?created=1');
                    exit();
                } catch (PDOException $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
            }
        }
    }
    
    // Edit campaign (only URL and dates, not short code)
    elseif ($_POST['action'] === 'edit') {
        $campaign_id = $_POST['campaign_id'] ?? 0;
        $campaign_name = trim($_POST['campaign_name'] ?? '');
        $original_url = trim($_POST['original_url'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $status = $_POST['status'] ?? 'active';
        
        if (empty($campaign_name) || empty($original_url)) {
            $error = 'Campaign name and URL are required!';
        } elseif (!filter_var($original_url, FILTER_VALIDATE_URL)) {
            $error = 'Please enter a valid URL!';
        } else {
            try {
                $stmt = $conn->prepare("UPDATE cpv_campaigns SET campaign_name = ?, original_url = ?, start_date = ?, end_date = ?, status = ? WHERE id = ? AND created_by = ?");
                $stmt->execute([$campaign_name, $original_url, $start_date, $end_date, $status, $campaign_id, $_SESSION['user_id']]);
                header('Location: cpv.php?updated=1');
                exit();
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
    
    // Delete campaign
    elseif ($_POST['action'] === 'delete') {
        $campaign_id = $_POST['campaign_id'] ?? 0;
        try {
            $stmt = $conn->prepare("DELETE FROM cpv_campaigns WHERE id = ? AND created_by = ?");
            $stmt->execute([$campaign_id, $_SESSION['user_id']]);
            header('Location: cpv.php?deleted=1');
            exit();
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get campaign for editing
$edit_campaign = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM cpv_campaigns WHERE id = ? AND created_by = ?");
    $stmt->execute([$_GET['edit'], $_SESSION['user_id']]);
    $edit_campaign = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_search = $_GET['search'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Build query with filters
$where_conditions = ["c.created_by = ?"];
$params = [$_SESSION['user_id']];

if ($filter_status !== 'all') {
    $where_conditions[] = "c.status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_search)) {
    $where_conditions[] = "(c.campaign_name LIKE ? OR c.short_code LIKE ?)";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
}

if (!empty($filter_date_from)) {
    $where_conditions[] = "c.start_date >= ?";
    $params[] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "c.end_date <= ?";
    $params[] = $filter_date_to;
}

$where_clause = implode(" AND ", $where_conditions);

// Get all CPV campaigns with click stats
try {
    $stmt = $conn->prepare("
        SELECT c.*, 
               COUNT(cc.id) as total_clicks,
               SUM(CASE WHEN cc.is_duplicate = 0 THEN 1 ELSE 0 END) as original_clicks,
               SUM(CASE WHEN cc.is_duplicate = 1 THEN 1 ELSE 0 END) as duplicate_clicks
        FROM cpv_campaigns c
        LEFT JOIN cpv_clicks cc ON c.id = cc.campaign_id
        WHERE $where_clause
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading campaigns: ' . $e->getMessage();
    $campaigns = [];
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$base_url .= dirname(dirname($_SERVER['PHP_SELF'])) . '/cpv_redirect.php?c=';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPV Campaigns - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: calc(100vh - 56px); }
        .nav-link { color: #333; padding: 10px 20px; }
        .nav-link:hover, .nav-link.active { background: #e9ecef; color: #0d6efd; }
        .card { border: none; border-radius: 12px; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px 12px 0 0 !important; }
        .short-url { font-family: monospace; background: #f8f9fa; padding: 8px 12px; border-radius: 6px; border: 1px solid #dee2e6; }
        .copy-btn { cursor: pointer; transition: color 0.2s; }
        .copy-btn:hover { color: #0d6efd !important; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .badge { font-size: 0.85rem; }
        .status-active { background: #198754; }
        .status-inactive { background: #6c757d; }
        .btn-action { padding: 5px 10px; margin: 2px; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-chart-line me-2"></i>Ads Platform</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3"><i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a class="btn btn-outline-light btn-sm" href="../logout.php"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            

            <main class="col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><i class="fas fa-compress-alt me-2 text-primary"></i>CPV Campaigns</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCampaignModal">
                        <i class="fas fa-plus me-1"></i>Add CPV Campaign
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><i class="fas fa-search me-1"></i>Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Campaign name or code" value="<?php echo htmlspecialchars($filter_search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="fas fa-toggle-on me-1"></i>Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="fas fa-calendar me-1"></i>Start From</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="fas fa-calendar me-1"></i>End To</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter me-1"></i>Filter</button>
                                <a href="cpv.php" class="btn btn-outline-secondary"><i class="fas fa-redo me-1"></i>Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Campaigns Table -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All CPV Campaigns</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($campaigns)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No CPV campaigns yet. Click "Add CPV Campaign" to create one.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Campaign Name</th>
                                            <th>Short URL</th>
                                            <th class="text-center">Total</th>
                                            <th class="text-center">Original</th>
                                            <th class="text-center">Duplicate</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($campaigns as $camp): ?>
                                            <?php 
                                            $today = date('Y-m-d');
                                            $is_expired = $camp['end_date'] < $today;
                                            $is_upcoming = $camp['start_date'] > $today;
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($camp['campaign_name']); ?></strong>
                                                    <br><small class="text-muted text-truncate d-inline-block" style="max-width:200px;"><?php echo htmlspecialchars($camp['original_url']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="short-url me-2" id="url-<?php echo $camp['id']; ?>"><?php echo $base_url . $camp['short_code']; ?></span>
                                                        <i class="fas fa-copy copy-btn text-secondary" onclick="copyUrl('url-<?php echo $camp['id']; ?>')" title="Copy URL"></i>
                                                    </div>
                                                </td>
                                                <td class="text-center"><span class="badge bg-primary"><?php echo $camp['total_clicks'] ?? 0; ?></span></td>
                                                <td class="text-center"><span class="badge bg-success"><?php echo $camp['original_clicks'] ?? 0; ?></span></td>
                                                <td class="text-center"><span class="badge bg-danger"><?php echo $camp['duplicate_clicks'] ?? 0; ?></span></td>
                                                <td>
                                                    <small>
                                                        <?php echo date('d M', strtotime($camp['start_date'])); ?> - <?php echo date('d M Y', strtotime($camp['end_date'])); ?>
                                                        <?php if ($is_expired): ?>
                                                            <br><span class="badge bg-danger">Expired</span>
                                                        <?php elseif ($is_upcoming): ?>
                                                            <br><span class="badge bg-info">Upcoming</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge status-<?php echo $camp['status']; ?>"><?php echo ucfirst($camp['status']); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="cpv_stats.php?id=<?php echo $camp['id']; ?>" class="btn btn-info btn-action" title="View Stats"><i class="fas fa-chart-bar"></i></a>
                                                    <button class="btn btn-warning btn-action" onclick="editCampaign(<?php echo htmlspecialchars(json_encode($camp)); ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="campaign_id" value="<?php echo $camp['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-action" title="Delete"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Campaign Modal -->
    <div class="modal fade" id="addCampaignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add CPV Campaign</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Campaign Name *</label>
                            <input type="text" name="campaign_name" class="form-control" placeholder="Enter campaign name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Original URL *</label>
                            <input type="url" name="original_url" class="form-control" placeholder="https://example.com/page" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Custom Short Code (Optional)</label>
                            <input type="text" name="custom_short_code" class="form-control" placeholder="e.g., lago1, lago2, etc." pattern="[a-zA-Z0-9_-]+" title="Only letters, numbers, hyphens and underscores allowed">
                            <div class="form-text">Leave empty to auto-generate. Only letters, numbers, hyphens and underscores allowed.</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date *</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date *</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Campaign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Campaign Modal -->
    <div class="modal fade" id="editCampaignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="campaign_id" id="edit_campaign_id">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit CPV Campaign</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Short URL (Cannot be changed)</label>
                            <input type="text" id="edit_short_url" class="form-control bg-light" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Campaign Name *</label>
                            <input type="text" name="campaign_name" id="edit_campaign_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Original URL *</label>
                            <input type="url" name="original_url" id="edit_original_url" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date *</label>
                                <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date *</label>
                                <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update Campaign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo $base_url; ?>';
        
        function copyUrl(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                // Show success message with better UX
                const copyBtn = document.querySelector(`[onclick="copyUrl('${elementId}')"]`);
                const originalClass = copyBtn.className;
                const originalTitle = copyBtn.title;
                
                copyBtn.className = 'fas fa-check copy-btn text-success';
                copyBtn.title = 'Copied!';
                
                setTimeout(() => {
                    copyBtn.className = originalClass;
                    copyBtn.title = originalTitle;
                }, 2000);
                
                // Optional: Show toast notification instead of alert
                showToast('URL copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy: ', err);
                showToast('Failed to copy URL', 'error');
            });
        }
        
        function showToast(message, type = 'success') {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
            `;
            
            document.body.appendChild(toast);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        function editCampaign(campaign) {
            document.getElementById('edit_campaign_id').value = campaign.id;
            document.getElementById('edit_campaign_name').value = campaign.campaign_name;
            document.getElementById('edit_original_url').value = campaign.original_url;
            document.getElementById('edit_start_date').value = campaign.start_date;
            document.getElementById('edit_end_date').value = campaign.end_date;
            document.getElementById('edit_status').value = campaign.status;
            document.getElementById('edit_short_url').value = baseUrl + campaign.short_code;
            
            new bootstrap.Modal(document.getElementById('editCampaignModal')).show();
        }
    </script>
</body>
</html>
