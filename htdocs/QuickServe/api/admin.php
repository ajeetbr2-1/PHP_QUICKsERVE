<?php
require_once 'config.php';

$conn = getDBConnection();
$currentUser = getCurrentUser();

// Check if user is admin
if (!$currentUser || $currentUser['role'] !== 'admin') {
    sendError('Access denied. Admin privileges required.', 403);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get-users':
        getUsersList();
        break;
    case 'block-user':
        blockUser();
        break;
    case 'unblock-user':
        unblockUser();
        break;
    case 'verify-user':
        verifyUser();
        break;
    case 'unverify-user':
        unverifyUser();
        break;
    case 'get-dashboard-stats':
        getDashboardStats();
        break;
    case 'get-admin-actions':
        getAdminActions();
        break;
    case 'get-certificates':
        getCertificatesAdmin();
        break;
    case 'approve-certificate':
        approveCertificate();
        break;
    case 'reject-certificate':
        rejectCertificate();
        break;
    case 'delete-service':
        deleteService();
        break;
    default:
        sendError('Invalid action');
}

function getUsersList() {
    global $conn;
    
    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT u.*, 
                   COALESCE(pp.business_name, '') as business_name,
                   COALESCE(pp.experience_years, 0) as experience_years,
                   (SELECT COUNT(*) FROM bookings WHERE customer_id = u.id OR provider_id = u.id) as total_bookings,
                   (SELECT COUNT(*) FROM services WHERE provider_id = u.id) as total_services
            FROM users u 
            LEFT JOIN provider_profiles pp ON u.id = pp.user_id
            WHERE 1=1";
    
    if ($filter !== 'all') {
        $sql .= " AND u.role = '$filter'";
    }
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
    }
    
    $sql .= " ORDER BY u.created_at DESC";
    
    $result = $conn->query($sql);
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        // Remove password from response
        unset($row['password']);
        $users[] = $row;
    }
    
    sendSuccess(['users' => $users]);
}

function blockUser() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['userId'] ?? 0;
    $reason = $input['reason'] ?? 'Admin action';
    
    if (!$userId) {
        sendError('User ID is required');
    }
    
    // Update user status
    $stmt = $conn->prepare("UPDATE users SET is_blocked = TRUE, blocked_date = NOW(), blocked_reason = ? WHERE id = ?");
    $stmt->bind_param("si", $reason, $userId);
    
    if ($stmt->execute()) {
        // Log admin action
        logAdminAction($currentUser['id'], 'block_user', $userId, null, null, $reason);
        sendSuccess(['message' => 'User blocked successfully']);
    } else {
        sendError('Failed to block user');
    }
}

function unblockUser() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['userId'] ?? 0;
    
    if (!$userId) {
        sendError('User ID is required');
    }
    
    // Update user status
    $stmt = $conn->prepare("UPDATE users SET is_blocked = FALSE, blocked_date = NULL, blocked_reason = NULL WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        // Log admin action
        logAdminAction($currentUser['id'], 'unblock_user', $userId);
        sendSuccess(['message' => 'User unblocked successfully']);
    } else {
        sendError('Failed to unblock user');
    }
}

function verifyUser() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['userId'] ?? 0;
    
    if (!$userId) {
        sendError('User ID is required');
    }
    
    // Update user verification status
    $stmt = $conn->prepare("UPDATE users SET is_verified = TRUE, verification_date = NOW() WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        // Log admin action
        logAdminAction($currentUser['id'], 'verify_user', $userId);
        sendSuccess(['message' => 'User verified successfully']);
    } else {
        sendError('Failed to verify user');
    }
}

function unverifyUser() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['userId'] ?? 0;
    
    if (!$userId) {
        sendError('User ID is required');
    }
    
    // Update user verification status
    $stmt = $conn->prepare("UPDATE users SET is_verified = FALSE, verification_date = NULL WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        // Log admin action
        logAdminAction($currentUser['id'], 'unverify_user', $userId);
        sendSuccess(['message' => 'User verification removed successfully']);
    } else {
        sendError('Failed to remove verification');
    }
}

function getDashboardStats() {
    global $conn;
    
    // Get counts
    $totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $totalProviders = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider'")->fetch_assoc()['count'];
    $totalCustomers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];
    $totalServices = $conn->query("SELECT COUNT(*) as count FROM services")->fetch_assoc()['count'];
    $totalBookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
    $verifiedProviders = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider' AND is_verified = TRUE")->fetch_assoc()['count'];
    $blockedUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_blocked = TRUE")->fetch_assoc()['count'];
    
    // Get recent activity
    $recentUsers = [];
    $result = $conn->query("SELECT full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        $recentUsers[] = $row;
    }
    
    $recentBookings = [];
    $result = $conn->query("SELECT b.*, u.full_name as customer_name, s.title as service_title 
                           FROM bookings b 
                           JOIN users u ON b.customer_id = u.id 
                           JOIN services s ON b.service_id = s.id 
                           ORDER BY b.created_at DESC LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        $recentBookings[] = $row;
    }
    
    // Monthly stats
    $monthlyStats = [];
    $result = $conn->query("SELECT 
                              DATE_FORMAT(created_at, '%Y-%m') as month,
                              COUNT(*) as count,
                              'users' as type
                           FROM users 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                           GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                           ORDER BY month DESC");
    while ($row = $result->fetch_assoc()) {
        $monthlyStats[] = $row;
    }
    
    sendSuccess([
        'stats' => [
            'totalUsers' => (int)$totalUsers,
            'totalProviders' => (int)$totalProviders,
            'totalCustomers' => (int)$totalCustomers,
            'totalServices' => (int)$totalServices,
            'totalBookings' => (int)$totalBookings,
            'verifiedProviders' => (int)$verifiedProviders,
            'blockedUsers' => (int)$blockedUsers
        ],
        'recentUsers' => $recentUsers,
        'recentBookings' => $recentBookings,
        'monthlyStats' => $monthlyStats
    ]);
}

function getCertificatesAdmin() {
    global $conn;
    $status = $_GET['status'] ?? 'pending';
    $allowed = ['pending','verified','rejected'];
    if (!in_array($status, $allowed)) { $status = 'pending'; }
    $stmt = $conn->prepare("SELECT c.*, u.full_name as user_name, u.email as user_email FROM certificates c JOIN users u ON c.user_id = u.id WHERE c.verification_status = ? ORDER BY c.issue_date DESC");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($row = $res->fetch_assoc()) { $items[] = $row; }
    sendSuccess(['certificates' => $items]);
}

function getAdminActions() {
    global $conn;
    
    $result = $conn->query("SELECT aa.*, u.full_name as admin_name, tu.full_name as target_user_name 
                           FROM admin_actions aa 
                           JOIN users u ON aa.admin_id = u.id 
                           LEFT JOIN users tu ON aa.target_user_id = tu.id 
                           ORDER BY aa.created_at DESC LIMIT 50");
    
    $actions = [];
    while ($row = $result->fetch_assoc()) {
        $actions[] = $row;
    }
    
    sendSuccess(['actions' => $actions]);
}

function approveCertificate() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $certificateId = $input['certificateId'] ?? 0;
    
    if (!$certificateId) {
        sendError('Certificate ID is required');
    }
    
    // Update certificate status
    $stmt = $conn->prepare("UPDATE certificates SET verification_status = 'verified' WHERE id = ?");
    $stmt->bind_param("i", $certificateId);
    
    if ($stmt->execute()) {
        // Log admin action
        logAdminAction($currentUser['id'], 'approve_certificate', null, null, $certificateId);
        sendSuccess(['message' => 'Certificate approved successfully']);
    } else {
        sendError('Failed to approve certificate');
    }
}

function rejectCertificate() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $certificateId = $input['certificateId'] ?? 0;
    $reason = $input['reason'] ?? '';
    
    if (!$certificateId) {
        sendError('Certificate ID is required');
    }
    
    // Update certificate status
    $stmt = $conn->prepare("UPDATE certificates SET verification_status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $certificateId);
    
    if ($stmt->execute()) {
        // Log admin action
        logAdminAction($currentUser['id'], 'reject_certificate', null, null, $certificateId, $reason);
        sendSuccess(['message' => 'Certificate rejected successfully']);
    } else {
        sendError('Failed to reject certificate');
    }
}

function deleteService() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serviceId = $input['serviceId'] ?? 0;
    $reason = $input['reason'] ?? 'Admin deletion';
    
    if (!$serviceId) {
        sendError('Service ID is required');
    }
    
    // Delete service
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $serviceId);
    
    if ($stmt->execute()) {
        // Log admin action
        logAdminAction($currentUser['id'], 'delete_service', null, $serviceId, null, $reason);
        sendSuccess(['message' => 'Service deleted successfully']);
    } else {
        sendError('Failed to delete service');
    }
}

function logAdminAction($adminId, $actionType, $targetUserId = null, $targetServiceId = null, $targetCertificateId = null, $notes = null) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO admin_actions 
                           (admin_id, action_type, target_user_id, target_service_id, target_certificate_id, notes) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiss", $adminId, $actionType, $targetUserId, $targetServiceId, $targetCertificateId, $notes);
    $stmt->execute();
}
?>