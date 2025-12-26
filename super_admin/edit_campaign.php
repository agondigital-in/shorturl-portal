<?php
// super_admin/edit_campaign.php - Edit Campaign
$page_title = 'Edit Campaign';
require_once 'includes/header.php';
require_once '../db_connection.php';

function generatePublisherShortcode($length = 8) {
    return 'CAMP' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

$campaign_id = $_GET['id'] ?? '';
$error = '';
$success = '';

if (empty($campaign_id)) {
    header('Location: campaigns.php');
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT c.*, GROUP_CONCAT(DISTINCT ca.advertiser_id) as advertiser_ids, GROUP_CONCAT(DISTINCT cp.publisher_id) as publisher_ids
        FROM campaigns c
        LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id
        LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
        WHERE c.id = ? GROUP BY c.id");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        header('Location: campaigns.php');
        exit();
    }
    
    $stmt = $conn->prepare("SELECT id, name FROM advertisers ORDER BY name");
    $stmt->execute();
    $advertisers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT id, name FROM publishers ORDER BY name");
    $stmt->execute();
    $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading campaign: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_url = trim($_POST['target_url'] ?? '');
    $advertiser_payout = $_POST['advertiser_payout'] ?? '0';
    $publisher_payout = $_POST['publisher_payout'] ?? '0';
    $advertiser_ids = $_POST['advertiser_ids'] ?? [];
    $publisher_ids = $_POST['publisher_ids'] ?? [];
    
    if (empty($target_url)) {
        $error = 'Website URL is required.';
    } elseif (empty($advertiser_ids)) {
        $error = 'At least one advertiser must be selected.';
    } elseif (empty($publisher_ids)) {
        $error = 'At least one publisher must be selected.';
    } else {
        try {
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("UPDATE campaigns SET target_url = ?, advertiser_payout = ?, publisher_payout = ? WHERE id = ?");
            $stmt->execute([$target_url, $advertiser_payout, $publisher_payout, $campaign_id]);
            
            $stmt = $conn->prepare("DELETE FROM campaign_advertisers WHERE campaign_id = ?");
            $stmt->execute([$campaign_id]);
            
            $stmt = $conn->prepare("INSERT INTO campaign_advertisers (campaign_id, advertiser_id) VALUES (?, ?)");
            foreach ($advertiser_ids as $advertiser_id) $stmt->execute([$campaign_id, $advertiser_id]);
            
            $stmt = $conn->prepare("SELECT publisher_id FROM campaign_publishers WHERE campaign_id = ?");
            $stmt->execute([$campaign_id]);
            $current_publisher_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            $publishers_to_add = array_diff($publisher_ids, $current_publisher_ids);
            $publishers_to_remove = array_diff($current_publisher_ids, $publisher_ids);
            
            if (!empty($publishers_to_remove)) {
                $placeholders = str_repeat('?,', count($publishers_to_remove) - 1) . '?';
                $stmt = $conn->prepare("DELETE FROM campaign_publishers WHERE campaign_id = ? AND publisher_id IN ($placeholders)");
                $stmt->execute(array_merge([$campaign_id], $publishers_to_remove));
                $stmt = $conn->prepare("DELETE FROM publisher_short_codes WHERE campaign_id = ? AND publisher_id IN ($placeholders)");
                $stmt->execute(array_merge([$campaign_id], $publishers_to_remove));
            }
            
            if (!empty($publishers_to_add)) {
                $stmt = $conn->prepare("INSERT INTO campaign_publishers (campaign_id, publisher_id) VALUES (?, ?)");
                $shortcode_stmt = $conn->prepare("INSERT INTO publisher_short_codes (campaign_id, publisher_id, short_code) VALUES (?, ?, ?)");
                foreach ($publishers_to_add as $publisher_id) {
                    $stmt->execute([$campaign_id, $publisher_id]);
                    $publisher_shortcode = generatePublisherShortcode();
                    $shortcode_stmt->execute([$campaign_id, $publisher_id, $publisher_shortcode]);
                }
            }
            
            $conn->commit();
            $success = "Campaign updated successfully.";
            
            $stmt = $conn->prepare("SELECT c.*, GROUP_CONCAT(DISTINCT ca.advertiser_id) as advertiser_ids, GROUP_CONCAT(DISTINCT cp.publisher_id) as publisher_ids
                FROM campaigns c LEFT JOIN campaign_advertisers ca ON c.id = ca.campaign_id LEFT JOIN campaign_publishers cp ON c.id = cp.campaign_id
                WHERE c.id = ? GROUP BY c.id");
            $stmt->execute([$campaign_id]);
            $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error updating campaign: " . $e->getMessage();
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Campaign</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="campaigns.php">Campaigns</a></li>
                <li class="breadcrumb-item active">Edit</li>
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
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($campaign['name']); ?></h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Campaign Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($campaign['name']); ?>" disabled>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Campaign Type</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($campaign['campaign_type']); ?>" disabled>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Website URL <span class="text-danger">*</span></label>
                <input type="url" class="form-control" name="target_url" value="<?php echo htmlspecialchars($campaign['target_url']); ?>" required>
                <small class="text-muted">All tracking links will redirect to this URL. Click counts are preserved.</small>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" value="<?php echo htmlspecialchars($campaign['start_date']); ?>" disabled>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" value="<?php echo htmlspecialchars($campaign['end_date']); ?>" disabled>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Advertiser Payout (₹)</label>
                    <input type="number" class="form-control" name="advertiser_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($campaign['advertiser_payout']); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Publisher Payout (₹)</label>
                    <input type="number" class="form-control" name="publisher_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($campaign['publisher_payout']); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Advertisers <span class="text-danger">*</span></label>
                    <div class="border rounded p-3" style="max-height:200px;overflow-y:auto;">
                        <?php 
                        $current_advertiser_ids = !empty($campaign['advertiser_ids']) ? explode(',', $campaign['advertiser_ids']) : [];
                        foreach ($advertisers as $advertiser): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="advertiser_ids[]" value="<?php echo $advertiser['id']; ?>" id="adv_<?php echo $advertiser['id']; ?>" <?php echo in_array($advertiser['id'], $current_advertiser_ids) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="adv_<?php echo $advertiser['id']; ?>"><?php echo htmlspecialchars($advertiser['name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Publishers <span class="text-danger">*</span></label>
                    <div class="border rounded p-3" style="max-height:200px;overflow-y:auto;">
                        <?php 
                        $current_publisher_ids = !empty($campaign['publisher_ids']) ? explode(',', $campaign['publisher_ids']) : [];
                        foreach ($publishers as $publisher): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="publisher_ids[]" value="<?php echo $publisher['id']; ?>" id="pub_<?php echo $publisher['id']; ?>" <?php echo in_array($publisher['id'], $current_publisher_ids) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pub_<?php echo $publisher['id']; ?>"><?php echo htmlspecialchars($publisher['name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Campaign</button>
                <a href="campaigns.php" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
