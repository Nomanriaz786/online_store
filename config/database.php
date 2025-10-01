<?php
/**
 * Database Connection Class
 * Handles PDO database connections with error handling
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME . ";charset=" . Config::DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . Config::DB_CHARSET
            ];
            
            $this->pdo = new PDO($dsn, Config::DB_USER, Config::DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Try to create database if it doesn't exist
            if ($e->getCode() == 1049) {
                $this->createDatabase();
                $this->connect(); // Reconnect after creating database
            } else {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
    }
    
    private function createDatabase() {
        try {
            $dsn = "mysql:host=" . Config::DB_HOST . ";charset=" . Config::DB_CHARSET;
            $pdo = new PDO($dsn, Config::DB_USER, Config::DB_PASS);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . Config::DB_NAME . " CHARACTER SET " . Config::DB_CHARSET . " COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            throw new Exception("Failed to create database: " . $e->getMessage());
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Prepare and execute a query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Initialize database schema
     */
    public function initializeSchema() {
        try {
            // Create tables in order (respecting foreign key dependencies)
            $this->createUsersTable();
            $this->createCategoriesTable();
            $this->createProductsTable();
            $this->createOrdersTable();
            $this->createOrderItemsTable();
            $this->createCartItemsTable();
            
            // Insert default data
            $this->insertDefaultData();
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to initialize schema: " . $e->getMessage());
        }
    }
    
    private function createUsersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(50) DEFAULT NULL,
            last_name VARCHAR(50) DEFAULT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            role ENUM('user', 'admin') DEFAULT 'user',
            profile_picture VARCHAR(255) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            city VARCHAR(50) DEFAULT NULL,
            state VARCHAR(50) DEFAULT NULL,
            zip_code VARCHAR(20) DEFAULT NULL,
            country VARCHAR(50) DEFAULT 'USA',
            email_verified TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            last_login TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
    }
    
    private function createCategoriesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            description TEXT DEFAULT NULL,
            image_path VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
    }
    
    private function createProductsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            price DECIMAL(10, 2) NOT NULL,
            category_id INT DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            image_path VARCHAR(255) DEFAULT NULL,
            sku VARCHAR(100) UNIQUE DEFAULT NULL,
            stock_quantity INT DEFAULT 0,
            weight DECIMAL(8, 2) DEFAULT NULL,
            dimensions VARCHAR(100) DEFAULT NULL,
            is_featured TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            INDEX idx_category (category),
            INDEX idx_featured (is_featured),
            INDEX idx_active (is_active),
            INDEX idx_price (price)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
    }
    
    private function createOrdersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            subtotal DECIMAL(10, 2) NOT NULL,
            shipping_cost DECIMAL(10, 2) DEFAULT 0.00,
            tax_amount DECIMAL(10, 2) DEFAULT 0.00,
            total_amount DECIMAL(10, 2) NOT NULL,
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            payment_method VARCHAR(50) DEFAULT NULL,
            
            -- Shipping Information
            shipping_first_name VARCHAR(50) DEFAULT NULL,
            shipping_last_name VARCHAR(50) DEFAULT NULL,
            shipping_email VARCHAR(100) DEFAULT NULL,
            shipping_phone VARCHAR(20) DEFAULT NULL,
            shipping_address TEXT DEFAULT NULL,
            shipping_city VARCHAR(50) DEFAULT NULL,
            shipping_state VARCHAR(50) DEFAULT NULL,
            shipping_zip_code VARCHAR(20) DEFAULT NULL,
            shipping_country VARCHAR(50) DEFAULT NULL,
            
            -- Billing Information
            billing_first_name VARCHAR(50) DEFAULT NULL,
            billing_last_name VARCHAR(50) DEFAULT NULL,
            billing_email VARCHAR(100) DEFAULT NULL,
            billing_phone VARCHAR(20) DEFAULT NULL,
            billing_address TEXT DEFAULT NULL,
            billing_city VARCHAR(50) DEFAULT NULL,
            billing_state VARCHAR(50) DEFAULT NULL,
            billing_zip_code VARCHAR(20) DEFAULT NULL,
            billing_country VARCHAR(50) DEFAULT NULL,
            
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id),
            INDEX idx_status (status),
            INDEX idx_order_number (order_number),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
    }
    
    private function createOrderItemsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            product_sku VARCHAR(100) DEFAULT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10, 2) NOT NULL,
            total_price DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
            INDEX idx_order (order_id),
            INDEX idx_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
    }
    
    private function createCartItemsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_product (user_id, product_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
    }
    
    private function insertDefaultData() {
        // Insert default admin user if not exists
        $adminExists = $this->query("SELECT id FROM users WHERE username = ?", [Config::DEFAULT_ADMIN_USERNAME])->fetch();
        
        if (!$adminExists) {
            $adminPassword = password_hash(Config::DEFAULT_ADMIN_PASSWORD, Config::HASH_ALGO);
            $this->query(
                "INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    Config::DEFAULT_ADMIN_USERNAME,
                    Config::DEFAULT_ADMIN_EMAIL,
                    $adminPassword,
                    'Admin',
                    'User',
                    'admin'
                ]
            );
        }
        
        // Insert default categories
        $categories = [
            ['Electronics', 'Electronic devices and gadgets'],
            ['Computers', 'Computers and computer accessories'],
            ['Audio', 'Audio equipment and accessories'],
            ['Office', 'Office supplies and equipment'],
            ['Accessories', 'Various accessories and small items']
        ];
        
        foreach ($categories as $category) {
            $exists = $this->query("SELECT id FROM categories WHERE name = ?", [$category[0]])->fetch();
            if (!$exists) {
                $this->query("INSERT INTO categories (name, description) VALUES (?, ?)", $category);
            }
        }
        
        // Insert sample products
        $this->insertSampleProducts();
    }
    
    private function insertSampleProducts() {
        $products = [
            [
                'name' => 'Wireless Bluetooth Speaker',
                'description' => 'High-quality portable Bluetooth speaker with excellent sound quality and long battery life.',
                'price' => 79.99,
                'category' => 'Audio',
                'image_path' => 'assets/img/speaker.svg',
                'sku' => 'SPK-001',
                'stock_quantity' => 25,
                'is_featured' => 1
            ],
            [
                'name' => 'Ergonomic Wireless Mouse',
                'description' => 'Comfortable wireless mouse with precision tracking and long battery life.',
                'price' => 29.99,
                'category' => 'Computers',
                'image_path' => 'assets/img/mouse.svg',
                'sku' => 'MSE-001',
                'stock_quantity' => 50,
                'is_featured' => 1
            ],
            [
                'name' => 'Mechanical Gaming Keyboard',
                'description' => 'Professional mechanical keyboard with RGB backlighting and premium switches.',
                'price' => 149.99,
                'category' => 'Computers',
                'image_path' => 'assets/img/keyboard.svg',
                'sku' => 'KBD-001',
                'stock_quantity' => 30,
                'is_featured' => 1
            ],
            [
                'name' => 'LED Desk Lamp',
                'description' => 'Adjustable LED desk lamp with multiple brightness levels and USB charging port.',
                'price' => 45.99,
                'category' => 'Office',
                'image_path' => 'assets/img/lamp.svg',
                'sku' => 'LMP-001',
                'stock_quantity' => 40,
                'is_featured' => 1
            ],
            [
                'name' => 'Premium Laptop Backpack',
                'description' => 'Durable laptop backpack with multiple compartments and water-resistant material.',
                'price' => 89.99,
                'category' => 'Accessories',
                'image_path' => 'assets/img/backpack.svg',
                'sku' => 'BAG-001',
                'stock_quantity' => 20,
                'is_featured' => 1
            ],
            [
                'name' => 'Stainless Steel Water Bottle',
                'description' => 'Insulated stainless steel water bottle that keeps drinks hot or cold for hours.',
                'price' => 24.99,
                'category' => 'Accessories',
                'image_path' => 'assets/img/bottle.svg',
                'sku' => 'BTL-001',
                'stock_quantity' => 60,
                'is_featured' => 1
            ],
            [
                'name' => 'Wireless Phone Charger',
                'description' => 'Fast wireless charging pad compatible with all Qi-enabled devices.',
                'price' => 39.99,
                'category' => 'Electronics',
                'image_path' => 'assets/img/charger.svg',
                'sku' => 'CHG-001',
                'stock_quantity' => 45,
                'is_featured' => 1
            ],
            [
                'name' => 'Professional Notebook',
                'description' => 'High-quality notebook with lined pages, perfect for professional note-taking.',
                'price' => 19.99,
                'category' => 'Office',
                'image_path' => 'assets/img/notebook.svg',
                'sku' => 'NBK-001',
                'stock_quantity' => 100,
                'is_featured' => 1
            ]
        ];
        
        foreach ($products as $product) {
            $exists = $this->query("SELECT id FROM products WHERE sku = ?", [$product['sku']])->fetch();
            if (!$exists) {
                $this->query(
                    "INSERT INTO products (name, description, price, category, image_path, sku, stock_quantity, is_featured) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $product['name'],
                        $product['description'],
                        $product['price'],
                        $product['category'],
                        $product['image_path'],
                        $product['sku'],
                        $product['stock_quantity'],
                        $product['is_featured']
                    ]
                );
            }
        }
    }
}
?>