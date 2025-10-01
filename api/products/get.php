<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Product.php';

try {
    // Initialize Product model
    $productModel = new Product();
    
    // Check if fetching single product by ID
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $productId = (int)$_GET['id'];
        $product = $productModel->find($productId);
        
        if ($product) {
            $product['category'] = $product['category_name'] ?? ''; // Map for compatibility
            // Map image_path to image_url for admin interface compatibility
            if (isset($product['image_path'])) {
                $product['image_url'] = $product['image_path'];
            }
            echo json_encode([
                'success' => true,
                'product' => $product
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Product not found'
            ]);
        }
        exit();
    }
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100; // Large limit for API
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Get all products from database
    $result = $productModel->getAllProducts($page, $limit, $categoryId, $search);
    
    // Map database fields to frontend expected fields
    $products = array_map(function($product) {
        $product['category'] = $product['category_name'];
        // Map image_path to image_url for admin interface compatibility
        if (isset($product['image_path'])) {
            $product['image_url'] = $product['image_path'];
        }
        return $product;
    }, $result['products']);
    
    // Return products in expected format
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => $result['total'],
        'page' => $result['current_page'],
        'pages' => $result['pages']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load products: ' . $e->getMessage()
    ]);
}
?>