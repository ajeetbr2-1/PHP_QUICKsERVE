<?php
/**
 * QuickServe - Get Conversations API (Simplified)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once '../config/auth.php';
    require_once '../config/database.php';
    
    $auth = new Auth();
    $user = $auth->getCurrentUser();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized', 'conversations' => [], 'count' => 0]);
        exit;
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Simple query without complex CASE statements
    $sql = "SELECT 
                c.id,
                c.customer_id,
                c.provider_id,
                c.service_id,
                c.created_at,
                c.updated_at,
                cu.full_name as customer_name,
                p.full_name as provider_name,
                s.title as service_title
            FROM conversations c
            LEFT JOIN users cu ON c.customer_id = cu.id
            LEFT JOIN users p ON c.provider_id = p.id
            LEFT JOIN services s ON c.service_id = s.id
            WHERE c.customer_id = ? OR c.provider_id = ?
            ORDER BY c.updated_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $user['id'], $user['id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        // Determine other user based on current user role
        if ($row['customer_id'] == $user['id']) {
            // Current user is customer, show provider
            $other_user_name = $row['provider_name'];
            $other_user_id = $row['provider_id'];
            $other_user_role = 'provider';
        } else {
            // Current user is provider, show customer
            $other_user_name = $row['customer_name'];
            $other_user_id = $row['customer_id'];
            $other_user_role = 'customer';
        }
        
        // Calculate time ago
        $timestamp = strtotime($row['updated_at']);
        $difference = time() - $timestamp;
        
        if ($difference < 60) {
            $time_ago = 'Just now';
        } elseif ($difference < 3600) {
            $time_ago = floor($difference / 60) . ' min ago';
        } elseif ($difference < 86400) {
            $time_ago = floor($difference / 3600) . ' hr ago';
        } elseif ($difference < 604800) {
            $time_ago = floor($difference / 86400) . ' days ago';
        } else {
            $time_ago = date('M d, Y', $timestamp);
        }
        
        $conversations[] = [
            'id' => (int)$row['id'],
            'other_user_id' => (int)$other_user_id,
            'other_user_name' => $other_user_name ?: 'Unknown User',
            'other_user_image' => null,
            'other_user_role' => $other_user_role,
            'service_id' => (int)$row['service_id'],
            'service_title' => $row['service_title'] ?: 'Unknown Service',
            'last_message' => 'Start chatting',
            'last_message_time' => $row['updated_at'],
            'unread_count' => 0,
            'created_at' => $row['created_at'],
            'time_ago' => $time_ago
        ];
    }
    
    echo json_encode([
        'success' => true,
        'conversations' => $conversations,
        'count' => count($conversations),
        'debug' => [
            'user_id' => $user['id'],
            'user_role' => $user['role']
        ]
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'conversations' => [],
        'count' => 0
    ]);
}
?>
