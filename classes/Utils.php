<?php
/**
 * Utility functions for the online store
 */

class Utils {
    
    /**
     * Generate a secure random string
     */
    public static function generateRandomString($length = 16) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Format price for display
     */
    public static function formatPrice($price) {
        return '$' . number_format((float)$price, 2);
    }
    
    /**
     * Format date for display
     */
    public static function formatDate($date, $format = 'M j, Y g:i A') {
        return date($format, strtotime($date));
    }
    
    /**
     * Truncate text with ellipsis
     */
    public static function truncateText($text, $maxLength = 100, $suffix = '...') {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        
        return substr($text, 0, $maxLength - strlen($suffix)) . $suffix;
    }
    
    /**
     * Generate SEO-friendly slug
     */
    public static function generateSlug($text) {
        // Convert to lowercase
        $slug = strtolower($text);
        
        // Remove special characters
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        
        // Replace spaces with hyphens
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        
        // Trim hyphens from ends
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Validate email format
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Send email (mock function for demo)
     */
    public static function sendEmail($to, $subject, $message, $headers = []) {
        // In production, this would use a real email service like PHPMailer or SendGrid
        error_log("Email sent to: $to, Subject: $subject");
        
        // Mock success for demo purposes
        return true;
    }
    
    /**
     * Log system events
     */
    public static function logEvent($level, $message, $context = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        error_log(json_encode($logEntry));
    }
    
    /**
     * Create directory if it doesn't exist
     */
    public static function ensureDirectory($path) {
        if (!file_exists($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }
    
    /**
     * Get file size in human readable format
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Clean up old files
     */
    public static function cleanupOldFiles($directory, $maxAge = 86400) {
        if (!is_dir($directory)) {
            return false;
        }
        
        $files = glob($directory . '/*');
        $now = time();
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Generate order number
     */
    public static function generateOrderNumber() {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(self::generateRandomString(), 0, 6));
    }
    
    /**
     * Calculate shipping cost (mock function)
     */
    public static function calculateShipping($items, $address = null) {
        // Mock shipping calculation - in production this would integrate with shipping APIs
        $totalWeight = 0;
        $totalValue = 0;
        
        foreach ($items as $item) {
            $totalWeight += ($item['weight'] ?? 1) * $item['quantity'];
            $totalValue += $item['price'] * $item['quantity'];
        }
        
        // Free shipping over $50
        if ($totalValue >= 50) {
            return 0;
        }
        
        // Calculate based on weight and distance
        return min(15, max(5, $totalWeight * 2));
    }
    
    /**
     * Apply discount code (mock function)
     */
    public static function applyDiscountCode($code, $totalAmount) {
        $discountCodes = [
            'SAVE10' => ['type' => 'percentage', 'value' => 10],
            'WELCOME5' => ['type' => 'fixed', 'value' => 5],
            'FREESHIP' => ['type' => 'shipping', 'value' => 0]
        ];
        
        $code = strtoupper($code);
        
        if (!isset($discountCodes[$code])) {
            return ['success' => false, 'message' => 'Invalid discount code'];
        }
        
        $discount = $discountCodes[$code];
        $discountAmount = 0;
        
        switch ($discount['type']) {
            case 'percentage':
                $discountAmount = $totalAmount * ($discount['value'] / 100);
                break;
            case 'fixed':
                $discountAmount = min($discount['value'], $totalAmount);
                break;
            case 'shipping':
                // Handle shipping discount separately
                break;
        }
        
        return [
            'success' => true,
            'discount_amount' => $discountAmount,
            'discount_type' => $discount['type'],
            'message' => "Discount applied successfully!"
        ];
    }
    
    /**
     * Resize image (requires GD extension)
     */
    public static function resizeImage($sourcePath, $destPath, $maxWidth = 800, $maxHeight = 600) {
        if (!extension_loaded('gd')) {
            return false;
        }
        
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $newWidth = round($sourceWidth * $ratio);
        $newHeight = round($sourceHeight * $ratio);
        
        // Create source image resource
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }
        
        // Resize image
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
        
        // Save resized image
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($newImage, $destPath, 90);
                break;
            case 'image/png':
                $result = imagepng($newImage, $destPath);
                break;
            case 'image/gif':
                $result = imagegif($newImage, $destPath);
                break;
        }
        
        // Clean up resources
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return $result;
    }
}