<?php
/**
 * QuickServe - Initialize Chat System
 * Run this once to add chat tables to the database
 */

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Initialize Chat System - QuickServe</title>
    <style>
        body { font-family: Arial; background: #1a1a2e; color: #fff; padding: 30px; }
        .success { background: rgba(76, 175, 80, 0.2); border: 2px solid #4CAF50; padding: 15px; margin: 10px 0; border-radius: 10px; }
        .error { background: rgba(244, 67, 54, 0.2); border: 2px solid #f44336; padding: 15px; margin: 10px 0; border-radius: 10px; }
        h1 { color: #4CAF50; }
    </style>
</head>
<body>
    <h1>🚀 QuickServe - Chat System Initialization</h1>
";

$errors = [];
$success = [];

// Read SQL file
$sql_file = __DIR__ . '/add-chat-tables.sql';
if (!file_exists($sql_file)) {
    die("<div class='error'>❌ SQL file not found: add-chat-tables.sql</div></body></html>");
}

$sql_content = file_get_contents($sql_file);
$sql_statements = array_filter(array_map('trim', explode(';', $sql_content)));

// Execute each statement
foreach ($sql_statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    // Execute the statement
    $result = $conn->query($statement);
    
    if ($result) {
        // Extract table/action name for success message
        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
            $success[] = "✅ Created table: {$matches[1]}";
        } elseif (preg_match('/ALTER TABLE.*?ADD COLUMN\s+(\w+)/i', $statement, $matches)) {
            $success[] = "✅ Added column: {$matches[1]} to users table";
        } elseif (preg_match('/CREATE INDEX.*?ON\s+`?(\w+)`?/i', $statement, $matches)) {
            $success[] = "✅ Created index on: {$matches[1]}";
        } else {
            $success[] = "✅ Executed SQL statement successfully";
        }
    } else {
        $error_msg = $conn->error;
        // Check if error is about duplicate/already exists
        if (stripos($error_msg, 'Duplicate') !== false || 
            stripos($error_msg, 'already exists') !== false ||
            stripos($error_msg, 'Duplicate column') !== false) {
            $success[] = "⚠️ Already exists (skipped): " . substr($error_msg, 0, 100);
        } else {
            $errors[] = "❌ Error: " . $error_msg;
        }
    }
}

// Display results
if (!empty($success)) {
    echo "<h2>✅ Success Messages:</h2>";
    foreach ($success as $msg) {
        echo "<div class='success'>$msg</div>";
    }
}

if (!empty($errors)) {
    echo "<h2>⚠️ Warnings/Errors:</h2>";
    foreach ($errors as $err) {
        echo "<div class='error'>$err</div>";
    }
}

// Verify tables exist
$tables_to_check = ['conversations', 'messages'];
echo "<h2>🔍 Verification:</h2>";

foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        // Count rows
        $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $count_result->fetch_assoc()['count'];
        echo "<div class='success'>✅ Table '$table' exists (contains $count rows)</div>";
    } else {
        echo "<div class='error'>❌ Table '$table' does not exist</div>";
    }
}

// Check for new columns in users table
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'address'");
if ($result && $result->num_rows > 0) {
    echo "<div class='success'>✅ Address fields added to users table</div>";
} else {
    echo "<div class='error'>⚠️ Address fields may not be added to users table</div>";
}

echo "
    <h2>✅ Chat System Initialization Complete!</h2>
    <p>You can now use the chat features in QuickServe.</p>
    <a href='../index.php' style='display: inline-block; margin-top: 20px; padding: 15px 30px; background: #4CAF50; color: white; text-decoration: none; border-radius: 10px;'>
        🏠 Back to Homepage
    </a>
</body>
</html>";

$conn->close();
?>
