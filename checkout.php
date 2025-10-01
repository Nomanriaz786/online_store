<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/models/Cart.php';
require_once __DIR__ . '/models/Order.php';
require_once __DIR__ . '/models/Product.php';

$auth = new Auth();
$cartModel = new Cart();
$orderModel = new Order();
$productModel = new Product();

// Require login
if (!$auth->isAuthenticated()) {
    header('Location: login.html');
    exit();
}

$userId = $_SESSION['user_id'];

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        // Get cart items
        $cartItems = $cartModel->getCartItems($userId);
        if (empty($cartItems)) {
            $error = 'Your cart is empty';
        } else {
            // Validate stock availability
            $stockError = false;
            foreach ($cartItems as $item) {
                $product = $productModel->find($item['product_id']);
                if (!$product || $product['stock_quantity'] < $item['quantity']) {
                    $stockError = true;
                    $error = "Insufficient stock for {$item['product_name']}";
                    break;
                }
            }
            
            if (!$stockError) {
                // Prepare order data according to Order model expectations
                $orderData = [
                    'shipping_name' => trim(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? '')),
                    'shipping_email' => $_POST['email'] ?? '',
                    'shipping_phone' => $_POST['phone'] ?? '',
                    'shipping_address' => $_POST['address'] ?? '',
                    'shipping_city' => $_POST['city'] ?? '',
                    'shipping_state' => $_POST['state'] ?? '',
                    'shipping_zip' => $_POST['zip'] ?? '',
                    'shipping_country' => 'US',
                    'payment_method' => $_POST['payment_method'] ?? 'credit_card',
                    'notes' => $_POST['order_notes'] ?? ''
                ];
                
                // Create order
                $result = $orderModel->createOrderFromCart($userId, $orderData);
                
                if ($result['success']) {
                    // Redirect to confirmation
                    header('Location: checkout-confirmation.php?order_id=' . $result['order_id']);
                    exit();
                } else {
                    $error = isset($result['errors']) ? implode(', ', $result['errors']) : ($result['message'] ?? 'Error creating order');
                }
            }
        }
    }
}

// Get cart items and summary
$cartItems = $cartModel->getCartItems($userId);
$cartSummary = $cartModel->getCartSummary($userId);

if (empty($cartItems)) {
    header('Location: cart.php');
    exit();
}

$subtotal = $cartSummary['total_amount'];
$tax = $subtotal * 0.08;
$total = $subtotal + $tax;

$pageTitle = "Checkout — My Store";
include 'partials/html-head.php';
?>
  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <main class="checkout-main">
    <div class="container py-5">
      
      <!-- Checkout Progress -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="checkout-progress">
            <div class="d-flex justify-content-between align-items-center">
              <div class="step active">
                <div class="step-circle">1</div>
                <span>Cart</span>
              </div>
              <div class="step-line"></div>
              <div class="step active">
                <div class="step-circle">2</div>
                <span>Checkout</span>
              </div>
              <div class="step-line"></div>
              <div class="step">
                <div class="step-circle">3</div>
                <span>Confirmation</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="checkoutForm">
        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="place_order">
        
        <div class="row">
          <!-- Checkout Form -->
          <div class="col-lg-8">
            
            <!-- Shipping Information -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Shipping Information</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="firstName" class="form-label">First Name *</label>
                      <input type="text" class="form-control" id="firstName" name="first_name" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="lastName" class="form-label">Last Name *</label>
                      <input type="text" class="form-control" id="lastName" name="last_name" required>
                    </div>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="email" class="form-label">Email Address *</label>
                  <input type="email" class="form-control" id="email" name="email" 
                         value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                  <label for="phone" class="form-label">Phone Number</label>
                  <input type="tel" class="form-control" id="phone" name="phone">
                </div>
                
                <div class="mb-3">
                  <label for="address" class="form-label">Street Address *</label>
                  <input type="text" class="form-control" id="address" name="address" 
                         placeholder="1234 Main St" required>
                </div>
                
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="city" class="form-label">City *</label>
                      <input type="text" class="form-control" id="city" name="city" required>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="mb-3">
                      <label for="state" class="form-label">State *</label>
                      <select class="form-select" id="state" name="state" required>
                        <option value="">Choose...</option>
                        <option value="AL">Alabama</option>
                        <option value="CA">California</option>
                        <option value="FL">Florida</option>
                        <option value="NY">New York</option>
                        <option value="TX">Texas</option>
                        <!-- Add more states as needed -->
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="mb-3">
                      <label for="zip" class="form-label">ZIP Code *</label>
                      <input type="text" class="form-control" id="zip" name="zip" required>
                    </div>
                  </div>
                </div>
                
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="saveAddress" name="save_address" value="1">
                  <label class="form-check-label" for="saveAddress">
                    Save this address for future orders
                  </label>
                </div>
              </div>
            </div>

            <!-- Payment Information -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Payment Information</h5>
              </div>
              <div class="card-body">
                
                <!-- Payment Method Selection -->
                <div class="mb-4">
                  <label class="form-label">Payment Method *</label>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <div class="form-check payment-method">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="creditCard" value="credit_card" checked>
                        <label class="form-check-label" for="creditCard">
                          <i class="bi bi-credit-card-2-front me-2"></i>Credit Card
                        </label>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-check payment-method">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="paypal" value="paypal">
                        <label class="form-check-label" for="paypal">
                          <i class="bi bi-paypal me-2"></i>PayPal
                        </label>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-check payment-method">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="applePay" value="apple_pay">
                        <label class="form-check-label" for="applePay">
                          <i class="bi bi-apple me-2"></i>Apple Pay
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Credit Card Form -->
                <div id="creditCardForm">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="mb-3">
                        <label for="cardNumber" class="form-label">Card Number *</label>
                        <input type="text" class="form-control" id="cardNumber" name="card_number" 
                               placeholder="1234 5678 9012 3456" maxlength="19">
                        <div class="form-text">
                          <i class="bi bi-shield-check text-success me-1"></i>Your payment information is secure and encrypted
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="cardCvv" class="form-label">CVV *</label>
                        <input type="text" class="form-control" id="cardCvv" name="card_cvv" 
                               placeholder="123" maxlength="4">
                      </div>
                    </div>
                  </div>
                  
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="cardExpiry" class="form-label">Expiry Date *</label>
                        <input type="text" class="form-control" id="cardExpiry" name="card_expiry" 
                               placeholder="MM/YY" maxlength="5">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="cardName" class="form-label">Name on Card *</label>
                        <input type="text" class="form-control" id="cardName" name="card_name" 
                               placeholder="John Doe">
                      </div>
                    </div>
                  </div>
                  
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="saveCard" name="save_card" value="1">
                    <label class="form-check-label" for="saveCard">
                      Save this card for future purchases (secure)
                    </label>
                  </div>
                </div>

                <!-- Alternative Payment Forms (hidden by default) -->
                <div id="paypalForm" class="d-none">
                  <div class="text-center py-4">
                    <i class="bi bi-paypal display-4 text-primary"></i>
                    <p class="mt-3">You will be redirected to PayPal to complete your payment.</p>
                  </div>
                </div>

                <div id="applePayForm" class="d-none">
                  <div class="text-center py-4">
                    <i class="bi bi-apple display-4"></i>
                    <p class="mt-3">Use Touch ID or Face ID to pay with Apple Pay.</p>
                  </div>
                </div>

              </div>
            </div>

            <!-- Order Notes -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-chat-text me-2"></i>Order Notes (Optional)</h5>
              </div>
              <div class="card-body">
                <textarea class="form-control" name="order_notes" rows="3" 
                          placeholder="Any special instructions for your order?"></textarea>
              </div>
            </div>

          </div>

          <!-- Order Summary -->
          <div class="col-lg-4">
            <div class="card sticky-top checkout-summary-sticky">
              <div class="card-header">
                <h5 class="mb-0">Order Summary</h5>
              </div>
              <div class="card-body">
                
                <!-- Order Items -->
                <div class="order-items mb-3">
                  <?php foreach ($cartItems as $item): ?>
                    <div class="d-flex align-items-center mb-3">
                      <?php if (!empty($item['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                             class="me-3 checkout-product-image">
                      <?php else: ?>
                        <div class="bg-light me-3 d-flex align-items-center justify-content-center checkout-placeholder-image">
                          <i class="bi bi-image text-muted"></i>
                        </div>
                      <?php endif; ?>
                      <div class="flex-grow-1">
                        <h6 class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                        <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                      </div>
                      <div class="text-end">
                        <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <hr>

                <!-- Price Breakdown -->
                <div class="price-breakdown">
                  <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span>Shipping:</span>
                    <span class="text-success">FREE</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span>Tax:</span>
                    <span>$<?php echo number_format($tax, 2); ?></span>
                  </div>
                  <hr>
                  <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <strong class="text-primary">$<?php echo number_format($total, 2); ?></strong>
                  </div>
                </div>

                <!-- Promo Code -->
                <div class="mb-3">
                  <div class="input-group">
                    <input type="text" class="form-control form-control-sm" 
                           placeholder="Promo code" id="promoCode">
                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="Utils.applyPromo()">
                      Apply
                    </button>
                  </div>
                </div>

                <!-- Place Order Button -->
                <div class="d-grid">
                  <button type="submit" class="btn btn-primary btn-lg" id="placeOrderBtn">
                    <i class="bi bi-lock-fill me-2"></i>Place Order
                  </button>
                </div>

                <!-- Security Info -->
                <div class="text-center mt-3">
                  <small class="text-muted">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    256-bit SSL encryption
                  </small>
                </div>

                <!-- Return Policy -->
                <div class="mt-3">
                  <small class="text-muted">
                    <strong>Free returns</strong> within 30 days. 
                    <a href="#" class="text-decoration-none">Learn more</a>
                  </small>
                </div>

              </div>
            </div>
          </div>
        </div>
      </form>
      
    </div>
  </main>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>