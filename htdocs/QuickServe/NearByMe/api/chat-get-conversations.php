<?php
/**
 * QuickServe - Get Conversations API
 * Fetch all conversations for current user
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

header('Content-Type: application/json');
require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get conversations where user is either customer or provider
$sql = "SELECT 
            c.*,
            CASE 
                WHEN c.customer_id = ? THEN p.full_name
                ELSE cu.full_name
            END as other_user_name,
            CASE 
                WHEN c.customer_id = ? THEN p.profile_image
                ELSE cu.profile_image
            END as other_user_image,
            CASE 
                WHEN c.customer_id = ? THEN c.provider_id
                ELSE c.customer_id
            END as other_user_id,
            CASE 
                WHEN c.customer_id = ? THEN 'provider'
                ELSE 'customer'
            END as other_user_role,
            s.title as service_title,
            CASE 
                WHEN c.customer_id = ? THEN c.customer_unread
                ELSE c.provider_unread
            END as unread_count
        FROM conversations c
        LEFT JOIN users cu ON c.customer_id = cu.id
        LEFT JOIN users p ON c.provider_id = p.id
        LEFT JOIN services s ON c.service_id = s.id
        WHERE c.customer_id = ? OR c.provider_id = ?
        ORDER BY c.updated_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("[API ERROR] Prepare failed: " . $conn->error);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $conn->error,
        'conversations' => [],
        'count' => 0
    ]);
    exit;
}

$stmt->bind_param("iiiiiii", $user['id'], $user['id'], $user['id'], $user['id'], 
                   $user['id'], $user['id'], $user['id']);
                   
if (!$stmt->execute()) {
    error_log("[API ERROR] Execute failed: " . $stmt->error);
    echo json_encode([
        'success' => false,
        'message' => 'Query error: ' . $stmt->error,
        'conversations' => [],
        'count' => 0
    ]);
    exit;
}

$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $conversations[] = [
        'id' => $row['id'],
        'other_user_id' => $row['other_user_id'],
        'other_user_name' => $row['other_user_name'],
        'other_user_image' => $row['other_user_image'],
        'other_user_role' => $row['other_user_role'],
        'service_id' => $row['service_id'],
        'service_title' => $row['service_title'],
        'last_message' => $row['last_message'],
        'last_message_time' => $row['last_message_time'],
        'unread_count' => (int)$row['unread_count'],
        'created_at' => $row['created_at'],
        'time_ago' => timeAgo($row['updated_at'])
    ];
}

echo json_encode([
    'success' => true,
    'conversations' => $conversations,
    'count' => count($conversations)
]);

$conn->close();

// Helper function for time ago
function timeAgo($datetime) {
    if (!$datetime) return 'Never';
    
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) return 'Just now';
    if ($difference < 3600) return floor($difference / 60) . ' min ago';
    if ($difference < 86400) return floor($difference / 3600) . ' hr ago';
    if ($difference < 604800) return floor($difference / 86400) . ' days ago';
    return date('M d, Y', $timestamp);
}
?>
