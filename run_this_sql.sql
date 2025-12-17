-- Copy paste this SQL in phpMyAdmin or MySQL command line

-- Step 1: Drop old tables
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS cpv_clicks;
DROP TABLE IF EXISTS cpv_campaigns;
DROP TABLE IF EXISTS cpv_links;
SET FOREIGN_KEY_CHECKS = 1;

-- Step 2: Create cpv_campaigns table
CREATE TABLE cpv_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_name VARCHAR(255) NOT NULL,
    original_url TEXT NOT NULL,
    short_code VARCHAR(20) UNIQUE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    total_clicks INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_short_code (short_code),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 3: Create cpv_clicks table
CREATE TABLE cpv_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    referer TEXT,
    is_duplicate TINYINT(1) DEFAULT 0,
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_campaign_ip_date (campaign_id, ip_address, clicked_at),
    KEY idx_clicked_at (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Done! Now refresh super_admin/cpv.php page
-- Copy paste this SQL in phpMyAdmin or MySQL command line

-- Step 1: Drop old tables
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS cpv_clicks;
DROP TABLE IF EXISTS cpv_campaigns;
DROP TABLE IF EXISTS cpv_links;
SET FOREIGN_KEY_CHECKS = 1;

-- Step 2: Create cpv_campaigns table
CREATE TABLE cpv_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_name VARCHAR(255) NOT NULL,
    original_url TEXT NOT NULL,
    short_code VARCHAR(20) UNIQUE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    total_clicks INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_short_code (short_code),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 3: Create cpv_clicks table
CREATE TABLE cpv_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    referer TEXT,
    is_duplicate TINYINT(1) DEFAULT 0,
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_campaign_ip_date (campaign_id, ip_address, clicked_at),
    KEY idx_clicked_at (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Done! Now refresh super_admin/cpv.php page
