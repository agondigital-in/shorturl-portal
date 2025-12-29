<?php
// super_admin/ie_budget.php - Indian Express Budget Leads Entry
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Create table if not exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS ie_budget_leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_date DATE NOT NULL UNIQUE,
        leads_count INT DEFAULT 0,
        notes VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}

// Handle Excel Export BEFORE any output
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filter = $_GET['filter'] ?? 'all';
    $date = $_GET['date'] ?? '';
    $month = $_GET['month'] ?? '';
    $year = $_GET['year'] ?? date('Y');
    
    $sql = "SELECT lead_date, leads_count, notes FROM ie_budget_leads WHERE 1=1";
    $params = [];
    
    if ($filter === 'date' && $date) {
        $sql .= " AND lead_date = ?";
        $params[] = $date;
    } elseif ($filter === 'month' && $month) {
        $sql .= " AND DATE_FORMAT(lead_date, '%Y-%m') = ?";
        $params[] = $month;
    } elseif ($filter === 'year' && $year) {
        $sql .= " AND YEAR(lead_date) = ?";
        $params[] = $year;
    }
    
    $sql .= " ORDER BY lead_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="IE_Budget_Leads_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th>Date</th><th>Day</th><th>Leads</th><th>Notes</th></tr>";
    $total = 0;
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . date('d-M-Y', strtotime($row['lead_date'])) . "</td>";
        echo "<td>" . date('l', strtotime($row['lead_date'])) . "</td>";
        echo "<td>" . $row['leads_count'] . "</td>";
        echo "<td>" . htmlspecialchars($row['notes']) . "</td>";
        echo "</tr>";
        $total += $row['leads_count'];
    }
    echo "<tr><td colspan='2'><strong>TOTAL</strong></td><td><strong>$total</strong></td><td></td></tr>";
    echo "</table>";
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_leads') {
            $lead_date = $_POST['lead_date'] ?? date('Y-m-d');
            $leads_count = $_POST['leads_count'] ?? 0;
            $notes = $_POST['notes'] ?? '';
            
            try {
                $stmt = $conn->prepare("INSERT INTO ie_budget_leads (lead_date, leads_count, notes) 
                                        VALUES (?, ?, ?) 
                                        ON DUPLICATE KEY UPDATE leads_count = ?, notes = ?");
                $stmt->execute([$lead_date, $leads_count, $notes, $leads_count, $notes]);
                $success = "Leads saved successfully for " . date('d M Y', strtotime($lead_date));
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
        
        if ($_POST['action'] === 'edit_leads') {
            $lead_date = $_POST['lead_date'] ?? '';
            $leads_count = $_POST['leads_count'] ?? 0;
            $notes = $_POST['notes'] ?? '';
            
            try {
                $stmt = $conn->prepare("UPDATE ie_budget_leads SET leads_count = ?, notes = ? WHERE lead_date = ?");
                $stmt->execute([$leads_count, $notes, $lead_date]);
                $success = "Entry updated successfully!";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
        
        if ($_POST['action'] === 'delete_leads') {
            $lead_date = $_POST['lead_date'] ?? '';
            
            try {
                $stmt = $conn->prepare("DELETE FROM ie_budget_leads WHERE lead_date = ?");
                $stmt->execute([$lead_date]);
                $success = "Entry deleted successfully!";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get filter for view
$view_filter = $_GET['view'] ?? 'month';
$view_date = $_GET['date'] ?? date('Y-m-d');
$view_month = $_GET['month'] ?? date('Y-m');
$view_year = $_GET['year'] ?? date('Y');

// Get data based on filter
$sql = "SELECT lead_date, leads_count, notes FROM ie_budget_leads WHERE 1=1";
$params = [];

if ($view_filter === 'date') {
    $sql .= " AND lead_date = ?";
    $params[] = $view_date;
    $filter_label = date('d M Y', strtotime($view_date));
} elseif ($view_filter === 'month') {
    $sql .= " AND DATE_FORMAT(lead_date, '%Y-%m') = ?";
    $params[] = $view_month;
    $filter_label = date('F Y', strtotime($view_month . '-01'));
} elseif ($view_filter === 'year') {
    $sql .= " AND YEAR(lead_date) = ?";
    $params[] = $view_year;
    $filter_label = $view_year;
} else {
    $filter_label = 'All Time';
}

$sql .= " ORDER BY lead_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$leads_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_leads = array_sum(array_column($leads_data, 'leads_count'));

// Get overall stats
$stmt = $conn->query("SELECT COUNT(*) as days, SUM(leads_count) as total FROM ie_budget_leads");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Now include header
$page_title = 'IE Budget';
require_once 'includes/header.php';
?>

<style>
.ie-header {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 25px;
    color: white;
    position: relative;
    overflow: hidden;
}
.ie-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}
.ie-header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
.ie-header p { opacity: 0.9; margin: 0; }

.stat-box {
    border-radius: 16px;
    padding: 25px;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s;
}
.stat-box:hover { transform: translateY(-5px); }
.stat-box .icon {
    font-size: 2.5rem;
    opacity: 0.3;
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
}
.stat-box h2 { font-size: 2.5rem; font-weight: 700; margin: 0; }
.stat-box p { margin: 5px 0 0; font-size: 0.95rem; opacity: 0.9; }
.stat-orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stat-blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
.stat-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }

.entry-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}
.entry-card h5 { color: #1e293b; font-weight: 700; margin-bottom: 20px; }

.view-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
}
.view-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%);
    color: white;
    padding: 20px 25px;
}
.view-header h5 { margin: 0; font-weight: 600; }

.filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-tab {
    padding: 10px 20px;
    border-radius: 25px;
    border: 2px solid #e2e8f0;
    background: white;
    color: #64748b;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}
.filter-tab:hover, .filter-tab.active {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-color: transparent;
    color: white;
}

.data-table { margin: 0; }
.data-table thead th {
    background: #f8fafc;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 1px;
    color: #64748b;
    padding: 15px 20px;
    border: none;
}
.data-table tbody td {
    padding: 15px 20px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.data-table tbody tr:hover { background: #f8fafc; }

.leads-badge {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 600;
}

.export-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 500;
}
.export-btn:hover { color: white; }

.btn-edit {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border: none;
    color: white;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
}
.btn-edit:hover { color: white; }

.btn-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: none;
    color: white;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
}
.btn-delete:hover { color: white; }
</style>

<!-- Page Header -->
<div class="ie-header">
    <h1><i class="fas fa-newspaper me-2"></i>IE Budget</h1>
    <p>Indian Express Budget - Daily Leads Entry & Tracking</p>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px;">
    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px;">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-box stat-orange">
            <i class="fas fa-calendar-check icon"></i>
            <h2><?php echo $stats['days'] ?? 0; ?></h2>
            <p><i class="fas fa-calendar me-1"></i>Total Days</p>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-box stat-blue">
            <i class="fas fa-users icon"></i>
            <h2><?php echo number_format($stats['total'] ?? 0); ?></h2>
            <p><i class="fas fa-chart-line me-1"></i>All Time Leads</p>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-box stat-green">
            <i class="fas fa-trophy icon"></i>
            <h2><?php echo number_format($total_leads); ?></h2>
            <p><i class="fas fa-filter me-1"></i><?php echo $filter_label; ?> Leads</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Entry Form -->
    <div class="col-lg-4 mb-4">
        <div class="entry-card">
            <h5><i class="fas fa-plus-circle me-2 text-warning"></i>Add Daily Leads</h5>
            <form method="POST">
                <input type="hidden" name="action" value="add_leads">
                <div class="mb-3">
                    <label class="form-label fw-bold">Date</label>
                    <input type="date" name="lead_date" class="form-control form-control-lg" style="border-radius: 12px;" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Leads Count</label>
                    <input type="number" name="leads_count" class="form-control form-control-lg" style="border-radius: 12px;" min="0" value="0" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Notes (Optional)</label>
                    <input type="text" name="notes" class="form-control" style="border-radius: 12px;" placeholder="Any remarks...">
                </div>
                <button type="submit" class="btn btn-lg w-100" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 12px;">
                    <i class="fas fa-save me-2"></i>Save Entry
                </button>
            </form>
        </div>
    </div>
    
    <!-- View Data -->
    <div class="col-lg-8 mb-4">
        <div class="view-card">
            <div class="view-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5><i class="fas fa-table me-2"></i>Leads Data - <?php echo $filter_label; ?></h5>
                <a href="?export=excel&filter=<?php echo $view_filter; ?>&date=<?php echo $view_date; ?>&month=<?php echo $view_month; ?>&year=<?php echo $view_year; ?>" class="export-btn">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </a>
            </div>
            <div class="p-4">
                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="?view=date&date=<?php echo date('Y-m-d'); ?>" class="filter-tab <?php echo $view_filter === 'date' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-day me-1"></i>Date
                    </a>
                    <a href="?view=month&month=<?php echo date('Y-m'); ?>" class="filter-tab <?php echo $view_filter === 'month' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt me-1"></i>Month
                    </a>
                    <a href="?view=year&year=<?php echo date('Y'); ?>" class="filter-tab <?php echo $view_filter === 'year' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar me-1"></i>Year
                    </a>
                    <a href="?view=all" class="filter-tab <?php echo $view_filter === 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-list me-1"></i>All
                    </a>
                </div>
                
                <!-- Filter Inputs -->
                <div class="row mb-4">
                    <?php if ($view_filter === 'date'): ?>
                    <div class="col-md-6">
                        <input type="date" class="form-control" value="<?php echo $view_date; ?>" onchange="window.location.href='?view=date&date=' + this.value">
                    </div>
                    <?php elseif ($view_filter === 'month'): ?>
                    <div class="col-md-6">
                        <input type="month" class="form-control" value="<?php echo $view_month; ?>" onchange="window.location.href='?view=month&month=' + this.value">
                    </div>
                    <?php elseif ($view_filter === 'year'): ?>
                    <div class="col-md-6">
                        <select class="form-select" onchange="window.location.href='?view=year&year=' + this.value">
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $view_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="table data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Leads</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leads_data)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                    <span class="text-muted">No data found</span>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($leads_data as $i => $row): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><strong><?php echo date('d M Y', strtotime($row['lead_date'])); ?></strong></td>
                                <td><?php echo date('l', strtotime($row['lead_date'])); ?></td>
                                <td><span class="leads-badge"><?php echo number_format($row['leads_count']); ?></span></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($row['notes'] ?? '-'); ?></small></td>
                                <td>
                                    <button class="btn btn-edit me-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $i; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $i; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $i; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="border-radius: 16px;">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="edit_leads">
                                            <input type="hidden" name="lead_date" value="<?php echo $row['lead_date']; ?>">
                                            <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border-radius: 16px 16px 0 0;">
                                                <h5 class="modal-title text-white"><i class="fas fa-edit me-2"></i>Edit - <?php echo date('d M Y', strtotime($row['lead_date'])); ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Leads Count</label>
                                                    <input type="number" name="leads_count" class="form-control form-control-lg" style="border-radius: 12px;" min="0" value="<?php echo $row['leads_count']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Notes</label>
                                                    <input type="text" name="notes" class="form-control" style="border-radius: 12px;" value="<?php echo htmlspecialchars($row['notes'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-edit"><i class="fas fa-save me-1"></i>Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $i; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="border-radius: 16px;">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="delete_leads">
                                            <input type="hidden" name="lead_date" value="<?php echo $row['lead_date']; ?>">
                                            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 16px 16px 0 0;">
                                                <h5 class="modal-title text-white"><i class="fas fa-trash me-2"></i>Delete Entry</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4 text-center">
                                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                                <h5>Are you sure?</h5>
                                                <p class="text-muted">Delete entry for <strong><?php echo date('d M Y', strtotime($row['lead_date'])); ?></strong>?</p>
                                            </div>
                                            <div class="modal-footer border-0 justify-content-center">
                                                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-delete px-4"><i class="fas fa-trash me-1"></i>Delete</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($leads_data)): ?>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="3" class="text-end">TOTAL:</th>
                                <th><span class="badge bg-success"><?php echo number_format($total_leads); ?></span></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
