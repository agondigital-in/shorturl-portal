<?php
// super_admin/office_expenses.php - Office Expenses Management
$page_title = 'Office Expenses';
require_once 'includes/header.php';
require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Create tables if not exist
$conn->exec("CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT 'fas fa-receipt',
    color VARCHAR(20) DEFAULT '#6366f1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->exec("CREATE TABLE IF NOT EXISTS office_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    payment_mode VARCHAR(50) DEFAULT 'Cash',
    receipt_no VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Clear and insert default categories (to fix duplicates)
$stmt = $conn->query("SELECT COUNT(*) FROM expense_categories");
$count = $stmt->fetchColumn();
if ($count > 10) {
    // Duplicates exist, clear and re-insert
    $conn->exec("TRUNCATE TABLE expense_categories");
    $count = 0;
}

if ($count == 0) {
    $conn->exec("INSERT IGNORE INTO expense_categories (name, icon, color) VALUES
        ('Electricity Bill', 'fas fa-bolt', '#f59e0b'),
        ('Water Bill', 'fas fa-tint', '#3b82f6'),
        ('Office Rent', 'fas fa-building', '#8b5cf6'),
        ('Sweeper/Cleaning', 'fas fa-broom', '#10b981'),
        ('Maintenance', 'fas fa-tools', '#ef4444'),
        ('Internet/Phone', 'fas fa-wifi', '#06b6d4'),
        ('Stationery', 'fas fa-pen', '#ec4899'),
        ('Tea/Snacks', 'fas fa-coffee', '#84cc16'),
        ('Transport', 'fas fa-car', '#f97316'),
        ('Other', 'fas fa-ellipsis-h', '#64748b')");
}

// Handle Add Expense
$redirect = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $conn->prepare("INSERT INTO office_expenses (expense_date, category, description, amount, payment_mode, receipt_no, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['expense_date'],
            $_POST['category'],
            $_POST['description'],
            $_POST['amount'],
            $_POST['payment_mode'],
            $_POST['receipt_no'],
            $_POST['notes']
        ]);
        $redirect = "office_expenses.php?success=1";
    }
    
    if ($_POST['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM office_expenses WHERE id = ?");
        $stmt->execute([$_POST['expense_id']]);
        $redirect = "office_expenses.php?deleted=1";
    }
}

$success = isset($_GET['success']) ? "Expense added successfully!" : null;
$success = isset($_GET['deleted']) ? "Expense deleted!" : $success;

// Get filter values
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_category = $_GET['category'] ?? '';

// Build query
$sql = "SELECT * FROM office_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?";
$params = [$filter_month];

if ($filter_category) {
    $sql .= " AND category = ?";
    $params[] = $filter_category;
}
$sql .= " ORDER BY expense_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$categories = $conn->query("SELECT * FROM expense_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get totals
$total_amount = array_sum(array_column($expenses, 'amount'));

// Get monthly summary
$stmt = $conn->prepare("SELECT category, SUM(amount) as total FROM office_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ? GROUP BY category");
$stmt->execute([$filter_month]);
$category_totals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.exp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
.exp-header h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
.exp-header h1 i { color: #6366f1; }
.btn-add { background: #6366f1; color: #fff; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; }
.btn-add:hover { background: #4f46e5; }

.filter-row { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
.filter-row input, .filter-row select { padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; }
.btn-filter { background: #1e293b; color: #fff; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; }
.btn-print { background: #10b981; color: #fff; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; }

.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; }
.stat-card h4 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
.stat-card p { color: #64748b; margin: 4px 0 0 0; font-size: 0.85rem; }
.stat-card.total { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; }
.stat-card.total h4, .stat-card.total p { color: #fff; }

.exp-table-wrap { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
.exp-table { width: 100%; border-collapse: collapse; }
.exp-table th { padding: 14px 16px; text-align: left; font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.exp-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
.exp-table tr:hover { background: #f8fafc; }

.cat-badge { padding: 4px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
.amount { font-weight: 700; color: #dc2626; }
.btn-del { background: #fee2e2; color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
.btn-del:hover { background: #dc2626; color: #fff; }

/* Modal */
.modal-content { border-radius: 16px; }
.modal-header { background: #f8fafc; border-radius: 16px 16px 0 0; }
.form-label { font-weight: 600; color: #475569; margin-bottom: 6px; }
.form-control, .form-select { border-radius: 8px; padding: 10px 14px; border: 1px solid #e2e8f0; }
.form-control:focus, .form-select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
.btn-save { background: #6366f1; color: #fff; padding: 12px 24px; border-radius: 8px; border: none; font-weight: 600; }

.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i { font-size: 3rem; margin-bottom: 16px; }

@media print {
    .no-print { display: none !important; }
    .exp-table-wrap { box-shadow: none; border: 1px solid #000; }
}
</style>

<div class="exp-header">
    <h1><i class="fas fa-wallet me-2"></i>Office Expenses</h1>
    <button class="btn-add no-print" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fas fa-plus me-1"></i> Add Expense
    </button>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success py-2 no-print" style="border-radius:8px;"><i class="fas fa-check-circle me-1"></i><?php echo $success; ?></div>
<?php endif; ?>

<!-- Filters -->
<form method="GET" class="filter-row no-print">
    <input type="month" name="month" value="<?php echo $filter_month; ?>" class="form-control">
    <select name="category" class="form-select">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?php echo $cat['name']; ?>" <?php echo $filter_category === $cat['name'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-filter"><i class="fas fa-filter me-1"></i> Filter</button>
    <a href="expense_receipt.php?month=<?php echo $filter_month; ?>" class="btn-print" target="_blank"><i class="fas fa-print me-1"></i> Print Receipt</a>
</form>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card total">
        <h4>₹<?php echo number_format($total_amount, 2); ?></h4>
        <p>Total Expenses (<?php echo date('F Y', strtotime($filter_month.'-01')); ?>)</p>
    </div>
    <?php foreach (array_slice($category_totals, 0, 4) as $ct): ?>
    <div class="stat-card">
        <h4>₹<?php echo number_format($ct['total'], 2); ?></h4>
        <p><?php echo $ct['category']; ?></p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Expenses Table -->
<div class="exp-table-wrap">
    <?php if (empty($expenses)): ?>
    <div class="empty-state">
        <i class="fas fa-receipt"></i>
        <p>No expenses found for this month</p>
    </div>
    <?php else: ?>
    <table class="exp-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Payment Mode</th>
                <th>Receipt No</th>
                <th class="no-print">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($expenses as $exp): ?>
            <tr>
                <td><?php echo date('d M Y', strtotime($exp['expense_date'])); ?></td>
                <td><span class="cat-badge" style="background:#ede9fe;color:#7c3aed;"><?php echo $exp['category']; ?></span></td>
                <td><?php echo htmlspecialchars($exp['description'] ?: '-'); ?></td>
                <td class="amount">₹<?php echo number_format($exp['amount'], 2); ?></td>
                <td><?php echo $exp['payment_mode']; ?></td>
                <td><?php echo $exp['receipt_no'] ?: '-'; ?></td>
                <td class="no-print">
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="expense_id" value="<?php echo $exp['id']; ?>">
                        <button type="submit" class="btn-del"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:#f8fafc;font-weight:700;">
                <td colspan="3">Total</td>
                <td class="amount">₹<?php echo number_format($total_amount, 2); ?></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-primary"></i>Add New Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['name']; ?>"><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g., March 2024 electricity bill">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (₹) *</label>
                            <input type="number" name="amount" class="form-control" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Card">Card</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Receipt/Bill No</label>
                        <input type="text" name="receipt_no" class="form-control" placeholder="Optional">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-save"><i class="fas fa-save me-1"></i> Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<?php if ($redirect): ?>
<script>window.location.href = '<?php echo $redirect; ?>';</script>
<?php endif; ?>
