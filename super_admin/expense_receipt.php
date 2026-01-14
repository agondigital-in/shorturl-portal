<?php
// super_admin/expense_receipt.php - Print Expense Receipt
require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$month = $_GET['month'] ?? date('Y-m');

$stmt = $conn->prepare("SELECT * FROM office_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ? ORDER BY expense_date, category");
$stmt->execute([$month]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = array_sum(array_column($expenses, 'amount'));

// Category wise totals
$stmt = $conn->prepare("SELECT category, SUM(amount) as total FROM office_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ? GROUP BY category ORDER BY total DESC");
$stmt->execute([$month]);
$category_totals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Expense Receipt - <?php echo date('F Y', strtotime($month.'-01')); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header h2 { font-size: 16px; color: #666; font-weight: normal; }
        .header p { color: #888; margin-top: 10px; }
        
        .info-row { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { background: #f5f5f5; padding: 15px; border-radius: 8px; }
        .info-box h4 { font-size: 11px; color: #666; margin-bottom: 5px; }
        .info-box p { font-size: 16px; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f8f8f8; font-size: 11px; text-transform: uppercase; }
        .amount { text-align: right; font-weight: bold; }
        .total-row { background: #f0f0f0; font-weight: bold; }
        
        .summary { margin-top: 30px; }
        .summary h3 { margin-bottom: 15px; font-size: 14px; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .summary-item { background: #f8f8f8; padding: 12px; border-radius: 6px; }
        .summary-item span { display: block; font-size: 10px; color: #666; }
        .summary-item strong { font-size: 14px; }
        
        .footer { margin-top: 40px; text-align: center; color: #888; font-size: 10px; border-top: 1px solid #ddd; padding-top: 20px; }
        
        .grand-total { background: #1e293b; color: #fff; padding: 15px; border-radius: 8px; text-align: center; margin-top: 20px; }
        .grand-total span { font-size: 12px; }
        .grand-total h2 { font-size: 28px; margin-top: 5px; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="no-print" style="position:fixed;top:20px;right:20px;padding:10px 20px;background:#6366f1;color:#fff;border:none;border-radius:8px;cursor:pointer;">
        <i class="fas fa-print"></i> Print
    </button>

    <div class="header">
        <h1>AGON DIGITAL</h1>
        <h2>Office Expense Report</h2>
        <p>Month: <?php echo date('F Y', strtotime($month.'-01')); ?></p>
    </div>
    
    <div class="info-row">
        <div class="info-box">
            <h4>Report Period</h4>
            <p><?php echo date('01 M Y', strtotime($month.'-01')); ?> - <?php echo date('t M Y', strtotime($month.'-01')); ?></p>
        </div>
        <div class="info-box">
            <h4>Total Entries</h4>
            <p><?php echo count($expenses); ?></p>
        </div>
        <div class="info-box">
            <h4>Generated On</h4>
            <p><?php echo date('d M Y, h:i A'); ?></p>
        </div>
    </div>
    
    <?php if (empty($expenses)): ?>
    <p style="text-align:center;padding:40px;color:#888;">No expenses recorded for this month.</p>
    <?php else: ?>
    
    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="12%">Date</th>
                <th width="18%">Category</th>
                <th width="25%">Description</th>
                <th width="12%">Payment</th>
                <th width="13%">Receipt No</th>
                <th width="15%">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($expenses as $exp): ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo date('d/m/Y', strtotime($exp['expense_date'])); ?></td>
                <td><?php echo $exp['category']; ?></td>
                <td><?php echo $exp['description'] ?: '-'; ?></td>
                <td><?php echo $exp['payment_mode']; ?></td>
                <td><?php echo $exp['receipt_no'] ?: '-'; ?></td>
                <td class="amount">₹<?php echo number_format($exp['amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="6" style="text-align:right;">TOTAL</td>
                <td class="amount">₹<?php echo number_format($total, 2); ?></td>
            </tr>
        </tbody>
    </table>
    
    <div class="summary">
        <h3>Category-wise Summary</h3>
        <div class="summary-grid">
            <?php foreach ($category_totals as $ct): ?>
            <div class="summary-item">
                <span><?php echo $ct['category']; ?></span>
                <strong>₹<?php echo number_format($ct['total'], 2); ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="grand-total">
        <span>Grand Total for <?php echo date('F Y', strtotime($month.'-01')); ?></span>
        <h2>₹<?php echo number_format($total, 2); ?></h2>
    </div>
    
    <?php endif; ?>
    
    <div class="footer">
        <p>This is a computer generated report. | Agon Digital - Office Expense Management System</p>
        <p>Generated on <?php echo date('d M Y, h:i:s A'); ?></p>
    </div>
</body>
</html>
