<?php
/**
 * Database Configuration
 * Application configuration for the online store
 */
class Config {
    // Database Configuration
    const DB_HOST = 'localhost';
    const DB_NAME = 'online_store';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';
    
    // Application Settings
    const APP_NAME = 'My Store';
    const APP_VERSION = '3.0.0';
    const BASE_URL = 'http://localhost/online_store/online_store/';
    
    // Session Configuration
    const SESSION_TIMEOUT = 3600; // 1 hour
    const SESSION_NAME = 'online_store_session';
    
    // Security Settings
    const HASH_ALGO = PASSWORD_DEFAULT;
    const CSRF_TOKEN_LENGTH = 32;
    
    // File Upload Settings
    const UPLOAD_DIR = 'uploads/';
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Pagination
    const PRODUCTS_PER_PAGE = 12;
    const ADMIN_ITEMS_PER_PAGE = 10;
    
    // Default admin credentials
    const DEFAULT_ADMIN_USERNAME = 'admin';
    const DEFAULT_ADMIN_PASSWORD = 'admin123';
    const DEFAULT_ADMIN_EMAIL = 'admin@mystore.com';
}
?>