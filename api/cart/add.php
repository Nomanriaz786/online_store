<?php
// Don't start session here - let Auth class handle it
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../models/Cart.php';

try {
    // Check if user is authenticated
    $auth = new Auth();
    if (!$auth->isAuthenticated()) {
        echo json_encode([
            'success' => false,
            'message' => 'Please log in to add items to cart'
        ]);
        exit;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Also check POST data as fallback
    if (!$input) {
        $input = $_POST;
    }
    
    $productId = $input['product_id'] ?? null;
    $quantity = (int)($input['quantity'] ?? 1);
    
    if (!$productId) {
        echo json_encode([
            'success' => false,
            'message' => 'Product ID is required'
        ]);
        exit;
    }
    
    if ($quantity < 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Quantity must be at least 1'
        ]);
        exit;
    }
    
    // Add item to cart
    $cartModel = new Cart();
    $userId = $auth->getCurrentUserId();
    
    // Debug: Log the user ID
    error_log("Cart Add Debug - User ID: " . ($userId ? $userId : 'NULL'));
    error_log("Cart Add Debug - Session data: " . json_encode($_SESSION));
    
    if (!$userId) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user session'
        ]);
        exit;
    }
    
    // Verify user exists in database
    require_once '../../models/User.php';
    $userModel = new User();
    $userExists = $userModel->find($userId);
    
    if (!$userExists) {
        error_log("Cart Add Error - User ID $userId does not exist in database");
        echo json_encode([
            'success' => false,
            'message' => 'User account not found. Please log in again.'
        ]);
        exit;
    }
    
    $result = $cartModel->addItem($userId, $productId, $quantity);
    
    if ($result['success']) {
        // Get updated cart count
        $cartCount = $cartModel->getCartItemCount($userId);
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'cart_count' => $cartCount
        ]);
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error adding item to cart: ' . $e->getMessage()
    ]);
}
?>