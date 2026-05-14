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

function generatePixelCode($length = 12) {
    return 'PX' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz'), 0, $length);
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
    $enable_image_pixel = isset($_POST['enable_image_pixel']) ? 1 : 0;
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
            
            $stmt = $conn->prepare("INSERT INTO campaigns (name, shortcode, target_url, start_date, end_date, advertiser_payout, publisher_payout, campaign_type, enable_image_pixel) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$campaign_name, $base_shortcode, $target_url, $start_date, $end_date, $advertiser_payout, $publisher_payout, $campaign_type, $enable_image_pixel]);
            
            $campaign_id = $conn->lastInsertId();
            
            if (!empty($advertiser_ids)) {
                $stmt = $conn->prepare("INSERT INTO campaign_advertisers (campaign_id, advertiser_id) VALUES (?, ?)");
                foreach ($advertiser_ids as $advertiser_id) $stmt->execute([$campaign_id, $advertiser_id]);
            }
            
            if (!empty($publisher_ids)) {
                $publisher_stmt = $conn->prepare("INSERT INTO campaign_publishers (campaign_id, publisher_id) VALUES (?, ?)");
                $shortcode_stmt = $conn->prepare("INSERT INTO publisher_short_codes (campaign_id, publisher_id, short_code) VALUES (?, ?, ?)");
                $pixel_stmt = $conn->prepare("INSERT INTO image_pixel_links (campaign_id, publisher_id, pixel_code, pixel_url) VALUES (?, ?, ?, ?)");
                
                $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                $base_url = rtrim($base_url, '/');
                
                foreach ($publisher_ids as $publisher_id) {
                    $publisher_stmt->execute([$campaign_id, $publisher_id]);
                    $publisher_shortcode = generatePublisherShortcode($base_shortcode, $publisher_id);
                    $shortcode_stmt->execute([$campaign_id, $publisher_id, $publisher_shortcode]);
                    
                    // Generate image pixel link if enabled
                    if ($enable_image_pixel) {
                        $pixel_code = generatePixelCode();
                        $pixel_url = $base_url . "/pixel.php?p=" . $pixel_code;
                        $pixel_stmt->execute([$campaign_id, $publisher_id, $pixel_code, $pixel_url]);
                    }
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

<style>
/* Page Header */
.add-camp-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 32px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f5f9;
}
.add-camp-header-left h1 {
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}
.add-camp-header-left h1 i {
    color: #6366f1;
    font-size: 1.8rem;
}
.breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
    font-size: 0.9rem;
}
.breadcrumb-item a {
    color: #64748b;
    text-decoration: none;
    transition: color 0.2s;
}
.breadcrumb-item a:hover {
    color: #6366f1;
}
.breadcrumb-item.active {
    color: #0f172a;
    font-weight: 600;
}
.btn-back {
    background: #f8fafc;
    color: #475569;
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-back:hover {
    background: #e2e8f0;
    color: #0f172a;
    border-color: #cbd5e1;
}

/* Form Card */
.form-card {
    background: #fff;
    border-radius: 16px;
    border: 2px solid #f1f5f9;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.form-card-header {
    padding: 24px 28px;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 2px solid #f1f5f9;
}
.form-card-header h5 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}
.form-card-header h5 i {
    color: #6366f1;
    font-size: 1.3rem;
}
.form-card-body {
    padding: 32px 28px;
}

/* Form Sections */
.form-section {
    margin-bottom: 32px;
    padding-bottom: 32px;
    border-bottom: 1px solid #f1f5f9;
}
.form-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.form-section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.form-section-title i {
    color: #6366f1;
    font-size: 1.2rem;
}

/* Form Labels */
.form-label {
    font-weight: 600;
    color: #475569;
    margin-bottom: 10px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
}
.form-label i {
    color: #6366f1;
    font-size: 0.9rem;
}
.text-danger {
    color: #dc2626;
}

/* Form Controls */
.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    padding: 12px 16px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}
.form-control:focus, .form-select:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}
.form-control::placeholder {
    color: #94a3b8;
}

/* Checkbox Lists */
.checkbox-list {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    max-height: 250px;
    overflow-y: auto;
    background: #f8fafc;
}
.checkbox-list::-webkit-scrollbar {
    width: 8px;
}
.checkbox-list::-webkit-scrollbar-track {
    background: #e2e8f0;
    border-radius: 10px;
}
.checkbox-list::-webkit-scrollbar-thumb {
    background: #6366f1;
    border-radius: 10px;
}
.form-check {
    padding: 10px 12px;
    margin-bottom: 8px;
    border-radius: 8px;
    transition: all 0.2s ease;
}
.form-check:hover {
    background: #ffffff;
}
.form-check-input {
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e1;
    border-radius: 6px;
    cursor: pointer;
}
.form-check-input:checked {
    background-color: #6366f1;
    border-color: #6366f1;
}
.form-check-label {
    font-size: 0.95rem;
    color: #475569;
    font-weight: 500;
    cursor: pointer;
    margin-left: 8px;
}

/* Switch */
.form-switch .form-check-input {
    width: 48px;
    height: 24px;
    border-radius: 12px;
}
.form-switch .form-check-label {
    font-size: 0.95rem;
    color: #475569;
}
.form-switch .form-check-label strong {
    color: #0f172a;
}

/* Buttons */
.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 14px 32px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    font-size: 1rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}
.btn-submit:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}
.btn-cancel {
    background: #f8fafc;
    color: #475569;
    padding: 14px 32px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    font-weight: 600;
    font-size: 1rem;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}
.btn-cancel:hover {
    background: #e2e8f0;
    color: #0f172a;
    border-color: #cbd5e1;
}

/* Alerts */
.alert {
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 24px;
    border: none;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-success {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #166534;
}
.alert-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}
.alert i {
    font-size: 1.2rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 20px;
    color: #94a3b8;
}
.empty-state a {
    color: #6366f1;
    font-weight: 600;
    text-decoration: none;
}
.empty-state a:hover {
    text-decoration: underline;
}

/* Responsive */
@media(max-width:768px) {
    .add-camp-header {
        flex-direction: column;
        gap: 16px;
    }
    .add-camp-header-left h1 {
        font-size: 1.5rem;
    }
    .form-card-body {
        padding: 24px 20px;
    }
    .btn-submit, .btn-cancel {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- Page Header -->
<div class="add-camp-header">
    <div class="add-camp-header-left">
        <h1><i class="fas fa-plus-circle"></i>Add New Campaign</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="campaigns.php"><i class="fas fa-rocket"></i> Campaigns</a></li>
                <li class="breadcrumb-item active">Add Campaign</li>
            </ol>
        </nav>
    </div>
    <a href="campaigns.php" class="btn-back">
        <i class="fas fa-arrow-left"></i>
        <span>Back to List</span>
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
<?php endif; ?>

<div class="form-card">
    <div class="form-card-header">
        <h5><i class="fas fa-edit"></i>Campaign Information</h5>
    </div>
    <div class="form-card-body">
        <form method="POST">
            <!-- Basic Information Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-info-circle"></i>
                    <span>Basic Information</span>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-tag"></i>
                            Campaign Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="campaign_name" value="<?php echo htmlspecialchars($campaign_name); ?>" required placeholder="Enter campaign name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-layer-group"></i>
                            Campaign Type
                        </label>
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
                    <label class="form-label">
                        <i class="fas fa-link"></i>
                        Website URL <span class="text-danger">*</span>
                    </label>
                    <input type="url" class="form-control" name="target_url" value="<?php echo htmlspecialchars($target_url); ?>" placeholder="https://example.com" required>
                </div>
            </div>
            
            <!-- Schedule & Budget Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Schedule & Budget</span>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-check"></i>
                            Start Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-times"></i>
                            End Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            <i class="fas fa-arrow-up"></i>
                            Advertiser Payout (₹)
                        </label>
                        <input type="number" class="form-control" name="advertiser_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($advertiser_payout); ?>" placeholder="0.00">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            <i class="fas fa-arrow-down"></i>
                            Publisher Payout (₹)
                        </label>
                        <input type="number" class="form-control" name="publisher_payout" step="0.01" min="0" value="<?php echo htmlspecialchars($publisher_payout); ?>" placeholder="0.00">
                    </div>
                </div>
            </div>
            
            <!-- Tracking Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-chart-line"></i>
                    <span>Tracking Options</span>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="enable_image_pixel" id="enable_image_pixel" value="1">
                    <label class="form-check-label" for="enable_image_pixel">
                        <strong>Enable Image Pixel Tracking</strong> - Generate unique pixel link for each publisher
                    </label>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle"></i> When enabled, each publisher will receive a unique image pixel URL for impression tracking
                </small>
            </div>
            
            <!-- Participants Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-users"></i>
                    <span>Campaign Participants</span>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-briefcase"></i>
                            Select Advertisers <span class="text-danger">*</span>
                        </label>
                        <div class="checkbox-list">
                            <?php if (empty($advertisers)): ?>
                                <div class="empty-state">
                                    <p class="mb-0">No advertisers available. <a href="advertisers.php">Add advertisers first</a>.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($advertisers as $advertiser): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="advertiser_ids[]" value="<?php echo $advertiser['id']; ?>" id="adv_<?php echo $advertiser['id']; ?>" <?php echo in_array($advertiser['id'], $advertiser_ids) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="adv_<?php echo $advertiser['id']; ?>">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($advertiser['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-users"></i>
                            Select Publishers <span class="text-danger">*</span>
                        </label>
                        <div class="checkbox-list">
                            <?php if (empty($publishers)): ?>
                                <div class="empty-state">
                                    <p class="mb-0">No publishers available. <a href="publishers.php">Add publishers first</a>.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($publishers as $publisher): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="publisher_ids[]" value="<?php echo $publisher['id']; ?>" id="pub_<?php echo $publisher['id']; ?>" <?php echo in_array($publisher['id'], $publisher_ids) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pub_<?php echo $publisher['id']; ?>">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($publisher['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="d-flex gap-3">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-rocket"></i>
                    <span>Create Campaign</span>
                </button>
                <a href="campaigns.php" class="btn-cancel">
                    <i class="fas fa-times"></i>
                    <span>Cancel</span>
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
