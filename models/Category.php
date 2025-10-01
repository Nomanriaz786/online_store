<?php
/**
 * Category Model
 * Handles all category-related database operations
 */
require_once __DIR__ . '/BaseModel.php';

class Category extends BaseModel
{
    protected $table = 'categories';
    
    /**
     * Get all active categories
     */
    public function getAllCategories()
    {
        return $this->findAll(['is_active' => 1], 'name ASC');
    }
    
    /**
     * Create new category with validation
     */
    public function createCategory($categoryData)
    {
        // Validate required fields
        $errors = $this->validate($categoryData, ['name']);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Sanitize data
        $categoryData = $this->sanitize($categoryData);
        
        // Check if category name already exists
        if ($this->findOne(['name' => $categoryData['name']])) {
            return ['success' => false, 'errors' => ['Category name already exists']];
        }
        
        $categoryData['created_at'] = date('Y-m-d H:i:s');
        $categoryData['is_active'] = $categoryData['is_active'] ?? 1;
        
        $categoryId = $this->create($categoryData);
        
        if ($categoryId) {
            return ['success' => true, 'category_id' => $categoryId];
        } else {
            return ['success' => false, 'errors' => ['Failed to create category']];
        }
    }
    
    /**
     * Update category
     */
    public function updateCategory($categoryId, $categoryData)
    {
        // Remove fields that shouldn't be updated directly
        unset($categoryData['created_at']);
        
        // Sanitize data
        $categoryData = $this->sanitize($categoryData);
        
        // Check if new name conflicts with existing categories
        if (isset($categoryData['name'])) {
            $existingCategory = $this->findOne(['name' => $categoryData['name']]);
            if ($existingCategory && $existingCategory['id'] != $categoryId) {
                return ['success' => false, 'errors' => ['Category name already exists']];
            }
        }
        
        $categoryData['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->update($categoryId, $categoryData)) {
            return ['success' => true, 'message' => 'Category updated successfully'];
        } else {
            return ['success' => false, 'errors' => ['Failed to update category']];
        }
    }
    
    /**
     * Get category with product count
     */
    public function getCategoriesWithProductCount()
    {
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                WHERE c.is_active = 1
                GROUP BY c.id 
                ORDER BY c.name ASC";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Toggle category status
     */
    public function toggleStatus($categoryId)
    {
        $category = $this->find($categoryId);
        if (!$category) {
            return false;
        }
        
        $newStatus = $category['is_active'] ? 0 : 1;
        return $this->update($categoryId, ['is_active' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')]);
    }
}