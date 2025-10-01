<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Category.php';

try {
    // Initialize Category model
    $categoryModel = new Category();
    
    // Check if fetching single category by ID
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $categoryId = (int)$_GET['id'];
        $category = $categoryModel->find($categoryId);
        
        if ($category) {
            echo json_encode([
                'success' => true,
                'category' => $category
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Category not found'
            ]);
        }
        exit();
    }
    
    // Get all categories with product count
    $categories = $categoryModel->getCategoriesWithProductCount();
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load categories: ' . $e->getMessage()]);
}
?>
