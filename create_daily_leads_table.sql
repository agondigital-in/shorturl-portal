-- Create campaign_daily_leads table for Daily Leads Entry feature
-- Run this SQL in phpMyAdmin

CREATE TABLE IF NOT EXISTS `campaign_daily_leads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `lead_date` DATE NOT NULL,
    `leads_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_campaign_date` (`campaign_id`, `lead_date`),
    KEY `idx_campaign_id` (`campaign_id`),
    KEY `idx_lead_date` (`lead_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- This table stores daily leads for each campaign
-- campaign_id: Links to campaigns table
-- lead_date: The date for which leads are entered
-- leads_count: Number of leads for that date
-- unique_campaign_date: Ensures only one entry per campaign per date
