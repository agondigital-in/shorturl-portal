-- CPV URL Shortener Tables
-- Run this SQL to create the required tables for CPV feature

-- First drop old tables if they exist (optional - uncomment if needed)
-- DROP TABLE IF EXISTS `cpv_clicks`;
-- DROP TABLE IF EXISTS `cpv_campaigns`;
-- DROP TABLE IF EXISTS `cpv_links`;

-- Table: cpv_campaigns - Stores CPV campaigns created by super admin
CREATE TABLE IF NOT EXISTS `cpv_campaigns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_name` VARCHAR(255) NOT NULL,
    `original_url` TEXT NOT NULL,
    `short_code` VARCHAR(20) UNIQUE NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `total_clicks` INT DEFAULT 0,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_short_code` (`short_code`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: cpv_clicks - Stores each click with IP tracking
CREATE TABLE IF NOT EXISTS `cpv_clicks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `referer` TEXT,
    `is_duplicate` TINYINT(1) DEFAULT 0,
    `clicked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_campaign_ip_date` (`campaign_id`, `ip_address`, `clicked_at`),
    INDEX `idx_clicked_at` (`clicked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
