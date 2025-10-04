-- QuickServe Chat System Tables
-- Add conversations and messages tables for real-time messaging

-- Conversations table
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

-- Messages table
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

-- Add address fields to users table
ALTER TABLE users ADD COLUMN address VARCHAR(500) NULL AFTER phone;
ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL AFTER address;
ALTER TABLE users ADD COLUMN pincode VARCHAR(10) NULL AFTER city;
ALTER TABLE users ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER pincode;
ALTER TABLE users ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_location ON users(latitude, longitude);
CREATE INDEX IF NOT EXISTS idx_messages_read ON messages(is_read, conversation_id);
