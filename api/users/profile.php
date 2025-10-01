<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
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

$userModel = new User();
$userId = $auth->getCurrentUserId();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get user profile
        $user = $userModel->find($userId);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }
        
        // Remove sensitive data
        unset($user['password']);
        unset($user['reset_token']);
        unset($user['reset_token_expires']);
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update user profile
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        // Validate required fields
        $allowedFields = [
            'first_name', 'last_name', 'email', 'phone', 
            'address', 'city', 'state', 'zip_code', 'country'
        ];
        
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = trim($input[$field]);
            }
        }
        
        // Validate email if being updated
        if (isset($updateData['email'])) {
            if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit();
            }
            
            // Check if email is already in use by another user
            $existingUser = $userModel->findOne(['email' => $updateData['email']]);
            if ($existingUser && $existingUser['id'] != $userId) {
                echo json_encode(['success' => false, 'message' => 'Email already in use']);
                exit();
            }
        }
        
        if (empty($updateData)) {
            echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
            exit();
        }
        
        $result = $userModel->update($userId, $updateData);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>