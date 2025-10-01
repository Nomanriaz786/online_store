<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../models/Order.php';
require_once '../../models/Cart.php';
require_once '../../models/Product.php';

$auth = new Auth();

// Check if user is authenticated
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }

    $userId = $_SESSION['user_id'];
    $orderModel = new Order();
    $cartModel = new Cart();
    $productModel = new Product();
    
    // Get cart items
    $cartItems = $cartModel->getCartItems($userId);
    if (empty($cartItems)) {
        throw new Exception('Your cart is empty');
    }
    
    // Validate stock availability
    foreach ($cartItems as $item) {
        $product = $productModel->getProductById($item['product_id']);
        if (!$product || $product['stock_quantity'] < $item['quantity']) {
            throw new Exception("Insufficient stock for {$item['product_name']}");
        }
    }
    
    // Prepare order data - map frontend fields to backend expected format
    $shippingNameParts = explode(' ', trim(($input['first_name'] ?? '') . ' ' . ($input['last_name'] ?? '')), 2);
    $orderData = [
        'shipping_name' => trim(($input['first_name'] ?? '') . ' ' . ($input['last_name'] ?? '')),
        'shipping_first_name' => $shippingNameParts[0] ?? ($input['first_name'] ?? ''),
        'shipping_last_name' => $shippingNameParts[1] ?? ($input['last_name'] ?? ''),
        'shipping_email' => $input['email'] ?? $input['shipping_email'] ?? '',
        'shipping_phone' => $input['phone'] ?? $input['shipping_phone'] ?? '',
        'shipping_address' => $input['address'] ?? $input['shipping_address'] ?? '',
        'shipping_city' => $input['city'] ?? $input['shipping_city'] ?? '',
        'shipping_state' => $input['state'] ?? $input['shipping_state'] ?? '',
        'shipping_zip' => $input['zip'] ?? $input['shipping_zip'] ?? $input['shipping_zip_code'] ?? '',
        'shipping_country' => $input['shipping_country'] ?? 'US',
        'payment_method' => $input['payment_method'] ?? 'credit_card',
        'shipping_method' => $input['shipping'] ?? 'standard',
        'notes' => $input['order_notes'] ?? $input['notes'] ?? ''
    ];
    
    error_log("API orderData before model: " . print_r($orderData, true));
    
    // Create the order
    $result = $orderModel->createOrderFromCart($userId, $orderData);
    
    error_log("Model result: " . print_r($result, true));
    
    if ($result['success']) {
        $orderId = $result['order_id'];
        
        // Clear the cart after successful order
        $cartModel->clearCart($userId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $orderId
        ]);
    } else {
        $errorMsg = isset($result['errors']) ? implode(', ', $result['errors']) : ($result['message'] ?? 'Failed to create order');
        error_log("Order creation failed in API: $errorMsg");
        throw new Exception($errorMsg);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>