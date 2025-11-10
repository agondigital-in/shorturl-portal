<?php
require_once 'config.php';

// Function to generate a unique short code
function generateShortCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $shortcode = '';
    for ($i = 0; $i < $length; $i++) {
        $shortcode .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $shortcode;
}

// Check if form is submitted
if ($_POST['url']) {
    $original_url = $_POST['url'];
    
    // Validate URL
    if (!filter_var($original_url, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid URL";
    } else {
        // Generate unique short code
        $short_code = '';
        $is_unique = false;
        
        while (!$is_unique) {
            $short_code = generateShortCode();
            
            // Check if short code already exists
            $stmt = $pdo->prepare("SELECT id FROM urls WHERE short_code = ?");
            $stmt->execute([$short_code]);
            
            if ($stmt->rowCount() == 0) {
                $is_unique = true;
            }
        }
        
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO urls (original_url, short_code) VALUES (?, ?)");
        $stmt->execute([$original_url, $short_code]);
        
        $shortened_url = "http://" . $_SERVER['HTTP_HOST'] . "/" . $short_code;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>URL Shortener</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        input[type="url"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9f7ef;
            border-radius: 5px;
            text-align: center;
        }
        .error {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8d7da;
            border-radius: 5px;
            color: #721c24;
            text-align: center;
        }
        .admin-link {
            text-align: center;
            margin-top: 20px;
        }
        .admin-link a {
            color: #1a0dab;
            text-decoration: none;
        }
        .admin-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>URL Shortener</h1>
        
        <form method="POST">
            <input type="url" name="url" placeholder="Enter URL to shorten (e.g. https://example.com)" required>
            <button type="submit">Shorten URL</button>
        </form>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($shortened_url)): ?>
            <div class="result">
                <p>Your shortened URL:</p>
                <p><strong><a href="<?php echo $shortened_url; ?>" target="_blank"><?php echo $shortened_url; ?></a></strong></p>
            </div>
        <?php endif; ?>
        
        <div class="admin-link">
            <a href="admin.php">View All Shortened URLs</a>
        </div>
    </div>
</body>
</html>