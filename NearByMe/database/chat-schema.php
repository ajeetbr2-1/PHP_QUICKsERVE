<?php
/**
 * Near By Me - Chat System Database Schema
 * Creates tables for real-time messaging and live location sharing
 */

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Add address fields to users table
$sql = "ALTER TABLE users 
        ADD COLUMN address TEXT,
        ADD COLUMN city VARCHAR(100),
        ADD COLUMN state VARCHAR(100),
        ADD COLUMN pincode VARCHAR(10),
        ADD COLUMN latitude DECIMAL(10, 8),
        ADD COLUMN longitude DECIMAL(11, 8),
        ADD COLUMN last_location_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

try {
    $conn->query($sql);
    echo "Users table updated with address and location fields<br>";
} catch (Exception $e) {
    echo "Note: Users table may already have these fields<br>";
}

// Create chat_rooms table
$sql = "CREATE TABLE IF NOT EXISTS chat_rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    provider_id INT NOT NULL,
    booking_id INT,
    room_name VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    UNIQUE KEY unique_room (customer_id, provider_id),
    INDEX idx_customer (customer_id),
    INDEX idx_provider (provider_id),
    INDEX idx_last_activity (last_activity)
)";

if ($conn->query($sql) === TRUE) {
    echo "Chat rooms table created successfully<br>";
} else {
    echo "Error creating chat rooms table: " . $conn->error . "<br>";
}

// Create messages table
$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_type ENUM('text', 'location', 'image', 'file') DEFAULT 'text',
    message_content TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    location_address TEXT,
    attachment_url VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room (room_id),
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_created (created_at),
    INDEX idx_is_read (is_read)
)";

if ($conn->query($sql) === TRUE) {
    echo "Messages table created successfully<br>";
} else {
    echo "Error creating messages table: " . $conn->error . "<br>";
}

// Create user_locations table for live location tracking
$sql = "CREATE TABLE IF NOT EXISTS user_locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address TEXT,
    accuracy FLOAT,
    is_active BOOLEAN DEFAULT TRUE,
    shared_with_user_id INT,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_shared_with (shared_with_user_id),
    INDEX idx_active (is_active),
    INDEX idx_expires (expires_at)
)";

if ($conn->query($sql) === TRUE) {
    echo "User locations table created successfully<br>";
} else {
    echo "Error creating user locations table: " . $conn->error . "<br>";
}

// Create notifications table for message alerts
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('message', 'location_share', 'booking', 'system') DEFAULT 'message',
    title VARCHAR(200) NOT NULL,
    message TEXT,
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created (created_at)
)";

if ($conn->query($sql) === TRUE) {
    echo "Notifications table created successfully<br>";
} else {
    echo "Error creating notifications table: " . $conn->error . "<br>";
}

echo "<br><strong>Chat system database schema created successfully!</strong><br>";
echo "You can now use real-time messaging and live location sharing features.<br>";

$conn->close();
?>