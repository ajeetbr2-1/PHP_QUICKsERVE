<?php
/**
 * QuickServe - Send Message API
 * Send text or location messages in a conversation
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

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$conversation_id = intval($data['conversation_id'] ?? 0);
$message_text = trim($data['message_text'] ?? '');
$message_type = $data['message_type'] ?? 'text';
$location_lat = $data['location_lat'] ?? null;
$location_lng = $data['location_lng'] ?? null;
$location_address = $data['location_address'] ?? null;

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

// Insert message
$sql = "INSERT INTO messages (conversation_id, sender_id, message_type, message_text, 
        location_lat, location_lng, location_address) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissdds", $conversation_id, $user['id'], $message_type, $message_text,
                   $location_lat, $location_lng, $location_address);

if ($stmt->execute()) {
    $message_id = $conn->insert_id;
    
    // Update conversation last message
    $last_message = $message_type === 'location' ? '📍 Shared location' : substr($message_text, 0, 100);
    $sql = "UPDATE conversations SET 
            last_message = ?,
            last_message_time = NOW(),
            customer_unread = IF(? = provider_id, customer_unread + 1, customer_unread),
            provider_unread = IF(? = customer_id, provider_unread + 1, provider_unread)
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siii", $last_message, $user['id'], $user['id'], $conversation_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message_id' => $message_id,
        'message' => 'Message sent successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}

$conn->close();
?>
