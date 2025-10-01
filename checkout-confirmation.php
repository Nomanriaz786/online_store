<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/models/Order.php';

$auth = new Auth();
$orderModel = new Order();

// Require login
if (!$auth->isAuthenticated()) {
    header('Location: login.html');
    exit();
}

// Get order ID from URL
$orderId = $_GET['order_id'] ?? null;
if (!$orderId) {
    header('Location: index.php');
    exit();
}

// Get order details
$order = $orderModel->getOrderWithItems($orderId);
if (!$order || $order['user_id'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit();
}

// Get order items
$orderItems = $orderModel->getOrderItems($orderId);

$pageTitle = "Order Confirmation — My Store";

include 'partials/html-head.php'; 
?>


<body>
  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <main class="confirmation-main">
    <div class="container py-5">
      
      <!-- Checkout Progress -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="checkout-progress">
            <div class="d-flex justify-content-between align-items-center">
              <div class="step completed">
                <div class="step-circle"><i class="bi bi-check"></i></div>
                <span>Cart</span>
              </div>
              <div class="step-line completed"></div>
              <div class="step completed">
                <div class="step-circle"><i class="bi bi-check"></i></div>
                <span>Checkout</span>
              </div>
              <div class="step-line completed"></div>
              <div class="step active">
                <div class="step-circle">3</div>
                <span>Confirmation</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Message -->
      <div class="row justify-content-center mb-5">
        <div class="col-md-8 text-center">
          <div class="success-animation mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
          </div>
          <h1 class="text-success mb-3">Order Placed Successfully!</h1>
          <p class="lead text-muted mb-4">
            Thank you for your purchase. Your order has been received and is being processed.
          </p>
          <div class="alert alert-info d-inline-block">
            <strong>Order Number: #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></strong>
          </div>
        </div>
      </div>

      <div class="row">
        
        <!-- Order Details -->
        <div class="col-lg-8">
          
          <!-- Order Information -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Order Information</h5>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <h6>Order Details</h6>
                  <p class="mb-1"><strong>Order Number:</strong> #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></p>
                  <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                  <p class="mb-1"><strong>Status:</strong> 
                    <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'processing' ? 'warning' : 'info'); ?>">
                      <?php echo ucfirst($order['status']); ?>
                    </span>
                  </p>
                  <p class="mb-0"><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>
                <div class="col-md-6">
                  <h6>Shipping Address</h6>
                  <address class="mb-0">
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                  </address>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Order Items</h5>
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
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $calculatedSubtotal = 0;
                    foreach ($orderItems as $item): 
                        $itemPrice = $item['unit_price'] ?? 0;
                        $itemTotal = $item['total_price'] ?? ($itemPrice * $item['quantity']);
                        $calculatedSubtotal += $itemTotal;
                    ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <?php if (!empty($item['image_url'])): ?>
                              <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                   alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                   class="me-3" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                              <div class="bg-light me-3 d-flex align-items-center justify-content-center" 
                                   style="width: 60px; height: 60px; border-radius: 8px;">
                                <i class="bi bi-image text-muted"></i>
                              </div>
                            <?php endif; ?>
                            <div>
                              <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                              <?php if (!empty($item['product_sku'])): ?>
                                <small class="text-muted">SKU: <?php echo htmlspecialchars($item['product_sku']); ?></small>
                              <?php endif; ?>
                            </div>
                          </div>
                        </td>
                        <td class="align-middle">$<?php echo number_format($itemPrice, 2); ?></td>
                        <td class="align-middle"><?php echo $item['quantity']; ?></td>
                        <td class="align-middle">
                          <strong>$<?php echo number_format($itemTotal, 2); ?></strong>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Order Timeline -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Order Timeline</h5>
            </div>
            <div class="card-body">
              <div class="timeline">
                <div class="timeline-item completed">
                  <div class="timeline-marker">
                    <i class="bi bi-check-circle-fill"></i>
                  </div>
                  <div class="timeline-content">
                    <h6>Order Placed</h6>
                    <small class="text-muted"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></small>
                    <p class="mb-0">Your order has been successfully placed and payment confirmed.</p>
                  </div>
                </div>
                
                <div class="timeline-item <?php echo in_array($order['status'], ['processing', 'shipped', 'completed']) ? 'completed' : 'pending'; ?>">
                  <div class="timeline-marker">
                    <?php if (in_array($order['status'], ['processing', 'shipped', 'completed'])): ?>
                      <i class="bi bi-check-circle-fill"></i>
                    <?php else: ?>
                      <i class="bi bi-circle"></i>
                    <?php endif; ?>
                  </div>
                  <div class="timeline-content">
                    <h6>Order Processing</h6>
                    <small class="text-muted">
                      <?php echo in_array($order['status'], ['processing', 'shipped', 'completed']) ? 'Completed' : 'Pending'; ?>
                    </small>
                    <p class="mb-0">Your order is being prepared for shipment.</p>
                  </div>
                </div>
                
                <div class="timeline-item <?php echo in_array($order['status'], ['shipped', 'completed']) ? 'completed' : 'pending'; ?>">
                  <div class="timeline-marker">
                    <?php if (in_array($order['status'], ['shipped', 'completed'])): ?>
                      <i class="bi bi-check-circle-fill"></i>
                    <?php else: ?>
                      <i class="bi bi-circle"></i>
                    <?php endif; ?>
                  </div>
                  <div class="timeline-content">
                    <h6>Order Shipped</h6>
                    <small class="text-muted">
                      <?php echo in_array($order['status'], ['shipped', 'completed']) ? 'Completed' : 'Pending'; ?>
                    </small>
                    <p class="mb-0">Your order has been shipped and is on its way.</p>
                  </div>
                </div>
                
                <div class="timeline-item <?php echo $order['status'] === 'completed' ? 'completed' : 'pending'; ?>">
                  <div class="timeline-marker">
                    <?php if ($order['status'] === 'completed'): ?>
                      <i class="bi bi-check-circle-fill"></i>
                    <?php else: ?>
                      <i class="bi bi-circle"></i>
                    <?php endif; ?>
                  </div>
                  <div class="timeline-content">
                    <h6>Order Delivered</h6>
                    <small class="text-muted">
                      <?php echo $order['status'] === 'completed' ? 'Completed' : 'Pending'; ?>
                    </small>
                    <p class="mb-0">Your order has been successfully delivered.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
          
          <!-- Order Summary -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
              <?php
                $subtotal = 0;
                foreach ($orderItems as $item) {
                    $subtotal += $item['total_price'] ?? (($item['unit_price'] ?? 0) * $item['quantity']);
                }
                $tax = $subtotal * 0.08; // 8% tax
                $total = $order['total_amount'] ?? ($subtotal + $tax);
              ?>
              
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
              <div class="d-flex justify-content-between">
                <strong>Total:</strong>
                <strong class="text-primary">$<?php echo number_format($total, 2); ?></strong>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="card mb-4">
            <div class="card-body">
              <div class="d-grid gap-2">
                <button class="btn btn-primary" onclick="printOrder()">
                  <i class="bi bi-printer me-2"></i>Print Order
                </button>
                <a href="user.html" class="btn btn-outline-primary">
                  <i class="bi bi-person me-2"></i>View All Orders
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                </a>
              </div>
            </div>
          </div>

          <!-- Support -->
          <div class="card">
            <div class="card-body">
              <h6 class="card-title">Need Help?</h6>
              <p class="card-text">If you have any questions about your order, feel free to contact us.</p>
              <div class="d-grid gap-2">
                <a href="#" class="btn btn-outline-info btn-sm">
                  <i class="bi bi-chat-dots me-2"></i>Live Chat
                </a>
                <a href="#" class="btn btn-outline-info btn-sm">
                  <i class="bi bi-envelope me-2"></i>Email Support
                </a>
                <a href="tel:+1234567890" class="btn btn-outline-info btn-sm">
                  <i class="bi bi-telephone me-2"></i>Call Us
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Email Confirmation Notice -->
      <div class="row mt-5">
        <div class="col-12">
          <div class="alert alert-info" role="alert">
            <i class="bi bi-envelope-check me-2"></i>
            <strong>Email Confirmation:</strong> We've sent a confirmation email to your registered email address with order details and tracking information.
          </div>
        </div>
      </div>
      
    </div>
  </main>

  <!-- Footer -->
  <?php include 'partials/html-footer.php'; ?>