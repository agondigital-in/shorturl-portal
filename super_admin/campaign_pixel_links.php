<?php
// super_admin/campaign_pixel_links.php - View Image Pixel Links for a Campaign
$page_title = 'Campaign Pixel Links';
require_once 'includes/header.php';
require_once '../db_connection.php';

$campaign_id = $_GET['id'] ?? 0;

if (!$campaign_id) {
    header('Location: campaigns.php');
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get campaign details
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        header('Location: campaigns.php');
        exit;
    }
    
    // Get pixel links with publisher info
    $stmt = $conn->prepare("
        SELECT ipl.*, p.name as publisher_name, p.email as publisher_email
        FROM image_pixel_links ipl
        JOIN publishers p ON ipl.publisher_id = p.id
        WHERE ipl.campaign_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$campaign_id]);
    $pixel_links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Image Pixel Links</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="campaigns.php">Campaigns</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($campaign['name']); ?> - Pixel Links</li>
            </ol>
        </nav>
    </div>
    <a href="campaigns.php" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back to Campaigns</a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Campaign: <?php echo htmlspecialchars($campaign['name']); ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <strong>Shortcode:</strong> <?php echo htmlspecialchars($campaign['shortcode']); ?>
            </div>
            <div class="col-md-4">
                <strong>Type:</strong> <?php echo htmlspecialchars($campaign['campaign_type']); ?>
            </div>
            <div class="col-md-4">
                <strong>Image Pixel:</strong> 
                <?php if ($campaign['enable_image_pixel']): ?>
                    <span class="badge bg-success">Enabled</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Disabled</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!$campaign['enable_image_pixel']): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Image Pixel tracking is not enabled for this campaign. 
        <a href="edit_campaign.php?id=<?php echo $campaign_id; ?>">Edit campaign</a> to enable it.
    </div>
<?php elseif (empty($pixel_links)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        No pixel links found. Pixel links are generated when publishers are assigned to the campaign.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-image me-2"></i>Publisher Pixel Links (<?php echo count($pixel_links); ?>)</h5>
            <button class="btn btn-sm btn-outline-primary" onclick="copyAllPixels()">
                <i class="fas fa-copy me-1"></i>Copy All
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="pixelTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Publisher</th>
                            <th>Pixel Code</th>
                            <th>Pixel URL</th>
                            <th>HTML Code</th>
                            <th>Impressions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pixel_links as $index => $link): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($link['publisher_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($link['publisher_email']); ?></small>
                                </td>
                                <td><code><?php echo htmlspecialchars($link['pixel_code']); ?></code></td>
                                <td>
                                    <input type="text" class="form-control form-control-sm pixel-url" 
                                           value="<?php echo htmlspecialchars($link['pixel_url']); ?>" 
                                           readonly style="min-width: 250px;">
                                </td>
                                <td>
                                    <code class="pixel-html" style="font-size: 11px;">&lt;img src="<?php echo htmlspecialchars($link['pixel_url']); ?>" width="1" height="1" style="display:none;" /&gt;</code>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo number_format($link['impressions']); ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="copyPixelUrl('<?php echo htmlspecialchars($link['pixel_url']); ?>')" title="Copy URL">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyPixelHtml('<?php echo htmlspecialchars($link['pixel_url']); ?>')" title="Copy HTML">
                                        <i class="fas fa-code"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function copyPixelUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        showToast('Pixel URL copied!');
    });
}

function copyPixelHtml(url) {
    const html = `<img src="${url}" width="1" height="1" style="display:none;" />`;
    navigator.clipboard.writeText(html).then(() => {
        showToast('HTML code copied!');
    });
}

function copyAllPixels() {
    let allData = "Publisher Pixel Links for <?php echo addslashes($campaign['name']); ?>\n\n";
    <?php foreach ($pixel_links as $link): ?>
    allData += "<?php echo addslashes($link['publisher_name']); ?>:\n";
    allData += "URL: <?php echo $link['pixel_url']; ?>\n";
    allData += "HTML: <img src=\"<?php echo $link['pixel_url']; ?>\" width=\"1\" height=\"1\" style=\"display:none;\" />\n\n";
    <?php endforeach; ?>
    
    navigator.clipboard.writeText(allData).then(() => {
        showToast('All pixel links copied!');
    });
}

function showToast(message) {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="toast show" role="alert">
            <div class="toast-body bg-success text-white rounded">
                <i class="fas fa-check me-2"></i>${message}
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
}
</script>

<?php require_once 'includes/footer.php'; ?>
