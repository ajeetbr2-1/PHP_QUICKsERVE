-- QuickServe Chat System - Manual Setup
-- Run this SQL directly in phpMyAdmin if init-chat-system.php has issues
-- Database: nearbyme_db

USE nearbyme_db;

-- Drop existing tables if any (optional - uncomment if needed)
-- DROP TABLE IF EXISTS messages;
-- DROP TABLE IF EXISTS conversations;

-- 1. Create conversations table
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    provider_id INT NOT NULL,
    service_id INT NULL,
    last_message TEXT,
    last_message_time DATETIME,
    customer_unread INT DEFAULT 0,
    provider_unread INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    UNIQUE KEY unique_conversation (customer_id, provider_id, service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_type ENUM('text', 'location', 'image', 'system') DEFAULT 'text',
    message_text TEXT,
    location_lat DECIMAL(10, 8) NULL,
    location_lng DECIMAL(11, 8) NULL,
    location_address VARCHAR(500) NULL,
    attachment_url VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (conversation_id),
    INDEX idx_sender (sender_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add address fields to users table (run each separately if error occurs)

-- Check if column exists before adding
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'nearbyme_db' 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'address');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE users ADD COLUMN address VARCHAR(500) NULL AFTER phone', 
    'SELECT ''Column address already exists'' AS INFO');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'nearbyme_db' 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'city');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL AFTER address', 
    'SELECT ''Column city already exists'' AS INFO');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'nearbyme_db' 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'pincode');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE users ADD COLUMN pincode VARCHAR(10) NULL AFTER city', 
    'SELECT ''Column pincode already exists'' AS INFO');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'nearbyme_db' 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'latitude');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE users ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER pincode', 
    'SELECT ''Column latitude already exists'' AS INFO');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'nearbyme_db' 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'longitude');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE users ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude', 
    'SELECT ''Column longitude already exists'' AS INFO');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- 4. Create indexes
CREATE INDEX IF NOT EXISTS idx_users_location ON users(latitude, longitude);
CREATE INDEX IF NOT EXISTS idx_messages_read ON messages(is_read, conversation_id);

-- Verify tables were created
SELECT 'conversations table created' AS status 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'nearbyme_db' 
AND TABLE_NAME = 'conversations';

SELECT 'messages table created' AS status 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'nearbyme_db' 
AND TABLE_NAME = 'messages';

-- Show all tables
SHOW TABLES;

-- Done!
SELECT '✅ Chat system tables setup complete!' AS RESULT;
