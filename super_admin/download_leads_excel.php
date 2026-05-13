<?php
// super_admin/download_leads_excel.php - Download Campaign Leads as Excel
require_once '../db_connection.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    die('Unauthorized access');
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get parameters
$campaign_id = $_GET['campaign_id'] ?? 0;
$month = $_GET['month'] ?? date('Y-m');
$campaign_name = $_GET['campaign_name'] ?? 'Campaign';

// Validate month format
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    die('Invalid month format');
}

// Get campaign leads data for the selected month
list($year, $month_num) = explode('-', $month);

$stmt = $conn->prepare("
    SELECT 
        lead_date,
        leads_count
    FROM campaign_daily_leads
    WHERE campaign_id = ? 
    AND YEAR(lead_date) = ? 
    AND MONTH(lead_date) = ?
    ORDER BY lead_date ASC
");
$stmt->execute([$campaign_id, $year, $month_num]);
$leads_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Month names
$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
$month_display = $monthNames[(int)$month_num] . ' ' . $year;

// Calculate total leads
$total_leads = array_sum(array_column($leads_data, 'leads_count'));

// Set headers for Excel download
$filename = sanitizeFilename($campaign_name) . '_' . $month . '_leads.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Start Excel output
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">
    
    <Styles>
        <Style ss:ID="header">
            <Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF"/>
            <Interior ss:Color="#667eea" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        <Style ss:ID="title">
            <Font ss:Bold="1" ss:Size="16" ss:Color="#667eea"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>
        <Style ss:ID="subtitle">
            <Font ss:Bold="1" ss:Size="12" ss:Color="#764ba2"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>
        <Style ss:ID="cell">
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
            </Borders>
        </Style>
        <Style ss:ID="cellLeft">
            <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
            </Borders>
        </Style>
        <Style ss:ID="total">
            <Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF"/>
            <Interior ss:Color="#10b981" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="2"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
            </Borders>
        </Style>
    </Styles>
    
    <Worksheet ss:Name="Leads Report">
        <Table>
            <Column ss:Width="60"/>
            <Column ss:Width="120"/>
            <Column ss:Width="100"/>
            
            <!-- Title Row -->
            <Row ss:Height="25">
                <Cell ss:MergeAcross="2" ss:StyleID="title">
                    <Data ss:Type="String">📊 Campaign Leads Report</Data>
                </Cell>
            </Row>
            
            <!-- Campaign Name Row -->
            <Row ss:Height="20">
                <Cell ss:MergeAcross="2" ss:StyleID="subtitle">
                    <Data ss:Type="String"><?php echo htmlspecialchars($campaign_name); ?></Data>
                </Cell>
            </Row>
            
            <!-- Month Row -->
            <Row ss:Height="20">
                <Cell ss:MergeAcross="2" ss:StyleID="subtitle">
                    <Data ss:Type="String"><?php echo $month_display; ?></Data>
                </Cell>
            </Row>
            
            <!-- Empty Row -->
            <Row ss:Height="10"/>
            
            <!-- Info Rows -->
            <Row>
                <Cell ss:StyleID="cellLeft">
                    <Data ss:Type="String">Report Generated:</Data>
                </Cell>
                <Cell ss:MergeAcross="1" ss:StyleID="cellLeft">
                    <Data ss:Type="String"><?php echo date('Y-m-d H:i:s'); ?></Data>
                </Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="cellLeft">
                    <Data ss:Type="String">Total Records:</Data>
                </Cell>
                <Cell ss:MergeAcross="1" ss:StyleID="cellLeft">
                    <Data ss:Type="String"><?php echo count($leads_data); ?></Data>
                </Cell>
            </Row>
            
            <!-- Empty Row -->
            <Row ss:Height="10"/>
            
            <!-- Header Row -->
            <Row ss:Height="30">
                <Cell ss:StyleID="header">
                    <Data ss:Type="String">#</Data>
                </Cell>
                <Cell ss:StyleID="header">
                    <Data ss:Type="String">Date</Data>
                </Cell>
                <Cell ss:StyleID="header">
                    <Data ss:Type="String">Leads Count</Data>
                </Cell>
            </Row>
            
            <!-- Data Rows -->
            <?php if (empty($leads_data)): ?>
            <Row>
                <Cell ss:MergeAcross="2" ss:StyleID="cell">
                    <Data ss:Type="String">No leads data found for this month</Data>
                </Cell>
            </Row>
            <?php else: ?>
                <?php foreach ($leads_data as $index => $row): ?>
            <Row>
                <Cell ss:StyleID="cell">
                    <Data ss:Type="Number"><?php echo $index + 1; ?></Data>
                </Cell>
                <Cell ss:StyleID="cell">
                    <Data ss:Type="String"><?php echo date('d-M-Y', strtotime($row['lead_date'])); ?></Data>
                </Cell>
                <Cell ss:StyleID="cell">
                    <Data ss:Type="Number"><?php echo $row['leads_count']; ?></Data>
                </Cell>
            </Row>
                <?php endforeach; ?>
            
            <!-- Total Row -->
            <Row ss:Height="30">
                <Cell ss:MergeAcross="1" ss:StyleID="total">
                    <Data ss:Type="String">TOTAL LEADS</Data>
                </Cell>
                <Cell ss:StyleID="total">
                    <Data ss:Type="Number"><?php echo $total_leads; ?></Data>
                </Cell>
            </Row>
            <?php endif; ?>
            
            <!-- Empty Row -->
            <Row ss:Height="20"/>
            
            <!-- Footer -->
            <Row>
                <Cell ss:MergeAcross="2" ss:StyleID="cell">
                    <Data ss:Type="String">© <?php echo date('Y'); ?> - Campaign Management System</Data>
                </Cell>
            </Row>
        </Table>
    </Worksheet>
</Workbook>

<?php
// Helper function to sanitize filename
function sanitizeFilename($filename) {
    // Remove special characters and replace spaces with underscores
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return trim($filename, '_');
}
?>
