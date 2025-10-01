<?php
/**
 * Cart Model
 * Handles shopping cart operations
 */
require_once __DIR__ . '/BaseModel.php';

class Cart extends BaseModel
{
    protected $table = 'cart_items';
    
    /**
     * Add item to cart
     */
    public function addItem($userId, $productId, $quantity = 1)
    {
        // Validate product exists and is active
        require_once __DIR__ . '/Product.php';
        $productModel = new Product();
        $product = $productModel->getProductById($productId);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        // Check stock availability
        $stockCheck = $productModel->checkStock($productId, $quantity);
        if (!$stockCheck['available']) {
            return ['success' => false, 'message' => $stockCheck['message']];
        }
        
        // Check if item already exists in cart
        $existingItem = $this->findOne(['user_id' => $userId, 'product_id' => $productId]);
        
        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            
            // Check stock for new quantity
            $stockCheck = $productModel->checkStock($productId, $newQuantity);
            if (!$stockCheck['available']) {
                return ['success' => false, 'message' => 'Not enough stock available'];
            }
            
            $success = $this->update($existingItem['id'], [
                'quantity' => $newQuantity,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            // Add new item
            $success = $this->create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        if ($success) {
            return ['success' => true, 'message' => 'Item added to cart'];
        } else {
            return ['success' => false, 'message' => 'Failed to add item to cart'];
        }
    }
    
    /**
     * Get user's cart items with product details
     */
    public function getCartItems($userId)
    {
        $sql = "SELECT ci.*, p.name as product_name, p.price, p.sku,
                COALESCE(p.image_path, 'assets/img/placeholder.svg') as image_url, 
                p.stock_quantity,
                (ci.quantity * p.price) as subtotal
                FROM cart_items ci
                INNER JOIN products p ON ci.product_id = p.id
                WHERE ci.user_id = ? AND p.is_active = 1
                ORDER BY ci.created_at DESC";
        
        $stmt = $this->query($sql, [$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get cart summary (total items and amount)
     */
    public function getCartSummary($userId)
    {
        $sql = "SELECT 
                COUNT(ci.id) as total_items,
                SUM(ci.quantity) as total_quantity,
                SUM(ci.quantity * p.price) as total_amount
                FROM cart_items ci
                INNER JOIN products p ON ci.product_id = p.id
                WHERE ci.user_id = ? AND p.is_active = 1";
        
        $stmt = $this->query($sql, [$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_items' => $result['total_items'] ?? 0,
            'total_quantity' => $result['total_quantity'] ?? 0,
            'total_amount' => $result['total_amount'] ?? 0
        ];
    }
    
    /**
     * Clear user's cart
     */
    public function clearCart($userId)
    {
        try {
            $sql = "DELETE FROM cart_items WHERE user_id = ?";
            $stmt = $this->query($sql, [$userId]);
            
            return [
                'success' => true, 
                'message' => 'Cart cleared successfully',
                'deleted_count' => $stmt->rowCount()
            ];
        } catch (Exception $e) {
            error_log('Cart clearCart error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to clear cart'];
        }
    }
    
    /**
     * Get cart item count for a user
     */
    public function getCartItemCount($userId)
    {
        $sql = "SELECT SUM(quantity) FROM cart_items WHERE user_id = ?";
        $stmt = $this->query($sql, [$userId]);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Update quantity of an item in cart
     */
    public function updateQuantity($userId, $productId, $quantity)
    {
        try {
            $sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() 
                    WHERE user_id = ? AND product_id = ?";
            $stmt = $this->query($sql, [$quantity, $userId, $productId]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Quantity updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Item not found in cart'];
            }
        } catch (Exception $e) {
            error_log('Cart updateQuantity error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update quantity'];
        }
    }
    
    /**
     * Remove an item from cart
     */
    public function removeFromCart($userId, $productId)
    {
        try {
            $sql = "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?";
            $stmt = $this->query($sql, [$userId, $productId]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Item removed from cart'];
            } else {
                return ['success' => false, 'message' => 'Item not found in cart'];
            }
        } catch (Exception $e) {
            error_log('Cart removeFromCart error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to remove item'];
        }
    }
}