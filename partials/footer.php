<footer class="footer-section pt-5" role="contentinfo">
  <div class="container">
    <div class="row">
      <div class="col-lg-4 mb-4">
        <div class="d-flex align-items-center mb-3">
          <img src="assets/img/logo.svg" alt="My Store Logo" width="32" height="32" class="me-2"/>
          <span id="footerSiteName" class="h4 mb-0 footer-brand">My Store</span>
        </div>
        <p class="footer-text">
          Your premium destination for quality products and exceptional shopping experience.
        </p>
        <!-- Social Media Links -->
        <div class="social-links mt-4">
          <a href="https://facebook.com" target="_blank" class="social-link me-3" 
             aria-label="Follow us on Facebook" rel="noopener">
            <i class="bi bi-facebook"></i>
          </a>
          <a href="https://twitter.com" target="_blank" class="social-link me-3" 
             aria-label="Follow us on Twitter" rel="noopener">
            <i class="bi bi-twitter-x"></i>
          </a>
          <a href="https://instagram.com" target="_blank" class="social-link me-3" 
             aria-label="Follow us on Instagram" rel="noopener">
            <i class="bi bi-instagram"></i>
          </a>
          <a href="https://linkedin.com" target="_blank" class="social-link" 
             aria-label="Connect with us on LinkedIn" rel="noopener">
            <i class="bi bi-linkedin"></i>
          </a>
        </div>
      </div>
      
      <div class="col-lg-2 col-md-3 col-sm-6 mb-4">
        <h6 class="footer-heading mb-3">Shop</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="products.php" class="footer-link">Products</a></li>
          <li><a href="cart.php" class="footer-link">Cart</a></li>
          <?php if ($isAuthenticated): ?>
            <li><a href="orders.php" class="footer-link">My Orders</a></li>
          <?php endif; ?>
          <li><a href="products.php?category=all" class="footer-link">All Categories</a></li>
        </ul>
      </div>
      
      <div class="col-lg-2 col-md-3 col-sm-6 mb-4">
        <h6 class="footer-heading mb-3">Account</h6>
        <ul class="list-unstyled footer-links">
          <?php if ($isAuthenticated): ?>
            <li><a href="profile.php" class="footer-link">My Profile</a></li>
            <li><a href="logout.php" class="footer-link">Logout</a></li>
          <?php else: ?>
            <li><a href="login.php" class="footer-link">Login</a></li>
            <li><a href="register.php" class="footer-link">Register</a></li>
          <?php endif; ?>
        </ul>
      </div>
      
      <div class="col-lg-2 col-md-3 col-sm-6 mb-4">
        <h6 class="footer-heading mb-3">Support</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="about.php" class="footer-link">About Us</a></li>
          <li><a href="contact.php" class="footer-link">Contact</a></li>
          <li><a href="help.php" class="footer-link">Help Center</a></li>
          <li><a href="faq.php" class="footer-link">FAQs</a></li>
        </ul>
      </div>
      
      <div class="col-lg-2 col-md-3 col-sm-6 mb-4">
        <h6 class="footer-heading mb-3">Legal</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="privacy.php" class="footer-link">Privacy Policy</a></li>
          <li><a href="terms.php" class="footer-link">Terms of Service</a></li>
          <li><a href="cookies.php" class="footer-link">Cookie Policy</a></li>
          <li><a href="refund.php" class="footer-link">Refund Policy</a></li>
        </ul>
      </div>
    </div>
    
    <hr class="footer-divider">
    <div class="row justify-content-center align-items-center my-3 min-vh-10">
      <div class="col-12 text-center">
        <p class="footer-text mb-0">
          &copy; <?php echo date('Y'); ?> My Store. All Rights Reserved.
        </p>
      </div>
    </div>
  </div>
</footer>