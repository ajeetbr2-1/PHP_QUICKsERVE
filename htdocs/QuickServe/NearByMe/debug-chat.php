<?php
/**
 * Debug Chat System - Check database and test functionality
 */

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$db = new Database();
$conn = $db->getConnection();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Chat System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: green; }
        .error { color: red; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #4CAF50;
            color: white;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔧 Chat System Debug Panel</h1>
    
    <div class="debug-section">
        <h2>👤 Current User Info</h2>
        <pre><?php print_r($user); ?></pre>
    </div>

    <div class="debug-section">
        <h2>📊 Database Tables Check</h2>
        <?php
        // Check conversations table
        $tables_check = [
            'conversations' => "SHOW COLUMNS FROM conversations",
            'messages' => "SHOW COLUMNS FROM messages"
        ];
        
        foreach ($tables_check as $table_name => $query) {
            echo "<h3>Table: $table_name</h3>";
            $result = $conn->query($query);
            
            if ($result) {
                echo "<p class='success'>✅ Table exists with " . $result->num_rows . " columns</p>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['Field'] . "</td>";
                    echo "<td>" . $row['Type'] . "</td>";
                    echo "<td>" . $row['Null'] . "</td>";
                    echo "<td>" . $row['Key'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ Table does not exist or error: " . $conn->error . "</p>";
            }
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>📋 Existing Conversations</h2>
        <?php
        $conv_sql = "SELECT * FROM conversations";
        $conv_result = $conn->query($conv_sql);
        
        if ($conv_result) {
            if ($conv_result->num_rows > 0) {
                echo "<p class='success'>Found " . $conv_result->num_rows . " conversations</p>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Customer ID</th><th>Provider ID</th><th>Service ID</th><th>Created At</th></tr>";
                while ($row = $conv_result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['customer_id'] . "</td>";
                    echo "<td>" . $row['provider_id'] . "</td>";
                    echo "<td>" . $row['service_id'] . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ No conversations found in database</p>";
            }
        } else {
            echo "<p class='error'>❌ Error querying conversations: " . $conn->error . "</p>";
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>📨 Existing Messages</h2>
        <?php
        $msg_sql = "SELECT * FROM messages ORDER BY created_at DESC LIMIT 10";
        $msg_result = $conn->query($msg_sql);
        
        if ($msg_result) {
            if ($msg_result->num_rows > 0) {
                echo "<p class='success'>Found " . $msg_result->num_rows . " messages (showing last 10)</p>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Conv ID</th><th>Sender ID</th><th>Message</th><th>Created At</th></tr>";
                while ($row = $msg_result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['conversation_id'] . "</td>";
                    echo "<td>" . $row['sender_id'] . "</td>";
                    echo "<td>" . htmlspecialchars(substr($row['message_text'] ?? 'N/A', 0, 50)) . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ No messages found in database</p>";
            }
        } else {
            echo "<p class='error'>❌ Error querying messages: " . $conn->error . "</p>";
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>👥 All Users (for testing)</h2>
        <?php
        $users_sql = "SELECT id, full_name, email, role FROM users ORDER BY role, id";
        $users_result = $conn->query($users_sql);
        
        if ($users_result && $users_result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
            while ($row = $users_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td><strong>" . ucfirst($row['role']) . "</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>📅 All Bookings (for testing)</h2>
        <?php
        $bookings_sql = "SELECT b.*, s.title, c.full_name as customer_name, p.full_name as provider_name 
                        FROM bookings b 
                        JOIN services s ON b.service_id = s.id 
                        JOIN users c ON b.customer_id = c.id 
                        JOIN users p ON b.provider_id = p.id 
                        ORDER BY b.created_at DESC LIMIT 10";
        $bookings_result = $conn->query($bookings_sql);
        
        if ($bookings_result && $bookings_result->num_rows > 0) {
            echo "<p>Found " . $bookings_result->num_rows . " bookings</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Service</th><th>Customer</th><th>Provider</th><th>Status</th><th>Test Chat Link</th></tr>";
            while ($row = $bookings_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>" . htmlspecialchars($row['customer_name']) . " (ID: " . $row['customer_id'] . ")</td>";
                echo "<td>" . htmlspecialchars($row['provider_name']) . " (ID: " . $row['provider_id'] . ")</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>";
                echo "<a href='chat.php?customer_id=" . $row['customer_id'] . "&service_id=" . $row['service_id'] . "' target='_blank'>Provider View</a> | ";
                echo "<a href='chat.php?provider_id=" . $row['provider_id'] . "&service_id=" . $row['service_id'] . "' target='_blank'>Customer View</a>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ No bookings found</p>";
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>🧪 Test Conversation Creation</h2>
        <?php
        if (isset($_GET['test_create'])) {
            $test_customer = intval($_GET['customer_id']);
            $test_provider = intval($_GET['provider_id']);
            $test_service = intval($_GET['service_id']);
            
            echo "<h3>Testing with:</h3>";
            echo "<p>Customer ID: $test_customer</p>";
            echo "<p>Provider ID: $test_provider</p>";
            echo "<p>Service ID: $test_service</p>";
            
            // Check if exists
            $check_sql = "SELECT id FROM conversations WHERE customer_id = ? AND provider_id = ? AND service_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("iii", $test_customer, $test_provider, $test_service);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $existing = $check_result->fetch_assoc();
                echo "<p class='success'>✅ Conversation already exists with ID: " . $existing['id'] . "</p>";
                echo "<a href='chat.php?conversation_id=" . $existing['id'] . "' class='btn'>Open Chat</a>";
            } else {
                // Create new
                $create_sql = "INSERT INTO conversations (customer_id, provider_id, service_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
                $create_stmt = $conn->prepare($create_sql);
                $create_stmt->bind_param("iii", $test_customer, $test_provider, $test_service);
                
                if ($create_stmt->execute()) {
                    $new_id = mysqli_insert_id($conn);
                    echo "<p class='success'>✅ New conversation created with ID: $new_id</p>";
                    echo "<a href='chat.php?conversation_id=$new_id'>Open Chat</a>";
                } else {
                    echo "<p class='error'>❌ Error creating conversation: " . $conn->error . "</p>";
                }
            }
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>🔗 Quick Links</h2>
        <p><a href="chat.php">Go to Chat Page</a></p>
        <p><a href="customer-dashboard.php">Customer Dashboard</a></p>
        <p><a href="provider-dashboard.php">Provider Dashboard</a></p>
        <p><a href="index.php">Home</a></p>
    </div>

</body>
</html>
