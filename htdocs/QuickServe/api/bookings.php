<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        switch ($endpoint) {
            case 'list':
                getBookings();
                break;
            case 'booking':
                getBooking();
                break;
            default:
                sendError('Invalid endpoint', 404);
        }
        break;

    case 'POST':
        switch ($endpoint) {
            case 'create':
                createBooking();
                break;
            default:
                sendError('Invalid endpoint', 404);
        }
        break;

    case 'PUT':
        switch ($endpoint) {
            case 'update-status':
                updateBookingStatus();
                break;
            default:
                sendError('Invalid endpoint', 404);
        }
        break;

    default:
        sendError('Method not allowed', 405);
}

function getBookings() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Authentication required', 401);
    }

    $conn = getDBConnection();

    // Different queries based on user role
    if ($user['role'] === 'admin') {
        $stmt = $conn->prepare("
            SELECT b.*, s.title as service_title, s.price as service_price,
                   c.full_name as customer_name, c.phone as customer_phone,
                   p.full_name as provider_name, p.phone as provider_phone
            FROM bookings b
            JOIN services s ON b.service_id = s.id
            JOIN users c ON b.customer_id = c.id
            JOIN users p ON b.provider_id = p.id
            ORDER BY b.created_at DESC
        ");
    } elseif ($user['role'] === 'provider') {
        $stmt = $conn->prepare("
            SELECT b.*, s.title as service_title, s.price as service_price,
                   c.full_name as customer_name, c.phone as customer_phone
            FROM bookings b
            JOIN services s ON b.service_id = s.id
            JOIN users c ON b.customer_id = c.id
            WHERE b.provider_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->bind_param("i", $user['id']);
    } else { // customer
        $stmt = $conn->prepare("
            SELECT b.*, s.title as service_title, s.price as service_price,
                   p.full_name as provider_name, p.phone as provider_phone
            FROM bookings b
            JOIN services s ON b.service_id = s.id
            JOIN users p ON b.provider_id = p.id
            WHERE b.customer_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->bind_param("i", $user['id']);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }

    sendSuccess(['bookings' => $bookings]);
}

function getBooking() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Authentication required', 401);
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        sendError('Booking ID is required');
    }

    $bookingId = intval($_GET['id']);
    $conn = getDBConnection();

    // Check if user has access to this booking
    $accessCheck = "";
    $params = [$bookingId];
    $types = 'i';

    if ($user['role'] === 'admin') {
        // Admin can see all bookings
    } elseif ($user['role'] === 'provider') {
        $accessCheck = "AND b.provider_id = ?";
        $params[] = $user['id'];
        $types .= 'i';
    } else { // customer
        $accessCheck = "AND b.customer_id = ?";
        $params[] = $user['id'];
        $types .= 'i';
    }

    $stmt = $conn->prepare("
        SELECT b.*, s.title as service_title, s.description as service_description,
               s.price as service_price, s.category as service_category,
               c.full_name as customer_name, c.phone as customer_phone, c.email as customer_email,
               p.full_name as provider_name, p.phone as provider_phone, p.email as provider_email
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        JOIN users c ON b.customer_id = c.id
        JOIN users p ON b.provider_id = p.id
        WHERE b.id = ? $accessCheck
    ");

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendError('Booking not found or access denied', 404);
    }

    $booking = $result->fetch_assoc();
    sendSuccess(['booking' => $booking]);
}

function createBooking() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Authentication required', 401);
    }

    if ($user['role'] !== 'customer') {
        sendError('Only customers can create bookings', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        sendError('Invalid input data');
    }

    $requiredFields = ['serviceId', 'bookingDate', 'bookingTime'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            sendError("$field is required");
        }
    }

    $serviceId = intval($input['serviceId']);
    $bookingDate = sanitizeInput($input['bookingDate']);
    $bookingTime = sanitizeInput($input['bookingTime']);
    $notes = isset($input['notes']) ? sanitizeInput($input['notes']) : '';

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $bookingDate)) {
        sendError('Invalid date format. Use YYYY-MM-DD');
    }

    // Validate time format
    if (!preg_match('/^\d{2}:\d{2}$/', $bookingTime)) {
        sendError('Invalid time format. Use HH:MM');
    }

    // Check if booking date is not in the past
    $bookingDateTime = strtotime("$bookingDate $bookingTime");
    if ($bookingDateTime < time()) {
        sendError('Cannot book for past dates');
    }

    $conn = getDBConnection();

    // Get service details and check if it exists and is active
    $serviceStmt = $conn->prepare("SELECT * FROM services WHERE id = ? AND is_active = TRUE");
    $serviceStmt->bind_param("i", $serviceId);
    $serviceStmt->execute();
    $serviceResult = $serviceStmt->get_result();

    if ($serviceResult->num_rows === 0) {
        sendError('Service not found or not available');
    }

    $service = $serviceResult->fetch_assoc();
    $providerId = $service['provider_id'];
    $totalAmount = $service['price'];

    // Check if customer is not booking their own service
    if ($user['id'] == $providerId) {
        sendError('Cannot book your own service');
    }

    // Check for conflicting bookings (same service, same date/time)
    $conflictStmt = $conn->prepare("
        SELECT id FROM bookings
        WHERE service_id = ? AND booking_date = ? AND booking_time = ?
        AND status NOT IN ('cancelled', 'completed')
    ");
    $conflictStmt->bind_param("iss", $serviceId, $bookingDate, $bookingTime);
    $conflictStmt->execute();

    if ($conflictStmt->get_result()->num_rows > 0) {
        sendError('This time slot is already booked');
    }

    // Check provider's availability for the day
    $availability = json_decode($service['availability'], true);
    $dayOfWeek = strtolower(date('l', strtotime($bookingDate)));

    if (isset($availability[$dayOfWeek]) && !$availability[$dayOfWeek]) {
        sendError('Service provider is not available on this day');
    }

    // Check working hours
    $workingHours = json_decode($service['working_hours'], true);
    $bookingHour = date('H:i', strtotime($bookingTime));

    if ($bookingHour < $workingHours['start'] || $bookingHour > $workingHours['end']) {
        sendError('Booking time is outside provider\'s working hours');
    }

    // Create booking
    $insertStmt = $conn->prepare("
        INSERT INTO bookings (customer_id, service_id, provider_id, booking_date, booking_time, notes, total_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->bind_param("iiisssd", $user['id'], $serviceId, $providerId, $bookingDate, $bookingTime, $notes, $totalAmount);

    if ($insertStmt->execute()) {
        $bookingId = $conn->insert_id;

        // Get the created booking with service details
        $selectStmt = $conn->prepare("
            SELECT b.*, s.title as service_title, s.price as service_price,
                   p.full_name as provider_name, p.phone as provider_phone
            FROM bookings b
            JOIN services s ON b.service_id = s.id
            JOIN users p ON b.provider_id = p.id
            WHERE b.id = ?
        ");
        $selectStmt->bind_param("i", $bookingId);
        $selectStmt->execute();
        $booking = $selectStmt->get_result()->fetch_assoc();

        sendSuccess(['booking' => $booking], 'Booking created successfully');
    } else {
        sendError('Failed to create booking');
    }
}

function updateBookingStatus() {
    $user = getCurrentUser();

    if (!$user) {
        sendError('Authentication required', 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['bookingId']) || !isset($input['status'])) {
        sendError('Booking ID and status are required');
    }

    $bookingId = intval($input['bookingId']);
    $newStatus = sanitizeInput($input['status']);

    $validStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        sendError('Invalid status');
    }

    $conn = getDBConnection();

    // Check if user has permission to update this booking
    $accessCheck = "";
    $params = [$bookingId];
    $types = 'i';

    if ($user['role'] === 'admin') {
        // Admin can update any booking
    } elseif ($user['role'] === 'provider') {
        $accessCheck = "AND provider_id = ?";
        $params[] = $user['id'];
        $types .= 'i';
    } elseif ($user['role'] === 'customer') {
        $accessCheck = "AND customer_id = ?";
        $params[] = $user['id'];
        $types .= 'i';

        // Customers can only cancel their bookings
        if ($newStatus !== 'cancelled') {
            sendError('Customers can only cancel bookings', 403);
        }
    } else {
        sendError('Insufficient permissions', 403);
    }

    // Check current booking status
    $checkStmt = $conn->prepare("SELECT status FROM bookings WHERE id = ? $accessCheck");
    $checkStmt->bind_param($types, ...$params);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        sendError('Booking not found or access denied', 404);
    }

    $currentStatus = $result->fetch_assoc()['status'];

    // Validate status transitions
    $allowedTransitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [], // Final state
        'cancelled' => []  // Final state
    ];

    if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
        sendError("Cannot change status from $currentStatus to $newStatus");
    }

    // Update booking status
    $params[] = $newStatus;
    $types .= 's';

    $updateStmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? $accessCheck");
    $updateStmt->bind_param('s' . $types, $newStatus, ...array_slice($params, 0, -1));

    if ($updateStmt->execute()) {
        sendSuccess(null, 'Booking status updated successfully');
    } else {
        sendError('Failed to update booking status');
    }
}
?>