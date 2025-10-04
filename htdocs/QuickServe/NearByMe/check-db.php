<?php
// Quick database check
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Database Check Results:</h2>";

// Check conversations table
$result = $conn->query("SHOW TABLES LIKE 'conversations'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Conversations table EXISTS</p>";
    
    // Check columns
    $cols = $conn->query("SHOW COLUMNS FROM conversations");
    echo "<p>Columns: ";
    while ($col = $cols->fetch_assoc()) {
        echo $col['Field'] . ", ";
    }
    echo "</p>";
} else {
    echo "<p style='color: red;'>❌ Conversations table MISSING</p>";
}

// Check messages table
$result = $conn->query("SHOW TABLES LIKE 'messages'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Messages table EXISTS</p>";
} else {
    echo "<p style='color: red;'>❌ Messages table MISSING</p>";
}

// Count conversations
$count = $conn->query("SELECT COUNT(*) as cnt FROM conversations");
if ($count) {
    $row = $count->fetch_assoc();
    echo "<p>Total conversations: " . $row['cnt'] . "</p>";
}
?>
