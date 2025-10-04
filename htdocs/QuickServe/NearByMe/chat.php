<?php
/**
 * QuickServe - Chat System
 * Real-time messaging between customers and providers
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$db = new Database();
$conn = $db->getConnection();

// Handle AJAX message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_send'])) {
    header('Content-Type: application/json');
    
    $conversation_id = intval($_POST['conversation_id']);
    $message_text = trim($_POST['message_text']);
    
    if ($conversation_id && $message_text) {
        $insert_sql = "INSERT INTO messages (conversation_id, sender_id, message_text, message_type, created_at) 
                      VALUES (?, ?, ?, 'text', NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iis", $conversation_id, $user['id'], $message_text);
        
        if ($insert_stmt->execute()) {
            // Update conversation timestamp
            $update_sql = "UPDATE conversations SET updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $conversation_id);
            $update_stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Message sent',
                'message_id' => mysqli_insert_id($conn)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
    exit;
}

// Handle AJAX get new messages
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_messages'])) {
    header('Content-Type: application/json');
    
    $conversation_id = intval($_GET['conversation_id']);
    $since_id = isset($_GET['since_id']) ? intval($_GET['since_id']) : 0;
    
    $msg_sql = "SELECT m.*, u.full_name as sender_name 
               FROM messages m
               LEFT JOIN users u ON m.sender_id = u.id
               WHERE m.conversation_id = ? AND m.id > ?
               ORDER BY m.created_at ASC";
    $msg_stmt = $conn->prepare($msg_sql);
    $msg_stmt->bind_param("ii", $conversation_id, $since_id);
    $msg_stmt->execute();
    $result = $msg_stmt->get_result();
    
    $messages = [];
    while ($msg = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $msg['id'],
            'sender_id' => $msg['sender_id'],
            'message_text' => $msg['message_text'],
            'message_type' => $msg['message_type'],
            'is_mine' => ($msg['sender_id'] == $user['id']),
            'created_at' => $msg['created_at']
        ];
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

// Get conversation_id from URL if provided
$active_conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : null;

// Get parameters for creating new conversation
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : null;
$provider_id = isset($_GET['provider_id']) ? intval($_GET['provider_id']) : null;

// Auto-create conversation if customer_id and service_id provided
if (($customer_id && $service_id) || ($provider_id && $service_id)) {
    // Determine customer and provider IDs
    if ($user['role'] === 'provider') {
        $final_customer_id = $customer_id;
        $final_provider_id = $user['id'];
    } else {
        $final_customer_id = $user['id'];
        $final_provider_id = $provider_id ?: $customer_id; // fallback
    }
    
    // DEBUG: Log the values
    error_log("[CHAT DEBUG] User Role: " . $user['role']);
    error_log("[CHAT DEBUG] Final Customer ID: " . $final_customer_id);
    error_log("[CHAT DEBUG] Final Provider ID: " . $final_provider_id);
    error_log("[CHAT DEBUG] Service ID: " . $service_id);
    
    // Check if conversation already exists
    $check_sql = "SELECT id FROM conversations WHERE customer_id = ? AND provider_id = ? AND service_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        error_log("[CHAT ERROR] Prepare failed: " . $conn->error);
        die("Database error: Unable to prepare statement. Check if conversations table exists.");
    }
    
    $check_stmt->bind_param("iii", $final_customer_id, $final_provider_id, $service_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Conversation exists, get its ID
        $existing_conv = $check_result->fetch_assoc();
        $active_conversation_id = $existing_conv['id'];
        error_log("[CHAT DEBUG] Found existing conversation: " . $active_conversation_id);
    } else {
        // Create new conversation
        error_log("[CHAT DEBUG] Creating new conversation...");
        $create_sql = "INSERT INTO conversations (customer_id, provider_id, service_id, created_at, updated_at) 
                      VALUES (?, ?, ?, NOW(), NOW())";
        $create_stmt = $conn->prepare($create_sql);
        
        if (!$create_stmt) {
            error_log("[CHAT ERROR] Insert prepare failed: " . $conn->error);
            die("Database error: Unable to create conversation. Check if conversations table exists.");
        }
        
        $create_stmt->bind_param("iii", $final_customer_id, $final_provider_id, $service_id);
        
        if ($create_stmt->execute()) {
            $active_conversation_id = mysqli_insert_id($conn);
            error_log("[CHAT DEBUG] Created new conversation: " . $active_conversation_id);
        } else {
            error_log("[CHAT ERROR] Insert execution failed: " . $create_stmt->error);
            die("Database error: " . $create_stmt->error);
        }
    }
    
    // Redirect to clean URL with conversation_id
    if ($active_conversation_id) {
        error_log("[CHAT DEBUG] Redirecting to conversation: " . $active_conversation_id);
        header("Location: chat.php?conversation_id=" . $active_conversation_id);
        exit;
    } else {
        error_log("[CHAT ERROR] No conversation ID available for redirect");
        die("Error: Unable to create or find conversation.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - QuickServe</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .chat-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            height: calc(100vh - 200px);
            max-height: 700px;
        }
        
        /* Conversations List */
        .conversations-list {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .conversation-item {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid transparent;
            display: flex;
            gap: 12px;
            align-items: start;
        }
        
        .conversation-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        
        .conversation-item.active {
            background: rgba(76, 175, 80, 0.3);
            border-color: #4CAF50;
        }
        
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .conversation-info {
            flex: 1;
            overflow: hidden;
        }
        
        .conversation-name {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .conversation-last-message {
            font-size: 0.85rem;
            opacity: 0.8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-time {
            font-size: 0.75rem;
            opacity: 0.6;
        }
        
        .unread-badge {
            background: #f44336;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Chat Window */
        .chat-window {
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chat-header-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .chat-header-info h3 {
            margin: 0 0 5px 0;
        }
        
        .chat-header-info p {
            margin: 0;
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        /* Messages Area */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            display: flex;
            gap: 10px;
            max-width: 70%;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message.mine {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .message-content {
            background: rgba(255, 255, 255, 0.2);
            padding: 12px 16px;
            border-radius: 16px;
            max-width: 100%;
            word-wrap: break-word;
        }
        
        .message.mine .message-content {
            background: rgba(76, 175, 80, 0.3);
        }
        
        .message-text {
            margin-bottom: 5px;
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.6;
        }
        
        .message-location {
            background: rgba(33, 150, 243, 0.2);
            padding: 10px;
            border-radius: 10px;
            margin-top: 5px;
        }
        
        .message-location a {
            color: #64B5F6;
            text-decoration: none;
            font-weight: 600;
        }
        
        /* Message Input */
        .message-input-container {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .message-input-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 20px;
            border-radius: 25px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 1rem;
        }
        
        .message-input:focus {
            outline: none;
            border-color: #4CAF50;
            background: rgba(255, 255, 255, 0.25);
        }
        
        .message-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .btn-send {
            padding: 12px 24px;
            border-radius: 25px;
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-send:hover {
            background: #45a049;
            transform: scale(1.05);
        }
        
        .btn-location {
            padding: 12px;
            border-radius: 50%;
            background: rgba(33, 150, 243, 0.3);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-location:hover {
            background: rgba(33, 150, 243, 0.5);
            transform: scale(1.1);
        }
        
        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            opacity: 0.6;
        }
        
        .empty-chat-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                grid-template-columns: 1fr;
            }
            
            .conversations-list {
                display: none;
            }
            
            .conversations-list.mobile-show {
                display: block;
            }
            
            .chat-window {
                display: none;
            }
            
            .chat-window.mobile-show {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="navbar-content glass">
                <div class="logo">
                    <span class="logo-icon">🚀</span>
                    <span>QuickServe</span>
                </div>
                <ul class="nav-links">
                    <li><a href="index.php">🏠 Home</a></li>
                    <?php if ($user['role'] === 'customer'): ?>
                        <li><a href="customer-dashboard.php">📊 Dashboard</a></li>
                    <?php elseif ($user['role'] === 'provider'): ?>
                        <li><a href="provider-dashboard.php">💼 Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="chat.php" class="active">💬 Messages</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">🚪 Logout</a></li>
                </ul>
            </div>
        </nav>

        <h1 style="margin: 30px 0;">💬 Messages</h1>

        <div class="chat-container">
            <!-- Conversations List -->
            <div class="conversations-list" id="conversationsList">
                <h3 style="margin-bottom: 20px;">Your Conversations</h3>
                <div id="conversationsContent">
                    <?php
                    // Load conversations directly from PHP
                    $conv_sql = "SELECT 
                                    c.id,
                                    c.customer_id,
                                    c.provider_id,
                                    c.service_id,
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
                    
                    $conv_stmt = $conn->prepare($conv_sql);
                    $conv_stmt->bind_param("ii", $user['id'], $user['id']);
                    $conv_stmt->execute();
                    $conv_result = $conv_stmt->get_result();
                    
                    if ($conv_result->num_rows > 0):
                        while ($conv = $conv_result->fetch_assoc()):
                            // Determine other user
                            if ($conv['customer_id'] == $user['id']) {
                                $other_name = $conv['provider_name'];
                                $other_id = $conv['provider_id'];
                                $other_role = 'provider';
                            } else {
                                $other_name = $conv['customer_name'];
                                $other_id = $conv['customer_id'];
                                $other_role = 'customer';
                            }
                            
                            $time_ago = 'Just now';
                            $is_active = ($active_conversation_id == $conv['id']) ? 'active' : '';
                    ?>
                        <div class="conversation-item <?php echo $is_active; ?>" 
                             onclick="window.location.href='chat.php?conversation_id=<?php echo $conv['id']; ?>'" style="cursor: pointer;">
                            <div class="conversation-avatar">
                                <?php echo $other_role === 'provider' ? '👨‍🔧' : '👤'; ?>
                            </div>
                            <div class="conversation-info">
                                <div class="conversation-name">
                                    <span><?php echo htmlspecialchars($other_name); ?></span>
                                </div>
                                <div style="font-size: 0.75rem; opacity: 0.7; margin-bottom: 3px;">📋 <?php echo htmlspecialchars($conv['service_title']); ?></div>
                                <div class="conversation-last-message">Start chatting</div>
                                <div class="conversation-time"><?php echo $time_ago; ?></div>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div style="text-align: center; padding: 20px; opacity: 0.6;">
                            <p>No conversations yet</p>
                            <p style="font-size: 0.85rem;">Start chatting with service providers!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Window -->
            <div class="chat-window" id="chatWindow">
                <?php if ($active_conversation_id): 
                    // Load conversation details and messages directly
                    $conv_detail_sql = "SELECT c.*, cu.full_name as customer_name, p.full_name as provider_name, s.title as service_title
                                       FROM conversations c
                                       LEFT JOIN users cu ON c.customer_id = cu.id
                                       LEFT JOIN users p ON c.provider_id = p.id
                                       LEFT JOIN services s ON c.service_id = s.id
                                       WHERE c.id = ?";
                    $conv_detail_stmt = $conn->prepare($conv_detail_sql);
                    $conv_detail_stmt->bind_param("i", $active_conversation_id);
                    $conv_detail_stmt->execute();
                    $conv_detail = $conv_detail_stmt->get_result()->fetch_assoc();
                    
                    if ($conv_detail):
                        // Determine other user
                        if ($conv_detail['customer_id'] == $user['id']) {
                            $other_name = $conv_detail['provider_name'];
                            $other_role = 'provider';
                        } else {
                            $other_name = $conv_detail['customer_name'];
                            $other_role = 'customer';
                        }
                        
                        // Load messages
                        $msg_sql = "SELECT m.*, u.full_name as sender_name 
                                   FROM messages m
                                   LEFT JOIN users u ON m.sender_id = u.id
                                   WHERE m.conversation_id = ?
                                   ORDER BY m.created_at ASC";
                        $msg_stmt = $conn->prepare($msg_sql);
                        $msg_stmt->bind_param("i", $active_conversation_id);
                        $msg_stmt->execute();
                        $messages = $msg_stmt->get_result();
                ?>
                    <div class="chat-header">
                        <div class="chat-header-avatar">
                            <?php echo $other_role === 'provider' ? '👨‍🔧' : '👤'; ?>
                        </div>
                        <div class="chat-header-info">
                            <h3><?php echo htmlspecialchars($other_name); ?></h3>
                            <p><?php echo $other_role === 'provider' ? 'Service Provider' : 'Customer'; ?></p>
                        </div>
                    </div>
                    
                    <div class="messages-container" id="messagesContainer">
                        <?php if ($messages->num_rows === 0): ?>
                            <div style="text-align: center; opacity: 0.6; margin: auto;">
                                <p>No messages yet. Start the conversation!</p>
                            </div>
                        <?php else:
                            while ($msg = $messages->fetch_assoc()):
                                $is_mine = ($msg['sender_id'] == $user['id']);
                        ?>
                            <div class="message <?php echo $is_mine ? 'mine' : ''; ?>">
                                <div class="message-avatar">
                                    <?php echo $is_mine ? '😊' : '👤'; ?>
                                </div>
                                <div class="message-content">
                                    <?php if ($msg['message_type'] === 'location'): ?>
                                        <div class="message-location">
                                            📍 <strong>Location Shared</strong><br>
                                            <?php echo htmlspecialchars($msg['location_address'] ?? 'View on map'); ?><br>
                                            <a href="https://www.google.com/maps?q=<?php echo $msg['location_lat']; ?>,<?php echo $msg['location_lng']; ?>" target="_blank">
                                                Open in Google Maps →
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="message-text"><?php echo htmlspecialchars($msg['message_text']); ?></div>
                                    <?php endif; ?>
                                    <div class="message-time">Just now</div>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </div>
                    
                    <div class="message-input-container">
                        <form class="message-input-form" id="messageForm" onsubmit="sendMessageAjax(event)">
                            <input type="hidden" id="conversationId" value="<?php echo $active_conversation_id; ?>">
                            <input type="text" class="message-input" id="messageInput" 
                                   placeholder="Type a message..." required autocomplete="off">
                            <button type="submit" class="btn-send" id="sendBtn">Send 📤</button>
                        </form>
                    </div>
                <?php 
                    endif;
                else: 
                ?>
                    <div class="empty-chat">
                        <div class="empty-chat-icon">💬</div>
                        <h3>Select a conversation to start chatting</h3>
                        <p>Choose a conversation from the list or start a new one</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer" style="margin-top: 50px;">
            <p>&copy; 2025 QuickServe - Your Local Service Marketplace</p>
        </div>
    </div>

    <script>
        let currentConversationId = <?php echo $active_conversation_id ?? 'null'; ?>;
        let currentUserId = <?php echo $user['id']; ?>;
        let lastMessageId = <?php 
            // Get last message ID if conversation is active
            if ($active_conversation_id) {
                $last_msg_sql = "SELECT MAX(id) as last_id FROM messages WHERE conversation_id = ?";
                $last_msg_stmt = $conn->prepare($last_msg_sql);
                $last_msg_stmt->bind_param("i", $active_conversation_id);
                $last_msg_stmt->execute();
                $last_msg_result = $last_msg_stmt->get_result()->fetch_assoc();
                echo $last_msg_result['last_id'] ?? 0;
            } else {
                echo 0;
            }
        ?>;
        let refreshInterval = null;

        // Load conversations on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[CHAT] Real-time chat ready');
            console.log('[CHAT] Current conversation ID:', currentConversationId);
            
            // Start polling for new messages if conversation is active
            if (currentConversationId) {
                // Get last message ID
                const messages = document.querySelectorAll('.message');
                if (messages.length > 0) {
                    const lastMsg = messages[messages.length - 1];
                    // We'll track with a global var
                }
                
                // Poll for new messages every 3 seconds
                setInterval(pollNewMessages, 3000);
            }
        });

        // Load conversations list
        function loadConversations() {
            console.log('[CHAT] Loading conversations...');
            fetch('api/chat-get-conversations.php')
                .then(response => {
                    console.log('[CHAT] Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('[CHAT] API Response:', data);
                    if (data.success) {
                        console.log('[CHAT] Found conversations:', data.count);
                        displayConversations(data.conversations);
                    } else {
                        console.error('[CHAT] API returned error:', data.message);
                        const container = document.getElementById('conversationsContent');
                        container.innerHTML = `
                            <div style="text-align: center; padding: 20px; color: #ff5555;">
                                <p><strong>Error loading conversations</strong></p>
                                <p style="font-size: 0.85rem;">${data.message || 'Unknown error'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('[CHAT] Error loading conversations:', error);
                    const container = document.getElementById('conversationsContent');
                    container.innerHTML = `
                        <div style="text-align: center; padding: 20px; color: #ff5555;">
                            <p><strong>Network Error</strong></p>
                            <p style="font-size: 0.85rem;">Unable to connect to server</p>
                        </div>
                    `;
                });
        }

        // Display conversations in sidebar
        function displayConversations(conversations) {
            const container = document.getElementById('conversationsContent');
            
            if (conversations.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 20px; opacity: 0.6;">
                        <p>No conversations yet</p>
                        <p style="font-size: 0.85rem;">Start chatting with service providers!</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = conversations.map(conv => `
                <div class="conversation-item ${conv.id === currentConversationId ? 'active' : ''}" 
                     onclick="openConversation(${conv.id}, '${conv.other_user_name}', '${conv.other_user_role}')">
                    <div class="conversation-avatar">
                        ${conv.other_user_role === 'provider' ? '👨‍🔧' : '👤'}
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-name">
                            <span>${conv.other_user_name}</span>
                            ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                        </div>
                        ${conv.service_title ? `<div style="font-size: 0.75rem; opacity: 0.7; margin-bottom: 3px;">📋 ${conv.service_title}</div>` : ''}
                        <div class="conversation-last-message">${conv.last_message || 'No messages yet'}</div>
                        <div class="conversation-time">${conv.time_ago}</div>
                    </div>
                </div>
            `).join('');
        }

        // Open a conversation (DISABLED - Using PHP redirect instead)
        function openConversation(conversationId, userName, userRole) {
            // Redirect to conversation page
            window.location.href = 'chat.php?conversation_id=' + conversationId;
        }

        // Load conversation messages
        function loadConversation(conversationId, userName, userRole) {
            console.log('[CHAT] Loading conversation messages...');
            const chatWindow = document.getElementById('chatWindow');
            
            chatWindow.innerHTML = '<div style="text-align: center; padding: 50px;">Loading messages...</div>';
            
            fetch(`api/chat-get-messages.php?conversation_id=${conversationId}`)
                .then(response => {
                    console.log('[CHAT] Messages API status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('[CHAT] Messages data:', data);
                    if (data.success) {
                        displayChat(conversationId, userName, userRole, data.messages);
                        if (data.messages.length > 0) {
                            lastMessageId = Math.max(...data.messages.map(m => m.id));
                        }
                    } else {
                        chatWindow.innerHTML = `<div style="text-align: center; padding: 50px; color: #ff5555;">Error: ${data.message || 'Failed to load messages'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('[CHAT] Error loading messages:', error);
                    chatWindow.innerHTML = `<div style="text-align: center; padding: 50px; color: #ff5555;">Network error loading messages</div>`;
                });
        }

        // Display chat window
        function displayChat(conversationId, userName, userRole, messages) {
            const chatWindow = document.getElementById('chatWindow');
            
            chatWindow.innerHTML = `
                <div class="chat-header">
                    <div class="chat-header-avatar">
                        ${userRole === 'provider' ? '👨‍🔧' : '👤'}
                    </div>
                    <div class="chat-header-info">
                        <h3>${userName}</h3>
                        <p>${userRole === 'provider' ? 'Service Provider' : 'Customer'}</p>
                    </div>
                </div>
                
                <div class="messages-container" id="messagesContainer">
                    ${messages.length === 0 ? `
                        <div style="text-align: center; opacity: 0.6; margin: auto;">
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    ` : messages.map(msg => createMessageHTML(msg)).join('')}
                </div>
                
                <div class="message-input-container">
                    <form class="message-input-form" onsubmit="sendMessage(event, ${conversationId})">
                        <button type="button" class="btn-location" onclick="shareLocation(${conversationId})" title="Share Location">
                            📍
                        </button>
                        <input type="text" class="message-input" id="messageInput" 
                               placeholder="Type a message..." required>
                        <button type="submit" class="btn-send">Send 📤</button>
                    </form>
                </div>
            `;
            
            // Scroll to bottom
            scrollToBottom();
        }

        // Create message HTML
        function createMessageHTML(msg) {
            const isMine = msg.is_mine;
            return `
                <div class="message ${isMine ? 'mine' : ''}">
                    <div class="message-avatar">
                        ${isMine ? '😊' : '👤'}
                    </div>
                    <div class="message-content">
                        ${msg.message_type === 'location' ? `
                            <div class="message-location">
                                📍 <strong>Location Shared</strong><br>
                                ${msg.location_address || 'View on map'}<br>
                                <a href="https://www.google.com/maps?q=${msg.location_lat},${msg.location_lng}" target="_blank">
                                    Open in Google Maps →
                                </a>
                            </div>
                        ` : `
                            <div class="message-text">${escapeHtml(msg.message_text)}</div>
                        `}
                        <div class="message-time">${msg.time_ago}</div>
                    </div>
                </div>
            `;
        }

        // Send message via AJAX
        function sendMessageAjax(event) {
            event.preventDefault();
            
            const form = event.target;
            const input = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendBtn');
            const conversationId = document.getElementById('conversationId').value;
            const messageText = input.value.trim();
            
            if (!messageText) return;
            
            // Disable button
            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';
            
            // Send via AJAX
            const formData = new FormData();
            formData.append('ajax_send', '1');
            formData.append('conversation_id', conversationId);
            formData.append('message_text', messageText);
            
            fetch('chat.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('[CHAT] Send response:', data);
                if (data.success) {
                    // Clear input
                    input.value = '';
                    
                    // Add message to UI immediately
                    addMessageToUI({
                        id: data.message_id,
                        message_text: messageText,
                        is_mine: true,
                        message_type: 'text'
                    });
                    
                    // Update last message ID
                    lastMessageId = data.message_id;
                    
                    // Scroll to bottom
                    scrollToBottom();
                } else {
                    alert('Failed to send message: ' + data.message);
                }
            })
            .catch(error => {
                console.error('[CHAT] Send error:', error);
                alert('Network error sending message');
            })
            .finally(() => {
                // Re-enable button
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send 📤';
            });
        }
        
        // Poll for new messages
        function pollNewMessages() {
            if (!currentConversationId) return;
            
            const url = `chat.php?ajax_get_messages=1&conversation_id=${currentConversationId}&since_id=${lastMessageId}`;
            
            fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    console.log('[CHAT] New messages:', data.messages.length);
                    data.messages.forEach(msg => {
                        addMessageToUI(msg);
                        lastMessageId = Math.max(lastMessageId, msg.id);
                    });
                    scrollToBottom();
                }
            })
            .catch(error => {
                console.error('[CHAT] Poll error:', error);
            });
        }
        
        // Add message to UI
        function addMessageToUI(msg) {
            const container = document.getElementById('messagesContainer');
            const isMine = msg.is_mine;
            
            const messageHTML = `
                <div class="message ${isMine ? 'mine' : ''}">
                    <div class="message-avatar">
                        ${isMine ? '😊' : '👤'}
                    </div>
                    <div class="message-content">
                        <div class="message-text">${escapeHtml(msg.message_text)}</div>
                        <div class="message-time">Just now</div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', messageHTML);
        }

        // Send message
        function sendMessage(event, conversationId) {
            event.preventDefault();
            
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            fetch('api/chat-send-message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    message_text: message,
                    message_type: 'text'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadNewMessages();
                }
            })
            .catch(error => console.error('Error sending message:', error));
        }

        // Share location
        function shareLocation(conversationId) {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                return;
            }
            
            navigator.geolocation.getCurrentPosition(function(position) {
                fetch('api/chat-send-message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversation_id: conversationId,
                        message_type: 'location',
                        location_lat: position.coords.latitude,
                        location_lng: position.coords.longitude,
                        location_address: `Lat: ${position.coords.latitude.toFixed(6)}, Lng: ${position.coords.longitude.toFixed(6)}`
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNewMessages();
                    }
                })
                .catch(error => console.error('Error sharing location:', error));
            }, function(error) {
                alert('Unable to get your location: ' + error.message);
            });
        }

        // Helper functions
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            if (container) {
                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                }, 100);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
