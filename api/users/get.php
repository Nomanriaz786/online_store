<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';

try {
    // Initialize User model
    $userModel = new User();
    
    // Check if fetching single user by ID
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $userId = (int)$_GET['id'];
        $user = $userModel->find($userId);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
        exit();
    }
    
    // Get pagination parameters (optional)
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $role = isset($_GET['role']) ? $_GET['role'] : null;
    
    // Get all users from database
    $users = $userModel->findAll([], 'created_at DESC', $limit, ($page - 1) * $limit);
    
    echo json_encode($users);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load users: ' . $e->getMessage()]);
}
?>
