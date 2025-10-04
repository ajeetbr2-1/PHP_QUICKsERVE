<?php
require_once 'config.php';

$conn = getDBConnection();
$currentUser = getCurrentUser();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        handleUpload();
        break;
    case 'list':
        listFiles();
        break;
    case 'delete':
        deleteFile();
        break;
    default:
        sendError('Invalid action');
}

function handleUpload() {
    global $conn, $currentUser;

    if (!$currentUser) { sendError('Authentication required', 401); }

    // Only POST multipart supported
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
        sendError('No file uploaded');
    }

    $purpose = $_POST['purpose'] ?? '';
    $referenceId = isset($_POST['referenceId']) ? intval($_POST['referenceId']) : null;

    $allowedPurposes = ['profile_image', 'portfolio', 'certificate', 'service_image', 'document'];
    if (!in_array($purpose, $allowedPurposes)) {
        sendError('Invalid purpose');
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        sendError('Upload failed with error code ' . $file['error']);
    }

    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'image/jpeg','image/png','image/gif','image/webp',
        'application/pdf',
        'video/mp4','video/webm','video/ogg'
    ];

    if (!in_array($mime, $allowedMimes)) {
        sendError('Unsupported file type');
    }

    // Prepare upload directory
    $baseDir = realpath(__DIR__ . '/..');
    $uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate safe file name
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        sendError('Failed to save uploaded file');
    }

    // Save to DB with relative path
    $relativePath = 'uploads/' . $safeName;
    $stmt = $conn->prepare("INSERT INTO uploaded_files (user_id, file_name, file_path, file_type, file_size, purpose, reference_id, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)");
    $stmt->bind_param('isssisis', $currentUser['id'], $file['name'], $relativePath, $mime, $file['size'], $purpose, $referenceId);

    if ($stmt->execute()) {
        sendSuccess([
            'id' => $conn->insert_id,
            'file' => [
                'id' => $conn->insert_id,
                'file_name' => $file['name'],
                'file_path' => $relativePath,
                'file_type' => $mime,
                'file_size' => $file['size'],
                'purpose' => $purpose,
                'reference_id' => $referenceId
            ]
        ], 'File uploaded successfully');
    } else {
        @unlink($destPath);
        sendError('Failed to record upload');
    }
}

function listFiles() {
    global $conn, $currentUser;

    if (!$currentUser) { sendError('Authentication required', 401); }

    $purpose = $_GET['purpose'] ?? null;
    $referenceId = isset($_GET['referenceId']) ? intval($_GET['referenceId']) : null;
    $userId = isset($_GET['userId']) ? intval($_GET['userId']) : $currentUser['id'];

    // Admin can view any user; others only own files
    if ($currentUser['role'] !== 'admin' && $userId !== $currentUser['id']) {
        sendError('Access denied', 403);
    }

    $where = ['user_id = ?'];
    $types = 'i';
    $params = [$userId];

    if ($purpose) { $where[] = 'purpose = ?'; $types .= 's'; $params[] = $purpose; }
    if ($referenceId) { $where[] = 'reference_id = ?'; $types .= 'i'; $params[] = $referenceId; }

    $sql = 'SELECT * FROM uploaded_files WHERE ' . implode(' AND ', $where) . ' ORDER BY uploaded_at DESC';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $files = [];
    while ($row = $res->fetch_assoc()) { $files[] = $row; }

    sendSuccess(['files' => $files]);
}

function deleteFile() {
    global $conn, $currentUser;

    if (!$currentUser) { sendError('Authentication required', 401); }

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id) { sendError('File ID is required'); }

    // Ensure ownership or admin
    $stmt = $conn->prepare('SELECT * FROM uploaded_files WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();

    if (!$file) { sendError('File not found', 404); }
    if ($currentUser['role'] !== 'admin' && $file['user_id'] !== $currentUser['id']) {
        sendError('Access denied', 403);
    }

    // Delete file from disk
    $baseDir = realpath(__DIR__ . '/..');
    $fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . $file['file_path']);

    // Safety: ensure path is inside uploads directory
    $uploadsRoot = realpath($baseDir . DIRECTORY_SEPARATOR . 'uploads');
    if ($fullPath && strpos($fullPath, $uploadsRoot) === 0) {
        @unlink($fullPath);
    }

    // Delete DB row
    $del = $conn->prepare('DELETE FROM uploaded_files WHERE id = ?');
    $del->bind_param('i', $id);
    if ($del->execute()) {
        sendSuccess(['message' => 'File deleted']);
    } else {
        sendError('Failed to delete file');
    }
}
?>