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
        $userData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'role' => 'user' // Default role
        ];
        
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Basic validation
        if (empty($userData['username']) || empty($userData['email']) || empty($userData['password']) || 
            empty($userData['first_name']) || empty($userData['last_name'])) {
            $error = 'Please fill in all required fields.';
        } elseif ($userData['password'] !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check password strength
            $passwordErrors = $auth->validatePasswordStrength($userData['password']);
            if (!empty($passwordErrors)) {
                $error = implode('. ', $passwordErrors) . '.';
            } else {
                $result = $auth->register($userData);
                
                if ($result['success']) {
                    $success = 'Account created successfully! Please log in.';
                    header('refresh:2;url=login.php');
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

$pageTitle = "Create Account — My Store";
$bodyClass = "auth-page";

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
              <h2 class="fw-bold mb-2">Join Us Today!</h2>
              <p class="text-muted mb-4">Create your account and start shopping</p>
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

              <!-- First Name & Last Name Row -->
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="first_name" class="form-label">First Name *</label>
                  <input type="text" class="form-control" id="first_name" name="first_name" 
                         value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                         placeholder="Enter your first name" required>
                  <div class="invalid-feedback">First name is required.</div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="last_name" class="form-label">Last Name *</label>
                  <input type="text" class="form-control" id="last_name" name="last_name" 
                         value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                         placeholder="Enter your last name" required>
                  <div class="invalid-feedback">Last name is required.</div>
                </div>
              </div>

              <!-- Username -->
              <div class="mb-3">
                <label for="username" class="form-label">Username *</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       placeholder="Choose a unique username" required>
                <div class="invalid-feedback">Username is required.</div>
              </div>

              <!-- Email -->
              <div class="mb-3">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="Enter your email address" required>
                <div class="invalid-feedback">Please enter a valid email address.</div>
              </div>

              <!-- Password -->
              <div class="mb-3">
                <label for="password" class="form-label">Password *</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="password" name="password"
                    placeholder="Create a strong password" required>
                  <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="bi bi-eye" id="passwordIcon"></i>
                  </button>
                </div>
                <div class="form-text">
                  Password must be at least 8 characters with uppercase, lowercase, and number.
                </div>
                <div class="invalid-feedback">Password is required.</div>
              </div>

              <!-- Confirm Password -->
              <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password *</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                    placeholder="Confirm your password" required>
                  <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                    <i class="bi bi-eye" id="confirmPasswordIcon"></i>
                  </button>
                </div>
                <div class="invalid-feedback">Please confirm your password.</div>
              </div>

              <!-- Terms & Conditions -->
              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="form-check-label" for="terms">
                  I agree to the <a href="#" class="text-primary">Terms & Conditions</a> and <a href="#" class="text-primary">Privacy Policy</a>
                </label>
                <div class="invalid-feedback">You must agree to the terms and conditions.</div>
              </div>

              <!-- Submit Button -->
              <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                <i class="bi bi-person-plus me-2"></i>Create Account
              </button>

              <!-- Login Link -->
              <div class="text-center">
                <span class="text-muted">Already have an account? </span>
                <a href="login.php" class="text-primary text-decoration-none fw-medium">Sign in here</a>
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