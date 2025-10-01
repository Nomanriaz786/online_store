# Online Store - E-commerce Web Application

A comprehensive e-commerce platform built with PHP, MySQL, and modern web technologies. This project demonstrates advanced web development practices including secure user authentication, dynamic product management, shopping cart functionality, and responsive design.

## ✨ Features

### User Management
- 🔐 Secure user registration and login
- 👤 User profile management with photo upload
- 🔒 Password change functionality
- 📱 Responsive user dashboard
- 🛡️ Session-based authentication

### E-commerce Functionality
- 🛍️ Dynamic product catalog
- 🔍 Advanced search and filtering
- 🛒 Shopping cart with persistence
- 💳 Checkout process with order management
- 📦 Order history and tracking

### Admin Panel
- 📊 Comprehensive admin dashboard
- ➕ Product CRUD operations
- 🖼️ Image upload and management
- 👥 User management
- 📈 Order management and statistics

### Design & UX
- 🎨 Modern responsive design
- 🌙 Dark/Light theme toggle
- 📱 Mobile-first approach
- ♿ Accessibility compliant
- 🚀 Fast loading and optimized

## 🔧 Prerequisites

Before setting up this project, ensure you have the following installed:

### Required Software
- **XAMPP** (Version 8.0 or higher) - Includes Apache, MySQL, and PHP
- **Web Browser** (Chrome, Firefox, Safari, or Edge)
- **Text Editor** (VS Code, Sublime Text, or similar)

### System Requirements
- **Operating System**: Windows, macOS, or Linux
- **RAM**: Minimum 4GB (8GB recommended)
- **Storage**: At least 2GB free space
- **PHP Version**: 8.0 or higher
- **MySQL Version**: 5.7 or higher

## 🚀 Installation Guide

### Step 1: Install XAMPP

1. **Download XAMPP**
   ```
   Visit: https://www.apachefriends.org/
   Download the appropriate version for your OS
   ```

2. **Install XAMPP**
   - Run the installer
   - Choose installation directory (default: `C:\xampp` on Windows)
   - Select components: Apache, MySQL, PHP (minimum required)
   - Complete installation

3. **Start XAMPP Control Panel**
   - Launch XAMPP Control Panel
   - Start **Apache** and **MySQL** services
   - Verify services are running (green status)

### Step 2: Download Project Files

1. **Extract Project**
   ```bash
   # Extract the project files to XAMPP htdocs directory
   # Windows: C:\xampp\htdocs\online_store
   # macOS/Linux: /Applications/XAMPP/htdocs/online_store
   ```

2. **Verify File Structure**
   ```
   htdocs/
   └── online_store/
       ├── index.php
       ├── config/
       ├── models/
       ├── api/
       └── ... (other project files)
   ```

### Step 3: Verify XAMPP Installation

1. **Test Apache**
   ```
   Open browser: http://localhost
   You should see XAMPP welcome page
   ```

2. **Test PHP**
   ```
   Open browser: http://localhost/online_store
   You should see the project homepage
   ```

## 🗄️ Database Setup

### Method 1: Automatic Setup (Recommended)

1. **Run Database Setup Script**
   ```
   Open browser: http://localhost/online_store/setup_database.php
   ```

2. **Follow Setup Instructions**
   - The script will automatically create the database
   - Create all necessary tables
   - Insert sample data
   - Create default admin account

3. **Note Default Credentials**
   ```
   Admin Username: admin
   Admin Password: admin123
   Admin Email: admin@mystore.com
   ```

### Method 2: Manual Setup

1. **Access phpMyAdmin**
   ```
   Open browser: http://localhost/phpmyadmin
   ```

2. **Create Database**
   ```sql
   CREATE DATABASE online_store_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import Database Schema**
   - Use the setup_database.php script or
   - Manually run the SQL commands from config/database.php

## ⚙️ Configuration

### 1. Database Configuration

Edit `config/config.php` if needed:

```php
// Database Configuration
const DB_HOST = 'localhost';
const DB_NAME = 'online_store_db';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

// Application Settings
const BASE_URL = 'http://localhost/online_store/';
```

## 🏃‍♂️ Running the Application

### 1. Start Services
```bash
# Start XAMPP Control Panel
# Start Apache and MySQL services
```

### 2. Access the Application
```
Frontend: http://localhost/online_store/
Admin Panel: http://localhost/online_store/admin.php
API Base: http://localhost/online_store/api/
```

### 3. Test the Setup
1. **Homepage**: Verify products load correctly
2. **Registration**: Create a new user account
3. **Login**: Test authentication
4. **Admin Panel**: Login with admin credentials
5. **Shopping**: Add products to cart and test checkout

## 👨‍💼 Admin Access

### Default Admin Credentials
```
Username: admin
Password: admin123
Email: admin@mystore.com
```

### Admin Features
- **Dashboard**: View statistics and analytics
- **Product Management**: Add, edit, delete products
- **User Management**: View and manage users
- **Order Management**: Process and track orders
- **Image Upload**: Manage product images

### Security Note
**⚠️ IMPORTANT**: Change the default admin password immediately after setup!

## 📁 Project Structure

```
online_store/
├── 📄 index.php              # Homepage
├── 📄 admin.php              # Admin dashboard
├── 📄 login.php              # User login
├── 📄 register.php           # User registration
├── 📄 products.php           # Product catalog
├── 📄 cart.php               # Shopping cart
├── 📄 checkout.php           # Checkout process
├── 📄 profile.php            # User profile
├── 📄 setup_database.php     # Database setup script
│
├── 📂 api/                   # REST API endpoints
│   ├── 📂 auth/              # Authentication
│   ├── 📂 products/          # Product operations
│   ├── 📂 cart/              # Cart operations
│   ├── 📂 orders/            # Order management
│   └── 📂 users/             # User operations
│
├── 📂 assets/                # Static assets
│   ├── 📂 css/               # Stylesheets
│   ├── 📂 js/                # JavaScript files
│   └── 📂 img/               # Images and icons
│
├── 📂 classes/               # PHP classes
│   ├── 📄 Auth.php           # Authentication class
│   └── 📄 Utils.php          # Utility functions
│
├── 📂 config/                # Configuration files
│   ├── 📄 config.php         # Application config
│   └── 📄 database.php       # Database class
│
├── 📂 models/                # Data models
│   ├── 📄 BaseModel.php      # Base model class
│   ├── 📄 User.php           # User model
│   ├── 📄 Product.php        # Product model
│   ├── 📄 Cart.php           # Cart model
│   └── 📄 Order.php          # Order model
│
├── 📂 partials/              # Reusable templates
│   ├── 📄 header.php         # Site header
│   ├── 📄 footer.php         # Site footer
│   └── 📄 html-head.php      # HTML head
│
└── 📂 uploads/               # User uploads
    ├── 📂 products/          # Product images
    └── 📂 users/             # User profile pictures
```

## 🔌 API Endpoints

### Authentication
```
POST /api/auth/login.php       # User login
POST /api/auth/logout.php      # User logout
```

### Products
```
GET  /api/products/get.php     # Get all products
GET  /api/products/get.php?id=1 # Get single product
POST /api/products/get.php     # Create product (admin)
PUT  /api/products/get.php     # Update product (admin)
DELETE /api/products/get.php   # Delete product (admin)
```

### Cart
```
GET  /api/cart/get.php         # Get cart items
POST /api/cart/add.php         # Add to cart
POST /api/cart/save.php        # Update cart
```

### Orders
```
GET  /api/orders/get.php       # Get user orders
POST /api/checkout/process.php # Process checkout
```

### Users
```
GET  /api/users/profile.php    # Get user profile
POST /api/users/profile.php    # Update profile
POST /api/users/change-password.php # Change password
POST /api/users/upload-profile-picture.php # Upload avatar
```

## 🛡️ Security Features

### Authentication & Authorization
- Secure password hashing (PHP password_hash())
- Session-based authentication
- Role-based access control (user/admin)
- Session timeout and regeneration

### Input Validation
- Server-side form validation
- SQL injection prevention (prepared statements)
- XSS protection (htmlspecialchars())
- File upload validation and sanitization

### Data Protection
- CSRF token protection
- Secure file uploads with type validation
- Sanitized database outputs
- Protected admin areas