<?php
/**
 * QuickServe - File Upload Handler
 * Handles secure image and document uploads
 */

header('Content-Type: application/json');
session_start();

require_once '../config/auth.php';

// Check if user is logged in
$auth = new Auth();
try {
    $user = $auth->getCurrentUser();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Configuration
$uploadDir = '../uploads/';
$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$allowedDocTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];

// Create uploads directory if not exists
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Create subdirectories
$subdirs = ['profiles', 'portfolio', 'certificates', 'services'];
foreach ($subdirs as $subdir) {
    $path = $uploadDir . $subdir;
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
}

// Check if file was uploaded
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$uploadType = $_POST['type'] ?? 'general'; // profile, portfolio, certificate, service

// Validate file size
if ($file['size'] > $maxFileSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit;
}

// Validate file type
$fileType = $file['type'];
$isImage = in_array($fileType, $allowedImageTypes);
$isDoc = in_array($fileType, $allowedDocTypes);

if (!$isImage && !$isDoc) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images and PDFs are allowed']);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('upload_' . time() . '_', true) . '.' . $extension;

// Determine upload path based on type
$subdir = 'general';
switch ($uploadType) {
    case 'profile':
        $subdir = 'profiles';
        break;
    case 'portfolio':
        $subdir = 'portfolio';
        break;
    case 'certificate':
        $subdir = 'certificates';
        break;
    case 'service':
        $subdir = 'services';
        break;
}

$targetPath = $uploadDir . $subdir . '/' . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Return relative URL
    $relativeUrl = 'uploads/' . $subdir . '/' . $filename;
    
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'url' => $relativeUrl,
        'filename' => $filename
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}
?>
