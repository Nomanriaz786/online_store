<?php
/**
 * Product Model
 * Handles all product-related database operations
 */
require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel
{
    protected $table = 'products';
    
    /**
     * Create a new product with validation
     */
    public function createProduct($productData)
    {
        // Validate required fields
        $errors = $this->validate($productData, ['name', 'price', 'category_id']);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Sanitize data
        $productData = $this->sanitize($productData);
        
        // Additional validations
        if (!is_numeric($productData['price']) || $productData['price'] < 0) {
            return ['success' => false, 'errors' => ['Price must be a valid positive number']];
        }
        
        if (isset($productData['stock_quantity']) && (!is_numeric($productData['stock_quantity']) || $productData['stock_quantity'] < 0)) {
            return ['success' => false, 'errors' => ['Stock quantity must be a valid positive number']];
        }
        
        // Set default values
        $productData['stock_quantity'] = $productData['stock_quantity'] ?? 0;
        $productData['is_active'] = $productData['is_active'] ?? 1;
        $productData['created_at'] = date('Y-m-d H:i:s');
        
        // Generate SKU if not provided
        if (empty($productData['sku'])) {
            $productData['sku'] = $this->generateSKU($productData['name']);
        }
        
        $productId = $this->create($productData);
        
        if ($productId) {
            return ['success' => true, 'product_id' => $productId];
        } else {
            return ['success' => false, 'errors' => ['Failed to create product']];
        }
    }
    
    /**
     * Get all products with category information
     */
    public function getAllProducts($page = 1, $limit = 12, $categoryId = null, $search = '', $sortBy = 'name', $sortOrder = 'ASC')
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.is_active = 1";
        
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        // Validate sort parameters
        $allowedSortFields = ['name', 'price', 'created_at', 'stock_quantity'];
        $allowedSortOrders = ['ASC', 'DESC'];
        
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'name';
        }
        
        if (!in_array(strtoupper($sortOrder), $allowedSortOrders)) {
            $sortOrder = 'ASC';
        }
        
        $sql .= " ORDER BY p.$sortBy $sortOrder LIMIT $limit OFFSET $offset";
        
        $stmt = $this->query($sql, $params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM products p WHERE p.is_active = 1";
        $countParams = [];
        
        if ($categoryId) {
            $countSql .= " AND p.category_id = ?";
            $countParams[] = $categoryId;
        }
        
        if (!empty($search)) {
            $countSql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchParam = "%$search%";
            $countParams[] = $searchParam;
            $countParams[] = $searchParam;
        }
        
        $countStmt = $this->query($countSql, $countParams);
        $total = $countStmt->fetchColumn();
        
        return [
            'products' => $products,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    /**
     * Get product by ID with category information
     */
    public function getProductById($productId)
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ? AND p.is_active = 1";
        
        $stmt = $this->query($sql, [$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get featured products
     */
    public function getFeaturedProducts($limit = 8)
    {
        $sql = "SELECT p.id, p.name, p.description, p.price, p.sku, p.stock_quantity, 
                       p.is_active, p.is_featured, p.created_at, p.updated_at, p.category_id,
                       COALESCE(p.image_path, 'assets/img/placeholder.svg') as image_url,
                       COALESCE(c.name, p.category, 'Uncategorized') as category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.is_active = 1 AND p.is_featured = 1
                ORDER BY p.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->query($sql, [$limit]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process products to ensure proper image URLs
        foreach ($products as &$product) {
            // Handle image URL - ensure it's not empty
            if (empty($product['image_url'])) {
                $product['image_url'] = 'assets/img/placeholder.svg';
            }
        }
        
        // Debug: Log what we're getting
        error_log("Featured products count: " . count($products));
        if (!empty($products)) {
            error_log("First product: " . print_r($products[0], true));
        }
        
        return $products;
    }
    
    /**
     * Update stock quantity
     */
    public function updateStock($productId, $quantity, $operation = 'set')
    {
        $product = $this->find($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        switch ($operation) {
            case 'add':
                $newQuantity = $product['stock_quantity'] + $quantity;
                break;
            case 'subtract':
                $newQuantity = $product['stock_quantity'] - $quantity;
                if ($newQuantity < 0) {
                    return ['success' => false, 'message' => 'Insufficient stock'];
                }
                break;
            case 'set':
            default:
                $newQuantity = $quantity;
                break;
        }
        
        if ($this->update($productId, ['stock_quantity' => $newQuantity, 'updated_at' => date('Y-m-d H:i:s')])) {
            return ['success' => true, 'new_quantity' => $newQuantity];
        } else {
            return ['success' => false, 'message' => 'Failed to update stock'];
        }
    }
    
    /**
     * Check stock availability
     */
    public function checkStock($productId, $requestedQuantity = 1)
    {
        $product = $this->find($productId);
        if (!$product) {
            return ['available' => false, 'message' => 'Product not found'];
        }
        
        if ($product['stock_quantity'] >= $requestedQuantity) {
            return ['available' => true, 'stock' => $product['stock_quantity']];
        } else {
            return ['available' => false, 'message' => 'Insufficient stock', 'stock' => $product['stock_quantity']];
        }
    }
    
    /**
     * Generate unique SKU
     */
    private function generateSKU($productName)
    {
        // Create SKU from product name
        $sku = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 6));
        $sku .= rand(1000, 9999);
        
        // Ensure SKU is unique
        while ($this->findOne(['sku' => $sku])) {
            $sku = substr($sku, 0, -4) . rand(1000, 9999);
        }
        
        return $sku;
    }
    
    /**
     * Get all unique categories from products
     */
    public function getCategories()
    {
        $sql = "SELECT DISTINCT c.id, c.name 
                FROM categories c 
                INNER JOIN products p ON c.id = p.category_id 
                WHERE p.is_active = 1 
                ORDER BY c.name";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get products by category
     */
    public function getProductsByCategory($categoryId, $limit = 4)
    {
        $sql = "SELECT p.*, c.name as category_name,
                COALESCE(p.image_path, 'assets/img/placeholder.svg') as image_path
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = ? AND p.is_active = 1
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        $stmt = $this->query($sql, [$categoryId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}