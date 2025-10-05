<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Order.php';
require_once __DIR__ . '/includes/admin-table-components.php';

$auth = new Auth();
$auth->requireAdmin();

// Initialize models
$productModel = new Product();
$categoryModel = new Category();
$userModel = new User();
$orderModel = new Order();

// Handle table refresh requests
if (isset($_GET['refresh'])) {
    header('Content-Type: text/html; charset=UTF-8');
    
    switch ($_GET['refresh']) {
        case 'products':
            $allProducts = $productModel->findAll();
            renderProductsTable($allProducts);
            exit();
            
        case 'categories':
            $categories = $categoryModel->getAllCategories();
            renderCategoriesTable($categories);
            exit();
            
        case 'orders':
            $allOrders = $orderModel->getAllOrders();
            renderOrdersTable($allOrders);
            exit();
            
        case 'users':
            $allUsers = $userModel->findAll();
            renderUsersTable($allUsers);
            exit();
    }
    exit();
}

// File upload handler
function handleImageUpload($file, $folder = 'products') {
    $uploadDir = __DIR__ . '/uploads/' . $folder . '/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'];
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true, 
            'file_path' => 'uploads/' . $folder . '/' . $filename,
            'filename' => $filename
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Handle server-side POST form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Invalid CSRF token';
        $_SESSION['flash_type'] = 'danger';
        header('Location: admin.php');
        exit();
    }
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'create_product':
            // Validate category_id if provided
            if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
                $categoryExists = $categoryModel->find($_POST['category_id']);
                if (!$categoryExists) {
                    $_SESSION['flash_message'] = 'Selected category does not exist';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: admin.php#products');
                    exit();
                }
            } else {
                $_POST['category_id'] = null;
            }
            
            // Handle file upload if present
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleImageUpload($_FILES['product_image'], 'products');
                if ($uploadResult['success']) {
                    $_POST['image_url'] = $uploadResult['file_path'];
                }
            }
            
            // Map image_url to image_path for database compatibility
            if (isset($_POST['image_url'])) {
                $_POST['image_path'] = $_POST['image_url'];
                unset($_POST['image_url']);
            }

            // Remove non-database fields
            unset($_POST['action'], $_POST['csrf_token'], $_POST['product_id']);
            
            try {
                $result = $productModel->createProduct($_POST);
                if ($result['success'] ?? false) {
                    $_SESSION['flash_message'] = 'Product created successfully';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Failed to create product';
                    $_SESSION['flash_type'] = 'danger';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $_SESSION['flash_message'] = 'Invalid category selected or constraint violation';
                } else {
                    $_SESSION['flash_message'] = 'Database error: ' . $e->getMessage();
                }
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#products');
            exit();
            
        case 'update_product':
            $productId = (int)$_POST['product_id'];
            
            // Validate category_id if provided
            if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
                $categoryExists = $categoryModel->find($_POST['category_id']);
                if (!$categoryExists) {
                    $_SESSION['flash_message'] = 'Selected category does not exist';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: admin.php#products');
                    exit();
                }
            } else {
                $_POST['category_id'] = null;
            }
            
            // Handle file upload if present
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleImageUpload($_FILES['product_image'], 'products');
                if ($uploadResult['success']) {
                    $_POST['image_url'] = $uploadResult['file_path'];
                }
            }
            
            // Map image_url to image_path for database compatibility
            if (isset($_POST['image_url'])) {
                $_POST['image_path'] = $_POST['image_url'];
                unset($_POST['image_url']);
            }

            unset($_POST['action'], $_POST['csrf_token'], $_POST['product_id']);
            
            try {
                $result = $productModel->update($productId, $_POST);
                if ($result) {
                    $_SESSION['flash_message'] = 'Product updated successfully';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Failed to update product';
                    $_SESSION['flash_type'] = 'danger';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $_SESSION['flash_message'] = 'Invalid category selected or constraint violation';
                } else {
                    $_SESSION['flash_message'] = 'Database error: ' . $e->getMessage();
                }
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#products');
            exit();
            
        case 'delete_product':
            $productId = (int)$_POST['product_id'];
            try {
                $result = $productModel->delete($productId);
                if ($result) {
                    $_SESSION['flash_message'] = 'Product deleted successfully';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Failed to delete product';
                    $_SESSION['flash_type'] = 'danger';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $_SESSION['flash_message'] = 'Cannot delete product: it is referenced by existing orders';
                } else {
                    $_SESSION['flash_message'] = 'Database error: ' . $e->getMessage();
                }
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#products');
            exit();
            
        case 'create_category':
            unset($_POST['action'], $_POST['csrf_token'], $_POST['category_id']);
            $result = $categoryModel->createCategory($_POST);
            if ($result['success'] ?? false) {
                $_SESSION['flash_message'] = 'Category created successfully';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to create category';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#categories');
            exit();
            
        case 'update_order_status':
        case 'update_order':
            $orderId = (int)$_POST['order_id'];
            
            if (isset($_POST['status']) && isset($_POST['notes'])) {
                $status = $_POST['status'];
                $notes = $_POST['notes'] ?? '';
                $result = $orderModel->update($orderId, [
                    'status' => $status,
                    'notes' => $notes
                ]);
            } else {
                unset($_POST['action'], $_POST['csrf_token'], $_POST['order_id']);
                $result = $orderModel->update($orderId, $_POST);
            }
            
            if ($result) {
                $_SESSION['flash_message'] = 'Order updated successfully';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to update order';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#orders');
            exit();

        case 'update_category':
            $categoryId = (int)$_POST['category_id'];
            unset($_POST['action'], $_POST['csrf_token'], $_POST['category_id']);
            $result = $categoryModel->update($categoryId, $_POST);
            if ($result) {
                $_SESSION['flash_message'] = 'Category updated successfully';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to update category';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#categories');
            exit();

        case 'delete_category':
            $categoryId = (int)$_POST['category_id'];
            $result = $categoryModel->delete($categoryId);
            if ($result) {
                $_SESSION['flash_message'] = 'Category deleted successfully';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to delete category';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#categories');
            exit();

        case 'delete_order':
            $orderId = (int)$_POST['order_id'];
            $result = $orderModel->delete($orderId);
            if ($result) {
                $_SESSION['flash_message'] = 'Order deleted successfully';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to delete order';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#orders');
            exit();

        case 'create_user':
            // Handle name field mapping (split full name into first/last)
            if (isset($_POST['name']) && !empty($_POST['name'])) {
                $nameParts = explode(' ', trim($_POST['name']), 2);
                $_POST['first_name'] = $nameParts[0] ?? '';
                $_POST['last_name'] = $nameParts[1] ?? '';
                unset($_POST['name']);
            }
            
            // Hash password if provided
            if (isset($_POST['password']) && !empty($_POST['password'])) {
                $_POST['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                unset($_POST['password']);
            }
            
            // Remove non-database fields
            unset($_POST['action'], $_POST['csrf_token'], $_POST['user_id']);
            $result = $userModel->create($_POST);
            
            if (is_numeric($result) && $result > 0) {
                $_SESSION['flash_message'] = 'User created successfully';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to create user';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#users');
            exit();

        case 'update_user':
            $userId = (int)$_POST['user_id'];
            
            // Handle name field mapping (split full name into first/last)
            if (isset($_POST['name']) && !empty($_POST['name'])) {
                $nameParts = explode(' ', trim($_POST['name']), 2);
                $_POST['first_name'] = $nameParts[0] ?? '';
                $_POST['last_name'] = $nameParts[1] ?? '';
                unset($_POST['name']);
            }
            
            // Hash password if provided (only if not empty)
            if (isset($_POST['password']) && !empty($_POST['password'])) {
                $_POST['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                unset($_POST['password']);
            } else {
                unset($_POST['password']);
            }
            
            unset($_POST['action'], $_POST['csrf_token'], $_POST['user_id']);
            $result = $userModel->update($userId, $_POST);
            if ($result) {
                $_SESSION['flash_message'] = 'User updated successfully';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to update user';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#users');
            exit();

        case 'delete_user':
            $userId = (int)$_POST['user_id'];
            $result = $userModel->delete($userId);
            if ($result) {
                $_SESSION['flash_message'] = 'User deleted successfully';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to delete user';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: admin.php#users');
            exit();
    }
}

// Get statistics
$stats = [
    'total_products' => $productModel->count(['is_active' => 1]),
    'total_users' => $userModel->count(),
    'total_orders' => $orderModel->count(),
    'pending_orders' => $orderModel->count(['status' => 'pending'])
];

// Get recent data
$recentProducts = $productModel->findAll([], 'created_at DESC', 5);
$recentOrders = $orderModel->findAll([], 'created_at DESC', 5);
$categories = $categoryModel->getAllCategories();

// Get all data for server-side rendering
$allProducts = $productModel->findAll();
$allOrders = $orderModel->findAll();
$allUsers = $userModel->findAll();

$pageTitle = "Admin Panel — My Store";

// Provide CSRF token into the head meta via $customHead so shared head includes it
$customHead = '<meta name="csrf-token" content="' . $auth->generateCSRFToken() . '">';

include 'partials/html-head.php';
?>

  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <main class="admin-main">
    <div class="container-fluid py-4">
      
      <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
          <?php 
            echo htmlspecialchars($_SESSION['flash_message']); 
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
          ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <!-- Admin Header with Navigation -->
      <div class="admin-header mb-4">
        <div class="row align-items-center mb-3">
          <div class="col">
            <h1 class="fw-bold text-dark mb-0">Admin Dashboard</h1>
            <p class="text-muted mb-0">Manage your store data and settings</p>
          </div>
          <div class="col-auto">
            <div class="admin-stats d-flex gap-3">
              <div class="stat-item text-center">
                <div class="fw-bold text-primary fs-4"><?php echo $stats['total_products']; ?></div>
                <small class="text-muted">Products</small>
              </div>
              <div class="stat-item text-center">
                <div class="fw-bold text-success fs-4"><?php echo $stats['total_users']; ?></div>
                <small class="text-muted">Users</small>
              </div>
              <div class="stat-item text-center">
                <div class="fw-bold text-info fs-4"><?php echo $stats['total_orders']; ?></div>
                <small class="text-muted">Orders</small>
              </div>
              <div class="stat-item text-center">
                <div class="fw-bold text-warning fs-4"><?php echo $stats['pending_orders']; ?></div>
                <small class="text-muted">Pending</small>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Beautiful Navigation Tabs -->
        <div class="admin-nav-pills">
          <ul class="nav nav-pills nav-fill bg-light rounded-3 p-2 shadow-sm">
            <li class="nav-item">
              <a class="nav-link active d-flex align-items-center justify-content-center" data-bs-toggle="tab" href="#dashboard">
                <i class="bi bi-speedometer2 me-2"></i>
                <span>Dashboard</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link d-flex align-items-center justify-content-center" data-bs-toggle="tab" href="#products">
                <i class="bi bi-box-seam me-2"></i>
                <span>Products</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link d-flex align-items-center justify-content-center" data-bs-toggle="tab" href="#categories">
                <i class="bi bi-tags me-2"></i>
                <span>Categories</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link d-flex align-items-center justify-content-center" data-bs-toggle="tab" href="#orders">
                <i class="bi bi-cart-check me-2"></i>
                <span>Orders</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link d-flex align-items-center justify-content-center" data-bs-toggle="tab" href="#users">
                <i class="bi bi-people me-2"></i>
                <span>Users</span>
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        
        <!-- Dashboard Tab -->
        <div class="tab-pane fade show active" id="dashboard">
          <div class="row g-4">
            <div class="col-md-6">
              <div class="card shadow-sm h-100">
                <div class="card-header admin-card-header-primary">
                  <h5 class="mb-0 fw-semibold d-flex align-items-center">
                    <i class="bi bi-box-seam me-2"></i>Recent Products
                  </h5>
                </div>
                <div class="card-body">
                  <?php if (!empty($recentProducts)): ?>
                    <div class="list-group list-group-flush">
                      <?php foreach ($recentProducts as $product): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-0">
                          <div class="d-flex align-items-center">
                            <?php if (!empty($product['image_path']) || !empty($product['image_url'])): ?>
                              <img src="<?php echo htmlspecialchars($product['image_path'] ?? $product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail me-3 admin-product-thumbnail-sm">
                            <?php else: ?>
                              <img src="assets/img/placeholder.svg" alt="No image" class="img-thumbnail me-3 admin-product-thumbnail-sm">
                            <?php endif; ?>
                            <div>
                              <h6 class="mb-1 fw-medium"><?php echo htmlspecialchars($product['name']); ?></h6>
                              <small class="text-muted">$<?php echo number_format($product['price'], 2); ?></small>
                            </div>
                          </div>
                          <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?> rounded-pill">
                            <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                          </span>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <div class="text-center py-4">
                      <i class="bi bi-box-seam text-muted mb-2" style="font-size: 3rem;"></i>
                      <p class="text-muted">No products found</p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="card shadow-sm h-100">
                <div class="card-header admin-card-header-success">
                  <h5 class="mb-0 fw-semibold d-flex align-items-center">
                    <i class="bi bi-cart-check me-2"></i>Recent Orders
                  </h5>
                </div>
                <div class="card-body">
                  <?php if (!empty($recentOrders)): ?>
                    <div class="list-group list-group-flush">
                      <?php foreach ($recentOrders as $order): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-0">
                          <div>
                            <h6 class="mb-1 fw-medium">Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></h6>
                            <small class="text-muted">$<?php echo number_format($order['total_amount'], 2); ?></small>
                          </div>
                          <span class="badge bg-<?php echo $order['status'] === 'pending' ? 'warning' : ($order['status'] === 'completed' ? 'success' : 'primary'); ?> rounded-pill">
                            <?php echo ucfirst($order['status']); ?>
                          </span>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <div class="text-center py-4">
                      <i class="bi bi-cart-check text-muted mb-2" style="font-size: 3rem;"></i>
                      <p class="text-muted">No orders found</p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Products Tab -->
            <div class="tab-pane fade" id="products">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">Products Management</h2>
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#productModal">
                  <i class="bi bi-plus-circle me-2"></i>Add Product
                </button>
              </div>
              
              <div class="card shadow-sm">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="productsTable">
                      <thead class="table-dark">
                        <tr>
                          <th>Image</th>
                          <th>Name</th>
                          <th>Price</th>
                          <th>Stock</th>
                          <th>Category</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody id="productsTableBody">
                        <?php renderProductsTable($allProducts); ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Categories Tab -->
            <div class="tab-pane fade" id="categories">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">Categories Management</h2>
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">
                  <i class="bi bi-plus-circle me-2"></i>Add Category
                </button>
              </div>
              
              <div class="card shadow-sm">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                      <thead class="table-dark">
                        <tr>
                          <th>Name</th>
                          <th>Description</th>
                          <th>Product Count</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody id="categoriesTableBody">
                        <?php renderCategoriesTable($categories); ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Orders Tab -->
            <div class="tab-pane fade" id="orders">
              <h2 class="mb-4 fw-bold">Orders Management</h2>
              
              <div class="card shadow-sm">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="ordersTable">
                      <thead class="table-dark">
                        <tr>
                          <th>Customer</th>
                          <th>Total</th>
                          <th>Status</th>
                          <th>Date</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody id="ordersTableBody">
                        <?php renderOrdersTable($allOrders); ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Users Tab -->
            <div class="tab-pane fade" id="users">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Users Management</h2>
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                  <i class="bi bi-plus-circle me-2"></i>Add User
                </button>
              </div>
              
              <div class="card shadow-sm">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="usersTable">
                      <thead class="table-dark">
                        <tr>
                          <th>Username</th>
                          <th>Email</th>
                          <th>Name</th>
                          <th>Role</th>
                          <th>Status</th>
                          <th>Joined</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody id="usersTableBody">
                        <?php renderUsersTable($allUsers); ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Product Modal -->
  <div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add/Edit Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="productForm" method="POST" action="admin.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create_product" id="productAction">
            <input type="hidden" name="product_id" id="productId">
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="productName" class="form-label">Product Name</label>
                  <input type="text" class="form-control" id="productName" name="name" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="productPrice" class="form-label">Price</label>
                  <input type="number" class="form-control" id="productPrice" name="price" step="0.01" min="0" required>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="productCategory" class="form-label">Category</label>
                  <select class="form-select" id="productCategory" name="category_id">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                      <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="productStock" class="form-label">Stock Quantity</label>
                  <input type="number" class="form-control" id="productStock" name="stock_quantity" min="0" value="0">
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="productSku" class="form-label">SKU (Optional)</label>
                  <input type="text" class="form-control" id="productSku" name="sku">
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="productImage" class="form-label">Product Image</label>
                  <input type="file" class="form-control" id="productImage" name="product_image" accept="image/*">
                  <div class="form-text">Upload JPG, PNG, GIF, or WebP images (max 5MB)</div>
                  <input type="hidden" id="productImageUrl" name="image_url">
                </div>
              </div>
            </div>
            
            <div class="mb-3">
              <label for="productDescription" class="form-label">Description</label>
              <textarea class="form-control" id="productDescription" name="description" rows="3"></textarea>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="productActive" name="is_active" value="1" checked>
                  <label class="form-check-label" for="productActive">Active</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="productFeatured" name="is_featured" value="1">
                  <label class="form-check-label" for="productFeatured">Featured</label>
                </div>
              </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Product</button>
        </div>
          </form>
      </div>
    </div>
  </div>

  <!-- Category Modal -->
  <div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add/Edit Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="categoryForm" method="POST" action="admin.php">
            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create_category" id="categoryAction">
            <input type="hidden" name="category_id" id="categoryId">
            
            <div class="mb-3">
              <label for="categoryName" class="form-label">Category Name</label>
              <input type="text" class="form-control" id="categoryName" name="name" required>
            </div>
            
            <div class="mb-3">
              <label for="categoryDescription" class="form-label">Description</label>
              <textarea class="form-control" id="categoryDescription" name="description" rows="3"></textarea>
            </div>
            
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="categoryActive" name="is_active" value="1" checked>
              <label class="form-check-label" for="categoryActive">Active</label>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Category</button>
        </div>
          </form>
      </div>
    </div>
  </div>

  <!-- User Modal -->
  <div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add/Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="userForm" method="POST" action="admin.php">
            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create_user" id="userAction">
            <input type="hidden" name="user_id" id="userId">
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="userUsername" class="form-label">Username</label>
                  <input type="text" class="form-control" id="userUsername" name="username" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="userEmail" class="form-label">Email</label>
                  <input type="email" class="form-control" id="userEmail" name="email" required>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="userName" class="form-label">Full Name</label>
                  <input type="text" class="form-control" id="userName" name="name">
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="userRole" class="form-label">Role</label>
                  <select class="form-select" id="userRole" name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="userPassword" class="form-label">Password (leave blank to keep current)</label>
                  <input type="password" class="form-control" id="userPassword" name="password">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" id="userActive" name="is_active" value="1" checked>
                  <label class="form-check-label" for="userActive">Active</label>
                </div>
              </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save User</button>
        </div>
          </form>
      </div>
    </div>
  </div>

  <!-- Order Modal -->
  <div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Update Order Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="orderForm" method="POST" action="admin.php">
            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update_order_status" id="orderAction">
            <input type="hidden" name="order_id" id="orderId">
            
            <div class="mb-3">
              <label for="orderStatus" class="form-label">Status</label>
              <select class="form-select" id="orderStatus" name="status" required>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label for="orderNotes" class="form-label">Admin Notes</label>
              <textarea class="form-control" id="orderNotes" name="notes" rows="3" placeholder="Optional notes for this order..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Order</button>
        </div>
          </form>
      </div>
    </div>
  </div>

<?php
// Provide admin script include to be printed by shared footer
$customScripts = '<script src="assets/js/admin.js"></script>';

include 'partials/html-footer.php';
?>