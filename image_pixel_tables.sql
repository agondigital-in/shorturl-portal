-- Image Pixel Tracking Tables
-- Run this SQL to add image pixel tracking feature

-- Add enable_image_pixel column to campaigns table
ALTER TABLE `campaigns` ADD COLUMN `enable_image_pixel` TINYINT(1) DEFAULT 0 AFTER `payment_status`;

-- Table: image_pixel_links (stores unique pixel links for each publisher per campaign)
CREATE TABLE IF NOT EXISTS `image_pixel_links` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `publisher_id` INT NOT NULL,
    `pixel_code` VARCHAR(50) UNIQUE NOT NULL,
    `pixel_url` TEXT NOT NULL,
    `impressions` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`publisher_id`) REFERENCES `publishers`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_campaign_publisher_pixel` (`campaign_id`, `publisher_id`)
);

-- Table: image_pixel_impressions (logs each pixel impression)
CREATE TABLE IF NOT EXISTS `image_pixel_impressions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pixel_id` INT NOT NULL,
    `campaign_id` INT NOT NULL,
    `publisher_id` INT NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `referer` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pixel_id`) REFERENCES `image_pixel_links`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`publisher_id`) REFERENCES `publishers`(`id`) ON DELETE CASCADE,
    INDEX `idx_pixel_date` (`pixel_id`, `created_at`),
    INDEX `idx_campaign_date` (`campaign_id`, `created_at`)
);
