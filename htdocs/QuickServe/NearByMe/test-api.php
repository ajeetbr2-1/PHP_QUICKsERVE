<!DOCTYPE html>
<html>
<head>
    <title>API Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .section { margin: 20px 0; padding: 15px; background: #2a2a2a; border-radius: 8px; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        pre { background: #000; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 API Test Results</h1>
    
    <div class="section">
        <h2>Test 1: Direct PHP Execution</h2>
        <?php
        session_start();
        
        // Simulate logged-in user
        if (!isset($_SESSION['user_id'])) {
            // Get first provider user for testing
            require_once 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            $result = $conn->query("SELECT id, full_name, email, role FROM users WHERE role = 'provider' LIMIT 1");
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                echo "<p class='success'>✅ Set test user: " . $user['full_name'] . " (" . $user['role'] . ")</p>";
            } else {
                echo "<p class='error'>❌ No users found in database</p>";
            }
        }
        
        // Now test the API
        echo "<h3>Calling API...</h3>";
        ob_start();
        include 'api/chat-get-conversations.php';
        $api_output = ob_get_clean();
        
        echo "<p><strong>API Response:</strong></p>";
        echo "<pre>" . htmlspecialchars($api_output) . "</pre>";
        
        // Try to decode as JSON
        $json = json_decode($api_output, true);
        if ($json) {
            echo "<p class='success'>✅ Valid JSON response</p>";
            echo "<pre>" . print_r($json, true) . "</pre>";
        } else {
            echo "<p class='error'>❌ Invalid JSON response</p>";
            echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Test 2: Fetch API Call (JavaScript)</h2>
        <button onclick="testFetch()">🚀 Test Fetch</button>
        <div id="fetchResult"></div>
    </div>
    
    <script>
        function testFetch() {
            const resultDiv = document.getElementById('fetchResult');
            resultDiv.innerHTML = '<p>Loading...</p>';
            
            fetch('api/chat-get-conversations.php')
                .then(response => {
                    resultDiv.innerHTML += `<p>Status: ${response.status}</p>`;
                    return response.text();
                })
                .then(text => {
                    resultDiv.innerHTML += `<p><strong>Raw Response:</strong></p><pre>${text}</pre>`;
                    try {
                        const json = JSON.parse(text);
                        resultDiv.innerHTML += `<p class="success">✅ Valid JSON</p><pre>${JSON.stringify(json, null, 2)}</pre>`;
                    } catch (e) {
                        resultDiv.innerHTML += `<p class="error">❌ JSON Parse Error: ${e.message}</p>`;
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML += `<p class="error">❌ Network Error: ${error.message}</p>`;
                });
        }
    </script>
    
    <div class="section">
        <h2>Test 3: Check File Paths</h2>
        <?php
        $files_to_check = [
            'api/chat-get-conversations.php',
            'config/auth.php',
            'config/database.php'
        ];
        
        foreach ($files_to_check as $file) {
            $full_path = __DIR__ . '/' . $file;
            if (file_exists($full_path)) {
                echo "<p class='success'>✅ $file exists</p>";
            } else {
                echo "<p class='error'>❌ $file NOT FOUND</p>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Test 4: Database Connection</h2>
        <?php
        try {
            $db = new Database();
            $conn = $db->getConnection();
            echo "<p class='success'>✅ Database connection successful</p>";
            
            // Check conversations
            $result = $conn->query("SELECT COUNT(*) as cnt FROM conversations");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "<p class='success'>✅ Conversations table accessible - {$row['cnt']} rows</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <p><a href="chat.php">← Back to Chat</a></p>
</body>
</html>
