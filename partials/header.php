<?php
// Include config if not already included
if (!class_exists('Config')) {
    require_once __DIR__ . '/../config/config.php';
}

// Initialize auth if not already available
if (!isset($auth)) {
    require_once __DIR__ . '/../classes/Auth.php';
    $auth = new Auth();
}

// Get current user info
$currentUser = $auth->getCurrentUser();
$isAuthenticated = $auth->isAuthenticated();
$isAdmin = $auth->isAdmin();

// Get cart count if user is logged in
$cartCount = 0;
if ($isAuthenticated) {
    require_once __DIR__ . '/../models/Cart.php';
    $cartModel = new Cart();
    $cartCount = $cartModel->getCartItemCount($auth->getCurrentUserId());
}

// Get current page for active nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header>
  <nav class="navbar navbar-expand-lg fixed-top" role="navigation" aria-label="Main navigation">
    <div class="container">
      <!-- Brand -->
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="assets/img/logo.svg" alt="My Store Logo" width="32" height="32" class="me-2"/>
        <span id="siteName" class="fw-bold">My Store</span>
      </a>
      
      <!-- Mobile toggle -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" 
              aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <!-- Navigation -->
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>" 
               href="index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $currentPage === 'products.php' ? 'active' : ''; ?>" 
               href="products.php">Products</a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 position-relative <?php echo $currentPage === 'cart.php' ? 'active' : ''; ?>" 
               href="cart.php">
               Cart
               <?php if ($cartCount > 0): ?>
                 <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                   <?php echo $cartCount; ?>
                   <span class="visually-hidden">items in cart</span>
                 </span>
               <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?php echo $currentPage === 'about.php' ? 'active' : ''; ?>" 
               href="about.php">About</a>
          </li>
          
          <?php if ($isAuthenticated): ?>
            <li class="nav-item">
              <a class="nav-link px-3 <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>" 
                 href="profile.php">Profile</a>
            </li>
            
            <?php if ($isAdmin): ?>
              <li class="nav-item">
                <a class="nav-link px-3 <?php echo $currentPage === 'admin.php' ? 'active' : ''; ?>" 
                   href="admin.php">Admin</a>
              </li>
            <?php endif; ?>
          <?php endif; ?>
        </ul>
        
        <!-- Right side items -->
        <div class="d-flex align-items-center gap-3">
          <!-- User greeting (only when logged in) -->
          <?php if ($isAuthenticated && $currentUser): ?>
            <span class="navbar-text d-none d-md-block">
              Hello, <strong class="text-primary"><?php echo htmlspecialchars($currentUser['first_name']); ?></strong>
            </span>
          <?php endif; ?>
          
          <!-- Theme toggle -->
          <button class="btn btn-outline-primary btn-sm theme-toggle-btn" 
                  data-action="theme-toggle" type="button" title="Toggle theme">
            <i class="bi bi-sun-fill theme-icon" data-theme="light"></i>
            <i class="bi bi-moon-fill theme-icon d-none" data-theme="dark"></i>
          </button>
          
          <!-- Auth buttons -->
          <div class="auth-buttons">
            <?php if ($isAuthenticated): ?>
              <!-- User dropdown -->
              <div class="dropdown">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle d-flex align-items-center" 
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <?php if (!empty($currentUser['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($currentUser['profile_picture']); ?>" 
                         alt="Profile" class="rounded-circle me-1" width="20" height="20">
                  <?php else: ?>
                    <i class="bi bi-person-circle me-1"></i>
                  <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="profile.php">
                    <i class="bi bi-person me-2"></i>My Profile
                  </a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                  </a></li>
                </ul>
              </div>
            <?php else: ?>
              <a href="login.php" class="btn btn-outline-primary btn-sm me-2">Login</a>
              <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </nav>
</header>