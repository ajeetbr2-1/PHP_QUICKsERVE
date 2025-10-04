<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        switch ($endpoint) {
            case 'list':
                getServices();
                break;
            case 'my-services':
                getMyServices();
                break;
            case 'service':
                getService();
                break;
            case 'categories':
                getCategories();
                break;
            default:
                sendError('Invalid endpoint', 404);
        }
        break;

    case 'POST':
        switch ($endpoint) {
            case 'create':
                createService();
                break;
            default:
                sendError('Invalid endpoint', 404);
        }
        break;

    case 'PUT':
        switch ($endpoint) {
            case 'update':
                updateService();
                break;
            default:
                sendError('Invalid endpoint', 404);
        }
        break;

    case 'DELETE':
        switch ($endpoint) {
            case 'delete':
                deleteService();
                break;
            default:
                sendError('Invalid endpoint', 404);
        }
        break;

    default:
        sendError('Method not allowed', 405);
}

function getServices() {
    $conn = getDBConnection();

    // Build query with filters
    $whereConditions = ["s.is_active = TRUE"];
    $params = [];
    $types = '';

    // Category filter
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $whereConditions[] = "s.category = ?";
        $params[] = sanitizeInput($_GET['category']);
        $types .= 's';
    }

    // Location filter
    if (isset($_GET['location']) && !empty($_GET['location'])) {
        $whereConditions[] = "s.location LIKE ?";
        $params[] = '%' . sanitizeInput($_GET['location']) . '%';
        $types .= 's';
    }

    // Price range filter
    if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
        $whereConditions[] = "s.price >= ?";
        $params[] = floatval($_GET['min_price']);
        $types .= 'd';
    }

    if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
        $whereConditions[] = "s.price <= ?";
        $params[] = floatval($_GET['max_price']);
        $types .= 'd';
    }

    // Search query
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchTerm = sanitizeInput($_GET['search']);
        $whereConditions[] = "(s.title LIKE ? OR s.description LIKE ? OR u.full_name LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $types .= 'sss';
    }

    $whereClause = implode(' AND ', $whereConditions);

    $query = "SELECT s.*, u.full_name as provider_name, u.phone as provider_phone, u.rating as provider_rating
              FROM services s
              JOIN users u ON s.provider_id = u.id
              WHERE $whereClause
              ORDER BY s.created_at DESC";

    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;

    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
    while ($row = $result->fetch_assoc()) {
        // Parse JSON fields
        $row['availability'] = json_decode($row['availability'], true);
        $row['working_hours'] = json_decode($row['working_hours'], true);
        $services[] = $row;
    }

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM services s JOIN users u ON s.provider_id = u.id WHERE $whereClause";
    $countStmt = $conn->prepare($countQuery);

    if (!empty(array_slice($params, 0, -2))) { // Remove limit and offset for count
        $countTypes = substr($types, 0, -2);
        $countParams = array_slice($params, 0, -2);
        $countStmt->bind_param($countTypes, ...$countParams);
    }

    $countStmt->execute();
    $totalCount = $countStmt->get_result()->fetch_assoc()['total'];

    sendSuccess([
        'services' => $services,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'total_pages' => ceil($totalCount / $limit)
        ]
    ]);
}

function getMyServices() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Authentication required', 401);
    }

    if ($user['role'] !== 'provider') {
        sendError('Only service providers can view their services', 403);
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM services WHERE provider_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
    while ($row = $result->fetch_assoc()) {
        $row['availability'] = json_decode($row['availability'], true);
        $row['working_hours'] = json_decode($row['working_hours'], true);
        $services[] = $row;
    }

    sendSuccess(['services' => $services]);
}

function getService() {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        sendError('Service ID is required');
    }

    $serviceId = intval($_GET['id']);
    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT s.*, u.full_name as provider_name, u.phone as provider_phone, u.rating as provider_rating
                           FROM services s
                           JOIN users u ON s.provider_id = u.id
                           WHERE s.id = ? AND s.is_active = TRUE");
    $stmt->bind_param("i", $serviceId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendError('Service not found', 404);
    }

    $service = $result->fetch_assoc();
    $service['availability'] = json_decode($service['availability'], true);
    $service['working_hours'] = json_decode($service['working_hours'], true);

    sendSuccess(['service' => $service]);
}

function getCategories() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT DISTINCT category FROM services WHERE is_active = TRUE ORDER BY category");

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }

    sendSuccess(['categories' => $categories]);
}

function createService() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Authentication required', 401);
    }

    if ($user['role'] !== 'provider') {
        sendError('Only service providers can create services', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        sendError('Invalid input data');
    }

    $requiredFields = ['title', 'description', 'category', 'price'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            sendError("$field is required");
        }
    }

    $title = sanitizeInput($input['title']);
    $description = sanitizeInput($input['description']);
    $category = sanitizeInput($input['category']);
    $price = floatval($input['price']);
    $location = isset($input['location']) ? sanitizeInput($input['location']) : '';

    // Handle availability and working hours
    $availability = isset($input['availability']) ? json_encode($input['availability']) : json_encode([
        'monday' => true, 'tuesday' => true, 'wednesday' => true,
        'thursday' => true, 'friday' => true, 'saturday' => true, 'sunday' => false
    ]);

    $workingHours = isset($input['workingHours']) ? json_encode($input['workingHours']) : json_encode([
        'start' => '09:00', 'end' => '18:00'
    ]);

    if ($price <= 0) {
        sendError('Price must be greater than 0');
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO services (provider_id, title, description, category, price, location, availability, working_hours)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdsss", $user['id'], $title, $description, $category, $price, $location, $availability, $workingHours);

    if ($stmt->execute()) {
        $serviceId = $conn->insert_id;

        // Get the created service
        $selectStmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
        $selectStmt->bind_param("i", $serviceId);
        $selectStmt->execute();
        $service = $selectStmt->get_result()->fetch_assoc();

        $service['availability'] = json_decode($service['availability'], true);
        $service['working_hours'] = json_decode($service['working_hours'], true);

        sendSuccess(['service' => $service], 'Service created successfully');
    } else {
        sendError('Failed to create service');
    }
}

function updateService() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Authentication required', 401);
    }

    if ($user['role'] !== 'provider') {
        sendError('Only service providers can update services', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        sendError('Service ID is required');
    }

    $serviceId = intval($input['id']);

    // Check if service belongs to user
    $conn = getDBConnection();
    $checkStmt = $conn->prepare("SELECT id FROM services WHERE id = ? AND provider_id = ?");
    $checkStmt->bind_param("ii", $serviceId, $user['id']);
    $checkStmt->execute();

    if ($checkStmt->get_result()->num_rows === 0) {
        sendError('Service not found or access denied', 403);
    }

    $updateFields = [];
    $updateValues = [];
    $types = '';

    if (isset($input['title'])) {
        $updateFields[] = 'title = ?';
        $updateValues[] = sanitizeInput($input['title']);
        $types .= 's';
    }

    if (isset($input['description'])) {
        $updateFields[] = 'description = ?';
        $updateValues[] = sanitizeInput($input['description']);
        $types .= 's';
    }

    if (isset($input['category'])) {
        $updateFields[] = 'category = ?';
        $updateValues[] = sanitizeInput($input['category']);
        $types .= 's';
    }

    if (isset($input['price'])) {
        $price = floatval($input['price']);
        if ($price <= 0) {
            sendError('Price must be greater than 0');
        }
        $updateFields[] = 'price = ?';
        $updateValues[] = $price;
        $types .= 'd';
    }

    if (isset($input['location'])) {
        $updateFields[] = 'location = ?';
        $updateValues[] = sanitizeInput($input['location']);
        $types .= 's';
    }

    if (isset($input['availability'])) {
        $updateFields[] = 'availability = ?';
        $updateValues[] = json_encode($input['availability']);
        $types .= 's';
    }

    if (isset($input['workingHours'])) {
        $updateFields[] = 'working_hours = ?';
        $updateValues[] = json_encode($input['workingHours']);
        $types .= 's';
    }

    if (isset($input['isActive'])) {
        $updateFields[] = 'is_active = ?';
        $updateValues[] = $input['isActive'] ? 1 : 0;
        $types .= 'i';
    }

    if (empty($updateFields)) {
        sendError('No valid fields to update');
    }

    $updateValues[] = $serviceId;
    $types .= 'i';

    $query = "UPDATE services SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$updateValues);

    if ($stmt->execute()) {
        // Get updated service
        $selectStmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
        $selectStmt->bind_param("i", $serviceId);
        $selectStmt->execute();
        $service = $selectStmt->get_result()->fetch_assoc();

        $service['availability'] = json_decode($service['availability'], true);
        $service['working_hours'] = json_decode($service['working_hours'], true);

        sendSuccess(['service' => $service], 'Service updated successfully');
    } else {
        sendError('Failed to update service');
    }
}

function deleteService() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Authentication required', 401);
    }

    if ($user['role'] !== 'provider') {
        sendError('Only service providers can delete services', 403);
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        sendError('Service ID is required');
    }

    $serviceId = intval($_GET['id']);

    $conn = getDBConnection();

    // Check if service belongs to user
    $checkStmt = $conn->prepare("SELECT id FROM services WHERE id = ? AND provider_id = ?");
    $checkStmt->bind_param("ii", $serviceId, $user['id']);
    $checkStmt->execute();

    if ($checkStmt->get_result()->num_rows === 0) {
        sendError('Service not found or access denied', 403);
    }

    // Soft delete by setting is_active to false
    $stmt = $conn->prepare("UPDATE services SET is_active = FALSE WHERE id = ?");
    $stmt->bind_param("i", $serviceId);

    if ($stmt->execute()) {
        sendSuccess(null, 'Service deleted successfully');
    } else {
        sendError('Failed to delete service');
    }
}
?>