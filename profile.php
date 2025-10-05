<?php
// Check authentication
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Order.php';

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$userModel = new User();
$orderModel = new Order();
$userId = $auth->getCurrentUserId();
$currentUser = $auth->getCurrentUser();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
        header('Location: profile.php?tab=' . ($_POST['tab'] ?? 'account'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_profile':
            $profileData = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'state' => trim($_POST['state'] ?? ''),
                'zip_code' => trim($_POST['zip_code'] ?? ''),
                'country' => trim($_POST['country'] ?? '')
            ];

            $result = $userModel->updateProfile($userId, $profileData);
            
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
                // Update session with new user data
                $currentUser = $userModel->find($userId);
                $_SESSION['user'] = $currentUser;
            } else {
                $_SESSION['error_message'] = implode(', ', $result['errors'] ?? ['Failed to update profile']);
            }
            
            header('Location: profile.php?tab=account');
            exit;

        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword !== $confirmPassword) {
                $_SESSION['error_message'] = 'New passwords do not match.';
            } else {
                $result = $userModel->changePassword($userId, $currentPassword, $newPassword);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
            }
            
            header('Location: profile.php?tab=security');
            exit;

        case 'upload_profile_picture':
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                if (!in_array($file['type'], $allowedTypes)) {
                    $_SESSION['error_message'] = 'Invalid file type. Please upload JPG, PNG, GIF, or WebP.';
                } elseif ($file['size'] > $maxSize) {
                    $_SESSION['error_message'] = 'File is too large. Maximum size is 2MB.';
                } else {
                    // Create upload directory if it doesn't exist
                    $uploadDir = 'uploads/users/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $userId . '_' . uniqid() . '.' . $extension;
                    $uploadPath = $uploadDir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        // Delete old profile picture if exists
                        if (!empty($currentUser['profile_picture']) && file_exists($currentUser['profile_picture'])) {
                            unlink($currentUser['profile_picture']);
                        }

                        // Update database
                        if ($userModel->updateProfilePicture($userId, $uploadPath)) {
                            $_SESSION['success_message'] = 'Profile picture updated successfully.';
                            // Update session
                            $_SESSION['user']['profile_picture'] = $uploadPath;
                        } else {
                            $_SESSION['error_message'] = 'Failed to update profile picture in database.';
                        }
                    } else {
                        $_SESSION['error_message'] = 'Failed to upload file.';
                    }
                }
            } else {
                $_SESSION['error_message'] = 'No file uploaded or upload error occurred.';
            }
            
            header('Location: profile.php?tab=account');
            exit;
    }
}

// Get current tab
$currentTab = $_GET['tab'] ?? 'account';

// Load user orders
$userOrders = $orderModel->getUserOrders($userId, 20);

$pageTitle = "My Profile — My Store";

// Use the template system
include 'partials/html-head.php';
?>

  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <main>
    <!-- Profile Section -->
    <section class="py-5">
      <div class="container">
        <div class="row">
          <!-- Sidebar Navigation -->
          <div class="col-lg-3 mb-4">
            <div class="card">
              <div class="card-body">
                <div class="text-center mb-4">
                  <div class="position-relative d-inline-block">
                    <img src="<?php echo htmlspecialchars($currentUser['profile_picture'] ?? 'assets/img/placeholder.svg'); ?>" 
                         alt="Profile" class="rounded-circle" width="80" height="80" style="object-fit: cover;">
                    <button type="button" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle p-1" 
                            data-bs-toggle="modal" data-bs-target="#profilePictureModal" title="Change photo">
                      <i class="bi bi-camera"></i>
                    </button>
                  </div>
                  <h6 class="mt-2 mb-1">
                    <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                  </h6>
                  <small class="text-muted"><?php echo htmlspecialchars($currentUser['email']); ?></small>
                </div>

                <div class="list-group list-group-flush">
                  <a href="?tab=account" class="list-group-item list-group-item-action <?php echo $currentTab === 'account' ? 'active' : ''; ?>">
                    <i class="bi bi-person me-2"></i>Account Info
                  </a>
                  <a href="?tab=orders" class="list-group-item list-group-item-action <?php echo $currentTab === 'orders' ? 'active' : ''; ?>">
                    <i class="bi bi-bag me-2"></i>My Orders
                  </a>
                  <a href="?tab=security" class="list-group-item list-group-item-action <?php echo $currentTab === 'security' ? 'active' : ''; ?>">
                    <i class="bi bi-shield-lock me-2"></i>Security
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Main Content -->
          <div class="col-lg-9">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <!-- Account Info Tab -->
            <div class="profile-content <?php echo $currentTab !== 'account' ? 'd-none' : ''; ?>">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                  <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                    <input type="hidden" name="tab" value="account">
                    
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($currentUser['first_name'] ?? ''); ?>" required>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($currentUser['last_name'] ?? ''); ?>" required>
                      </div>
                    </div>
                    <div class="mb-3">
                      <label for="email" class="form-label">Email Address *</label>
                      <input type="email" class="form-control" id="email" name="email" 
                             value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                      <label for="phone" class="form-label">Phone Number</label>
                      <input type="tel" class="form-control" id="phone" name="phone" 
                             value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                      <label for="address" class="form-label">Address</label>
                      <input type="text" class="form-control" id="address" name="address" 
                             value="<?php echo htmlspecialchars($currentUser['address'] ?? ''); ?>">
                    </div>
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" 
                               value="<?php echo htmlspecialchars($currentUser['city'] ?? ''); ?>">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="state" class="form-label">State</label>
                        <input type="text" class="form-control" id="state" name="state" 
                               value="<?php echo htmlspecialchars($currentUser['state'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="zip_code" class="form-label">ZIP Code</label>
                        <input type="text" class="form-control" id="zip_code" name="zip_code" 
                               value="<?php echo htmlspecialchars($currentUser['zip_code'] ?? ''); ?>">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" 
                               value="<?php echo htmlspecialchars($currentUser['country'] ?? ''); ?>">
                      </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-save me-1"></i>Save Changes
                    </button>
                  </form>
                </div>
              </div>
            </div>

            <!-- Orders Tab -->
            <div class="profile-content <?php echo $currentTab !== 'orders' ? 'd-none' : ''; ?>">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">My Orders</h5>
                </div>
                <div class="card-body">
                  <?php if (!empty($userOrders)): ?>
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($userOrders as $order): ?>
                            <tr>
                              <td>
                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                              </td>
                              <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                              <td><?php echo $order['item_count']; ?> item(s)</td>
                              <td>
                                <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                              </td>
                              <td>
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'shipped' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $statusColor = $statusColors[$order['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $statusColor; ?>">
                                  <?php echo ucfirst($order['status']); ?>
                                </span>
                              </td>
                              <td>
                                <a href="order-confirmation.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                  <i class="bi bi-eye me-1"></i>View
                                </a>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php else: ?>
                    <div class="text-center py-5">
                      <i class="bi bi-bag display-1 text-muted mb-3"></i>
                      <h5>No orders found</h5>
                      <p class="text-muted">You haven't placed any orders yet.</p>
                      <a href="products.php" class="btn btn-primary">
                        <i class="bi bi-shop me-1"></i>Start Shopping
                      </a>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Security Tab -->
            <div class="profile-content <?php echo $currentTab !== 'security' ? 'd-none' : ''; ?>">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">Security Settings</h5>
                </div>
                <div class="card-body">
                  <!-- Change Password -->
                  <h6 class="mb-3">Change Password</h6>
                  <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                    <input type="hidden" name="tab" value="security">
                    
                    <div class="mb-3">
                      <label for="current_password" class="form-label">Current Password *</label>
                      <div class="input-group">
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                          <i class="bi bi-eye"></i>
                        </button>
                      </div>
                    </div>
                    <div class="mb-3">
                      <label for="new_password" class="form-label">New Password * (min. 6 characters)</label>
                      <div class="input-group">
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               minlength="6" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                          <i class="bi bi-eye"></i>
                        </button>
                      </div>
                    </div>
                    <div class="mb-3">
                      <label for="confirm_password" class="form-label">Confirm New Password *</label>
                      <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               minlength="6" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                          <i class="bi bi-eye"></i>
                        </button>
                      </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-shield-lock me-1"></i>Change Password
                    </button>
                  </form>

                  <hr class="my-4">

                  <!-- Account Info -->
                  <h6 class="mb-3">Account Information</h6>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <strong>Username:</strong>
                      <p class="text-muted mb-0"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <strong>Member Since:</strong>
                      <p class="text-muted mb-0">
                        <?php echo date('F j, Y', strtotime($currentUser['created_at'])); ?>
                      </p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <strong>Last Login:</strong>
                      <p class="text-muted mb-0">
                        <?php 
                        echo !empty($currentUser['last_login']) 
                          ? date('M d, Y g:i A', strtotime($currentUser['last_login'])) 
                          : 'Never'; 
                        ?>
                      </p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <strong>Account Status:</strong>
                      <p class="mb-0">
                        <span class="badge bg-success">Active</span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Profile Picture Upload Modal -->
  <div class="modal fade" id="profilePictureModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="profile.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_profile_picture">
          <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
          <input type="hidden" name="tab" value="account">
          
          <div class="modal-header">
            <h5 class="modal-title">Change Profile Picture</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="text-center mb-3">
              <img id="preview-profile-picture" 
                   src="<?php echo htmlspecialchars($currentUser['profile_picture'] ?? 'assets/img/placeholder.svg'); ?>" 
                   alt="Preview" class="rounded-circle" width="120" height="120" style="object-fit: cover;">
            </div>
            <div class="mb-3">
              <label for="profile_picture" class="form-label">Choose Profile Picture</label>
              <input type="file" class="form-control" id="profile_picture" name="profile_picture" 
                     accept="image/jpeg,image/png,image/gif,image/webp" required>
              <div class="form-text">
                Supported formats: JPG, PNG, GIF, WebP. Maximum size: 2MB.
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-upload me-1"></i>Upload Picture
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>