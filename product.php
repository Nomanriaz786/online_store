<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';

$auth = new Auth();
$productModel = new Product();
$categoryModel = new Category();

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load product details
$product = null;
$category = null;
$relatedProducts = [];

if ($productId > 0) {
    $product = $productModel->find($productId);
    
    if ($product) {
        // Load category info
        if (!empty($product['category_id'])) {
            $category = $categoryModel->find($product['category_id']);
        }
        
        // Load related products (same category, excluding current product)
        if (!empty($product['category_id'])) {
            $relatedProducts = $productModel->getProductsByCategory($product['category_id'], 4);
            // Remove current product from related products
            $relatedProducts = array_filter($relatedProducts, function($p) use ($productId) {
                return $p['id'] != $productId;
            });
            $relatedProducts = array_slice($relatedProducts, 0, 4);
        }
    }
}

$pageTitle = $product ? htmlspecialchars($product['name']) . " — My Store" : "Product Not Found — My Store";

// Use the template system
include 'partials/html-head.php';
?>

  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <main>
    <!-- Product Details Section -->
    <section class="py-5">
      <div class="container">
        <?php if (!$product): ?>
          <!-- Product Not Found -->
          <div class="text-center py-5">
            <i class="bi bi-exclamation-triangle display-1 text-warning mb-3"></i>
            <h2>Product Not Found</h2>
            <p class="text-muted">The product you're looking for doesn't exist or has been removed.</p>
            <a href="products.php" class="btn btn-primary">Browse All Products</a>
          </div>
        <?php else: ?>
          <!-- Product Content -->
          <div>
          <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item"><a href="products.php">Products</a></li>
              <?php if ($category): ?>
                <li class="breadcrumb-item">
                  <a href="products.php?category=<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                  </a>
                </li>
              <?php endif; ?>
              <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars($product['name']); ?>
              </li>
            </ol>
          </nav>

          <div class="row">
            <!-- Product Image -->
            <div class="col-lg-6 mb-4">
              <div class="position-relative">
                <img src="<?php echo htmlspecialchars($product['image_path'] ?? 'assets/img/placeholder.svg'); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="img-fluid rounded shadow-sm w-100" 
                     style="max-height: 500px; object-fit: cover;">
                <?php
                // Check if product is new (created within last 7 days)
                $isNew = (strtotime($product['created_at']) > strtotime('-7 days'));
                if ($isNew):
                ?>
                  <div class="position-absolute top-0 end-0 m-3">
                    <span class="badge bg-primary">New</span>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-6">
              <div class="ps-lg-4">
                <h1 class="h3 mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="mb-3">
                  <span class="badge bg-secondary">
                    <?php echo htmlspecialchars($category['name'] ?? 'Uncategorized'); ?>
                  </span>
                </div>
                
                <div class="d-flex align-items-center mb-4">
                  <span class="h4 text-primary mb-0 me-3 fw-bold">
                    $<?php echo number_format($product['price'], 2); ?>
                  </span>
                </div>

                <div class="mb-4">
                  <h5>Description</h5>
                  <p class="text-muted">
                    <?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?>
                  </p>
                </div>

                <!-- Quantity and Add to Cart -->
                <form method="POST" action="cart.php">
                  <input type="hidden" name="action" value="add_to_cart">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                  
                  <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                      <label for="quantity" class="form-label">Quantity</label>
                      <div class="input-group">
                        <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('quantity').stepDown()">
                          <i class="bi bi-dash"></i>
                        </button>
                        <input type="number" class="form-control text-center" id="quantity" name="quantity" 
                               value="1" min="1" max="<?php echo min($product['stock_quantity'], 10); ?>">
                        <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('quantity').stepUp()">
                          <i class="bi bi-plus"></i>
                        </button>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label d-block">Stock Status</label>
                      <div>
                        <?php if ($product['stock_quantity'] > 0): ?>
                          <span class="badge bg-success fs-6">
                            <i class="bi bi-check-circle me-1"></i>In Stock (<?php echo $product['stock_quantity']; ?>)
                          </span>
                        <?php else: ?>
                          <span class="badge bg-danger fs-6">
                            <i class="bi bi-x-circle me-1"></i>Out of Stock
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="d-grid gap-2">
                    <?php if ($product['stock_quantity'] > 0): ?>
                      <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-cart-plus me-2"></i>Add to Cart
                      </button>
                      <a href="checkout.php?product_id=<?php echo $product['id']; ?>&quantity=1" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-lightning me-2"></i>Buy Now
                      </a>
                    <?php else: ?>
                      <button type="button" class="btn btn-secondary btn-lg" disabled>
                        <i class="bi bi-x-circle me-2"></i>Out of Stock
                      </button>
                    <?php endif; ?>
                  </div>
                </form>

                <!-- Product Features -->
                <div class="mt-4">
                  <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Free shipping on orders over $50</li>
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>30-day return policy</li>
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>1 year warranty</li>
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Secure payment</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Product Tabs -->
          <div class="mt-5">
            <ul class="nav nav-tabs" id="productTabs">
              <li class="nav-item">
                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">
                  Details
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" data-bs-target="#specifications" type="button">
                  Specifications
                </button>
              </li>
            </ul>
            <div class="tab-content border border-top-0 p-4" id="productTabsContent">
              <div class="tab-pane fade show active" id="details">
                <h5>Product Details</h5>
                <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No detailed description available.')); ?></p>
                
                <h6 class="mt-4">Product Information</h6>
                <ul class="list-unstyled">
                  <li><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></li>
                  <li><strong>Category:</strong> <?php echo htmlspecialchars($category['name'] ?? 'Uncategorized'); ?></li>
                  <li><strong>Stock:</strong> <?php echo $product['stock_quantity']; ?> available</li>
                  <li><strong>Added:</strong> <?php echo date('F j, Y', strtotime($product['created_at'])); ?></li>
                </ul>
              </div>
              <div class="tab-pane fade" id="specifications">
                <h5>Specifications</h5>
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                      <th width="30%">Product Name</th>
                      <td><?php echo htmlspecialchars($product['name']); ?></td>
                    </tr>
                    <tr>
                      <th>SKU</th>
                      <td><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                      <th>Category</th>
                      <td><?php echo htmlspecialchars($category['name'] ?? 'Uncategorized'); ?></td>
                    </tr>
                    <tr>
                      <th>Price</th>
                      <td>$<?php echo number_format($product['price'], 2); ?></td>
                    </tr>
                    <tr>
                      <th>Stock Quantity</th>
                      <td><?php echo $product['stock_quantity']; ?></td>
                    </tr>
                    <tr>
                      <th>Warranty</th>
                      <td>1 Year Manufacturer Warranty</td>
                    </tr>
                    <tr>
                      <th>Shipping</th>
                      <td>Free shipping on orders over $50</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Related Products -->
          <?php if (!empty($relatedProducts)): ?>
            <div class="mt-5">
              <h3 class="mb-4">You Might Also Like</h3>
              <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                  <div class="col">
                    <div class="card product-card h-100">
                      <img src="<?php echo htmlspecialchars($relatedProduct['image_path'] ?? 'assets/img/placeholder.svg'); ?>"
                           class="card-img-top"
                           alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                           style="height: 200px; object-fit: cover;">
                      <div class="card-body d-flex flex-column">
                        <h5 class="card-title h6"><?php echo htmlspecialchars($relatedProduct['name']); ?></h5>
                        <p class="card-text text-muted small flex-grow-1">
                          <?php echo htmlspecialchars(substr($relatedProduct['description'] ?? '', 0, 60)); ?>
                          <?php if (strlen($relatedProduct['description'] ?? '') > 60): ?>...<?php endif; ?>
                        </p>
                        <div class="mt-auto">
                          <div class="mb-3">
                            <span class="h6 text-primary fw-bold">
                              $<?php echo number_format($relatedProduct['price'], 2); ?>
                            </span>
                          </div>
                          <div class="d-grid gap-2">
                            <a href="product.php?id=<?php echo $relatedProduct['id']; ?>" class="btn btn-outline-primary btn-sm">
                              <i class="bi bi-eye me-1"></i>View Details
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>