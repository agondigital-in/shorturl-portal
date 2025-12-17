<?php
// update_sidebars.php - Instructions for updating sidebars
// Run this file to see instructions

echo "<h1>Sidebar Update Instructions</h1>";
echo "<p>The common sidebar file has been created at: <code>super_admin/includes/sidebar.php</code></p>";

echo "<h2>To use the common sidebar in any page:</h2>";
echo "<ol>";
echo "<li>Find the sidebar section in your PHP file (usually starts with <code>&lt;!-- Sidebar --&gt;</code>)</li>";
echo "<li>Delete everything from <code>&lt;!-- Sidebar --&gt;</code> to just before <code>&lt;main class=</code></li>";
echo "<li>Replace with: <code>&lt;?php include 'includes/sidebar.php'; ?&gt;</code></li>";
echo "</ol>";

echo "<h2>Example:</h2>";
echo "<pre>";
echo htmlspecialchars('
<div class="container-fluid">
    <div class="row">
        <?php include \'includes/sidebar.php\'; ?>
        
        <main class="col-lg-10 ms-sm-auto px-md-4 py-3">
            <!-- Your page content here -->
        </main>
    </div>
</div>
');
echo "</pre>";

echo "<h2>Sidebar Menu Items:</h2>";
echo "<ul>";
echo "<li>Dashboard</li>";
echo "<li>Campaigns</li>";
echo "<li>Advertisers</li>";
echo "<li>Publishers</li>";
echo "<li>Admins</li>";
echo "<li>Advertiser Campaigns</li>";
echo "<li>Publisher Campaigns</li>";
echo "<li>All Publishers Stats</li>";
echo "<li>CPV Campaigns</li>";
echo "<li>CPV Report</li>";
echo "<li>Payment Reports</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> The sidebar automatically highlights the current page.</p>";
?>
