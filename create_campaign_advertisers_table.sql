USE ads_platform;
CREATE TABLE IF NOT EXISTS campaign_advertisers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    campaign_id INT(11) NOT NULL,
    advertiser_id INT(11) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_campaign_advertiser (campaign_id, advertiser_id)
);