<?php
$pageTitle = "Product Details — My Store";

// Use the template system
include 'partials/html-head.php';
?>

  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <main>
    <!-- Product Details Section -->
    <section class="py-5">
      <div class="container">
        <!-- Loading State -->
        <div id="productLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading product...</span>
          </div>
        </div>

        <!-- Product Not Found -->
        <div id="productNotFound" class="text-center py-5 d-none">
          <i class="bi bi-exclamation-triangle display-1 text-warning mb-3"></i>
          <h2>Product Not Found</h2>
          <p class="text-muted">The product you're looking for doesn't exist or has been removed.</p>
          <a href="products.php" class="btn btn-primary">Browse All Products</a>
        </div>

        <!-- Product Content -->
        <div id="productContent" class="d-none">
          <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item"><a href="products.php">Products</a></li>
              <li class="breadcrumb-item"><span id="productCategoryBreadcrumb">Category</span></li>
              <li class="breadcrumb-item active" aria-current="page" id="productNameBreadcrumb">Product</li>
            </ol>
          </nav>

          <div class="row">
            <!-- Product Image -->
            <div class="col-lg-6 mb-4">
              <div class="position-relative">
                <img id="productImage" src="" alt="" class="img-fluid rounded shadow-sm">
                <div class="position-absolute top-0 end-0 m-3">
                  <span id="productBadge" class="badge bg-primary d-none">New</span>
                </div>
              </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-6">
              <div class="ps-lg-4">
                <h1 id="productName" class="h3 mb-3"></h1>
                <div class="mb-3">
                  <span class="badge bg-secondary" id="productCategory"></span>
                </div>
                
                <div class="d-flex align-items-center mb-4">
                  <span class="h4 text-primary mb-0 me-3" id="productPrice"></span>
                  <span class="text-muted text-decoration-line-through" id="originalPrice" style="display: none;"></span>
                </div>

                <div class="mb-4">
                  <h5>Description</h5>
                  <p id="productDescription" class="text-muted"></p>
                </div>

                <!-- Quantity and Add to Cart -->
                <div class="row mb-4">
                  <div class="col-md-6 mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <div class="input-group">
                      <button class="btn btn-outline-secondary" type="button" id="decreaseQty">-</button>
                      <input type="number" class="form-control text-center" id="quantity" value="1" min="1" max="10">
                      <button class="btn btn-outline-secondary" type="button" id="increaseQty">+</button>
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Stock Status</label>
                    <div>
                      <span id="stockStatus" class="badge bg-success">In Stock</span>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2 d-md-flex">
                  <button id="addToCartBtn" class="btn btn-primary btn-lg flex-fill">
                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                  </button>
                  <button id="buyNowBtn" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-lightning me-2"></i>Buy Now
                  </button>
                </div>

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
            <ul class="nav nav-tabs" id="productTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                  Details
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" data-bs-target="#specifications" type="button" role="tab">
                  Specifications
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">
                  Reviews
                </button>
              </li>
            </ul>
            <div class="tab-content border border-top-0 p-4" id="productTabsContent">
              <div class="tab-pane fade show active" id="details" role="tabpanel">
                <h5>Product Details</h5>
                <p id="detailedDescription"></p>
              </div>
              <div class="tab-pane fade" id="specifications" role="tabpanel">
                <h5>Specifications</h5>
                <div id="productSpecs">
                  <!-- Specs will be loaded here -->
                </div>
              </div>
              <div class="tab-pane fade" id="reviews" role="tabpanel">
                <h5>Customer Reviews</h5>
                <div class="text-center py-4">
                  <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                  <button class="btn btn-outline-primary">Write a Review</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Related Products -->
          <div class="mt-5">
            <h3 class="mb-4">You Might Also Like</h3>
            <div id="relatedProducts" class="row">
              <!-- Related products will be loaded here -->
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>