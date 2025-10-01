<?php
// Check authentication
session_start();
require_once 'classes/Auth.php';

$auth = new Auth();
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

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
                  <div id="profileImageContainer" class="position-relative d-inline-block">
                    <img id="profileImage" src="assets/img/placeholder.svg" alt="Profile" class="rounded-circle" width="80" height="80">
                    <button id="changePhotoBtn" type="button" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle p-1" data-bs-toggle="modal" data-bs-target="#profilePictureModal">
                      <i class="bi bi-camera"></i>
                    </button>
                  </div>
                  <h6 id="profileName" class="mt-2 mb-1"></h6>
                  <small id="profileEmail" class="text-muted"></small>
                </div>

                <div class="list-group list-group-flush">
                  <button class="list-group-item list-group-item-action active profile-tab" data-tab="account">
                    <i class="bi bi-person me-2"></i>Account Info
                  </button>
                  <button class="list-group-item list-group-item-action profile-tab" data-tab="orders">
                    <i class="bi bi-bag me-2"></i>My Orders
                  </button>
                  <button class="list-group-item list-group-item-action profile-tab" data-tab="addresses">
                    <i class="bi bi-geo-alt me-2"></i>Addresses
                  </button>
                  <button class="list-group-item list-group-item-action profile-tab" data-tab="security">
                    <i class="bi bi-shield-lock me-2"></i>Security
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Main Content -->
          <div class="col-lg-9">
            <!-- Account Info Tab -->
            <div id="account-tab" class="profile-content">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                  <form id="profileForm">
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="firstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstName" name="firstName" required>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="lastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastName" name="lastName" required>
                      </div>
                    </div>
                    <div class="mb-3">
                      <label for="email" class="form-label">Email Address</label>
                      <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                      <label for="phone" class="form-label">Phone Number</label>
                      <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                      <label for="address" class="form-label">Address</label>
                      <input type="text" class="form-control" id="address" name="address">
                    </div>
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="state" class="form-label">State</label>
                        <input type="text" class="form-control" id="state" name="state">
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="zipCode" class="form-label">ZIP Code</label>
                        <input type="text" class="form-control" id="zipCode" name="zipCode">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country">
                      </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                  </form>
                </div>
              </div>
            </div>

            <!-- Orders Tab -->
            <div id="orders-tab" class="profile-content d-none">
              <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">My Orders</h5>
                  <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" id="orderFilter" style="width: auto;">
                      <option value="all">All Orders</option>
                      <option value="pending">Pending</option>
                      <option value="processing">Processing</option>
                      <option value="shipped">Shipped</option>
                      <option value="delivered">Delivered</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </div>
                </div>
                <div class="card-body">
                  <div id="ordersContainer">
                    <!-- Orders will be loaded here -->
                  </div>
                  <div id="noOrders" class="text-center py-4 d-none">
                    <i class="bi bi-bag display-1 text-muted mb-3"></i>
                    <h5>No orders found</h5>
                    <p class="text-muted">You haven't placed any orders yet.</p>
                    <a href="products.php" class="btn btn-primary">Start Shopping</a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Addresses Tab -->
            <div id="addresses-tab" class="profile-content d-none">
              <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">Saved Addresses</h5>
                  <button class="btn btn-primary btn-sm" id="addAddressBtn">
                    <i class="bi bi-plus me-1"></i>Add Address
                  </button>
                </div>
                <div class="card-body">
                  <div id="addressesContainer">
                    <!-- Addresses will be loaded here -->
                  </div>
                  <div id="noAddresses" class="text-center py-4">
                    <i class="bi bi-geo-alt display-1 text-muted mb-3"></i>
                    <h5>No addresses saved</h5>
                    <p class="text-muted">Add an address for faster checkout.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Security Tab -->
            <div id="security-tab" class="profile-content d-none">
              <div class="card">
                <div class="card-header">
                  <h5 class="mb-0">Security Settings</h5>
                </div>
                <div class="card-body">
                  <!-- Change Password -->
                  <div class="mb-4">
                    <h6>Change Password</h6>
                    <form id="passwordForm">
                      <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                      </div>
                      <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                      </div>
                      <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                      </div>
                      <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                  </div>

                  <hr>

                  <!-- Two-Factor Authentication -->
                  <div class="mb-4">
                    <h6>Two-Factor Authentication</h6>
                    <p class="text-muted small">Add an extra layer of security to your account.</p>
                    <div class="d-flex align-items-center justify-content-between">
                      <span>SMS Authentication</span>
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="smsAuth">
                      </div>
                    </div>
                  </div>

                  <hr>

                  <!-- Login Activity -->
                  <div>
                    <h6>Recent Login Activity</h6>
                    <div id="loginActivity">
                      <!-- Login activity will be loaded here -->
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
        <div class="modal-header">
          <h5 class="modal-title">Change Profile Picture</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="profilePictureForm" enctype="multipart/form-data">
            <div class="text-center mb-3">
              <img id="previewImage" src="assets/img/placeholder.svg" alt="Preview" class="rounded-circle" width="120" height="120">
            </div>
            <div class="mb-3">
              <label for="profilePictureFile" class="form-label">Choose Profile Picture</label>
              <input type="file" class="form-control" id="profilePictureFile" name="profile_picture" accept="image/*" required>
              <div class="form-text">
                Supported formats: JPG, PNG, GIF. Maximum size: 2MB.
              </div>
            </div>
            <div id="uploadProgress" class="progress d-none mb-3">
              <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" form="profilePictureForm" class="btn btn-primary">Upload Picture</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Address Modal -->
  <div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Address</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addressForm">
            <div class="mb-3">
              <label for="addressType" class="form-label">Address Type</label>
              <select class="form-select" id="addressType" required>
                <option value="home">Home</option>
                <option value="work">Work</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="addressFirstName" class="form-label">First Name</label>
                <input type="text" class="form-control" id="addressFirstName" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="addressLastName" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="addressLastName" required>
              </div>
            </div>
            <div class="mb-3">
              <label for="addressLine1" class="form-label">Address Line 1</label>
              <input type="text" class="form-control" id="addressLine1" required>
            </div>
            <div class="mb-3">
              <label for="addressLine2" class="form-label">Address Line 2 (Optional)</label>
              <input type="text" class="form-control" id="addressLine2">
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="addressCity" class="form-label">City</label>
                <input type="text" class="form-control" id="addressCity" required>
              </div>
              <div class="col-md-3 mb-3">
                <label for="addressState" class="form-label">State</label>
                <input type="text" class="form-control" id="addressState" required>
              </div>
              <div class="col-md-3 mb-3">
                <label for="addressZip" class="form-label">ZIP Code</label>
                <input type="text" class="form-control" id="addressZip" required>
              </div>
            </div>
            <div class="mb-3">
              <label for="addressCountry" class="form-label">Country</label>
              <select class="form-select" id="addressCountry" required>
                <option value="US">United States</option>
                <option value="CA">Canada</option>
                <option value="MX">Mexico</option>
              </select>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="isDefault">
              <label class="form-check-label" for="isDefault">
                Set as default address
              </label>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" form="addressForm" class="btn btn-primary">Save Address</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>