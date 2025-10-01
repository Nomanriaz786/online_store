<?php
/**
 * Authentication Class
 * Handles user authentication, sessions, and security
 */
require_once __DIR__ . '/../models/User.php';

class Auth
{
    private $userModel;
    
    public function __construct()
    {
        $this->userModel = new User();
        $this->startSession();
    }
    
    /**
     * Start secure session
     */
    private function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set session name from config
            if (defined('Config::SESSION_NAME')) {
                session_name(Config::SESSION_NAME);
            }
            
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_path', '/');
            ini_set('session.cookie_domain', '');
            
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Login user
     */
    public function login($usernameOrEmail, $password, $rememberMe = false)
    {
        // Rate limiting (simple implementation)
        if ($this->isRateLimited()) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }
        
        $result = $this->userModel->authenticate($usernameOrEmail, $password);
        
        if ($result['success']) {
            $user = $result['user'];
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['authenticated'] = true;
            $_SESSION['last_activity'] = time();
            
            // Generate CSRF token
            $_SESSION['csrf_token'] = $this->generateCSRFToken();
            
            // Set remember me cookie if requested
            if ($rememberMe) {
                $this->setRememberMeCookie($user['id']);
            }
            
            // Clear login attempts
            unset($_SESSION['login_attempts']);
            
            return ['success' => true, 'user' => $user];
        } else {
            // Record failed attempt
            $this->recordFailedAttempt();
            return $result;
        }
    }
    
    /**
     * Register new user
     */
    public function register($userData)
    {
        return $this->userModel->createUser($userData);
    }
    
    /**
     * Logout user
     */
    public function logout()
    {
        // Clear remember me cookie if exists
        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        }
        
        // Clear all session data
        $_SESSION = array();
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated()
    {
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            return false;
        }
        
        // Check session timeout (2 hours)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Check if user has admin role
     */
    public function isAdmin()
    {
        return $this->isAuthenticated() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $this->userModel->find($_SESSION['user_id']);
    }
    
    /**
     * Get current user (alias for getCurrentUser for compatibility)
     */
    public function getUser()
    {
        return $this->getCurrentUser();
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId()
    {
        return $this->isAuthenticated() ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Require authentication (redirect if not authenticated)
     */
    public function requireAuth()
    {
        if (!$this->isAuthenticated()) {
            $this->redirectToLogin();
        }
    }
    
    /**
     * Require admin role (redirect if not admin)
     */
    public function requireAdmin()
    {
        if (!$this->isAdmin()) {
            $this->redirectWithError('Access denied. Admin privileges required.');
        }
    }
    
    /**
     * Redirect to login page
     */
    private function redirectToLogin()
    {
        $currentUrl = $_SERVER['REQUEST_URI'];
        header("Location: /login.php?redirect=" . urlencode($currentUrl));
        exit();
    }
    
    /**
     * Redirect with error message
     */
    private function redirectWithError($message)
    {
        $_SESSION['error_message'] = $message;
        header("Location: /index.php");
        exit();
    }
    
    /**
     * Set remember me cookie
     */
    private function setRememberMeCookie($userId)
    {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database (you might want to create a remember_tokens table)
        // For now, we'll store it in user session
        $_SESSION['remember_token'] = $token;
        
        setcookie('remember_me', $token, $expires, '/', '', isset($_SERVER['HTTPS']), true);
    }
    
    /**
     * Rate limiting for login attempts
     */
    private function isRateLimited()
    {
        if (!isset($_SESSION['login_attempts'])) {
            return false;
        }
        
        $attempts = $_SESSION['login_attempts'];
        $maxAttempts = 5;
        $lockoutTime = 900; // 15 minutes
        
        if ($attempts['count'] >= $maxAttempts) {
            if (time() - $attempts['last_attempt'] < $lockoutTime) {
                return true;
            } else {
                // Reset attempts after lockout period
                unset($_SESSION['login_attempts']);
            }
        }
        
        return false;
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt()
    {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = ['count' => 0, 'last_attempt' => 0];
        }
        
        $_SESSION['login_attempts']['count']++;
        $_SESSION['login_attempts']['last_attempt'] = time();
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate password strength
     */
    public function validatePasswordStrength($password)
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        return $errors;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (for proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent($event, $details = [])
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'details' => $details
        ];
        
        // In production, this should log to a database table
        error_log('Security Event: ' . json_encode($logEntry));
    }
    
    /**
     * Detect suspicious activity
     */
    public function detectSuspiciousActivity()
    {
        $suspicious = false;
        $reasons = [];
        
        // Check for rapid requests
        if (!isset($_SESSION['request_timestamps'])) {
            $_SESSION['request_timestamps'] = [];
        }
        
        $now = time();
        $_SESSION['request_timestamps'][] = $now;
        
        // Keep only last 20 requests
        $_SESSION['request_timestamps'] = array_slice($_SESSION['request_timestamps'], -20);
        
        // Check if more than 15 requests in last minute
        $recentRequests = array_filter($_SESSION['request_timestamps'], function($timestamp) use ($now) {
            return ($now - $timestamp) <= 60;
        });
        
        if (count($recentRequests) > 15) {
            $suspicious = true;
            $reasons[] = 'Too many requests';
        }
        
        // Check for session hijacking attempts
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            $suspicious = true;
            $reasons[] = 'User agent mismatch';
        }
        
        if ($suspicious) {
            $this->logSecurityEvent('suspicious_activity', ['reasons' => $reasons]);
        }
        
        return $suspicious;
    }
    
    /**
     * Validate file upload security
     */
    public function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'])
    {
        $errors = [];
        
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $errors[] = 'No file uploaded';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File too large (max 5MB)';
        }
        
        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes);
        }
        
        // Check MIME type
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            $allowedMimes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif'
            ];
            
            if (!in_array($mimeType, array_values($allowedMimes))) {
                $errors[] = 'Invalid file MIME type';
            }
        }
        
        // Check if file is actually an image
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $imageInfo = getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                $errors[] = 'File is not a valid image';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'extension' => $extension,
            'mime_type' => $mimeType ?? 'unknown'
        ];
    }
    
    /**
     * Enhanced input sanitization
     */
    public function sanitizeInputAdvanced($input, $type = 'string')
    {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return $this->sanitizeInputAdvanced($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'int':
                return (int) $input;
            case 'float':
                return (float) $input;
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            case 'string':
            default:
                return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
        }
    }
}