<?php
/**
 * Database Initialization Script
 * Run this file to create the database schema and insert sample data
 */

require_once __DIR__ . '/config/database.php';

try {
    echo "Initializing Online Store Database...\n";
    
    $db = Database::getInstance();
    $db->initializeSchema();
    
    echo "Database initialized successfully!\n";
    echo "Default admin credentials:\n";
    echo "Username: " . Config::DEFAULT_ADMIN_USERNAME . "\n";
    echo "Password: " . Config::DEFAULT_ADMIN_PASSWORD . "\n";
    echo "Email: " . Config::DEFAULT_ADMIN_EMAIL . "\n\n";
    
    echo "Sample products have been added to the database.\n";
    echo "You can now access the application at: " . Config::BASE_URL . "\n";
    
} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}
?>