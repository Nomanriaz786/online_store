<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../models/Order.php';

try {
    // Initialize Auth and Order model
    $auth = new Auth();
    $orderModel = new Order();
    
    // Check if fetching single order by ID
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $orderId = (int)$_GET['id'];
        $order = $orderModel->find($orderId);
        
        if ($order) {
            echo json_encode([
                'success' => true,
                'order' => $order
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Order not found'
            ]);
        }
        exit();
    }
    
    // Get pagination parameters (optional)
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
    // If user is authenticated and no specific user_id is requested, show their orders
    if ($auth->isAuthenticated() && !$userId) {
        $userId = $auth->getCurrentUserId();
    }
    
    // Build filters
    $filters = [];
    if ($status) {
        $filters['status'] = $status;
    }
    if ($userId) {
        $filters['user_id'] = $userId;
    }
    
    // Get orders with user information from database
    $orders = $orderModel->getAllOrders($limit, ($page - 1) * $limit, $filters);
    
    // Return flat array of orders
    echo json_encode($orders);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load orders: ' . $e->getMessage()]);
}
?>
