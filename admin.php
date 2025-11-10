<?php
require_once 'config.php';

// Get all URLs with their stats
$stmt = $pdo->prepare("SELECT * FROM urls ORDER BY created_at DESC");
$stmt->execute();
$urls = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>URL Shortener Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .short-url {
            color: #1a0dab;
            text-decoration: none;
        }
        .short-url:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>URL Shortener Statistics</h1>
        
        <table>
            <tr>
                <th>Short URL</th>
                <th>Original URL</th>
                <th>Clicks</th>
                <th>Created</th>
            </tr>
            <?php foreach ($urls as $url): ?>
            <tr>
                <td>
                    <a class="short-url" href="<?php echo "http://" . $_SERVER['HTTP_HOST'] . "/" . $url['short_code']; ?>" target="_blank">
                        <?php echo $_SERVER['HTTP_HOST'] . "/" . $url['short_code']; ?>
                    </a>
                </td>
                <td><?php echo substr($url['original_url'], 0, 50) . (strlen($url['original_url']) > 50 ? '...' : ''); ?></td>
                <td><?php echo $url['click_count']; ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($url['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>