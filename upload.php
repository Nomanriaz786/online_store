<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Utils.php';

$auth = new Auth();

// Require login
if (!$auth->isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Please login to upload files']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Validate CSRF token
if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

$uploadType = $_POST['upload_type'] ?? 'profile'; // profile, product
$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

// Validate file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
        UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
        UPLOAD_ERR_PARTIAL => 'File upload incomplete',
        UPLOAD_ERR_NO_FILE => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Upload directory not found',
        UPLOAD_ERR_CANT_WRITE => 'Cannot write to upload directory',
        UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
    ];
    
    $error = $errors[$_FILES['file']['error']] ?? 'Unknown upload error';
    echo json_encode(['success' => false, 'message' => $error]);
    exit();
}

// Validate file using Auth security features
$validation = $auth->validateFileUpload($_FILES['file'], $allowedTypes);
if (!$validation['valid']) {
    echo json_encode(['success' => false, 'message' => implode(', ', $validation['errors'])]);
    exit();
}

// Determine upload directory
$uploadDir = __DIR__ . '/uploads/';
if ($uploadType === 'profile') {
    $uploadDir .= 'profiles/';
} elseif ($uploadType === 'product') {
    $uploadDir .= 'products/';
} else {
    $uploadDir .= 'misc/';
}

// Ensure upload directory exists
if (!Utils::ensureDirectory($uploadDir)) {
    echo json_encode(['success' => false, 'message' => 'Cannot create upload directory']);
    exit();
}

// Generate unique filename
$extension = $validation['extension'];
$filename = Utils::generateRandomString(16) . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
    exit();
}

// Create thumbnail for images
$thumbnailPath = null;
if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
    $thumbnailDir = $uploadDir . 'thumbs/';
    Utils::ensureDirectory($thumbnailDir);
    
    $thumbnailPath = $thumbnailDir . 'thumb_' . $filename;
    Utils::resizeImage($filepath, $thumbnailPath, 200, 200);
}

// If this is a profile picture, update user record
if ($uploadType === 'profile') {
    require_once __DIR__ . '/models/User.php';
    $userModel = new User();
    
    // Get relative path for database storage
    $relativePath = 'uploads/profiles/' . $filename;
    
    $updateResult = $userModel->update($_SESSION['user_id'], ['profile_picture' => $relativePath]);
    
    if (!$updateResult) {
        // Clean up uploaded file on database error
        unlink($filepath);
        if ($thumbnailPath && file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        echo json_encode(['success' => false, 'message' => 'Failed to update user profile']);
        exit();
    }
}

// Log successful upload
Utils::logEvent('info', 'File uploaded', [
    'user_id' => $_SESSION['user_id'],
    'filename' => $filename,
    'upload_type' => $uploadType,
    'file_size' => $_FILES['file']['size']
]);

// Return success response
$response = [
    'success' => true,
    'message' => 'File uploaded successfully',
    'filename' => $filename,
    'path' => str_replace(__DIR__, '', $filepath),
    'size' => $_FILES['file']['size'],
    'type' => $validation['mime_type']
];

if ($thumbnailPath && file_exists($thumbnailPath)) {
    $response['thumbnail'] = str_replace(__DIR__, '', $thumbnailPath);
}

echo json_encode($response);