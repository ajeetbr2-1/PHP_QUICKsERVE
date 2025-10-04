<?php
/**
 * Near By Me - Chat API
 * Handles messaging, location sharing, and conversation management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$db = new Database();
$conn = $db->getConnection();

// Check authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$user = $auth->getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($conn, $user, $action);
            break;
        case 'POST':
            handlePost($conn, $user, $action);
            break;
        case 'PUT':
            handlePut($conn, $user, $action);
            break;
        case 'DELETE':
            handleDelete($conn, $user, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGet($conn, $user, $action) {
    switch ($action) {
        case 'conversations':
            getConversations($conn, $user);
            break;
        case 'messages':
            getMessages($conn, $user);
            break;
        case 'unread-count':
            getUnreadCount($conn, $user);
            break;
        case 'live-location':
            getLiveLocation($conn, $user);
            break;
        case 'notifications':
            getNotifications($conn, $user);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($conn, $user, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'send-message':
            sendMessage($conn, $user, $input);
            break;
        case 'create-room':
            createChatRoom($conn, $user, $input);
            break;
        case 'share-location':
            shareLocation($conn, $user, $input);
            break;
        case 'update-location':
            updateLocation($conn, $user, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePut($conn, $user, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'mark-read':
            markMessagesAsRead($conn, $user, $input);
            break;
        case 'update-notification':
            updateNotification($conn, $user, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function getConversations($conn, $user) {
    $user_id = $user['id'];
    
    $sql = "SELECT cr.id as room_id, cr.customer_id, cr.provider_id, cr.room_name, 
                   cr.last_activity, cr.booking_id,
                   CASE 
                       WHEN cr.customer_id = ? THEN p.full_name 
                       ELSE c.full_name 
                   END as other_user_name,
                   CASE 
                       WHEN cr.customer_id = ? THEN p.id 
                       ELSE c.id 
                   END as other_user_id,
                   CASE 
                       WHEN cr.customer_id = ? THEN p.role 
                       ELSE c.role 
                   END as other_user_role,
                   CASE 
                       WHEN cr.customer_id = ? THEN pp.profile_image 
                       ELSE NULL 
                   END as other_user_image,
                   m.message_content as last_message,
                   m.message_type as last_message_type,
                   m.created_at as last_message_time,
                   (SELECT COUNT(*) FROM messages WHERE room_id = cr.id AND receiver_id = ? AND is_read = FALSE) as unread_count
            FROM chat_rooms cr
            JOIN users c ON cr.customer_id = c.id
            JOIN users p ON cr.provider_id = p.id
            LEFT JOIN provider_profiles pp ON p.id = pp.provider_id
            LEFT JOIN messages m ON m.id = (
                SELECT id FROM messages WHERE room_id = cr.id 
                ORDER BY created_at DESC LIMIT 1
            )
            WHERE (cr.customer_id = ? OR cr.provider_id = ?) AND cr.is_active = TRUE
            ORDER BY cr.last_activity DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = [
            'room_id' => $row['room_id'],
            'other_user_id' => $row['other_user_id'],
            'other_user_name' => $row['other_user_name'],
            'other_user_role' => $row['other_user_role'],
            'other_user_image' => $row['other_user_image'],
            'room_name' => $row['room_name'],
            'last_message' => $row['last_message'],
            'last_message_type' => $row['last_message_type'],
            'last_message_time' => $row['last_message_time'],
            'last_activity' => $row['last_activity'],
            'unread_count' => (int)$row['unread_count'],
            'booking_id' => $row['booking_id']
        ];
    }
    
    echo json_encode(['success' => true, 'conversations' => $conversations]);
}

function getMessages($conn, $user) {
    $room_id = $_GET['room_id'] ?? 0;
    $page = $_GET['page'] ?? 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    if (!$room_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Room ID required']);
        return;
    }
    
    // Verify user has access to this room
    $sql = "SELECT id FROM chat_rooms WHERE id = ? AND (customer_id = ? OR provider_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $room_id, $user['id'], $user['id']);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $sql = "SELECT m.*, u.full_name as sender_name, u.role as sender_role
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.room_id = ?
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $room_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'room_id' => $row['room_id'],
            'sender_id' => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'sender_role' => $row['sender_role'],
            'receiver_id' => $row['receiver_id'],
            'message_type' => $row['message_type'],
            'message_content' => $row['message_content'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'location_address' => $row['location_address'],
            'attachment_url' => $row['attachment_url'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
            'is_own_message' => $row['sender_id'] == $user['id']
        ];
    }
    
    // Reverse to show oldest first
    $messages = array_reverse($messages);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

function sendMessage($conn, $user, $input) {
    $room_id = $input['room_id'] ?? 0;
    $message_type = $input['message_type'] ?? 'text';
    $message_content = trim($input['message_content'] ?? '');
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    $location_address = $input['location_address'] ?? '';
    $attachment_url = $input['attachment_url'] ?? '';
    
    if (!$room_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Room ID required']);
        return;
    }
    
    if ($message_type === 'text' && !$message_content) {
        http_response_code(400);
        echo json_encode(['error' => 'Message content required']);
        return;
    }
    
    // Get room info and verify access
    $sql = "SELECT customer_id, provider_id FROM chat_rooms WHERE id = ? AND is_active = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    if (!$room || ($room['customer_id'] != $user['id'] && $room['provider_id'] != $user['id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $receiver_id = ($room['customer_id'] == $user['id']) ? $room['provider_id'] : $room['customer_id'];
    
    // Insert message
    $sql = "INSERT INTO messages (room_id, sender_id, receiver_id, message_type, message_content, 
                                 latitude, longitude, location_address, attachment_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissddss", $room_id, $user['id'], $receiver_id, $message_type, 
                     $message_content, $latitude, $longitude, $location_address, $attachment_url);
    
    if ($stmt->execute()) {
        $message_id = $conn->insert_id;
        
        // Update room last activity
        $sql = "UPDATE chat_rooms SET last_activity = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        
        // Create notification
        $notification_title = ($message_type === 'location') ? 'New location shared' : 'New message';
        $notification_message = ($message_type === 'location') ? 
                               $user['full_name'] . ' shared their location' :
                               $user['full_name'] . ': ' . substr($message_content, 0, 50);
        
        createNotification($conn, $receiver_id, 'message', $notification_title, $notification_message, $message_id);
        
        echo json_encode([
            'success' => true, 
            'message_id' => $message_id,
            'message' => 'Message sent successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message']);
    }
}

function createChatRoom($conn, $user, $input) {
    $other_user_id = $input['user_id'] ?? 0;
    $booking_id = $input['booking_id'] ?? null;
    
    if (!$other_user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    // Get other user details
    $sql = "SELECT id, full_name, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $other_user_id);
    $stmt->execute();
    $other_user = $stmt->get_result()->fetch_assoc();
    
    if (!$other_user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        return;
    }
    
    // Determine customer and provider
    $customer_id = ($user['role'] === 'customer') ? $user['id'] : $other_user_id;
    $provider_id = ($user['role'] === 'provider') ? $user['id'] : $other_user_id;
    
    // Check if room already exists
    $sql = "SELECT id FROM chat_rooms WHERE customer_id = ? AND provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $customer_id, $provider_id);
    $stmt->execute();
    $existing_room = $stmt->get_result()->fetch_assoc();
    
    if ($existing_room) {
        echo json_encode([
            'success' => true,
            'room_id' => $existing_room['id'],
            'message' => 'Chat room already exists'
        ]);
        return;
    }
    
    // Create new room
    $room_name = "Chat between " . $user['full_name'] . " and " . $other_user['full_name'];
    $sql = "INSERT INTO chat_rooms (customer_id, provider_id, booking_id, room_name) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $customer_id, $provider_id, $booking_id, $room_name);
    
    if ($stmt->execute()) {
        $room_id = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'room_id' => $room_id,
            'message' => 'Chat room created successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create chat room']);
    }
}

function shareLocation($conn, $user, $input) {
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    $address = $input['address'] ?? '';
    $accuracy = $input['accuracy'] ?? null;
    $shared_with_user_id = $input['shared_with_user_id'] ?? null;
    $expires_hours = $input['expires_hours'] ?? 24; // Default 24 hours
    
    if (!$latitude || !$longitude) {
        http_response_code(400);
        echo json_encode(['error' => 'Latitude and longitude required']);
        return;
    }
    
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_hours} hours"));
    
    $sql = "INSERT INTO user_locations (user_id, latitude, longitude, address, accuracy, 
                                       shared_with_user_id, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iddssis", $user['id'], $latitude, $longitude, $address, $accuracy, 
                     $shared_with_user_id, $expires_at);
    
    if ($stmt->execute()) {
        $location_id = $conn->insert_id;
        
        // Update user's current location
        $sql = "UPDATE users SET latitude = ?, longitude = ?, last_location_update = CURRENT_TIMESTAMP 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddi", $latitude, $longitude, $user['id']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'location_id' => $location_id,
            'message' => 'Location shared successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to share location']);
    }
}

function updateLocation($conn, $user, $input) {
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    $address = $input['address'] ?? '';
    
    if (!$latitude || !$longitude) {
        http_response_code(400);
        echo json_encode(['error' => 'Latitude and longitude required']);
        return;
    }
    
    $sql = "UPDATE users SET latitude = ?, longitude = ?, last_location_update = CURRENT_TIMESTAMP 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddi", $latitude, $longitude, $user['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Location updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update location']);
    }
}

function markMessagesAsRead($conn, $user, $input) {
    $room_id = $input['room_id'] ?? 0;
    
    if (!$room_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Room ID required']);
        return;
    }
    
    $sql = "UPDATE messages SET is_read = TRUE 
            WHERE room_id = ? AND receiver_id = ? AND is_read = FALSE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $room_id, $user['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Messages marked as read']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to mark messages as read']);
    }
}

function getUnreadCount($conn, $user) {
    $sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = FALSE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    echo json_encode(['success' => true, 'unread_count' => (int)$result['count']]);
}

function getLiveLocation($conn, $user) {
    $user_id = $_GET['user_id'] ?? 0;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    $sql = "SELECT latitude, longitude, address, accuracy, updated_at 
            FROM user_locations 
            WHERE user_id = ? AND is_active = TRUE 
            AND (shared_with_user_id = ? OR shared_with_user_id IS NULL)
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY updated_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user['id']);
    $stmt->execute();
    $location = $stmt->get_result()->fetch_assoc();
    
    if ($location) {
        echo json_encode(['success' => true, 'location' => $location]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No location available']);
    }
}

function getNotifications($conn, $user) {
    $limit = $_GET['limit'] ?? 20;
    
    $sql = "SELECT * FROM notifications WHERE user_id = ? 
            ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user['id'], $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'type' => $row['type'],
            'title' => $row['title'],
            'message' => $row['message'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
}

function createNotification($conn, $user_id, $type, $title, $message, $related_id = null) {
    $sql = "INSERT INTO notifications (user_id, type, title, message, related_id) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $user_id, $type, $title, $message, $related_id);
    $stmt->execute();
}

function updateNotification($conn, $user, $input) {
    $notification_id = $input['notification_id'] ?? 0;
    $is_read = $input['is_read'] ?? false;
    
    if (!$notification_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Notification ID required']);
        return;
    }
    
    $sql = "UPDATE notifications SET is_read = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $is_read, $notification_id, $user['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notification updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update notification']);
    }
}
?>