<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';

$auth = new Auth();
$productModel = new Product();
$categoryModel = new Category();

// Get featured products
try {
    $featuredProducts = $productModel->getFeaturedProducts(8);
    error_log("Featured products count: " . count($featuredProducts));
} catch (Exception $e) {
    error_log("Error fetching featured products: " . $e->getMessage());
    $featuredProducts = [];
}

// Get categories for navigation
try {
    $categories = $categoryModel->getAllCategories();
    error_log("Categories count: " . count($categories));
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

$pageTitle = "My Store — Premium Shopping Experience";
$pageDescription = "Experience exceptional shopping with our carefully curated collection. Quality guaranteed, fast shipping, and customer satisfaction.";

// Custom head content for SEO and CSRF token
$customHead = '<meta name="description" content="' . htmlspecialchars($pageDescription) . '">
<meta name="csrf-token" content="' . $auth->generateCSRFToken() . '">';

// Use the template system
include 'partials/html-head.php';
?>

  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <main>
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="hero-content">
              <h1 class="hero-title">
                Discover Premium
                <span class="text-gradient">Products</span>
              </h1>
              <p class="hero-subtitle">
                Experience exceptional shopping with our carefully curated collection.
                Quality guaranteed, fast shipping, and customer satisfaction at the heart of everything we do.
              </p>
              <div class="hero-cta">
                <a href="products.php" class="btn btn-primary btn-lg">
                  Start Shopping
                </a>
                <a href="#featured" class="btn btn-outline-primary btn-lg">
                  View Collection
                </a>
              </div>

              <!-- Trust indicators -->
              <div class="row mt-4 pt-3">
                <div class="col-4 text-center">
                  <div class="fw-bold text-primary">1000+</div>
                  <small class="text-muted">Happy Customers</small>
                </div>
                <div class="col-4 text-center">
                  <div class="fw-bold text-primary">4.9★</div>
                  <small class="text-muted">Rating</small>
                </div>
                <div class="col-4 text-center">
                  <div class="fw-bold text-primary">24/7</div>
                  <small class="text-muted">Support</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6 d-none d-lg-block">
            <div class="hero-image-container text-end">
              <img src="assets/img/hero_section.svg" class="img-fluid hero-image"
                alt="Woman working with shopping list and products" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Products Section -->
    <section id="featured" class="featured-section">
      <div class="container">
        <div class="row align-items-center mb-4">
          <div class="col-md-6">
            <h2 class="section-title">Featured Products</h2>
            <p class="section-subtitle">Handpicked items just for you</p>
          </div>
          <div class="col-md-6">
            <div class="search-enhanced ms-auto">
              <label for="featureSearch" class="visually-hidden">Search featured products</label>
              <input id="featureSearch" class="form-control" type="search" placeholder="Search product titles...">
            </div>
          </div>
        </div>

        <!-- Search Results Counter -->
        <div class="row mb-3">
          <div class="col-12">
            <div id="searchResultsCount" class="search-results-count text-muted small d-none">
              Showing results
            </div>
          </div>
        </div>

        <div class="row" id="featuredGrid" aria-live="polite">
          <?php if (!empty($featuredProducts)): ?>
            <?php foreach ($featuredProducts as $index => $product): ?>
              <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4 delay-<?php echo ($index + 1); ?>" data-category="<?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>">
                <article class="card product-card h-100" aria-label="<?php echo htmlspecialchars($product['name']); ?>">
                  <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'assets/img/placeholder.svg'); ?>" 
                       class="card-img-top product-img" 
                       alt="<?php echo htmlspecialchars($product['name']); ?>" 
                       loading="lazy">
                  <div class="card-body d-flex flex-column">
                    <h3 class="h6 card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                    <?php if (!empty($product['description'])): ?>
                      <p class="card-text text-muted small">
                        <?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>
                        <?php if (strlen($product['description']) > 80): ?>...<?php endif; ?>
                      </p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                      <span class="fw-bold fs-5 text-gradient" data-price="<?php echo $product['price']; ?>">
                        $<?php echo number_format($product['price'], 2); ?>
                      </span>
                      <?php if ($product['stock_quantity'] > 0): ?>
                        <button class="btn btn-primary btn-sm add-to-cart-btn" 
                                data-product-id="<?php echo $product['id']; ?>"
                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                data-product-price="<?php echo $product['price']; ?>">
                          Add to Cart
                        </button>
                      <?php else: ?>
                        <button class="btn btn-outline-secondary btn-sm" disabled>
                          Out of Stock
                        </button>
                      <?php endif; ?>
                    </div>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12 text-center py-5">
              <div class="no-products-message">
                <i class="bi bi-box-seam mb-3" style="font-size: 3rem; color: var(--bs-gray-400);"></i>
                <h3 class="h5 mb-2">No featured products available</h3>
                <p class="text-muted">Check back later for exciting new products!</p>
                <a href="products.php" class="btn btn-outline-primary">Browse All Products</a>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- No Results Message -->
        <div class="row d-none" id="noResults">
          <div class="col-12 text-center py-5">
            <div class="no-results-message">
              <i class="bi bi-search mb-3 no-results-icon"></i>
              <h3 class="h5 mb-2">No products found</h3>
              <p class="text-muted">Try adjusting your search or filter criteria</p>
              <button class="btn btn-outline-primary" id="clearSearch">Clear Search</button>
            </div>
          </div>
        </div>

        <div class="text-center mt-5">
          <a href="products.php" class="btn btn-primary btn-lg">View All Products</a>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
      <div class="container">
        <div class="row g-4">
          <div class="col-6 col-lg-3">
            <div class="stat-card text-center">
              <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
              </div>
              <h3 class="stat-number">1000+</h3>
              <p class="stat-label">Happy Customers</p>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="stat-card text-center">
              <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                  <path d="M3 6h18" />
                  <path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
              </div>
              <h3 class="stat-number"><?php echo count($featuredProducts); ?>+</h3>
              <p class="stat-label">Featured Products</p>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="stat-card text-center">
              <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10" />
                  <polyline points="12,6 12,12 16,14" />
                </svg>
              </div>
              <h3 class="stat-number">24/7</h3>
              <p class="stat-label">Support</p>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="stat-card text-center">
              <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="13,2 3,14 12,14 11,22 21,10 12,10 13,2" />
                </svg>
              </div>
              <h3 class="stat-number">Fast</h3>
              <p class="stat-label">Delivery</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <button id="scrollTop" class="btn btn-primary btn-circle" aria-label="Scroll to top">
    <i class="bi bi-arrow-up"></i>
  </button>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

  <!-- Success/Error Messages -->
  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
      <div class="toast show" role="alert">
        <div class="toast-header bg-success text-white">
          <i class="bi bi-check-circle me-2"></i>
          <strong class="me-auto">Success</strong>
        </div>
        <div class="toast-body">
          <?php 
          echo htmlspecialchars($_SESSION['success_message']); 
          unset($_SESSION['success_message']);
          ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
      <div class="toast show" role="alert">
        <div class="toast-header bg-danger text-white">
          <i class="bi bi-exclamation-circle me-2"></i>
          <strong class="me-auto">Error</strong>
        </div>
        <div class="toast-body">
          <?php 
          echo htmlspecialchars($_SESSION['error_message']); 
          unset($_SESSION['error_message']);
          ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

<?php
// Use the HTML footer template
include 'partials/html-footer.php';
?>