<?php
// super_admin/change_password.php - Change Password
$page_title = 'Change Password';
require_once 'includes/header.php';
require_once '../db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$success = '';
$error = '';

// Get current user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required!";
    } elseif (strlen($new_password) < 4) {
        $error = "New password must be at least 4 characters!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match!";
    } else {
        // Check current password - try both plain text and hashed
        $password_valid = false;
        
        // Check if plain text match
        if ($current_password === $user['password']) {
            $password_valid = true;
        }
        // Check if hashed match
        elseif (password_verify($current_password, $user['password'])) {
            $password_valid = true;
        }
        // Check MD5 match
        elseif (md5($current_password) === $user['password']) {
            $password_valid = true;
        }
        
        if (!$password_valid) {
            $error = "Current password is incorrect!";
        } else {
            try {
                // Update password with hash
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $success = "Password changed successfully!";
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT username, password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<style>
.password-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 30px;
    color: white;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 25px;
}
.password-header::before {
    content: '';
    position: absolute;
    top: -100px;
    right: -100px;
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    border-radius: 50%;
    opacity: 0.3;
}
.password-header::after {
    content: '';
    position: absolute;
    bottom: -50px;
    left: 50%;
    width: 200px;
    height: 200px;
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    border-radius: 50%;
    opacity: 0.2;
}
.header-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    position: relative;
    z-index: 1;
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
}
.header-content {
    position: relative;
    z-index: 1;
}
.header-content h1 { 
    font-size: 2rem; 
    font-weight: 700; 
    margin-bottom: 8px; 
}
.header-content p { 
    opacity: 0.8; 
    margin: 0;
    font-size: 1rem;
}
.header-badge {
    background: rgba(255,255,255,0.15);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    margin-top: 10px;
    display: inline-block;
}

.password-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    max-width: 500px;
    margin: 0 auto;
}

.password-card h5 {
    color: #1e293b;
    font-weight: 700;
    margin-bottom: 30px;
    text-align: center;
}

.user-info {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: center;
}

.user-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 2rem;
    color: white;
    font-weight: 700;
}

.form-label {
    font-weight: 600;
    color: #475569;
    margin-bottom: 8px;
}

.form-control {
    border-radius: 12px;
    padding: 14px 18px;
    border: 2px solid #e2e8f0;
    font-size: 1rem;
}

.form-control:focus {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}

.input-group-text {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-right: none;
    border-radius: 12px 0 0 12px;
    cursor: pointer;
}

.input-group .form-control {
    border-left: none;
    border-radius: 0 12px 12px 0;
}

.btn-save {
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    border: none;
    color: white;
    padding: 14px 30px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    width: 100%;
    transition: all 0.3s;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
    color: white;
}

.password-tips {
    background: #fffbeb;
    border-radius: 12px;
    padding: 15px 20px;
    margin-top: 25px;
}

.password-tips h6 {
    color: #92400e;
    font-weight: 600;
    margin-bottom: 10px;
}

.password-tips ul {
    margin: 0;
    padding-left: 20px;
    color: #78716c;
}

.password-tips li {
    margin-bottom: 5px;
    font-size: 0.9rem;
}
</style>

<!-- Page Header -->
<div class="password-header">
    <div class="header-icon">
        <i class="fas fa-shield-alt"></i>
    </div>
    <div class="header-content">
        <h1>Change Password</h1>
        <p>Keep your account secure by updating your password regularly</p>
        <span class="header-badge"><i class="fas fa-lock me-1"></i>Security Settings</span>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px; max-width: 500px; margin: 0 auto 20px;">
    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px; max-width: 500px; margin: 0 auto 20px;">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="password-card">
    <!-- User Info -->
    <div class="user-info">
        <div class="user-avatar">
            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
        </div>
        <h5 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
        <span class="badge bg-purple" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">Super Admin</span>
    </div>
    
    <h5><i class="fas fa-lock me-2 text-purple"></i>Update Password</h5>
    
    <form method="POST">
        <div class="mb-4">
            <label class="form-label">Current Password</label>
            <div class="input-group">
                <span class="input-group-text" onclick="togglePassword('current_password', this)">
                    <i class="fas fa-eye"></i>
                </span>
                <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter current password" required>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label">New Password</label>
            <div class="input-group">
                <span class="input-group-text" onclick="togglePassword('new_password', this)">
                    <i class="fas fa-eye"></i>
                </span>
                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password" required>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label">Confirm New Password</label>
            <div class="input-group">
                <span class="input-group-text" onclick="togglePassword('confirm_password', this)">
                    <i class="fas fa-eye"></i>
                </span>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required>
            </div>
        </div>
        
        <button type="submit" class="btn btn-save">
            <i class="fas fa-save me-2"></i>Change Password
        </button>
    </form>
    
    <div class="password-tips">
        <h6><i class="fas fa-lightbulb me-1"></i>Password Tips</h6>
        <ul>
            <li>Use at least 4 characters</li>
            <li>Mix letters and numbers for better security</li>
            <li>Don't share your password with anyone</li>
        </ul>
    </div>
</div>

<script>
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    const iconElement = icon.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
