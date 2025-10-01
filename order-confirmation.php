<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/models/Order.php';

$auth = new Auth();

// Require login
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit();
}

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header('Location: index.php');
    exit();
}

$orderModel = new Order();
$order = $orderModel->getOrderWithItems($orderId);

// Debug: Log what we got from the database
error_log("Order data for ID $orderId: " . print_r($order, true));

// Make sure the order belongs to the current user
if (!$order || $order['user_id'] != $auth->getCurrentUserId()) {
    error_log("Order access denied - Order: " . ($order ? 'found' : 'not found') . 
              ", User ID: " . $auth->getCurrentUserId() . 
              ", Order User ID: " . ($order['user_id'] ?? 'N/A'));
    header('Location: index.php');
    exit();
}

$pageTitle = "Order Confirmation — My Store";
include 'partials/html-head.php';
?>

<!-- Header -->
<?php include 'partials/header.php'; ?>

<main class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="text-center mb-4">
                            <div class="checkout-success-icon mb-3">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h1 class="mb-3">Order Confirmed!</h1>
                            <p class="lead text-muted">Thank you for your purchase. Your order has been successfully placed.</p>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Order Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Order Number:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <?php echo htmlspecialchars($order['order_number'] ?? '#' . $order['id']); ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Order Date:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Status:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <?php 
                                        $status = ucfirst($order['status']);
                                        switch($order['status']) {
                                            case 'pending':
                                                $statusClass = 'bg-warning';
                                                break;
                                            case 'processing':
                                                $statusClass = 'bg-info';
                                                break;
                                            case 'shipped':
                                                $statusClass = 'bg-primary';
                                                break;
                                            case 'delivered':
                                                $statusClass = 'bg-success';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'bg-danger';
                                                break;
                                            default:
                                                $statusClass = 'bg-secondary';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Total Amount:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Order Items</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($order['items'])): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($order['items'] as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'assets/img/placeholder.svg'); ?>" 
                                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                                 class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                                <?php if (!empty($item['product_sku'])): ?>
                                                                <small class="text-muted">SKU: <?php echo htmlspecialchars($item['product_sku']); ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo (int)$item['quantity']; ?></td>
                                                    <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                                    <td><strong>$<?php echo number_format($item['total_price'], 2); ?></strong></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="3" class="text-end">Subtotal:</th>
                                                    <th>$<?php echo number_format($order['subtotal'], 2); ?></th>
                                                </tr>
                                                <?php if ($order['shipping_cost'] > 0): ?>
                                                <tr>
                                                    <th colspan="3" class="text-end">Shipping:</th>
                                                    <th>$<?php echo number_format($order['shipping_cost'], 2); ?></th>
                                                </tr>
                                                <?php endif; ?>
                                                <?php if ($order['tax_amount'] > 0): ?>
                                                <tr>
                                                    <th colspan="3" class="text-end">Tax:</th>
                                                    <th>$<?php echo number_format($order['tax_amount'], 2); ?></th>
                                                </tr>
                                                <?php endif; ?>
                                                <tr class="table-active">
                                                    <th colspan="3" class="text-end">Total Amount:</th>
                                                    <th>$<?php echo number_format($order['total_amount'], 2); ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-box-seam display-1 text-muted mb-3"></i>
                                        <h5>No items found</h5>
                                        <p class="text-muted">There was an issue loading the order items.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Shipping Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Shipping Address</h6>
                                        <p class="mb-0"><?php echo htmlspecialchars($order['shipping_first_name'] . ' ' . $order['shipping_last_name']); ?></p>
                                        <p class="mb-0"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                        <p class="mb-0"><?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_state']); ?> <?php echo htmlspecialchars($order['shipping_zip_code']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Contact Information</h6>
                                        <p class="mb-0">Email: <?php echo htmlspecialchars($order['shipping_email']); ?></p>
                                        <p class="mb-0">Phone: <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="profile.php" class="btn btn-primary me-3">View Order History</a>
                            <a href="index.php" class="btn btn-outline-secondary">Continue Shopping</a>
                        </div>

                        <div class="alert alert-info mt-4">
                            <h6 class="alert-heading">What's Next?</h6>
                            <ul class="mb-0">
                                <li>You will receive an email confirmation shortly</li>
                                <li>We'll send you tracking information once your order ships</li>
                                <li>Estimated delivery: 3-5 business days</li>
                                <li>Need help? <a href="mailto:support@mystore.com">Contact our support team</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>