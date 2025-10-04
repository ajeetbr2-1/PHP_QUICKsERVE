<?php
/**
 * Supabase Database Initialization Script
 * This script creates all necessary tables for the QuickServe application
 */

require_once 'api/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    $result = initializeDatabase();
    
    if ($result['success']) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Database initialized successfully with all tables and sample data',
            'tables_created' => ['users', 'services', 'bookings', 'reviews']
        ]);
    } else {
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database initialization failed: ' . $e->getMessage()
    ]);
}
?>
