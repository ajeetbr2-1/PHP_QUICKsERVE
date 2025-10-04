<?php
require_once 'config.php';

$conn = getDBConnection();
$currentUser = getCurrentUser();

if (!$currentUser) {
    sendError('Authentication required', 401);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get-profile':
        getProfile();
        break;
    case 'update-profile':
        updateProfile();
        break;
    case 'get-portfolio':
        getPortfolio();
        break;
    case 'add-portfolio-item':
        addPortfolioItem();
        break;
    case 'update-portfolio-item':
        updatePortfolioItem();
        break;
    case 'delete-portfolio-item':
        deletePortfolioItem();
        break;
    case 'get-certificates':
        getCertificates();
        break;
    case 'add-certificate':
        addCertificate();
        break;
    case 'delete-certificate':
        deleteCertificate();
        break;
    case 'get-business-hours':
        getBusinessHours();
        break;
    case 'update-business-hours':
        updateBusinessHours();
        break;
    case 'get-provider-profile':
        getProviderProfile();
        break;
    case 'update-provider-profile':
        updateProviderProfile();
        break;
    case 'get-service-areas':
        getServiceAreas();
        break;
    case 'add-service-area':
        addServiceArea();
        break;
    case 'update-service-area':
        updateServiceArea();
        break;
    case 'delete-service-area':
        deleteServiceArea();
        break;
    case 'get-work-experience':
        getWorkExperience();
        break;
    case 'add-work-experience':
        addWorkExperience();
        break;
    case 'update-work-experience':
        updateWorkExperience();
        break;
    case 'delete-work-experience':
        deleteWorkExperience();
        break;
    default:
        sendError('Invalid action');
}

function getProfile() {
    global $conn, $currentUser;
    
    $userId = $_GET['userId'] ?? $currentUser['id'];
    
    // Get basic user info
    $stmt = $conn->prepare("SELECT u.*, 
                                  COALESCE(u.rating, 0) as rating,
                                  COALESCE(u.total_reviews, 0) as total_reviews
                           FROM users u WHERE u.id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        sendError('User not found');
    }
    
    // Remove password
    unset($user['password']);
    
    // Get additional profile data based on role
    if ($user['role'] === 'provider') {
        // Get provider profile
        $stmt = $conn->prepare("SELECT * FROM provider_profiles WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $providerProfile = $stmt->get_result()->fetch_assoc();
        
        $user['provider_profile'] = $providerProfile;
        
        // Get portfolio count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM portfolio_items WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user['portfolio_count'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get certificates count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM certificates WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user['certificates_count'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get services count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM services WHERE provider_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user['services_count'] = $stmt->get_result()->fetch_assoc()['count'];
    }
    
    // Get total bookings
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE customer_id = ? OR provider_id = ?");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $user['total_bookings'] = $stmt->get_result()->fetch_assoc()['count'];
    
    sendSuccess(['profile' => $user]);
}

function updateProfile() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fullName = $input['fullName'] ?? '';
    $phone = $input['phone'] ?? '';
    $bio = $input['bio'] ?? '';
    $socialLinks = json_encode($input['socialLinks'] ?? []);
    
    if (empty($fullName)) {
        sendError('Full name is required');
    }
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, bio = ?, social_links = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $fullName, $phone, $bio, $socialLinks, $currentUser['id']);
    
    if ($stmt->execute()) {
        sendSuccess(['message' => 'Profile updated successfully']);
    } else {
        sendError('Failed to update profile');
    }
}

function getPortfolio() {
    global $conn, $currentUser;
    
    $userId = $_GET['userId'] ?? $currentUser['id'];
    
    $stmt = $conn->prepare("SELECT * FROM portfolio_items WHERE user_id = ? ORDER BY is_featured DESC, created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $portfolio = [];
    while ($row = $result->fetch_assoc()) {
        $portfolio[] = $row;
    }
    
    sendSuccess(['portfolio' => $portfolio]);
}

function addPortfolioItem() {
    global $conn, $currentUser;
    
    if ($currentUser['role'] !== 'provider') {
        sendError('Only providers can add portfolio items');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $imageUrl = $input['imageUrl'] ?? '';
    $videoUrl = $input['videoUrl'] ?? '';
    $projectDate = $input['projectDate'] ?? null;
    $projectLocation = $input['projectLocation'] ?? '';
    $projectCost = $input['projectCost'] ?? 0;
    $clientName = $input['clientName'] ?? '';
    $isFeatured = $input['isFeatured'] ?? false;
    
    if (empty($title)) {
        sendError('Title is required');
    }
    
    $stmt = $conn->prepare("INSERT INTO portfolio_items 
                           (user_id, title, description, image_url, video_url, project_date, project_location, project_cost, client_name, is_featured) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssdssi", $currentUser['id'], $title, $description, $imageUrl, $videoUrl, $projectDate, $projectLocation, $projectCost, $clientName, $isFeatured);
    
    if ($stmt->execute()) {
        sendSuccess(['message' => 'Portfolio item added successfully', 'id' => $conn->insert_id]);
    } else {
        sendError('Failed to add portfolio item');
    }
}

function updatePortfolioItem() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $itemId = $input['id'] ?? 0;
    
    if (!$itemId) {
        sendError('Portfolio item ID is required');
    }
    
    // Check ownership
    $stmt = $conn->prepare("SELECT user_id FROM portfolio_items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result || $result['user_id'] != $currentUser['id']) {
        sendError('Portfolio item not found or access denied');
    }
    
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $imageUrl = $input['imageUrl'] ?? '';
    $videoUrl = $input['videoUrl'] ?? '';
    $projectDate = $input['projectDate'] ?? null;
    $projectLocation = $input['projectLocation'] ?? '';
    $projectCost = $input['projectCost'] ?? 0;
    $clientName = $input['clientName'] ?? '';
    $isFeatured = $input['isFeatured'] ?? false;
    
    $stmt = $conn->prepare("UPDATE portfolio_items SET 
                           title = ?, description = ?, image_url = ?, video_url = ?, 
                           project_date = ?, project_location = ?, project_cost = ?, 
                           client_name = ?, is_featured = ? 
                           WHERE id = ?");
    $stmt->bind_param("ssssssdssi", $title, $description, $imageUrl, $videoUrl, $projectDate, $projectLocation, $projectCost, $clientName, $isFeatured, $itemId);
    
    if ($stmt->execute()) {
        sendSuccess(['message' => 'Portfolio item updated successfully']);
    } else {
        sendError('Failed to update portfolio item');
    }
}

function deletePortfolioItem() {
    global $conn, $currentUser;
    
    $itemId = $_GET['id'] ?? 0;
    
    if (!$itemId) {
        sendError('Portfolio item ID is required');
    }
    
    // Check ownership
    $stmt = $conn->prepare("SELECT user_id FROM portfolio_items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result || $result['user_id'] != $currentUser['id']) {
        sendError('Portfolio item not found or access denied');
    }
    
    $stmt = $conn->prepare("DELETE FROM portfolio_items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    
    if ($stmt->execute()) {
        sendSuccess(['message' => 'Portfolio item deleted successfully']);
    } else {
        sendError('Failed to delete portfolio item');
    }
}

function getCertificates() {
    global $conn, $currentUser;
    
    $userId = $_GET['userId'] ?? $currentUser['id'];
    
    $stmt = $conn->prepare("SELECT * FROM certificates WHERE user_id = ? ORDER BY issue_date DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $certificates = [];
    while ($row = $result->fetch_assoc()) {
        $certificates[] = $row;
    }
    
    sendSuccess(['certificates' => $certificates]);
}

function addCertificate() {
    global $conn, $currentUser;
    
    if ($currentUser['role'] !== 'provider') {
        sendError('Only providers can add certificates');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $title = $input['title'] ?? '';
    $issuingOrganization = $input['issuingOrganization'] ?? '';
    $certificateUrl = $input['certificateUrl'] ?? '';
    $issueDate = $input['issueDate'] ?? null;
    $expiryDate = $input['expiryDate'] ?? null;
    $certificateType = $input['certificateType'] ?? 'certification';
    $description = $input['description'] ?? '';
    
    if (empty($title) || empty($issuingOrganization)) {
        sendError('Title and issuing organization are required');
    }
    
    $stmt = $conn->prepare("INSERT INTO certificates 
                           (user_id, title, issuing_organization, certificate_url, issue_date, expiry_date, certificate_type, description) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $currentUser['id'], $title, $issuingOrganization, $certificateUrl, $issueDate, $expiryDate, $certificateType, $description);
    
    if ($stmt->execute()) {
        sendSuccess(['message' => 'Certificate added successfully', 'id' => $conn->insert_id]);
    } else {
        sendError('Failed to add certificate');
    }
}

function deleteCertificate() {
    global $conn, $currentUser;
    
    $certId = $_GET['id'] ?? 0;
    
    if (!$certId) {
        sendError('Certificate ID is required');
    }
    
    // Check ownership
    $stmt = $conn->prepare("SELECT user_id FROM certificates WHERE id = ?");
    $stmt->bind_param("i", $certId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result || $result['user_id'] != $currentUser['id']) {
        sendError('Certificate not found or access denied');
    }
    
    $stmt = $conn->prepare("DELETE FROM certificates WHERE id = ?");
    $stmt->bind_param("i", $certId);
    
    if ($stmt->execute()) {
        sendSuccess(['message' => 'Certificate deleted successfully']);
    } else {
        sendError('Failed to delete certificate');
    }
}

function getBusinessHours() {
    global $conn, $currentUser;
    
    $userId = $_GET['userId'] ?? $currentUser['id'];
    
    $stmt = $conn->prepare("SELECT * FROM business_hours WHERE user_id = ? ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $businessHours = [];
    while ($row = $result->fetch_assoc()) {
        $businessHours[] = $row;
    }
    
    sendSuccess(['businessHours' => $businessHours]);
}

function updateBusinessHours() {
    global $conn, $currentUser;
    
    if ($currentUser['role'] !== 'provider') {
        sendError('Only providers can update business hours');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $businessHours = $input['businessHours'] ?? [];
    
    // Delete existing hours
    $stmt = $conn->prepare("DELETE FROM business_hours WHERE user_id = ?");
    $stmt->bind_param("i", $currentUser['id']);
    $stmt->execute();
    
    // Insert new hours
    foreach ($businessHours as $hours) {
        $dayOfWeek = $hours['dayOfWeek'];
        $isOpen = $hours['isOpen'] ?? false;
        $openTime = $hours['openTime'] ?? null;
        $closeTime = $hours['closeTime'] ?? null;
        $is24Hours = $hours['is24Hours'] ?? false;
        
        $stmt = $conn->prepare("INSERT INTO business_hours 
                               (user_id, day_of_week, is_open, open_time, close_time, is_24_hours) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $currentUser['id'], $dayOfWeek, $isOpen, $openTime, $closeTime, $is24Hours);
        $stmt->execute();
    }
    
    sendSuccess(['message' => 'Business hours updated successfully']);
}

function getProviderProfile() {
    global $conn, $currentUser;
    
    $userId = $_GET['userId'] ?? $currentUser['id'];
    
    $stmt = $conn->prepare("SELECT * FROM provider_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    sendSuccess(['providerProfile' => $result]);
}

function updateProviderProfile() {
    global $conn, $currentUser;
    
    if ($currentUser['role'] !== 'provider') {
        sendError('Only providers can update provider profile');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $businessName = $input['businessName'] ?? '';
    $experienceYears = $input['experienceYears'] ?? 0;
    $hourlyRate = $input['hourlyRate'] ?? 0;
    $serviceRadius = $input['serviceRadius'] ?? 10;
    $languagesSpoken = json_encode($input['languagesSpoken'] ?? []);
    $specializations = json_encode($input['specializations'] ?? []);
    $businessLicense = $input['businessLicense'] ?? '';
    $insuranceDetails = $input['insuranceDetails'] ?? '';
    $emergencyServices = $input['emergencyServices'] ?? false;
    $freeConsultation = $input['freeConsultation'] ?? false;
    
    // Check if profile exists
    $stmt = $conn->prepare("SELECT id FROM provider_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $currentUser['id']);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    
    if ($exists) {
        // Update existing
        $stmt = $conn->prepare("UPDATE provider_profiles SET 
                               business_name = ?, experience_years = ?, hourly_rate = ?, 
                               service_radius = ?, languages_spoken = ?, specializations = ?, 
                               business_license = ?, insurance_details = ?, emergency_services = ?, 
                               free_consultation = ? 
                               WHERE user_id = ?");
        $stmt->bind_param("sidsssssiii", $businessName, $experienceYears, $hourlyRate, $serviceRadius, 
                         $languagesSpoken, $specializations, $businessLicense, $insuranceDetails, 
                         $emergencyServices, $freeConsultation, $currentUser['id']);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO provider_profiles 
                               (user_id, business_name, experience_years, hourly_rate, service_radius, 
                                languages_spoken, specializations, business_license, insurance_details, 
                                emergency_services, free_consultation) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isidsssssii", $currentUser['id'], $businessName, $experienceYears, $hourlyRate, 
                         $serviceRadius, $languagesSpoken, $specializations, $businessLicense, 
                         $insuranceDetails, $emergencyServices, $freeConsultation);
    }
    
    if ($stmt->execute()) {
        sendSuccess(['message' => 'Provider profile updated successfully']);
    } else {
        sendError('Failed to update provider profile');
    }
}

function getServiceAreas() {
    global $conn, $currentUser;
    $userId = $_GET['userId'] ?? $currentUser['id'];
    $stmt = $conn->prepare("SELECT * FROM service_areas WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $areas = [];
    while ($row = $result->fetch_assoc()) { $areas[] = $row; }
    sendSuccess(['serviceAreas' => $areas]);
}

function addServiceArea() {
    global $conn, $currentUser;
    if ($currentUser['role'] !== 'provider') { sendError('Only providers can add service areas'); }
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO service_areas (user_id, area_name, city, state, pincode, latitude, longitude, service_charge, travel_time_minutes, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssdddis", $currentUser['id'], $input['areaName'], $input['city'], $input['state'], $input['pincode'], $input['latitude'], $input['longitude'], $input['serviceCharge'], $input['travelTimeMinutes'], $input['isPrimary']);
    if ($stmt->execute()) { sendSuccess(['id' => $conn->insert_id, 'message' => 'Service area added']); } else { sendError('Failed to add service area'); }
}

function updateServiceArea() {
    global $conn, $currentUser;
    if ($currentUser['role'] !== 'provider') { sendError('Only providers can update service areas'); }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'])) { sendError('Service area ID is required'); }
    // Ensure ownership
    $check = $conn->prepare("SELECT id FROM service_areas WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $input['id'], $currentUser['id']);
    $check->execute();
    if ($check->get_result()->num_rows === 0) { sendError('Service area not found or access denied'); }
    $stmt = $conn->prepare("UPDATE service_areas SET area_name = ?, city = ?, state = ?, pincode = ?, latitude = ?, longitude = ?, service_charge = ?, travel_time_minutes = ?, is_primary = ? WHERE id = ?");
    $stmt->bind_param("ssssdddisi", $input['areaName'], $input['city'], $input['state'], $input['pincode'], $input['latitude'], $input['longitude'], $input['serviceCharge'], $input['travelTimeMinutes'], $input['isPrimary'], $input['id']);
    if ($stmt->execute()) { sendSuccess(['message' => 'Service area updated']); } else { sendError('Failed to update service area'); }
}

function deleteServiceArea() {
    global $conn, $currentUser;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id) { sendError('Service area ID is required'); }
    $stmt = $conn->prepare("DELETE FROM service_areas WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $currentUser['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) { sendSuccess(['message' => 'Service area deleted']); } else { sendError('Service area not found or access denied'); }
}

function getWorkExperience() {
    global $conn, $currentUser;
    $userId = $_GET['userId'] ?? $currentUser['id'];
    $stmt = $conn->prepare("SELECT * FROM work_experience WHERE user_id = ? ORDER BY is_current DESC, start_date DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) { $items[] = $row; }
    sendSuccess(['workExperience' => $items]);
}

function addWorkExperience() {
    global $conn, $currentUser;
    if ($currentUser['role'] !== 'provider') { sendError('Only providers can add work experience'); }
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO work_experience (user_id, company_name, position, start_date, end_date, is_current, description, achievements) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssiss", $currentUser['id'], $input['companyName'], $input['position'], $input['startDate'], $input['endDate'], $input['isCurrent'], $input['description'], $input['achievements']);
    if ($stmt->execute()) { sendSuccess(['id' => $conn->insert_id, 'message' => 'Work experience added']); } else { sendError('Failed to add work experience'); }
}

function updateWorkExperience() {
    global $conn, $currentUser;
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'])) { sendError('Work experience ID is required'); }
    // Ensure ownership
    $check = $conn->prepare("SELECT id FROM work_experience WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $input['id'], $currentUser['id']);
    $check->execute();
    if ($check->get_result()->num_rows === 0) { sendError('Work experience not found or access denied'); }
    $stmt = $conn->prepare("UPDATE work_experience SET company_name = ?, position = ?, start_date = ?, end_date = ?, is_current = ?, description = ?, achievements = ? WHERE id = ?");
    $stmt->bind_param("ssssissi", $input['companyName'], $input['position'], $input['startDate'], $input['endDate'], $input['isCurrent'], $input['description'], $input['achievements'], $input['id']);
    if ($stmt->execute()) { sendSuccess(['message' => 'Work experience updated']); } else { sendError('Failed to update work experience'); }
}

function deleteWorkExperience() {
    global $conn, $currentUser;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id) { sendError('Work experience ID is required'); }
    $stmt = $conn->prepare("DELETE FROM work_experience WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $currentUser['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) { sendSuccess(['message' => 'Work experience deleted']); } else { sendError('Work experience not found or access denied'); }
}

?>
