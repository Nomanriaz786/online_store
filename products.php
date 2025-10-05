<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';

$auth = new Auth();
$productModel = new Product();
$categoryModel = new Category();

// Get query parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryId = isset($_GET['category']) && $_GET['category'] !== 'all' ? (int)$_GET['category'] : null;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'grid';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12; // Products per page

// Validate sort parameters
$allowedSortFields = ['name', 'price', 'created_at'];
$allowedSortOrders = ['ASC', 'DESC'];
$allowedViews = ['grid', 'list'];

if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'name';
}
if (!in_array(strtoupper($sortOrder), $allowedSortOrders)) {
    $sortOrder = 'ASC';
}
if (!in_array($viewMode, $allowedViews)) {
    $viewMode = 'grid';
}

// Load categories for filter dropdown
$categories = $categoryModel->getAllCategories();

// Load products
try {
    $result = $productModel->getAllProducts($page, $limit, $categoryId, $search, $sortBy, $sortOrder);
    $products = $result['products'];
    $totalProducts = $result['total'];
    $totalPages = $result['pages'];
    $currentPage = $result['current_page'];
} catch (Exception $e) {
    error_log("Error loading products: " . $e->getMessage());
    $products = [];
    $totalProducts = 0;
    $totalPages = 0;
    $currentPage = 1;
}

$pageTitle = "Products — My Store";

// Build current URL for pagination/filtering
function buildUrl($params = []) {
    $currentParams = $_GET;
    $newParams = array_merge($currentParams, $params);
    
    // Remove empty values
    $newParams = array_filter($newParams, function($value) {
        return $value !== '' && $value !== null;
    });
    
    return '?' . http_build_query($newParams);
}

include 'partials/html-head.php';
?>

  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <main>
    <!-- Products Section -->
    <section class="py-5">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <h1 class="h2 mb-4">Our Products</h1>

            <!-- Filter and Search Form -->
            <form method="GET" action="products.php" class="row mb-4">
              <div class="col-md-6">
                <div class="input-group">
                  <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                  <button class="btn btn-outline-secondary" type="submit">
                    <i class="bi bi-search"></i>
                  </button>
                </div>
              </div>
              <div class="col-md-6">
                <select class="form-select" name="category" onchange="this.form.submit()">
                  <option value="all">All Categories</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </form>

            <!-- View Toggle and Sort -->
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="btn-group" role="group" aria-label="View mode">
                <a href="<?php echo buildUrl(['view' => 'grid']); ?>" class="btn <?php echo $viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                  <i class="bi bi-grid-3x3-gap"></i> Grid
                </a>
                <a href="<?php echo buildUrl(['view' => 'list']); ?>" class="btn <?php echo $viewMode === 'list' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                  <i class="bi bi-list"></i> List
                </a>
              </div>

              <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  Sort by
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item <?php echo $sortBy === 'name' && $sortOrder === 'ASC' ? 'active' : ''; ?>" href="<?php echo buildUrl(['sort' => 'name', 'order' => 'ASC']); ?>">Name (A-Z)</a></li>
                  <li><a class="dropdown-item <?php echo $sortBy === 'name' && $sortOrder === 'DESC' ? 'active' : ''; ?>" href="<?php echo buildUrl(['sort' => 'name', 'order' => 'DESC']); ?>">Name (Z-A)</a></li>
                  <li><a class="dropdown-item <?php echo $sortBy === 'price' && $sortOrder === 'ASC' ? 'active' : ''; ?>" href="<?php echo buildUrl(['sort' => 'price', 'order' => 'ASC']); ?>">Price: Low to High</a></li>
                  <li><a class="dropdown-item <?php echo $sortBy === 'price' && $sortOrder === 'DESC' ? 'active' : ''; ?>" href="<?php echo buildUrl(['sort' => 'price', 'order' => 'DESC']); ?>">Price: High to Low</a></li>
                  <li><a class="dropdown-item <?php echo $sortBy === 'created_at' && $sortOrder === 'DESC' ? 'active' : ''; ?>" href="<?php echo buildUrl(['sort' => 'created_at', 'order' => 'DESC']); ?>">Newest First</a></li>
                </ul>
              </div>
            </div>

            <!-- Results Summary -->
            <?php if ($totalProducts > 0): ?>
              <div class="mb-3 text-muted">
                Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> products
                <?php if (!empty($search)): ?>
                  for "<?php echo htmlspecialchars($search); ?>"
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <!-- Products Container -->
            <?php if (!empty($products)): ?>
              <div class="row <?php echo $viewMode === 'list' ? '' : 'row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4'; ?>" id="productsContainer">
                <?php foreach ($products as $product): ?>
                  <?php if ($viewMode === 'list'): ?>
                    <!-- List View -->
                    <div class="col-12 mb-3">
                      <div class="card product-card h-100">
                        <div class="row g-0">
                          <div class="col-md-3">
                            <img src="<?php echo htmlspecialchars($product['image_path'] ?? 'assets/img/placeholder.svg'); ?>"
                                 class="img-fluid rounded-start h-100 object-fit-cover"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="max-height: 200px;">
                          </div>
                          <div class="col-md-9">
                            <div class="card-body d-flex flex-column justify-content-between h-100">
                              <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                  <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                                  <span class="h5 text-primary mb-0">$<?php echo number_format($product['price'], 2); ?></span>
                                </div>
                              </div>
                              <div class="d-flex gap-2 mt-3">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">View Details</a>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                  <form method="POST" action="cart.php" class="d-inline">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                                    <button type="submit" class="btn btn-primary">
                                      <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                    </button>
                                  </form>
                                <?php else: ?>
                                  <button class="btn btn-outline-secondary" disabled>Out of Stock</button>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php else: ?>
                    <!-- Grid View -->
                    <div class="col">
                      <div class="card product-card h-100">
                        <img src="<?php echo htmlspecialchars($product['image_path'] ?? 'assets/img/placeholder.svg'); ?>"
                             class="card-img-top"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                          <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                          <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 80)); ?>
                            <?php if (strlen($product['description'] ?? '') > 80): ?>...<?php endif; ?>
                          </p>
                          <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                              <span class="h5 text-primary mb-0 fw-bold">$<?php echo number_format($product['price'], 2); ?></span>
                              <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                            </div>
                            <div class="d-grid gap-2">
                              <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-1"></i>View Details
                              </a>
                              <?php if ($product['stock_quantity'] > 0): ?>
                                <form method="POST" action="cart.php">
                                  <input type="hidden" name="action" value="add_to_cart">
                                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                  <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                                  <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                  </button>
                                </form>
                              <?php else: ?>
                                <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                  <i class="bi bi-x-circle me-1"></i>Out of Stock
                                </button>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>

              <!-- Pagination -->
              <?php if ($totalPages > 1): ?>
                <nav aria-label="Product pagination" class="mt-4">
                  <ul class="pagination justify-content-center">
                    <?php if ($currentPage > 1): ?>
                      <li class="page-item">
                        <a class="page-link" href="<?php echo buildUrl(['page' => $currentPage - 1]); ?>">Previous</a>
                      </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                      <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo buildUrl(['page' => $i]); ?>"><?php echo $i; ?></a>
                      </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                      <li class="page-item">
                        <a class="page-link" href="<?php echo buildUrl(['page' => $currentPage + 1]); ?>">Next</a>
                      </li>
                    <?php endif; ?>
                  </ul>
                </nav>
              <?php endif; ?>

            <?php else: ?>
              <!-- No Products Message -->
              <div class="text-center py-5">
                <i class="bi bi-search display-1 text-muted mb-3"></i>
                <h3>No products found</h3>
                <p class="text-muted">Try adjusting your search or filter criteria.</p>
                <?php if (!empty($search) || $categoryId): ?>
                  <a href="products.php" class="btn btn-outline-primary">Clear Filters</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>