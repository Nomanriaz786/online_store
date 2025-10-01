<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/config.php';
require_once '../../models/Product.php';

try {
    $productModel = new Product();
    
    // Get all categories using the Product model
    $categories = $productModel->getCategories();
    
    echo json_encode([
        'success' => true,
        'data' => $categories ?: []
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading categories: ' . $e->getMessage()
    ]);
}
?>