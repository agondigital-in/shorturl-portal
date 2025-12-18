-- CPV Optimization: Daily stats table for permanent records
-- Run this SQL in phpMyAdmin

-- Create daily stats table (permanent storage)
CREATE TABLE IF NOT EXISTS cpv_daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    stat_date DATE NOT NULL,
    total_clicks INT DEFAULT 0,
    original_clicks INT DEFAULT 0,
    duplicate_clicks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_campaign_date (campaign_id, stat_date),
    FOREIGN KEY (campaign_id) REFERENCES cpv_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index for faster queries
CREATE INDEX idx_cpv_daily_stats_date ON cpv_daily_stats(stat_date);

-- Remove is_duplicate column from cpv_clicks (we won't store duplicates)
-- First check if column exists, then drop
-- ALTER TABLE cpv_clicks DROP COLUMN IF EXISTS is_duplicate;

-- Clean up: Delete records older than 1 month (run this periodically or via cron)
-- DELETE FROM cpv_clicks WHERE clicked_at < DATE_SUB(NOW(), INTERVAL 1 MONTH);
