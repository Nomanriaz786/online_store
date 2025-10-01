<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/models/Cart.php';
require_once __DIR__ . '/models/Product.php';

$auth = new Auth();
$cartModel = new Cart();
$productModel = new Product();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }
    
    $action = $_POST['action'];
    $userId = $_SESSION['user_id'] ?? null;
    
    switch ($action) {
        case 'add_to_cart':
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
                exit();
            }
            
            $productId = (int)$_POST['product_id'];
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            // Verify product exists and is active
            $product = $productModel->find($productId);
            if (!$product || !$product['is_active']) {
                echo json_encode(['success' => false, 'message' => 'Product not available']);
                exit();
            }
            
            // Check stock
            if ($product['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                exit();
            }
            
            $result = $cartModel->addToCart($userId, $productId, $quantity);
            echo json_encode($result);
            exit();
            
        case 'update_quantity':
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Please login']);
                exit();
            }
            
            $productId = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            
            if ($quantity <= 0) {
                $result = $cartModel->removeFromCart($userId, $productId);
            } else {
                $result = $cartModel->updateQuantity($userId, $productId, $quantity);
            }
            
            echo json_encode($result);
            exit();
            
        case 'remove_item':
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Please login']);
                exit();
            }
            
            $productId = (int)$_POST['product_id'];
            $result = $cartModel->removeFromCart($userId, $productId);
            echo json_encode($result);
            exit();
            
        case 'clear_cart':
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Please login']);
                exit();
            }
            
            $result = $cartModel->clearCart($userId);
            echo json_encode($result);
            exit();
            
        case 'get_cart_count':
            $count = $userId ? $cartModel->getCartItemCount($userId) : 0;
            echo json_encode(['success' => true, 'count' => $count]);
            exit();
    }
}

// Get cart items for logged in user
$cartItems = [];
$cartTotal = 0;
$cartCount = 0;

if ($auth->isAuthenticated()) {
    $userId = $_SESSION['user_id'];
    $cartItems = $cartModel->getCartItems($userId);
    $cartSummary = $cartModel->getCartSummary($userId);
    $cartTotal = $cartSummary['total_amount'];
    $cartCount = $cartSummary['total_quantity'] ?? 0;
}

$pageTitle = "Shopping Cart — My Store";
$customHead = '<meta name="csrf-token" content="' . $auth->generateCSRFToken() . '">';
include 'partials/html-head.php'; 
?>
  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <main class="cart-main">
    <div class="container py-5">
      
      <?php if (!$auth->isAuthenticated()): ?>
        <!-- Not logged in message -->
        <div class="row justify-content-center">
          <div class="col-md-8 text-center">
            <div class="card">
              <div class="card-body py-5">
                <i class="bi bi-cart-x display-1 text-muted mb-3"></i>
                <h3 class="mb-3">Please Login to View Cart</h3>
                <p class="text-muted mb-4">You need to be logged in to manage your shopping cart.</p>
                <div class="d-flex gap-3 justify-content-center">
                  <a href="login.html" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                  </a>
                  <a href="register.html" class="btn btn-outline-primary">
                    <i class="bi bi-person-plus me-2"></i>Register
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
        
      <?php elseif (empty($cartItems)): ?>
        <!-- Empty cart -->
        <div class="row justify-content-center">
          <div class="col-md-8 text-center">
            <div class="card">
              <div class="card-body py-5">
                <i class="bi bi-cart display-1 text-muted mb-3"></i>
                <h3 class="mb-3">Your Cart is Empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added anything to your cart yet.</p>
                <a href="index.php" class="btn btn-primary">
                  <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                </a>
              </div>
            </div>
          </div>
        </div>
        
      <?php else: ?>
        <!-- Cart with items -->
        <div class="row">
          <div class="col-lg-8">
            <div class="card">
              <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                  <h4 class="mb-0">Shopping Cart (<?php echo $cartCount; ?> items)</h4>
                  <button class="btn btn-outline-danger btn-sm" onclick="Cart.clearEntireCart()">
                    <i class="bi bi-trash me-1"></i>Clear Cart
                  </button>
                </div>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($cartItems as $item): ?>
                        <tr id="cart-item-<?php echo $item['product_id']; ?>">
                          <td>
                            <div class="d-flex align-items-center">
                              <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                     class="me-3 cart-item-image">
                              <?php else: ?>
                                <div class="bg-light me-3 d-flex align-items-center justify-content-center cart-placeholder-image">
                                  <i class="bi bi-image text-muted"></i>
                                </div>
                              <?php endif; ?>
                              <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                <?php if (!empty($item['sku'])): ?>
                                  <small class="text-muted">SKU: <?php echo htmlspecialchars($item['sku']); ?></small>
                                <?php endif; ?>
                              </div>
                            </div>
                          </td>
                          <td class="align-middle">
                            <strong>$<?php echo number_format($item['price'], 2); ?></strong>
                          </td>
                          <td class="align-middle">
                            <div class="input-group cart-qty-group">
                              <button class="btn btn-outline-secondary btn-sm" type="button" 
                                      onclick="Cart.updateCartQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                <i class="bi bi-dash"></i>
                              </button>
                              <input type="number" class="form-control form-control-sm text-center cart-qty-input" 
                                     value="<?php echo htmlspecialchars($item['quantity'] ?? '1'); ?>" min="1" max="<?php echo htmlspecialchars($item['stock_quantity'] ?? '99'); ?>"
                                     id="qty-<?php echo $item['product_id']; ?>"
                                     onchange="Cart.updateCartQuantity(<?php echo $item['product_id']; ?>, this.value)">
                              <button class="btn btn-outline-secondary btn-sm" type="button" 
                                      onclick="Cart.updateCartQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                <i class="bi bi-plus"></i>
                              </button>
                            </div>
                            <?php if ($item['stock_quantity'] < 10): ?>
                              <small class="text-warning d-block mt-1">Only <?php echo $item['stock_quantity']; ?> left</small>
                            <?php endif; ?>
                          </td>
                          <td class="align-middle">
                            <strong id="item-total-<?php echo $item['product_id']; ?>">
                              $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </strong>
                          </td>
                          <td class="align-middle">
                            <button class="btn btn-outline-danger btn-sm" 
                                    onclick="Cart.removeCartItem(<?php echo $item['product_id']; ?>)"
                                    title="Remove item">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            
            <!-- Continue Shopping -->
            <div class="mt-3">
              <a href="index.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i>Continue Shopping
              </a>
            </div>
          </div>
          
          <!-- Cart Summary -->
          <div class="col-lg-4">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Order Summary</h5>
              </div>
              <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                  <span>Subtotal (<?php echo $cartCount; ?> items):</span>
                  <span id="cart-subtotal">$<?php echo number_format($cartTotal, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                  <span>Shipping:</span>
                  <span class="text-success">FREE</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                  <span>Tax:</span>
                  <span id="cart-tax">$<?php echo number_format($cartTotal * 0.08, 2); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                  <strong>Total:</strong>
                  <strong id="cart-total">$<?php echo number_format($cartTotal * 1.08, 2); ?></strong>
                </div>
                
                <div class="d-grid gap-2">
                  <a href="checkout.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
                  </a>
                  <button class="btn btn-outline-secondary" onclick="Cart.saveForLater()">
                    <i class="bi bi-bookmark me-2"></i>Save for Later
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
      
    </div>
  </main>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>