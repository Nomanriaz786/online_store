<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$pageTitle = "Products — My Store";

// Add CSRF token to head
$customHead = '<meta name="csrf-token" content="' . $auth->generateCSRFToken() . '">';

// Use the template system
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
            
            <!-- Filter and Search -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="input-group">
                  <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
                  <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                    <i class="bi bi-search"></i>
                  </button>
                </div>
              </div>
              <div class="col-md-6">
                <select class="form-select" id="categoryFilter">
                  <option value="all">All Categories</option>
                </select>
              </div>
            </div>

            <!-- View Toggle -->
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="viewMode" id="gridView" checked>
                <label class="btn btn-outline-primary" for="gridView">
                  <i class="bi bi-grid-3x3-gap"></i> Grid
                </label>
                <input type="radio" class="btn-check" name="viewMode" id="listView">
                <label class="btn btn-outline-primary" for="listView">
                  <i class="bi bi-list"></i> List
                </label>
              </div>
              
              <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  Sort by
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item sort-option" href="#" data-sort="name">Name</a></li>
                  <li><a class="dropdown-item sort-option" href="#" data-sort="price-low">Price: Low to High</a></li>
                  <li><a class="dropdown-item sort-option" href="#" data-sort="price-high">Price: High to Low</a></li>
                </ul>
              </div>
            </div>

            <!-- Products Grid -->
            <div id="productsContainer" class="row">
              <!-- Products will be loaded here by JavaScript -->
            </div>
            
            <!-- Loading State -->
            <div id="loadingSpinner" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            
            <!-- No Products Message -->
            <div id="noProducts" class="text-center py-5 d-none">
              <i class="bi bi-search display-1 text-muted mb-3"></i>
              <h3>No products found</h3>
              <p class="text-muted">Try adjusting your search or filter criteria.</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>