<?php
// API Test Script
// Run this to verify all API endpoints are working

require_once 'api/config.php';

header('Content-Type: text/html');

echo "<h1>QuickServe API Test Results</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .test{margin:10px 0;padding:10px;border:1px solid #ccc;border-radius:5px;}</style>";

// Test database connection
echo "<div class='test'>";
echo "<h3>Testing Database Connection...</h3>";
try {
    $conn = getDBConnection();
    echo "<span class='success'>✅ Database connection successful</span>";
} catch (Exception $e) {
    echo "<span class='error'>❌ Database connection failed: " . $e->getMessage() . "</span>";
}
echo "</div>";

// Test user registration
echo "<div class='test'>";
echo "<h3>Testing User Registration...</h3>";
$testUser = [
    'email' => 'test' . time() . '@example.com',
    'password' => 'testpass123',
    'fullName' => 'Test User',
    'role' => 'customer'
];

$ch = curl_init('http://localhost/QuickServe/api/auth.php?action=signup');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testUser));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($httpCode == 200 && isset($data['success']) && $data['success']) {
    echo "<span class='success'>✅ User registration successful</span>";
    $testToken = $data['data']['token'];
} else {
    echo "<span class='error'>❌ User registration failed: " . ($data['message'] ?? 'Unknown error') . "</span>";
}
echo "</div>";

// Test user login
if (isset($testToken)) {
    echo "<div class='test'>";
    echo "<h3>Testing User Login...</h3>";

    $loginData = [
        'email' => $testUser['email'],
        'password' => $testUser['password']
    ];

    $ch = curl_init('http://localhost/QuickServe/api/auth.php?action=login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($httpCode == 200 && isset($data['success']) && $data['success']) {
        echo "<span class='success'>✅ User login successful</span>";
    } else {
        echo "<span class='error'>❌ User login failed: " . ($data['message'] ?? 'Unknown error') . "</span>";
    }
    echo "</div>";
}

// Test services endpoint
echo "<div class='test'>";
echo "<h3>Testing Services API...</h3>";

$ch = curl_init('http://localhost/QuickServe/api/services.php?action=list');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($httpCode == 200 && isset($data['success']) && $data['success']) {
    $serviceCount = count($data['data']['services']);
    echo "<span class='success'>✅ Services API working. Found $serviceCount services.</span>";
} else {
    echo "<span class='error'>❌ Services API failed: " . ($data['message'] ?? 'Unknown error') . "</span>";
}
echo "</div>";

// Summary
echo "<div class='test'>";
echo "<h3>Setup Summary</h3>";
echo "<p><strong>API Base URL:</strong> http://localhost/QuickServe/api/</p>";
echo "<p><strong>Main Application:</strong> http://localhost/QuickServe/php-marketplace.html</p>";
echo "<p><strong>Database:</strong> quickserve_db (MySQL via XAMPP)</p>";
echo "<p><strong>Admin Login:</strong> admin@quickserve.com / admin123</p>";
echo "</div>";

echo "<p><a href='../php-marketplace.html'>← Back to Application</a></p>";
?>