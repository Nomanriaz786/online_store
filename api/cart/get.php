<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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
            'message' => 'Please log in to view cart'
        ]);
        exit;
    }
    
    // Get cart items
    $cartModel = new Cart();
    $userId = $auth->getCurrentUserId();
    $cartItems = $cartModel->getCartItems($userId);
    $cartSummary = $cartModel->getCartSummary($userId);
    
    echo json_encode([
        'success' => true,
        'items' => $cartItems,
        'summary' => $cartSummary
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading cart: ' . $e->getMessage()
    ]);
}
?>