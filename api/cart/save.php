<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../models/Cart.php';

try {
    // Check if user is authenticated
    $auth = new Auth();
    if (!$auth->isAuthenticated()) {
        echo json_encode([
            'success' => false,
            'message' => 'Please log in to save cart'
        ]);
        exit;
    }

    // Only handle POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['items']) || !is_array($input['items'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid cart data'
        ]);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $cart = new Cart();
    
    // Clear existing cart items for this user
    $cart->clearCart($userId);
    
    // Add each item from the client-side cart
    foreach ($input['items'] as $item) {
        if (!isset($item['id']) || !isset($item['quantity'])) {
            continue;
        }
        
        $productId = (int)$item['id'];
        $quantity = (int)$item['quantity'];
        
        if ($productId > 0 && $quantity > 0) {
            $cart->addItem($userId, $productId, $quantity);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cart saved successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Cart save error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>