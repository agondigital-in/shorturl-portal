<?php
require_once 'config.php';

// Get the short code from the URL
$short_code = $_GET['code'] ?? '';

if ($short_code) {
    // Look up the original URL in the database
    $stmt = $pdo->prepare("SELECT id, original_url FROM urls WHERE short_code = ?");
    $stmt->execute([$short_code]);
    
    if ($row = $stmt->fetch()) {
        $id = $row['id'];
        $original_url = $row['original_url'];
        
        // Increment click count
        $update_stmt = $pdo->prepare("UPDATE urls SET click_count = click_count + 1 WHERE id = ?");
        $update_stmt->execute([$id]);
        
        // Redirect to the original URL
        header("Location: " . $original_url);
        exit();
    } else {
        // Short code not found
        http_response_code(404);
        echo "<h1>404 - URL Not Found</h1>";
        echo "<p>The requested URL was not found on this server.</p>";
        echo "<a href='/'>Create your own short URL</a>";
        exit();
    }
} else {
    // Redirect to main page if no code provided
    header("Location: /");
    exit();
}
?>