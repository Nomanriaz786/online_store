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
    const actionInput = document.querySelector('#productForm input[name="action"]');
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
    const actionInput = document.querySelector('#categoryForm input[name="action"]');
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
    const actionInput = document.querySelector('#userForm input[name="action"]');
    if (actionInput) actionInput.value = 'create_user';
    
    // Update modal title
    const modalTitle = document.querySelector('#userModal .modal-title');
    if (modalTitle) modalTitle.textContent = 'Add New User';
  }

  // Products
  async function saveProduct() {
    const form = document.getElementById('productForm');
    if (!form) return;
    const formData = new FormData(form);
    if (!formData.get('csrf_token')) formData.append('csrf_token', CONFIG.csrfToken);

    try {
      const response = await fetch(CONFIG.adminEndpoint, { method: 'POST', body: formData });
      const data = await response.json();
      if (data.success) {
        showAlert('Product saved successfully!', 'success');
        clearProductForm();
        bootstrap.Modal.getInstance(document.getElementById('productModal'))?.hide();
        // Refresh only the products table
        await refreshProductsTable();
      } else {
        showAlert('Error: ' + (data.message || (data.errors && data.errors.join(', '))), 'danger');
      }
    } catch (err) {
      console.error('Error saving product:', err);
      showAlert('An error occurred while saving the product.', 'danger');
    }
  }

  async function editProduct(id) {
    try {
      const response = await fetch(`api/products/get.php?id=${encodeURIComponent(id)}`);
      const data = await response.json();
      
      if (!data.success || !data.product) {
        showAlert('Product not found.', 'warning');
        return;
      }
      const p = data.product;
      
      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal'));
      modal.show();
      
      // Set values after modal is shown
      setTimeout(() => {
        document.getElementById('productId').value = p.id;
        document.getElementById('productName').value = p.name || '';
        document.getElementById('productPrice').value = p.price ?? '';
        document.getElementById('productCategory').value = p.category_id ?? '';
        document.getElementById('productStock').value = p.stock_quantity ?? p.stock ?? 0;
        document.getElementById('productSku').value = p.sku ?? '';
        document.getElementById('productImageUrl').value = p.image_url || p.image_path || p.image || '';
        document.getElementById('productDescription').value = p.description ?? '';
        document.getElementById('productActive').checked = !!p.is_active;
        document.getElementById('productFeatured').checked = !!p.is_featured;
        document.querySelector('#productForm input[name="action"]').value = 'update_product';
        document.querySelector('#productModal .modal-title').textContent = 'Edit Product';
      }, 100);
      
    } catch (err) {
      console.error('Error editing product:', err);
      showAlert('Error loading product details.', 'danger');
    }
  }

  async function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) return;
    
    try {
      const formData = new FormData();
      formData.append('action', 'delete_product');
      formData.append('product_id', id);
      formData.append('csrf_token', CONFIG.csrfToken);

      const response = await fetch(CONFIG.adminEndpoint, { method: 'POST', body: formData });
      const res = await response.json();
      
      if (res.success) {
        showAlert('Product deleted successfully.', 'success');
        await refreshProductsTable();
      } else {
        showAlert(res.message || 'Failed to delete product.', 'danger');
      }
    } catch (err) {
      console.error('Error deleting product:', err);
      showAlert('Error deleting product.', 'danger');
    }
  }

  // Categories
  async function saveCategory() {
    const form = document.getElementById('categoryForm');
    if (!form) return;
    const formData = new FormData(form);
    if (!formData.get('csrf_token')) formData.append('csrf_token', CONFIG.csrfToken);

    const categoryId = document.getElementById('categoryId').value;
    
    if (categoryId && categoryId.trim() !== '') {
      formData.set('action', 'update_category');
      formData.set('category_id', categoryId);
    } else {
      formData.set('action', 'create_category');
    }

    try {
      const response = await fetch(CONFIG.adminEndpoint, { method: 'POST', body: formData });
      const data = await response.json();
      
      if (data.success) {
        showAlert('Category saved successfully!', 'success');
        clearCategoryForm();
        bootstrap.Modal.getInstance(document.getElementById('categoryModal'))?.hide();
        await refreshCategoriesTable();
      } else {
        showAlert('Error: ' + (data.message || (data.errors && data.errors.join(', '))), 'danger');
      }
    } catch (err) {
      console.error('Error saving category:', err);
      showAlert('An error occurred while saving the category.', 'danger');
    }
  }

  async function editCategory(id) {
    try {
      const response = await fetch(`api/categories/get.php?id=${encodeURIComponent(id)}`);
      const data = await response.json();
      if (!data.success || !data.category) {
        showAlert('Category not found.', 'warning');
        return;
      }
      const c = data.category;
      
      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('categoryModal'));
      modal.show();
      
      setTimeout(() => {
        document.getElementById('categoryId').value = c.id;
        document.getElementById('categoryName').value = c.name || '';
        document.getElementById('categoryDescription').value = c.description || '';
        document.getElementById('categoryActive').checked = !!c.is_active;
        document.querySelector('#categoryForm input[name="action"]').value = 'update_category';
        document.querySelector('#categoryModal .modal-title').textContent = 'Edit Category';
      }, 100);
      
    } catch (err) {
      console.error('Error editing category:', err);
      showAlert('Error loading category details.', 'danger');
    }
  }

  async function deleteCategory(id) {
    if (!confirm('Are you sure you want to delete this category? This may affect products and cannot be undone.')) return;
    
    try {
      const formData = new FormData();
      formData.append('action', 'delete_category');
      formData.append('category_id', id);
      formData.append('csrf_token', CONFIG.csrfToken);

      const response = await fetch(CONFIG.adminEndpoint, { method: 'POST', body: formData });
      const res = await response.json();
      if (res.success) {
        showAlert('Category deleted successfully.', 'success');
        await refreshCategoriesTable();
      } else {
        showAlert(res.message || 'Failed to delete category.', 'danger');
      }
    } catch (err) {
      console.error('Error deleting category:', err);
      showAlert('Error deleting category.', 'danger');
    }
  }

  // Orders
  async function saveOrder() {
    const form = document.getElementById('orderForm');
    if (!form) return;
    const formData = new FormData(form);
    if (!formData.get('csrf_token')) formData.append('csrf_token', CONFIG.csrfToken);

    try {
      const response = await fetch(CONFIG.adminEndpoint, { method: 'POST', body: formData });
      const data = await response.json();
      if (data.success) {
        showAlert('Order updated successfully!', 'success');
        clearOrderForm();
        bootstrap.Modal.getInstance(document.getElementById('orderModal'))?.hide();
        await refreshOrdersTable();
      } else {
        showAlert('Error: ' + (data.message || (data.errors && data.errors.join(', '))), 'danger');
      }
    } catch (err) {
      console.error('Error updating order:', err);
      showAlert('An error occurred while updating the order.', 'danger');
    }
  }

  async function editOrder(id) {
    try {
      const response = await fetch(`api/orders/get.php?id=${encodeURIComponent(id)}`);
      const data = await response.json();
      if (!data || !data.success || !data.order) {
        showAlert('Order not found.', 'warning');
        return;
      }
      const o = data.order;
      document.getElementById('orderId').value = o.id;
      document.getElementById('orderStatus').value = o.status || 'pending';
      document.getElementById('orderNotes').value = o.notes || '';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('orderModal')).show();
    } catch (err) {
      console.error('Error editing order:', err);
      showAlert('Error loading order details.', 'danger');
    }
  }

  async function deleteOrder(id) {
    if (!confirm('Are you sure you want to delete this order? This action cannot be undone and may affect sales records.')) return;
    
    try {
      const formData = new FormData();
      formData.append('action', 'delete_order');
      formData.append('order_id', id);
      formData.append('csrf_token', CONFIG.csrfToken);

      const response = await fetch(CONFIG.adminEndpoint, { method: 'POST', body: formData });
      const res = await response.json();
      if (res.success) {
        showAlert('Order deleted successfully.', 'success');
        await refreshOrdersTable();
      } else {
        showAlert(res.message || 'Failed to delete order.', 'danger');
      }
    } catch (err) {
      console.error('Error deleting order:', err);
      showAlert('Error deleting order.', 'danger');
    }
  }

  // Users
  async function saveUser() {
    const form = document.getElementById('userForm');
    if (!form) return;
    const formData = new FormData(form);
    if (!formData.get('csrf_token')) formData.append('csrf_token', CONFIG.csrfToken);

    const userId = document.getElementById('userId').value;
    if (userId && userId.trim() !== '') {
      formData.set('action', 'update_user');
      formData.set('user_id', userId);
    } else {
      formData.set('action', 'create_user');
    }

    try {
      const response = await fetch(CONFIG.adminEndpoint, { method: 'POST', body: formData });
      const data = await response.json();
      if (data.success) {
        showAlert('User saved successfully!', 'success');
        clearUserForm();
        bootstrap.Modal.getInstance(document.getElementById('userModal'))?.hide();
        await refreshUsersTable();
      } else {
        showAlert('Error: ' + (data.message || (data.errors && data.errors.join(', '))), 'danger');
      }
    } catch (err) {
      console.error('Error saving user:', err);
      showAlert('An error occurred while saving the user.', 'danger');
    }
  }

  async function editUser(id) {
    try {
      const response = await fetch(`api/users/get.php?id=${encodeURIComponent(id)}`);
      const data = await response.json();
      if (!data.success || !data.user) {
        showAlert('User not found.', 'warning');
        return;
      }
      const u = data.user;
      
      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal'));
      modal.show();
      
      setTimeout(() => {
        document.getElementById('userId').value = u.id;
        document.getElementById('userUsername').value = u.username || '';
        document.getElementById('userEmail').value = u.email || '';
        
        // Combine first_name and last_name into name field
        const fullName = [u.first_name || '', u.last_name || ''].filter(n => n.trim()).join(' ');
        document.getElementById('userName').value = fullName;
        
        document.getElementById('userRole').value = u.role || 'user';
        document.getElementById('userPassword').value = '';
        document.getElementById('userActive').checked = !!u.is_active;
        document.querySelector('#userForm input[name="action"]').value = 'update_user';
        document.querySelector('#userModal .modal-title').textContent = 'Edit User';
      }, 100);
      
    } catch (err) {
      console.error('Error editing user:', err);
      showAlert('Error loading user details.', 'danger');
    }
  }

  async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone and may affect order history.')) return;
    
    try {
      const formData = new FormData();
      formData.append('action', 'delete_user');
      formData.append('user_id', id);
      formData.append('csrf_token', CONFIG.csrfToken);

      const response = await fetch(CONFIG.adminEndpoint, { method: 'POST', body: formData });
      const res = await response.json();
      if (res.success) {
        showAlert('User deleted successfully.', 'success');
        await refreshUsersTable();
      } else {
        showAlert(res.message || 'Failed to delete user.', 'danger');
      }
    } catch (err) {
      console.error('Error deleting user:', err);
      showAlert('Error deleting user.', 'danger');
    }
  }

  // Form clearing functions

  // Initialization
  document.addEventListener('DOMContentLoaded', function() {
    // Event delegation for all admin actions
    document.addEventListener('click', function(e) {
      const target = e.target.closest('button');
      if (!target) return;
      
      // Product actions
      if (target.classList.contains('edit-product')) {
        editProduct(target.dataset.productId);
      } else if (target.classList.contains('delete-product')) {
        deleteProduct(target.dataset.productId);
      }
      // Category actions
      else if (target.classList.contains('edit-category')) {
        editCategory(target.dataset.categoryId);
      } else if (target.classList.contains('delete-category')) {
        deleteCategory(target.dataset.categoryId);
      }
      // Order actions
      else if (target.classList.contains('edit-order')) {
        editOrder(target.dataset.orderId);
      } else if (target.classList.contains('delete-order')) {
        deleteOrder(target.dataset.orderId);
      }
      // User actions
      else if (target.classList.contains('edit-user')) {
        editUser(target.dataset.userId);
      } else if (target.classList.contains('delete-user')) {
        deleteUser(target.dataset.userId);
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

  // Expose API
  window.Admin = {
    saveProduct,
    saveCategory,
    saveOrder,
    saveUser,
    editProduct,
    editCategory,
    editOrder,
    editUser,
    deleteProduct,
    deleteCategory,
    clearProductForm,
    clearCategoryForm,
    clearOrderForm,
    clearUserForm,
    deleteOrder,
    deleteUser
  };

})(window, document);