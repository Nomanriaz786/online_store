<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/models/Cart.php';
require_once __DIR__ . '/models/Product.php';

$auth = new Auth();
$cartModel = new Cart();
$productModel = new Product();

// Handle POST requests for cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
        header('Location: cart.php');
        exit();
    }

    if (!$auth->isAuthenticated()) {
        $_SESSION['error_message'] = 'Please login to manage your cart.';
        header('Location: login.php?redirect=cart.php');
        exit();
    }

    $action = $_POST['action'];
    $userId = $_SESSION['user_id'];

    switch ($action) {
        case 'add_to_cart':
            $productId = (int)$_POST['product_id'];
            $quantity = (int)($_POST['quantity'] ?? 1);

            // Verify product exists and is active
            $product = $productModel->find($productId);
            if (!$product || !$product['is_active']) {
                $_SESSION['error_message'] = 'Product not available.';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'products.php'));
                exit();
            }

            // Check stock
            if ($product['stock_quantity'] < $quantity) {
                $_SESSION['error_message'] = 'Insufficient stock available.';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'products.php'));
                exit();
            }

            $result = $cartModel->addItem($userId, $productId, $quantity);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'] ?? 'Item added to cart successfully.';
            } else {
                $_SESSION['error_message'] = $result['message'] ?? 'Failed to add item to cart.';
            }
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
            exit();

        case 'update_quantity':
            $productId = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];

            if ($quantity <= 0) {
                $result = $cartModel->removeFromCart($userId, $productId);
                $message = $result['success'] ? 'Item removed from cart.' : 'Failed to remove item.';
            } else {
                $result = $cartModel->updateQuantity($userId, $productId, $quantity);
                $message = $result['success'] ? 'Cart updated successfully.' : 'Failed to update cart.';
            }

            if ($result['success']) {
                $_SESSION['success_message'] = $message;
            } else {
                $_SESSION['error_message'] = $message;
            }
            header('Location: cart.php');
            exit();

        case 'remove_item':
            $productId = (int)$_POST['product_id'];
            $result = $cartModel->removeFromCart($userId, $productId);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Item removed from cart.';
            } else {
                $_SESSION['error_message'] = 'Failed to remove item from cart.';
            }
            header('Location: cart.php');
            exit();

        case 'clear_cart':
            $result = $cartModel->clearCart($userId);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Cart cleared successfully.';
            } else {
                $_SESSION['error_message'] = 'Failed to clear cart.';
            }
            header('Location: cart.php');
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
                  <a href="login.php" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                  </a>
                  <a href="register.php" class="btn btn-outline-primary">
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
                  <form method="POST" action="cart.php" class="d-inline">
                    <input type="hidden" name="action" value="clear_cart">
                    <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to clear your entire cart?')">
                      <i class="bi bi-trash me-1"></i>Clear Cart
                    </button>
                  </form>
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
                            <form method="POST" action="cart.php" class="d-inline">
                              <input type="hidden" name="action" value="update_quantity">
                              <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                              <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                              <div class="input-group input-group-sm" style="width: 120px;">
                                <button class="btn btn-outline-secondary" type="submit" name="quantity" value="<?php echo $item['quantity'] - 1; ?>" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                  <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" class="form-control text-center" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity'] ?? 99; ?>" onchange="this.form.submit()">
                                <button class="btn btn-outline-secondary" type="submit" name="quantity" value="<?php echo $item['quantity'] + 1; ?>" <?php echo ($item['stock_quantity'] ?? 99) <= $item['quantity'] ? 'disabled' : ''; ?>>
                                  <i class="bi bi-plus"></i>
                                </button>
                              </div>
                            </form>
                            <?php if (($item['stock_quantity'] ?? 0) < 10): ?>
                              <small class="text-warning d-block mt-1">Only <?php echo $item['stock_quantity'] ?? 0; ?> left</small>
                            <?php endif; ?>
                          </td>
                          <td class="align-middle">
                            <strong id="item-total-<?php echo $item['product_id']; ?>">
                              $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </strong>
                          </td>
                          <td class="align-middle">
                            <form method="POST" action="cart.php" class="d-inline">
                              <input type="hidden" name="action" value="remove_item">
                              <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                              <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                              <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to remove this item from your cart?')" title="Remove item">
                                <i class="bi bi-trash"></i>
                              </button>
                            </form>
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