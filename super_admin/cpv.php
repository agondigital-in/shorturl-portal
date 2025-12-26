<?php
// super_admin/cpv.php - CPV Campaign Manager
$page_title = 'CPV Campaigns';
require_once 'includes/header.php';
require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$message = '';
$error = '';

if (isset($_GET['created'])) $message = 'CPV Campaign created successfully!';
if (isset($_GET['updated'])) $message = 'Campaign updated successfully!';
if (isset($_GET['deleted'])) $message = 'Campaign deleted successfully!';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $campaign_name = trim($_POST['campaign_name'] ?? '');
        $original_url = trim($_POST['original_url'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if (empty($campaign_name) || empty($original_url) || empty($start_date) || empty($end_date)) {
            $error = 'All fields are required!';
        } elseif (!filter_var($original_url, FILTER_VALIDATE_URL)) {
            $error = 'Please enter a valid URL!';
        } else {
            $stmt = $conn->prepare("SELECT MAX(CAST(short_code AS UNSIGNED)) as max_num FROM cpv_campaigns WHERE short_code REGEXP '^[0-9]+$'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $short_code = (string)(($result['max_num'] ?? 0) + 1);
            
            try {
                $stmt = $conn->prepare("INSERT INTO cpv_campaigns (campaign_name, original_url, short_code, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$campaign_name, $original_url, $short_code, $start_date, $end_date, $_SESSION['user_id']]);
                header('Location: cpv.php?created=1');
                exit();
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
    
    if ($_POST['action'] === 'edit') {
        $campaign_id = $_POST['campaign_id'] ?? 0;
        $campaign_name = trim($_POST['campaign_name'] ?? '');
        $original_url = trim($_POST['original_url'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $status = $_POST['status'] ?? 'active';
        
        try {
            $stmt = $conn->prepare("UPDATE cpv_campaigns SET campaign_name = ?, original_url = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
            $stmt->execute([$campaign_name, $original_url, $start_date, $end_date, $status, $campaign_id]);
            header('Location: cpv.php?updated=1');
            exit();
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'delete') {
        $campaign_id = $_POST['campaign_id'] ?? 0;
        try {
            $stmt = $conn->prepare("DELETE FROM cpv_campaigns WHERE id = ?");
            $stmt->execute([$campaign_id]);
            header('Location: cpv.php?deleted=1');
            exit();
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_search = $_GET['search'] ?? '';

$where = "1=1";
$params = [];

if ($filter_status !== 'all') {
    $where .= " AND c.status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_search)) {
    $where .= " AND (c.campaign_name LIKE ? OR c.short_code LIKE ?)";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
}

try {
    $stmt = $conn->prepare("
        SELECT c.*, 
               COALESCE(SUM(ds.total_clicks), 0) as total_clicks,
               COALESCE(SUM(ds.original_clicks), 0) as original_clicks,
               COALESCE(SUM(ds.duplicate_clicks), 0) as duplicate_clicks
        FROM cpv_campaigns c
        LEFT JOIN cpv_daily_stats ds ON c.id = ds.campaign_id
        WHERE $where
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
    $campaigns = [];
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$base_url .= dirname(dirname($_SERVER['PHP_SELF'])) . '/cpv_redirect.php?c=';

$total_clicks = array_sum(array_column($campaigns, 'total_clicks'));
$total_original = array_sum(array_column($campaigns, 'original_clicks'));
$active_campaigns = count(array_filter($campaigns, fn($c) => $c['status'] === 'active'));
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">CPV Campaigns</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">CPV Campaigns</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="cpv_report.php" class="btn btn-soft-info me-2"><i class="fas fa-chart-bar me-2"></i>View Report</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-2"></i>Add Campaign
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-card-icon"><i class="fas fa-eye"></i></div>
            <div class="stat-card-value"><?php echo count($campaigns); ?></div>
            <div class="stat-card-label">Total Campaigns</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-card-value"><?php echo $active_campaigns; ?></div>
            <div class="stat-card-label">Active Campaigns</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="stat-card-icon"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_clicks); ?></div>
            <div class="stat-card-label">Total Clicks</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-card-icon"><i class="fas fa-star"></i></div>
            <div class="stat-card-value"><?php echo number_format($total_original); ?></div>
            <div class="stat-card-label">Original Clicks</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Campaign name or code" value="<?php echo htmlspecialchars($filter_search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="cpv.php" class="btn btn-light"><i class="fas fa-redo me-1"></i>Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Campaigns Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All CPV Campaigns</h5>
        <span class="badge bg-primary"><?php echo count($campaigns); ?> Total</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($campaigns)): ?>
        <div class="text-center py-5">
            <i class="fas fa-eye fa-3x text-muted mb-3"></i>
            <p class="text-muted">No CPV campaigns found</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Short URL</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Original</th>
                        <th class="text-center">Duplicate</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $camp): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($camp['campaign_name']); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($camp['original_url'], 0, 40)); ?>...</small>
                        </td>
                        <td>
                            <code id="url-<?php echo $camp['id']; ?>"><?php echo $base_url . $camp['short_code']; ?></code>
                            <button class="btn btn-sm btn-link p-0 ms-2" onclick="copyUrl('url-<?php echo $camp['id']; ?>')" title="Copy URL">
                                <i class="fas fa-copy text-primary"></i>
                            </button>
                        </td>
                        <td class="text-center"><span class="badge badge-soft-primary"><?php echo $camp['total_clicks'] ?? 0; ?></span></td>
                        <td class="text-center"><span class="badge badge-soft-success"><?php echo $camp['original_clicks'] ?? 0; ?></span></td>
                        <td class="text-center"><span class="badge badge-soft-danger"><?php echo $camp['duplicate_clicks'] ?? 0; ?></span></td>
                        <td><small><?php echo date('M d', strtotime($camp['start_date'])); ?> - <?php echo date('M d, Y', strtotime($camp['end_date'])); ?></small></td>
                        <td>
                            <?php if ($camp['status'] === 'active'): ?>
                                <span class="badge badge-soft-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                            <?php else: ?>
                                <span class="badge badge-soft-warning">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="cpv_stats.php?id=<?php echo $camp['id']; ?>" class="btn btn-soft-info" title="Stats"><i class="fas fa-chart-bar"></i></a>
                                <button class="btn btn-soft-warning" onclick='editCampaign(<?php echo json_encode($camp); ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="campaign_id" value="<?php echo $camp['id']; ?>">
                                    <button type="submit" class="btn btn-soft-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add CPV Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Campaign Name <span class="text-danger">*</span></label>
                        <input type="text" name="campaign_name" class="form-control" required placeholder="Enter campaign name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Original URL <span class="text-danger">*</span></label>
                        <input type="url" name="original_url" class="form-control" placeholder="https://example.com" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="campaign_id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit CPV Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Campaign Name <span class="text-danger">*</span></label>
                        <input type="text" name="campaign_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Original URL <span class="text-danger">*</span></label>
                        <input type="url" name="original_url" id="edit_url" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="edit_start" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="edit_end" class="form-control">
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
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyUrl(id) {
    const text = document.getElementById(id).innerText;
    navigator.clipboard.writeText(text);
    alert('URL copied to clipboard!');
}

function editCampaign(camp) {
    document.getElementById('edit_id').value = camp.id;
    document.getElementById('edit_name').value = camp.campaign_name;
    document.getElementById('edit_url').value = camp.original_url;
    document.getElementById('edit_start').value = camp.start_date;
    document.getElementById('edit_end').value = camp.end_date;
    document.getElementById('edit_status').value = camp.status;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
