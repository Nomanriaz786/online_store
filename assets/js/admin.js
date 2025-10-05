// Admin panel JavaScript (clean server-side rendering version)
(function(window, document) {
  'use strict';

  // Configuration
  const CONFIG = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    adminEndpoint: 'admin.php'
  };

  // Simple alert function
  function showAlert(message, type = 'info') {
    // Use Bootstrap toast if available, fallback to alert
    if (window.bootstrap && window.bootstrap.Toast) {
      // Create or get toast container
      let toastContainer = document.getElementById('toast-container');
      if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1080';
        document.body.appendChild(toastContainer);
      }

      const toastEl = document.createElement('div');
      toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
      toastEl.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">${escapeHtml(message)}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      `;
      toastContainer.appendChild(toastEl);
      const toast = new bootstrap.Toast(toastEl);
      toast.show();
      setTimeout(() => toastEl.remove(), 5000);
    } else {
      alert(message);
    }
  }

  // Simple HTML escape function
  function escapeHtml(str) {
    if (str === undefined || str === null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Table refresh functions
  async function refreshProductsTable() {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;
    
    // Show loading state
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm me-2"></div>Refreshing...</td></tr>';
    
    try {
      const response = await fetch('admin.php?refresh=products');
      const html = await response.text();
      tbody.innerHTML = html;
    } catch (err) {
      console.error('Error refreshing products table:', err);
      showAlert('Error refreshing products table', 'danger');
      // Reload the page as fallback
      location.reload();
    }
  }

  async function refreshCategoriesTable() {
    const tbody = document.getElementById('categoriesTableBody');
    if (!tbody) return;
    
    // Show loading state
    tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm me-2"></div>Refreshing...</td></tr>';
    
    try {
      const response = await fetch('admin.php?refresh=categories');
      const html = await response.text();
      tbody.innerHTML = html;
    } catch (err) {
      console.error('Error refreshing categories table:', err);
      showAlert('Error refreshing categories table', 'danger');
      location.reload();
    }
  }

  async function refreshOrdersTable() {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;
    
    // Show loading state
    tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm me-2"></div>Refreshing...</td></tr>';
    
    try {
      const response = await fetch('admin.php?refresh=orders');
      const html = await response.text();
      tbody.innerHTML = html;
    } catch (err) {
      console.error('Error refreshing orders table:', err);
      showAlert('Error refreshing orders table', 'danger');
      location.reload();
    }
  }

  async function refreshUsersTable() {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;
    
    // Show loading state
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm me-2"></div>Refreshing...</td></tr>';
    
    try {
      const response = await fetch('admin.php?refresh=users');
      const html = await response.text();
      tbody.innerHTML = html;
    } catch (err) {
      console.error('Error refreshing users table:', err);
      showAlert('Error refreshing users table', 'danger');
      location.reload();
    }
  }

  function clearProductForm() {
    const form = document.getElementById('productForm');
    if (!form) return;
    
    // Reset all form fields
    form.reset();
    
    // Clear specific fields that might not be handled by reset()
    document.getElementById('productId').value = '';
    document.getElementById('productImageUrl').value = '';
    
    // Reset action to create_product
    const actionInput = document.getElementById('productAction');
    if (actionInput) actionInput.value = 'create_product';
    
    // Update modal title
    const modalTitle = document.querySelector('#productModal .modal-title');
    if (modalTitle) modalTitle.textContent = 'Add New Product';
  }

  function clearCategoryForm() {
    const form = document.getElementById('categoryForm');
    if (!form) return;
    
    // Reset all form fields
    form.reset();
    
    // Clear specific fields
    document.getElementById('categoryId').value = '';
    
    // Reset action to create_category
    const actionInput = document.getElementById('categoryAction');
    if (actionInput) actionInput.value = 'create_category';
    
    // Update modal title
    const modalTitle = document.querySelector('#categoryModal .modal-title');
    if (modalTitle) modalTitle.textContent = 'Add New Category';
  }

  function clearOrderForm() {
    const form = document.getElementById('orderForm');
    if (!form) return;
    
    // Reset all form fields
    form.reset();
    
    // Clear specific fields
    document.getElementById('orderId').value = '';
    
    // Reset action to update_order_status (orders are only updated, not created)
    const actionInput = document.querySelector('#orderForm input[name="action"]');
    if (actionInput) actionInput.value = 'update_order_status';
    
    // Update modal title
    const modalTitle = document.querySelector('#orderModal .modal-title');
    if (modalTitle) modalTitle.textContent = 'Edit Order';
  }

  function clearUserForm() {
    const form = document.getElementById('userForm');
    if (!form) return;
    
    // Reset all form fields
    form.reset();
    
    // Clear specific fields
    document.getElementById('userId').value = '';
    
    // Reset action to create_user
    const actionInput = document.getElementById('userAction');
    if (actionInput) actionInput.value = 'create_user';
    
    // Update modal title
    const modalTitle = document.querySelector('#userModal .modal-title');
    if (modalTitle) modalTitle.textContent = 'Add New User';
  }

  // Products - now handled by server-side form submission

  function editProduct(button) {
    try {
      // Get data from button's data attributes
      const dataset = button.dataset;
      
      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal'));
      modal.show();
      
      // Set values after modal is shown
      setTimeout(() => {
        document.getElementById('productId').value = dataset.productId || '';
        document.getElementById('productName').value = dataset.productName || '';
        document.getElementById('productPrice').value = dataset.productPrice || '';
        document.getElementById('productCategory').value = dataset.productCategory || '';
        document.getElementById('productStock').value = dataset.productStock || 0;
        document.getElementById('productSku').value = dataset.productSku || '';
        document.getElementById('productImageUrl').value = dataset.productImage || '';
        document.getElementById('productDescription').value = dataset.productDescription || '';
        document.getElementById('productActive').checked = dataset.productActive === '1';
        document.getElementById('productFeatured').checked = false; // Default to false
        // Update action field for update
        const actionInput = document.getElementById('productAction');
        if (actionInput) actionInput.value = 'update_product';
        document.querySelector('#productModal .modal-title').textContent = 'Edit Product';
      }, 100);
      
    } catch (err) {
      console.error('Error editing product:', err);
      showAlert('Error loading product details.', 'danger');
    }
  }

  // Delete functionality now handled by server-side forms
  
  // Categories - now handled by server-side form submission

  function editCategory(button) {
    try {
      // Get data from button's data attributes
      const dataset = button.dataset;
      
      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('categoryModal'));
      modal.show();
      
      setTimeout(() => {
        document.getElementById('categoryId').value = dataset.categoryId || '';
        document.getElementById('categoryName').value = dataset.categoryName || '';
        document.getElementById('categoryDescription').value = dataset.categoryDescription || '';
        document.getElementById('categoryActive').checked = dataset.categoryActive === '1';
        // Update action field for update
        const actionInput = document.getElementById('categoryAction');
        if (actionInput) actionInput.value = 'update_category';
        document.querySelector('#categoryModal .modal-title').textContent = 'Edit Category';
      }, 100);
      
    } catch (err) {
      console.error('Error editing category:', err);
      showAlert('Error loading category details.', 'danger');
    }
  }

  // Delete functionality now handled by server-side forms
  
  // Orders - now handled by server-side form submission

  function editOrder(button) {
    try {
      // Get data from button's data attributes
      const dataset = button.dataset;
      
      document.getElementById('orderId').value = dataset.orderId || '';
      document.getElementById('orderStatus').value = dataset.orderStatus || 'pending';
      
      const notesField = document.getElementById('orderNotes');
      if (notesField) {
        notesField.value = '';
      }
      
      bootstrap.Modal.getOrCreateInstance(document.getElementById('orderModal')).show();
    } catch (err) {
      console.error('Error editing order:', err);
      showAlert('Error loading order details.', 'danger');
    }
  }

  // Delete functionality now handled by server-side forms
  
  // Users - now handled by server-side form submission

  function editUser(button) {
    try {
      // Get data from button's data attributes
      const dataset = button.dataset;
      
      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal'));
      modal.show();
      
      setTimeout(() => {
        document.getElementById('userId').value = dataset.userId || '';
        document.getElementById('userUsername').value = dataset.userUsername || '';
        document.getElementById('userEmail').value = dataset.userEmail || '';
        
        // Combine first_name and last_name into name field
        const fullName = [dataset.userFirstname || '', dataset.userLastname || ''].filter(n => n.trim()).join(' ');
        document.getElementById('userName').value = fullName;
        
        document.getElementById('userRole').value = dataset.userRole || 'user';
        document.getElementById('userPassword').value = '';
        document.getElementById('userActive').checked = dataset.userActive === '1';
        // Update action field for update
        const actionInput = document.getElementById('userAction');
        if (actionInput) actionInput.value = 'update_user';
        document.querySelector('#userModal .modal-title').textContent = 'Edit User';
      }, 100);
      
    } catch (err) {
      console.error('Error editing user:', err);
      showAlert('Error loading user details.', 'danger');
    }
  }

  // Initialization
  document.addEventListener('DOMContentLoaded', function() {
    // Event delegation for edit actions only (delete is handled by forms)
    document.addEventListener('click', function(e) {
      const target = e.target.closest('button');
      if (!target) return;
      
      // Product actions
      if (target.classList.contains('edit-product')) {
        editProduct(target);
      }
      // Category actions
      else if (target.classList.contains('edit-category')) {
        editCategory(target);
      }
      // Order actions
      else if (target.classList.contains('edit-order')) {
        editOrder(target);
      }
      // User actions
      else if (target.classList.contains('edit-user')) {
        editUser(target);
      }
    });

    // Modal event listeners for clearing forms when adding new items
    const productModal = document.getElementById('productModal');
    if (productModal) {
      productModal.addEventListener('show.bs.modal', function (event) {
        if (event.relatedTarget && event.relatedTarget.hasAttribute('data-bs-target')) {
          clearProductForm();
        }
      });
    }

    const categoryModal = document.getElementById('categoryModal');
    if (categoryModal) {
      categoryModal.addEventListener('show.bs.modal', function (event) {
        if (event.relatedTarget && event.relatedTarget.hasAttribute('data-bs-target')) {
          clearCategoryForm();
        }
      });
    }

    const userModal = document.getElementById('userModal');
    if (userModal) {
      userModal.addEventListener('show.bs.modal', function (event) {
        if (event.relatedTarget && event.relatedTarget.hasAttribute('data-bs-target')) {
          clearUserForm();
        }
      });
    }
  });

  window.Admin = {
    editProduct,
    editCategory,
    editOrder,
    editUser,
    clearProductForm,
    clearCategoryForm,
    clearOrderForm,
    clearUserForm
  };

})(window, document);