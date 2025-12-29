<?php
session_start();
require_once 'db_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill all required fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters";
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM publishers WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email already registered";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO publishers (name, email, password, phone, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
                $stmt->execute([$name, $email, $hashed_password, $phone]);
                $success = "Registration successful! Please wait for admin approval.";
            }
        } catch (PDOException $e) {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Registration - Agon Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #ec4899;
            --dark: #0f172a;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            color: #fff;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--secondary), #f472b6);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 20px;
        }
        .register-header h2 {
            font-weight: 700;
            margin-bottom: 8px;
        }
        .register-header p {
            color: rgba(255,255,255,0.6);
        }
        .form-label {
            color: rgba(255,255,255,0.8);
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-control {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 12px 16px;
            color: #fff;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.15);
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.2);
            color: #fff;
        }
        .form-control::placeholder {
            color: rgba(255,255,255,0.4);
        }
        .btn-register {
            background: linear-gradient(135deg, var(--secondary), #f472b6);
            border: none;
            color: #fff;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(236, 72, 153, 0.4);
            color: #fff;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
        }
        .back-link a:hover {
            color: #fff;
        }
        .alert {
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <div class="register-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Publisher Registration</h2>
            <p>Create your publisher account</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" placeholder="Enter your name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" placeholder="Enter phone number">
            </div>
            <div class="mb-3">
                <label class="form-label">Password *</label>
                <input type="password" name="password" class="form-control" placeholder="Create password" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password *</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm password" required>
            </div>
            <button type="submit" class="btn btn-register">
                <i class="fas fa-user-plus me-2"></i>Register
            </button>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="publisher_login.php"><i class="fas fa-sign-in-alt me-1"></i>Already have account? Login</a>
            <br><br>
            <a href="index.php"><i class="fas fa-arrow-left me-1"></i>Back to Home</a>
        </div>
    </div>
</body>
</html>
