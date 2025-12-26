<?php
// super_admin/add_campaign.php - Add New Campaign
$page_title = 'Add Campaign';
require_once 'includes/header.php';
require_once '../db_connection.php';

function generateShortcode($length = 8) {
    return 'CAMP' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

function generatePublisherShortcode($baseCode, $publisherId, $length = 4) {
    return $baseCode . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

$campaign_name = '';
$target_url = '';
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+30 days'));
$advertiser_payout = '';
$publisher_payout = '';
$campaign_type = 'None';
$advertiser_ids = [];
$publisher_ids = [];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_name = trim($_POST['campaign_name'] ?? '');
    $target_url = trim($_POST['target_url'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $advertiser_payout = $_POST['advertiser_payout'] ?? '0';
    $publisher_payout = $_POST['publisher_payout'] ?? '0';
    $campaign_type = $_POST['campaign_type'] ?? 'None';
    $advertiser_ids = $_POST['advertiser_ids'] ?? [];
    $publisher_ids = $_POST['publisher_ids'] ?? [];
    
    if (empty($campaign_name)) {
        $error = 'Campaign name is required.';
    } elseif (empty($target_url)) {
        $error = 'Website URL is required.';
    } elseif (empty($advertiser_ids)) {
        $error = 'At least one advertiser must be selected.';
    } elseif (empty($publisher_ids)) {
        $error = 'At least one publisher must be selected.';
    } elseif (strtotime($end_date) <= strtotime($start_date)) {
        $error = 'End date must be after start date.';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $base_shortcode = '';
            $is_unique = false;
            $attempts = 0;
            
            while (!$is_unique && $attempts < 10) {
                $base_shortcode = generateShortcode();
                $stmt = $conn->prepare("SELECT COUNT(*) FROM campaigns WHERE shortcode = ?");
                $stmt->execute([$base_shortcode]);
                if ($stmt->fetchColumn() == 0) $is_unique = true;
                $attempts++;
            }
            
            if (!$is_unique) throw new Exception('Unable to generate unique shortcode.');
            
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("INSERT INTO campaigns (name, shortcode, target_url, start_date, end_date, advertiser_payout, publisher_payout, campaign_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$campaign_name, $base_shortcode, $target_url, $start_date, $end_date, $advertiser_payout, $publisher_payout, $campaign_type]);
            
            $campaign_id = $conn->lastInsertId();
            
            if (!empty($advertiser_ids)) {
                $stmt = $conn->prepare("INSERT INTO campaign_advertisers (campaign_id, advertiser_id) VALUES (?, ?)");
                foreach ($advertiser_ids as $advertiser_id) $stmt->execute([$campaign_id, $advertiser_id]);
            }
            
            if (!empty($publisher_ids)) {
                $publisher_stmt = $conn->prepare("INSERT INTO campaign_publishers (campaign_id, publisher_id) VALUES (?, ?)");
                $shortcode_stmt = $conn->prepare("INSERT INTO publisher_short_codes (campaign_id, publisher_id, short_code) VALUES (?, ?, ?)");
                
                foreach ($publisher_ids as $publisher_id) {
                    $publisher_stmt->execute([$campaign_id, $publisher_id]);
                    $publisher_shortcode = generatePublisherShortcode($base_shortcode, $publisher_id);
                    $shortcode_stmt->execute([$campaign_id, $publisher_id, $publisher_shortcode]);
                }
            }
            
            $conn->commit();
            $success = "Campaign created successfully with shortcode: $base_shortcode";
            
            $campaign_name = $target_url = $advertiser_payout = $publisher_payout = '';
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+30 days'));
            $campaign_type = 'None';
            $advertiser_ids = $publisher_ids = [];
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error creating campaign: " . $e->getMessage();
        }
    }
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, name FROM advertisers ORDER BY name");
    $stmt->execute();
    $advertisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT id, name FROM publishers ORDER BY name");
    $stmt->execute();
    $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Add New Campaign</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="campaigns.php">Campaigns</a></li>
                <li class="breadcrumb-item active">Add Campaign</li>
            </ol>
        </nav>
    </div>
    <a href="campaigns.php" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Campaign Details</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Campaign Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="campaign_name" value="<?php echo htmlspecialchars($campaign_name); ?>" required placeholder="Enter campaign name">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Campaign Type</label>
                    <select class="form-select" name="campaign_type">
                        <option value="None" <?php echo $campaign_type === 'None' ? 'selected' : ''; ?>>None</option>
                        <option value="CPR" <?php echo $campaign_type === 'CPR' ? 'selected' : ''; ?>>CPR (Cost Per Registration)</option>
                        <option value="CPL" <?php echo $campaign_type === 'CPL' ? 'selected' : ''; ?>>CPL (Cost Per Lead)</option>
                        <option value="CPC" <?php echo $campaign_type === 'CPC' ? 'selected' : ''; ?>>CPC (Cost Per Click)</option>
                        <option value="CPM" <?php echo $campaign_type === 'CPM' ? 'selected' : ''; ?>>CPM (Cost Per Thousand)</option>
                        <option value="CPS" <?php echo $campaign_type === 'CPS' ? 'selected' : ''; ?>>CPS (Cost Per Sale)</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Website URL <span class="text-danger">*</span></label>
                <input type="url" class="form-control" name="target_url" value="<?php echo htmlspecialchars($target_url); ?>" placeholder="https://example.com" required>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">End Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Advertiser Payout (₹)</label>
                    <input type="number" class="form-control" name="advertiser_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($advertiser_payout); ?>" placeholder="0.00">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Publisher Payout (₹)</label>
                    <input type="number" class="form-control" name="publisher_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($publisher_payout); ?>" placeholder="0.00">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Advertisers <span class="text-danger">*</span></label>
                    <div class="border rounded p-3" style="max-height:200px;overflow-y:auto;">
                        <?php if (empty($advertisers)): ?>
                            <p class="text-muted mb-0">No advertisers available. <a href="advertisers.php">Add advertisers first</a>.</p>
                        <?php else: ?>
                            <?php foreach ($advertisers as $advertiser): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="advertiser_ids[]" value="<?php echo $advertiser['id']; ?>" id="adv_<?php echo $advertiser['id']; ?>" <?php echo in_array($advertiser['id'], $advertiser_ids) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="adv_<?php echo $advertiser['id']; ?>"><?php echo htmlspecialchars($advertiser['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Publishers <span class="text-danger">*</span></label>
                    <div class="border rounded p-3" style="max-height:200px;overflow-y:auto;">
                        <?php if (empty($publishers)): ?>
                            <p class="text-muted mb-0">No publishers available. <a href="publishers.php">Add publishers first</a>.</p>
                        <?php else: ?>
                            <?php foreach ($publishers as $publisher): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="publisher_ids[]" value="<?php echo $publisher['id']; ?>" id="pub_<?php echo $publisher['id']; ?>" <?php echo in_array($publisher['id'], $publisher_ids) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="pub_<?php echo $publisher['id']; ?>"><?php echo htmlspecialchars($publisher['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Create Campaign</button>
                <a href="campaigns.php" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
