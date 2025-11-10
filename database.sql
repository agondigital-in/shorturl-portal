CREATE DATABASE IF NOT EXISTS shorturl;

USE shorturl;

CREATE TABLE IF NOT EXISTS urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_url TEXT NOT NULL,
    short_code VARCHAR(10) NOT NULL UNIQUE,
    click_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data for demonstration
-- INSERT INTO urls (original_url, short_code, click_count) VALUES 
-- ('https://www.google.com', 'goog', 0),
-- ('https://www.github.com', 'ghub', 0),
-- ('https://www.stackoverflow.com', 'stck', 0);