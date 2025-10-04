<?php
require_once 'config.php';

// Initialize database on first run
if (isset($_GET['init'])) {
    $result = initializeDatabase();
    sendResponse($result);
}

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'POST':
        switch ($endpoint) {
            case 'login':
                handleLogin();
                break;
            case 'signup':
                handleSignup();
                break;
            case 'logout':
                handleLogout();
                break;
            case 'verify-token':
                verifyToken();
                break;
            default:
                sendError('Invalid endpoint', 404);
        }
        break;

    case 'GET':
        switch ($endpoint) {
            case 'profile':
                getProfile();
                break;
            case 'users':
                getUsers();
                break;
            default:
                sendError('Invalid endpoint', 404);
        }
        break;

    case 'PUT':
        switch ($endpoint) {
            case 'profile':
                updateProfile();
                break;
            default:
                sendError('Invalid endpoint', 404);
        }
        break;

    default:
        sendError('Method not allowed', 405);
}

function handleLogin() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        sendError('Email and password are required');
    }

    $email = sanitizeInput($input['email']);
    $password = $input['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format');
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendError('Invalid email or password', 401);
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        sendError('Invalid email or password', 401);
    }

    // Blocked user check
    if (isset($user['is_blocked']) && $user['is_blocked']) {
        $reason = isset($user['blocked_reason']) ? (": " . $user['blocked_reason']) : '';
        sendError('Your account has been blocked by the administrator' . $reason, 403);
    }

    // Update last login
    $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
    $updateStmt->bind_param("i", $user['id']);
    $updateStmt->execute();

    // Generate token
    $token = generateToken($user['id']);

    // Remove password from response
    unset($user['password']);

    sendSuccess([
        'user' => $user,
        'token' => $token
    ], 'Login successful');
}

function handleSignup() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        sendError('Invalid input data');
    }

    $requiredFields = ['email', 'password', 'fullName', 'role'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            sendError("$field is required");
        }
    }

    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $fullName = sanitizeInput($input['fullName']);
    $role = sanitizeInput($input['role']);
    $phone = isset($input['phone']) ? sanitizeInput($input['phone']) : '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format');
    }

    // Validate role
    $validRoles = ['customer', 'provider', 'admin'];
    if (!in_array($role, $validRoles)) {
        sendError('Invalid role specified');
    }

    // Validate password strength
    if (strlen($password) < 6) {
        sendError('Password must be at least 6 characters long');
    }

    $conn = getDBConnection();

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        sendError('Email already registered');
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insertStmt = $conn->prepare("INSERT INTO users (email, password, full_name, phone, role, verification_status, profile_completed)
                                 VALUES (?, ?, ?, ?, ?, 'phone_verified', TRUE)");
    $insertStmt->bind_param("sssss", $email, $hashedPassword, $fullName, $phone, $role);

    if ($insertStmt->execute()) {
        $userId = $conn->insert_id;

        // Generate token for auto-login
        $token = generateToken($userId);

        // Get user data
        $selectStmt = $conn->prepare("SELECT id, email, full_name, phone, role, verification_status, is_active, profile_completed, rating, total_reviews, created_at, last_login FROM users WHERE id = ?");
        $selectStmt->bind_param("i", $userId);
        $selectStmt->execute();
        $user = $selectStmt->get_result()->fetch_assoc();

        sendSuccess([
            'user' => $user,
            'token' => $token
        ], 'Account created successfully');
    } else {
        sendError('Failed to create account');
    }
}

function handleLogout() {
    // For stateless JWT-like tokens, logout is handled on client side
    // In a production app, you might want to implement token blacklisting
    sendSuccess(null, 'Logged out successfully');
}

function verifyToken() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Invalid or expired token', 401);
    }

    sendSuccess(['user' => $user], 'Token is valid');
}

function getProfile() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Authentication required', 401);
    }

    sendSuccess(['user' => $user]);
}

function updateProfile() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Authentication required', 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        sendError('Invalid input data');
    }

    $updateFields = [];
    $updateValues = [];
    $types = '';

    if (isset($input['fullName'])) {
        $updateFields[] = 'full_name = ?';
        $updateValues[] = sanitizeInput($input['fullName']);
        $types .= 's';
    }

    if (isset($input['phone'])) {
        $updateFields[] = 'phone = ?';
        $updateValues[] = sanitizeInput($input['phone']);
        $types .= 's';
    }

    if (isset($input['profileCompleted'])) {
        $updateFields[] = 'profile_completed = ?';
        $updateValues[] = $input['profileCompleted'] ? 1 : 0;
        $types .= 'i';
    }

    if (empty($updateFields)) {
        sendError('No valid fields to update');
    }

    $conn = getDBConnection();
    $updateValues[] = $user['id'];
    $types .= 'i';

    $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($query);

    $stmt->bind_param($types, ...$updateValues);

    if ($stmt->execute()) {
        // Get updated user data
        $selectStmt = $conn->prepare("SELECT id, email, full_name, phone, role, verification_status, is_active, profile_completed, rating, total_reviews, created_at, last_login FROM users WHERE id = ?");
        $selectStmt->bind_param("i", $user['id']);
        $selectStmt->execute();
        $updatedUser = $selectStmt->get_result()->fetch_assoc();

        sendSuccess(['user' => $updatedUser], 'Profile updated successfully');
    } else {
        sendError('Failed to update profile');
    }
}

function getUsers() {
    $user = getCurrentUser();

    if (!$user || $user['role'] !== 'admin') {
        sendError('Admin access required', 403);
    }

    $conn = getDBConnection();
    $role = isset($_GET['role']) ? sanitizeInput($_GET['role']) : null;

    if ($role) {
        $stmt = $conn->prepare("SELECT id, email, full_name, phone, role, verification_status, is_active, profile_completed, rating, total_reviews, created_at, last_login FROM users WHERE role = ? ORDER BY created_at DESC");
        $stmt->bind_param("s", $role);
    } else {
        $stmt = $conn->prepare("SELECT id, email, full_name, phone, role, verification_status, is_active, profile_completed, rating, total_reviews, created_at, last_login FROM users ORDER BY created_at DESC");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    sendSuccess(['users' => $users]);
}
?>