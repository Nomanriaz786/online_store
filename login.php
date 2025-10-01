<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isAuthenticated()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['rememberMe']);
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } else {
            $result = $auth->login($email, $password, $rememberMe);
            
            if ($result['success']) {
                // Redirect to intended page or dashboard
                $redirectUrl = $_GET['redirect'] ?? 'index.php';
                header('Location: ' . $redirectUrl);
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = "Login — My Store";
$bodyClass = "auth-page";

// Use the template system
include 'partials/html-head.php';
?>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm my-5">
          <div class="card-body p-5">

            <!-- Header -->
            <div class="text-center mb-4">
              <a href="index.php" class="d-inline-block">
                <img src="assets/img/logo.svg" alt="My Store Logo" class="mb-3" width="64" height="64">
              </a>
              <h2 class="fw-bold mb-2">Welcome Back!</h2>
              <p class="text-muted mb-4">Please sign-in to your account and start the adventure</p>
            </div>

            <!-- Error Alert -->
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <!-- Success Alert -->
            <?php if (!empty($success)): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="needs-validation" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">

              <!-- Email/Username -->
              <div class="mb-3">
                <label for="email" class="form-label">Email / Username</label>
                <input type="text" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="Enter your email or username" required>
                <div class="invalid-feedback">Email / Username is required.</div>
              </div>

              <!-- Password -->
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="password" name="password"
                    placeholder="Enter your password" required>
                  <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="bi bi-eye" id="passwordIcon"></i>
                  </button>
                </div>
                <div class="invalid-feedback">Password is required.</div>
              </div>

              <!-- Remember Me & Forgot Password -->
              <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe" 
                         <?php echo isset($_POST['rememberMe']) ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="rememberMe">
                    Remember Me
                  </label>
                </div>
                <a href="forgot-password.php" class="text-decoration-none text-primary">Forgot Password?</a>
              </div>

              <!-- Submit Button -->
              <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
              </button>

              <!-- Register Link -->
              <div class="text-center">
                <span class="text-muted">New on our platform? </span>
                <a href="register.php" class="text-primary text-decoration-none fw-medium">Create an account</a>
              </div>

              <!-- Back to Home -->
              <div class="text-center mt-3">
                <a href="index.php" class="text-muted text-decoration-none small">
                  <i class="bi bi-arrow-left me-1"></i>Back to Home
                </a>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php include 'partials/html-footer.php'; ?>