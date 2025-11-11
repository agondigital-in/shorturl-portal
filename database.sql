-- Create database
CREATE DATABASE IF NOT EXISTS ads_platform;
USE ads_platform;

-- Table structure for users (Super Admin & Admin)
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Table structure for advertisers
CREATE TABLE IF NOT EXISTS advertisers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Table structure for publishers
CREATE TABLE IF NOT EXISTS publishers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Table structure for campaigns
CREATE TABLE IF NOT EXISTS campaigns (
    id INT(11) NOT NULL AUTO_INCREMENT,
    campaign_name VARCHAR(100) NOT NULL,
    advertiser_id INT(11) NOT NULL,
    publisher_id INT(11),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    advertiser_payout DECIMAL(10,2) NOT NULL,
    publisher_payout DECIMAL(10,2) NOT NULL,
    type ENUM('CPR', 'CPL', 'CPC', 'CPM', 'CPS', 'None') NOT NULL DEFAULT 'None',
    website_url TEXT NOT NULL,
    short_code VARCHAR(20) NOT NULL UNIQUE,
    clicks INT(11) NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    target_leads INT(11) DEFAULT 0,
    validated_leads INT(11) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    advertiser_payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE SET NULL
);

-- Table structure for campaign_advertisers
CREATE TABLE IF NOT EXISTS campaign_advertisers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    campaign_id INT(11) NOT NULL,
    advertiser_id INT(11) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_campaign_advertiser (campaign_id, advertiser_id)
);

-- Table structure for campaign_publishers
CREATE TABLE IF NOT EXISTS campaign_publishers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    campaign_id INT(11) NOT NULL,
    publisher_id INT(11) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_campaign_publisher (campaign_id, publisher_id)
);