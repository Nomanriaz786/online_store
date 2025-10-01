<?php
/**
 * Order Model
 * Handles all order-related database operations
 */
require_once __DIR__ . '/BaseModel.php';

class Order extends BaseModel
{
    protected $table = 'orders';
    
    // Define allowed fields for DB insert to match schema
    private $allowedOrderFields = [
        'order_number', 'user_id', 'status', 'subtotal', 'shipping_cost', 'tax_amount', 'total_amount',
        'payment_status', 'payment_method',
        'shipping_first_name', 'shipping_last_name', 'shipping_email', 'shipping_phone', 'shipping_address',
        'shipping_city', 'shipping_state', 'shipping_zip_code', 'shipping_country',
        'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone', 'billing_address',
        'billing_city', 'billing_state', 'billing_zip_code', 'billing_country', 'notes', 'created_at'
    ];
    
    /**
     * Create order from cart
     */
    public function createOrderFromCart($userId, $orderData)
    {
        error_log("=== ORDER CREATION START ===");
        error_log("User ID: $userId");
        error_log("Raw input data: " . print_r($orderData, true));
        
        // Flexible mapping for input data (handle both form and API formats)
        $shippingName = trim($orderData['shipping_name'] ?? '');
        if (empty($shippingName)) {
            $shippingName = trim(($orderData['first_name'] ?? '') . ' ' . ($orderData['last_name'] ?? ''));
        }
        $shippingNameParts = explode(' ', $shippingName, 2);
        
        $mappedData = [
            'shipping_first_name' => $shippingNameParts[0] ?? ($orderData['first_name'] ?? ''),
            'shipping_last_name' => $shippingNameParts[1] ?? ($orderData['last_name'] ?? ''),
            'shipping_email' => $orderData['shipping_email'] ?? $orderData['email'] ?? '',
            'shipping_phone' => $orderData['shipping_phone'] ?? $orderData['phone'] ?? '',
            'shipping_address' => $orderData['shipping_address'] ?? $orderData['address'] ?? '',
            'shipping_city' => $orderData['shipping_city'] ?? $orderData['city'] ?? '',
            'shipping_state' => $orderData['shipping_state'] ?? $orderData['state'] ?? '',
            'shipping_zip_code' => $orderData['shipping_zip'] ?? $orderData['shipping_zip_code'] ?? $orderData['zip'] ?? '',
            'shipping_country' => $orderData['shipping_country'] ?? 'US',
            'payment_method' => $orderData['payment_method'] ?? 'card',
            'notes' => $orderData['notes'] ?? $orderData['order_notes'] ?? ''
        ];
        
        // Merge with original for flexibility
        $orderData = array_merge($orderData, $mappedData);
        
        error_log("Mapped data: " . print_r($orderData, true));
        
        // Validate required fields with defaults
        $requiredFields = ['shipping_first_name', 'shipping_last_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_zip_code'];
        $errors = [];
        foreach ($requiredFields as $field) {
            if (empty(trim($orderData[$field] ?? ''))) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        if (!empty($errors)) {
            error_log("Validation errors: " . implode(', ', $errors));
            return ['success' => false, 'errors' => $errors];
        }
        
        error_log("Validation passed");
        
        // Get cart items
        require_once __DIR__ . '/Cart.php';
        $cartModel = new Cart();
        $cartItems = $cartModel->getCartItems($userId);
        
        error_log("Cart items count: " . count($cartItems));
        error_log("Cart items structure: " . print_r(array_keys($cartItems[0] ?? []), true));
        
        if (empty($cartItems)) {
            return ['success' => false, 'errors' => ['Cart is empty']];
        }
        
        $this->beginTransaction();
        error_log("Transaction started");
        
        try {
            // Calculate totals with flexible cart structure
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $price = $item['price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $subtotal += $price * $quantity;
            }
            
            error_log("Subtotal calculated: $subtotal");
            
            $taxRate = 0.10;
            $shippingCost = $subtotal >= 50 ? 0 : 10;
            $taxAmount = round($subtotal * $taxRate, 2);
            $totalAmount = round($subtotal + $taxAmount + $shippingCost, 2);
            
            error_log("Tax: $taxAmount, Shipping: $shippingCost, Total: $totalAmount");
            
            // Generate order number
            $orderNumber = $this->generateOrderNumber();
            error_log("Order number: $orderNumber");
            
            // Prepare only allowed fields for DB insert
            $orderCreateData = [
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_status' => 'pending',
                'payment_method' => $orderData['payment_method'],
                'shipping_first_name' => trim($orderData['shipping_first_name']),
                'shipping_last_name' => trim($orderData['shipping_last_name']),
                'shipping_email' => trim($orderData['shipping_email']),
                'shipping_phone' => trim($orderData['shipping_phone']),
                'shipping_address' => trim($orderData['shipping_address']),
                'shipping_city' => trim($orderData['shipping_city']),
                'shipping_state' => trim($orderData['shipping_state']),
                'shipping_zip_code' => trim($orderData['shipping_zip_code']),
                'shipping_country' => trim($orderData['shipping_country']),
                'billing_first_name' => trim($orderData['shipping_first_name']),
                'billing_last_name' => trim($orderData['shipping_last_name']),
                'billing_email' => trim($orderData['shipping_email']),
                'billing_phone' => trim($orderData['shipping_phone']),
                'billing_address' => trim($orderData['shipping_address']),
                'billing_city' => trim($orderData['shipping_city']),
                'billing_state' => trim($orderData['shipping_state']),
                'billing_zip_code' => trim($orderData['shipping_zip_code']),
                'billing_country' => trim($orderData['shipping_country']),
                'notes' => trim($orderData['notes']),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Filter to only allowed fields
            $orderCreateData = array_intersect_key($orderCreateData, array_flip($this->allowedOrderFields));
            
            error_log("Final order data for DB: " . print_r($orderCreateData, true));
            
            $orderId = $this->create($orderCreateData);
            
            if (!$orderId) {
                throw new Exception('Failed to create order');
            }
            
            error_log("Order created with ID: $orderId");
            
            // Create order items and update stock
            require_once __DIR__ . '/Product.php';
            $productModel = new Product();
            
            foreach ($cartItems as $item) {
                $productId = $item['product_id'] ?? $item['id'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $unitPrice = $item['price'] ?? 0;
                $totalPrice = $unitPrice * $quantity;
                $productName = $item['name'] ?? $item['product_name'] ?? 'Unknown Product';
                $productSku = $item['sku'] ?? $item['product_sku'] ?? '';
                
                $orderItemData = [
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'product_sku' => $productSku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                error_log("Inserting order item: " . print_r($orderItemData, true));
                
                $sql = "INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, unit_price, total_price, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $itemResult = $this->query($sql, array_values($orderItemData));
                
                if (!$itemResult) {
                    throw new Exception("Failed to create order item for product ID: $productId");
                }
                
                error_log("Order item created for product $productId");
                
                // Update stock if product exists
                if ($productId > 0) {
                    $stockResult = $productModel->updateStock($productId, $quantity, 'subtract');
                    if (!$stockResult['success']) {
                        error_log("Stock update warning for product $productId: " . $stockResult['message']);
                        // Don't fail the order for stock update, just log
                    } else {
                        error_log("Stock updated for product $productId");
                    }
                }
            }
            
            // Clear cart
            $cartModel->clearCart($userId);
            error_log("Cart cleared");
            
            $this->commit();
            error_log("Transaction committed");
            error_log("=== ORDER CREATION SUCCESS ===");
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("=== ORDER CREATION FAILED ===");
            error_log("Error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Get order with items
     */
    public function getOrderWithItems($orderId, $userId = null)
    {
        $sql = "SELECT * FROM orders WHERE id = ?";
        $params = [$orderId];
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $stmt = $this->query($sql, $params);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $sql = "SELECT oi.*, 
                COALESCE(p.image_path, 'assets/img/placeholder.svg') as image_url 
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        
        $stmt = $this->query($sql, [$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $order;
    }
    
    /**
     * Generate unique order number
     */
    private function generateOrderNumber()
    {
        $prefix = 'ORD';
        $timestamp = date('YmdHis');
        $random = rand(100, 999);
        
        return $prefix . $timestamp . $random;
    }
    
    /**
     * Update order status
     */
    public function updateStatus($orderId, $status, $notes = '')
    {
        $sql = "UPDATE orders SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->query($sql, [$status, $notes, $orderId]);
        
        if ($result) {
            // Log status change
            error_log("Order $orderId status changed to $status");
            return ['success' => true, 'message' => 'Order status updated'];
        }
        
        return ['success' => false, 'message' => 'Failed to update order status'];
    }
    
    /**
     * Get order items
     */
    public function getOrderItems($orderId)
    {
        $sql = "SELECT oi.*, p.name as product_name, 
                COALESCE(p.image_path, 'assets/img/placeholder.svg') as image_url, 
                p.sku 
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        
        $stmt = $this->query($sql, [$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get orders by user
     */
    public function getOrdersByUser($userId, $limit = 10, $offset = 0)
    {
        $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->query($sql, [$userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user orders with item count
     */
    public function getUserOrders($userId, $limit = 20)
    {
        $sql = "SELECT o.*, COUNT(oi.id) as item_count 
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = ? 
                GROUP BY o.id
                ORDER BY o.created_at DESC 
                LIMIT ?";
        $stmt = $this->query($sql, [$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all orders with pagination
     */
    public function getAllOrders($limit = 20, $offset = 0, $filters = [])
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "o.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "o.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                $whereClause
                ORDER BY o.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get order statistics
     */
    public function getOrderStats()
    {
        $stats = [];
        
        // Total orders
        $sql = "SELECT COUNT(*) as total_orders FROM orders";
        $stmt = $this->query($sql);
        $stats['total_orders'] = $stmt->fetchColumn();
        
        // Total revenue
        $sql = "SELECT SUM(total_amount) as total_revenue FROM orders WHERE status != 'cancelled'";
        $stmt = $this->query($sql);
        $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
        
        // Orders by status
        $sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
        $stmt = $this->query($sql);
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Recent orders (last 7 days)
        $sql = "SELECT COUNT(*) as recent_orders FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $this->query($sql);
        $stats['recent_orders'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    /**
     * Override BaseModel create to log SQL for debugging
     */
    public function create($data)
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        // Log the exact SQL and params
        error_log("Executing SQL: $sql");
        error_log("Parameters: " . print_r(array_values($data), true));
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute(array_values($data));
        
        if ($success) {
            $insertId = $this->db->lastInsertId();
            error_log("Insert successful, ID: $insertId");
            return $insertId;
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Insert failed. Error code: " . $errorInfo[1] . ", Message: " . $errorInfo[2]);
            return false;
        }
    }

    /**
     * Override query method to log all queries
     */
    protected function query($sql, $params = [])
    {
        error_log("Query SQL: $sql");
        if (!empty($params)) {
            error_log("Query params: " . print_r($params, true));
        }
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute($params);
        
        if (!$success) {
            $errorInfo = $stmt->errorInfo();
            error_log("Query failed. Error code: " . $errorInfo[1] . ", Message: " . $errorInfo[2]);
        }
        
        return $stmt;
    }
}