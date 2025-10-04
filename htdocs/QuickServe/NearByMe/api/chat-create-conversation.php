<?php
/**
 * QuickServe - Create/Get Conversation API
 * Creates a new conversation or returns existing one
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
$other_user_id = intval($data['other_user_id'] ?? 0);
$service_id = isset($data['service_id']) ? intval($data['service_id']) : null;

if ($other_user_id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Determine customer and provider IDs
$customer_id = $user['role'] === 'customer' ? $user['id'] : $other_user_id;
$provider_id = $user['role'] === 'provider' ? $user['id'] : $other_user_id;

// Check if conversation already exists
$sql = "SELECT * FROM conversations 
        WHERE customer_id = ? AND provider_id = ? AND (service_id = ? OR service_id IS NULL)
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $customer_id, $provider_id, $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Conversation exists
    $conversation = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversation['id'],
        'exists' => true
    ]);
} else {
    // Create new conversation
    $sql = "INSERT INTO conversations (customer_id, provider_id, service_id) 
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $customer_id, $provider_id, $service_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'conversation_id' => $conn->insert_id,
            'exists' => false
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create conversation']);
    }
}

$conn->close();
?>
