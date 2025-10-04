<?php
/**
 * QuickServe - Get Messages API
 * Fetch messages from a conversation
 */

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

// Get parameters
$conversation_id = intval($_GET['conversation_id'] ?? 0);
$limit = intval($_GET['limit'] ?? 50);
$offset = intval($_GET['offset'] ?? 0);
$since_id = intval($_GET['since_id'] ?? 0);

if ($conversation_id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid conversation ID']);
    exit;
}

// Verify user is part of this conversation
$sql = "SELECT * FROM conversations WHERE id = ? AND (customer_id = ? OR provider_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $conversation_id, $user['id'], $user['id']);
$stmt->execute();
$conversation = $stmt->get_result()->fetch_assoc();

if (!$conversation) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get messages
if ($since_id > 0) {
    // Get only new messages since last check
    $sql = "SELECT m.*, u.full_name as sender_name, u.profile_image as sender_image
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ? AND m.id > ?
            ORDER BY m.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $conversation_id, $since_id);
} else {
    // Get recent messages with pagination
    $sql = "SELECT m.*, u.full_name as sender_name, u.profile_image as sender_image
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $conversation_id, $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['id'],
        'sender_id' => $row['sender_id'],
        'sender_name' => $row['sender_name'],
        'sender_image' => $row['sender_image'],
        'message_type' => $row['message_type'],
        'message_text' => $row['message_text'],
        'location_lat' => $row['location_lat'],
        'location_lng' => $row['location_lng'],
        'location_address' => $row['location_address'],
        'is_read' => (bool)$row['is_read'],
        'is_mine' => $row['sender_id'] == $user['id'],
        'created_at' => $row['created_at'],
        'time_ago' => timeAgo($row['created_at'])
    ];
}

// If fetching recent messages, reverse to show oldest first
if ($since_id === 0) {
    $messages = array_reverse($messages);
}

// Mark messages as read
$sql = "UPDATE messages SET is_read = TRUE 
        WHERE conversation_id = ? AND sender_id != ? AND is_read = FALSE";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $conversation_id, $user['id']);
$stmt->execute();

// Update unread count
$unread_field = $user['role'] === 'customer' ? 'customer_unread' : 'provider_unread';
$sql = "UPDATE conversations SET $unread_field = 0 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $conversation_id);
$stmt->execute();

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages)
]);

$conn->close();

// Helper function for time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) return 'Just now';
    if ($difference < 3600) return floor($difference / 60) . ' min ago';
    if ($difference < 86400) return floor($difference / 3600) . ' hr ago';
    if ($difference < 604800) return floor($difference / 86400) . ' days ago';
    return date('M d, Y', $timestamp);
}
?>
