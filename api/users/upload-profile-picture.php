<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../models/User.php';

$auth = new Auth();

// Check if user is authenticated
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$userModel = new User();
$userId = $auth->getCurrentUserId();

try {
    // Check if file was uploaded
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit();
    }
    
    $file = $_FILES['profile_picture'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
        exit();
    }
    
    // Validate file size (2MB max)
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 2MB']);
        exit();
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/users/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('profile_' . $userId . '_') . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    $relativePath = 'uploads/users/' . $filename;
    
    // Get current user to delete old profile picture
    $currentUser = $userModel->find($userId);
    $oldProfilePicture = $currentUser['profile_picture'] ?? null;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Update user record with new profile picture path
        $result = $userModel->update($userId, ['profile_picture' => $relativePath]);
        
        if ($result) {
            // Delete old profile picture if it exists and is not the default
            if ($oldProfilePicture && $oldProfilePicture !== 'assets/img/placeholder.svg') {
                $oldFilePath = __DIR__ . '/../../' . $oldProfilePicture;
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Profile picture updated successfully',
                'profile_picture' => $relativePath
            ]);
        } else {
            // Delete uploaded file if database update failed
            unlink($uploadPath);
            echo json_encode(['success' => false, 'message' => 'Failed to update user record']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>