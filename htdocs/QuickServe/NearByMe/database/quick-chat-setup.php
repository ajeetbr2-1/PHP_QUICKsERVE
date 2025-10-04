<?php
/**
 * QuickServe - Quick Chat System Setup
 * Simple direct execution - no SQL file parsing
 */

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Quick Chat Setup - QuickServe</title>
    <style>
        body { font-family: Arial; background: #1a1a2e; color: #fff; padding: 30px; }
        .success { background: rgba(76, 175, 80, 0.2); border: 2px solid #4CAF50; padding: 15px; margin: 10px 0; border-radius: 10px; }
        .error { background: rgba(244, 67, 54, 0.2); border: 2px solid #f44336; padding: 15px; margin: 10px 0; border-radius: 10px; }
        h1 { color: #4CAF50; }
        .step { margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🚀 QuickServe - Quick Chat Setup</h1>
";

$success = [];
$errors = [];

// Step 1: Create conversations table
echo "<div class='step'><h3>Step 1: Creating conversations table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS conversations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Conversations table created successfully!</div>";
    $success[] = "conversations";
} else {
    if (stripos($conn->error, 'already exists') !== false) {
        echo "<div class='success'>⚠️ Conversations table already exists (OK)</div>";
        $success[] = "conversations (existing)";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
        $errors[] = "conversations: " . $conn->error;
    }
}
echo "</div>";

// Step 2: Create messages table
echo "<div class='step'><h3>Step 2: Creating messages table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS messages (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Messages table created successfully!</div>";
    $success[] = "messages";
} else {
    if (stripos($conn->error, 'already exists') !== false) {
        echo "<div class='success'>⚠️ Messages table already exists (OK)</div>";
        $success[] = "messages (existing)";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
        $errors[] = "messages: " . $conn->error;
    }
}
echo "</div>";

// Step 3: Add address fields to users table
echo "<div class='step'><h3>Step 3: Adding address fields to users table...</h3>";

$fields = [
    'address' => "ALTER TABLE users ADD COLUMN address VARCHAR(500) NULL AFTER phone",
    'city' => "ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL AFTER address",
    'pincode' => "ALTER TABLE users ADD COLUMN pincode VARCHAR(10) NULL AFTER city",
    'latitude' => "ALTER TABLE users ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER pincode",
    'longitude' => "ALTER TABLE users ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude"
];

foreach ($fields as $field => $sql) {
    // Check if column already exists
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$field'");
    if ($check && $check->num_rows > 0) {
        echo "<div class='success'>⚠️ Column $field already exists (OK)</div>";
        $success[] = "users.$field (existing)";
    } else {
        // Column doesn't exist, try to add it
        if ($conn->query($sql)) {
            echo "<div class='success'>✅ Added column: $field</div>";
            $success[] = "users.$field";
        } else {
            if (stripos($conn->error, 'Duplicate column') !== false) {
                echo "<div class='success'>⚠️ Column $field already exists (OK)</div>";
                $success[] = "users.$field (existing)";
            } else {
                echo "<div class='error'>❌ Error adding $field: " . $conn->error . "</div>";
                $errors[] = "users.$field: " . $conn->error;
            }
        }
    }
}
echo "</div>";

// Step 4: Create indexes
echo "<div class='step'><h3>Step 4: Creating indexes...</h3>";

// Check and create idx_users_location
$check = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'idx_users_location'");
if ($check && $check->num_rows > 0) {
    echo "<div class='success'>⚠️ Index idx_users_location already exists (OK)</div>";
    $success[] = "idx_users_location (existing)";
} else {
    $sql = "CREATE INDEX idx_users_location ON users(latitude, longitude)";
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Index on users location created</div>";
        $success[] = "idx_users_location";
    } else {
        if (stripos($conn->error, 'Duplicate') !== false) {
            echo "<div class='success'>⚠️ Index already exists (OK)</div>";
            $success[] = "idx_users_location (existing)";
        } else {
            echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
        }
    }
}

// Check and create idx_messages_read
$check = $conn->query("SHOW INDEX FROM messages WHERE Key_name = 'idx_messages_read'");
if ($check && $check->num_rows > 0) {
    echo "<div class='success'>⚠️ Index idx_messages_read already exists (OK)</div>";
    $success[] = "idx_messages_read (existing)";
} else {
    $sql = "CREATE INDEX idx_messages_read ON messages(is_read, conversation_id)";
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Index on messages read created</div>";
        $success[] = "idx_messages_read";
    } else {
        if (stripos($conn->error, 'Duplicate') !== false) {
            echo "<div class='success'>⚠️ Index already exists (OK)</div>";
            $success[] = "idx_messages_read (existing)";
        } else {
            echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
        }
    }
}
echo "</div>";

// Final Summary
echo "<hr><h2>📊 Setup Summary</h2>";
echo "<div class='success'>";
echo "<strong>✅ Successful Operations: " . count($success) . "</strong><br>";
foreach ($success as $item) {
    echo "  • $item<br>";
}
echo "</div>";

if (!empty($errors)) {
    echo "<div class='error'>";
    echo "<strong>❌ Errors: " . count($errors) . "</strong><br>";
    foreach ($errors as $error) {
        echo "  • $error<br>";
    }
    echo "</div>";
}

// Verification
echo "<h2>🔍 Verification</h2>";

$tables = ['conversations', 'messages'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
        echo "<div class='success'>✅ Table '$table' exists (contains $count rows)</div>";
    } else {
        echo "<div class='error'>❌ Table '$table' does NOT exist!</div>";
    }
}

// Check address field
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'address'");
if ($result && $result->num_rows > 0) {
    echo "<div class='success'>✅ Address fields added to users table</div>";
} else {
    echo "<div class='error'>⚠️ Address fields not found in users table</div>";
}

if (count($errors) == 0) {
    echo "<hr><h2 style='color: #4CAF50;'>🎉 SUCCESS!</h2>";
    echo "<div class='success'>";
    echo "<h3>Chat System Setup Complete!</h3>";
    echo "<p>You can now use the chat features.</p>";
    echo "<a href='../chat.php' style='display: inline-block; margin-top: 20px; padding: 15px 30px; background: #4CAF50; color: white; text-decoration: none; border-radius: 10px;'>
        💬 Go to Chat
    </a>";
    echo " ";
    echo "<a href='../index.php' style='display: inline-block; margin-top: 20px; padding: 15px 30px; background: #2196F3; color: white; text-decoration: none; border-radius: 10px;'>
        🏠 Go to Homepage
    </a>";
    echo "</div>";
} else {
    echo "<hr><h2 style='color: #f44336;'>⚠️ SETUP INCOMPLETE</h2>";
    echo "<div class='error'>";
    echo "<p>Some errors occurred. Please check the errors above and try again.</p>";
    echo "<p><strong>Alternative:</strong> Use phpMyAdmin to run manual-chat-setup.sql</p>";
    echo "</div>";
}

echo "</body></html>";

$conn->close();
?>
