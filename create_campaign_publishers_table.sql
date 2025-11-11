USE ads_platform;
CREATE TABLE IF NOT EXISTS campaign_publishers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    campaign_id INT(11) NOT NULL,
    publisher_id INT(11) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_campaign_publisher (campaign_id, publisher_id)
);