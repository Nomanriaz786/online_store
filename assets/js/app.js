/**
 * Unified JavaScript Application
 * E-commerce Store - All functionality consolidated in one file
 * Version: 3.0
 */

// ============================================
// APPLICATION CONFIGURATION
// ============================================
const AppConfig = {
    siteName: "My Store",
    currency: "USD",
    apiUrl: window.location.origin,
    cartStorage: 'shopping_cart',
    authStorage: 'auth_state',
    themeStorage: 'color-mode'
};

// ============================================
// UTILITY FUNCTIONS
// ============================================
const Utils = {
    /**
     * Display toast notification
     */
    showToast: function(message, type = 'primary') {
        const wrap = document.createElement('div');
        wrap.setAttribute('aria-live', 'polite');
        wrap.setAttribute('aria-atomic', 'true');
        wrap.className = 'position-fixed top-0 end-0 p-3';
        wrap.style.zIndex = '1080';
        wrap.innerHTML = `
            <div class="toast align-items-center text-bg-${type} border-0 show" role="status">
                <div class="d-flex">
                    <div class="toast-body">${this.escapeHtml(message)}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>`;
        document.body.appendChild(wrap);
        setTimeout(() => wrap.remove(), 4000);
    },

    /**
     * Escape HTML to prevent XSS attacks
     */
    escapeHtml: function(str) {
        const div = document.createElement('div');
        div.innerText = String(str);
        return div.innerHTML;
    },

    /**
     * Format price with currency
     */
    formatPrice: function(price) {
        return `$${parseFloat(price).toFixed(2)}`;
    },

    /**
     * Generate unique ID
     */
    generateId: function() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    },

    /**
     * Debounce function calls
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Get form data as object
     */
    getFormData: function(form) {
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        return data;
    },

    /**
     * Get CSRF token from meta tag
     */
    getCSRFToken: function() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : null;
    },

    /**
     * Make HTTP request
     */
    request: async function(url, options = {}) {
        try {
            // Get CSRF token
            const csrfToken = this.getCSRFToken();
            
            // Default headers
            const defaultHeaders = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            
            // Add CSRF token if available
            if (csrfToken) {
                defaultHeaders['X-CSRF-TOKEN'] = csrfToken;
            }
            
            const response = await fetch(url, {
                credentials: 'include',
                headers: {
                    ...defaultHeaders,
                    ...options.headers
                },
                ...options
            });

            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    },

    applyPromo: function() {
        const promoCode = document.getElementById('promoCode');
        if (!promoCode) return;
        
        const code = promoCode.value.trim();
        if (!code) {
            this.showToast('Please enter a promo code', 'warning');
            return;
        }
        
        this.showToast('Promo code functionality will be implemented in the future', 'info');
    }
};

// ============================================
// THEME MANAGEMENT
// ============================================
const ThemeManager = {
    init: function() {
        this.loadSavedTheme();
        this.setupToggleListener();
        this.updateIcon();
    },

    loadSavedTheme: function() {
        const savedTheme = localStorage.getItem(AppConfig.themeStorage);
        const root = document.documentElement;
        
        if (savedTheme === 'dark') {
            root.setAttribute('data-bs-theme', 'dark');
        } else {
            root.removeAttribute('data-bs-theme');
        }
    },

    setupToggleListener: function() {
        document.addEventListener('click', (e) => {
            const toggleBtn = e.target.closest('[data-action="theme-toggle"]');
            if (!toggleBtn) return;
            
            this.toggleTheme();
        });
    },

    toggleTheme: function() {
        const root = document.documentElement;
        const isDark = root.getAttribute('data-bs-theme') === 'dark';
        const newTheme = isDark ? 'light' : 'dark';
        
        if (newTheme === 'dark') {
            root.setAttribute('data-bs-theme', 'dark');
        } else {
            root.removeAttribute('data-bs-theme');
        }
        
        localStorage.setItem(AppConfig.themeStorage, newTheme);
        this.updateIcon();
    },

    updateIcon: function() {
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const lightIcons = document.querySelectorAll('.theme-icon[data-theme="light"]');
        const darkIcons = document.querySelectorAll('.theme-icon[data-theme="dark"]');
        
        lightIcons.forEach(icon => {
            icon.classList.toggle('d-none', isDark);
        });
        
        darkIcons.forEach(icon => {
            icon.classList.toggle('d-none', !isDark);
        });
    }
};

// ============================================
// AUTHENTICATION SYSTEM
// ============================================
const Auth = {
    init: function() {
        // Sync with server auth state if available
        this.syncWithServerAuthState();
        this.setupEventListeners();
        this.updateNavigation();
    },

    syncWithServerAuthState: function() {
        if (window.SERVER_AUTH_STATE) {
            if (window.SERVER_AUTH_STATE.isAuthenticated && window.SERVER_AUTH_STATE.user) {
                // Server says user is authenticated, update local state
                this.setAuthState(window.SERVER_AUTH_STATE.user);
            } else {
                // Server says user is not authenticated, clear local state
                localStorage.removeItem(AppConfig.authStorage);
            }
        }
    },

    setupEventListeners: function() {
        // Logout button
        document.addEventListener('click', (e) => {
            const logoutBtn = e.target.closest('[data-action="logout"]');
            if (logoutBtn) {
                e.preventDefault();
                this.logout();
            }
        });

        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            this.initLoginForm(loginForm);
        }

        // Register form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            this.initRegisterForm(registerForm);
        }
    },

    initLoginForm: function(form) {
        // Password toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        
        if (togglePassword && passwordField) {
            togglePassword.addEventListener('click', () => {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                const icon = togglePassword.querySelector('i');
                icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        }

        // Form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (form.checkValidity()) {
                const formData = Utils.getFormData(form);
                await this.handleLogin(formData);
            } else {
                form.classList.add('was-validated');
            }
        });
    },

    initRegisterForm: function(form) {
        // Password toggles
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirmPassword');
        
        if (togglePassword && passwordField) {
            togglePassword.addEventListener('click', () => {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                const icon = togglePassword.querySelector('i');
                icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        }

        if (toggleConfirmPassword && confirmPasswordField) {
            toggleConfirmPassword.addEventListener('click', () => {
                const type = confirmPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordField.setAttribute('type', type);
                const icon = toggleConfirmPassword.querySelector('i');
                icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        }

        // Password matching validation
        const checkPasswordMatch = () => {
            if (confirmPasswordField.value && passwordField.value !== confirmPasswordField.value) {
                confirmPasswordField.setCustomValidity('Passwords do not match');
            } else {
                confirmPasswordField.setCustomValidity('');
            }
        };

        if (passwordField && confirmPasswordField) {
            passwordField.addEventListener('input', checkPasswordMatch);
            confirmPasswordField.addEventListener('input', checkPasswordMatch);
        }

        // Form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            checkPasswordMatch();
            
            if (form.checkValidity()) {
                const formData = Utils.getFormData(form);
                await this.handleRegister(formData);
            } else {
                form.classList.add('was-validated');
            }
        });
    },

    async handleLogin(formData) {
        try {
            const response = await Utils.request('api/auth/login.php', {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            if (response.success) {
                this.setAuthState(response.user);
                Utils.showToast(`Welcome back, ${response.user.username}!`, 'success');
                
                // Trigger cart sync after login
                if (window.Cart) {
                    Cart.syncWithServer();
                }
                
                // Dispatch login event for other components
                document.dispatchEvent(new CustomEvent('userLoggedIn', { detail: response.user }));
                
                // Redirect based on role or return to previous page
                setTimeout(() => {
                    if (response.user.role === 'admin') {
                        window.location.href = 'admin.php';
                    } else {
                        // Go back to previous page or home
                        const urlParams = new URLSearchParams(window.location.search);
                        const redirect = urlParams.get('redirect') || 'index.php';
                        window.location.href = redirect;
                    }
                }, 1500);
            }
        } catch (error) {
            Utils.showToast(error.message || 'Login failed. Please try again.', 'danger');
        }
    },

    async handleRegister(formData) {
        try {
            const response = await Utils.request('api/auth/register.php', {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            if (response.success) {
                Utils.showToast('Registration successful! Please login with your new account.', 'success');
                
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
        } catch (error) {
            Utils.showToast(error.message || 'Registration failed. Please try again.', 'danger');
        }
    },

    logout: function() {
        // Clear local storage
        localStorage.removeItem(AppConfig.authStorage);
        
        // Call server logout
        Utils.request('api/auth/logout.php', { method: 'POST' })
            .then(() => {
                Utils.showToast('You have been logged out successfully.', 'success');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 500);
            })
            .catch(() => {
                // Even if server logout fails, redirect to home
                window.location.href = 'index.php';
            });
    },

    getAuthState: function() {
        try {
            return JSON.parse(localStorage.getItem(AppConfig.authStorage)) || { loggedIn: false };
        } catch {
            return { loggedIn: false };
        }
    },

    isAuthenticated: function() {
        const state = this.getAuthState();
        return state.loggedIn === true;
    },

    setAuthState: function(user) {
        localStorage.setItem(AppConfig.authStorage, JSON.stringify({
            loggedIn: true,
            ...user,
            timestamp: Date.now()
        }));
        this.updateNavigation();
    },

    updateNavigation: function() {
        const state = this.getAuthState();
        const loginBtn = document.querySelector('[data-nav="login"]');
        const registerBtn = document.querySelector('[data-nav="register"]');
        const userLink = document.querySelector('[data-nav="user"]');
        const adminLink = document.querySelector('[data-nav="admin"]');
        const logoutBtn = document.querySelector('[data-action="logout"]');
        const userGreeting = document.querySelector('[data-nav="user-greeting"]');

        if (!loginBtn || !registerBtn || !userLink || !logoutBtn) return;

        if (state.loggedIn) {
            // Hide login/register buttons
            loginBtn.classList.add('d-none');
            registerBtn.classList.add('d-none');
            
            // Show user links
            userLink.classList.remove('d-none');
            if (adminLink) {
                adminLink.classList.toggle('d-none', state.role !== 'admin');
            }
            logoutBtn.classList.remove('d-none');
            
            // Show user greeting
            if (userGreeting) {
                userGreeting.classList.remove('d-none');
                const usernameEl = userGreeting.querySelector('[data-username]');
                if (usernameEl) usernameEl.textContent = state.username;
            }
        } else {
            // Show login/register buttons
            loginBtn.classList.remove('d-none');
            registerBtn.classList.remove('d-none');
            
            // Hide user links
            userLink.classList.add('d-none');
            if (adminLink) adminLink.classList.add('d-none');
            logoutBtn.classList.add('d-none');
            
            // Hide user greeting
            if (userGreeting) {
                userGreeting.classList.add('d-none');
            }
        }
    }
};

// ============================================
// PRODUCT MANAGEMENT
// ============================================
const ProductManager = {
    products: [],
    filteredProducts: [],
    currentView: 'grid',
    currentCategory: 'all',
    currentSort: { key: 'name', dir: 'asc' },
    searchTerm: '',

    init: function() {
        this.setupEventListeners();
        this.loadProducts();
    },

    setupEventListeners: function() {
        // View toggle buttons
        const gridBtn = document.getElementById('viewGrid');
        const listBtn = document.getElementById('viewList');
        
        if (gridBtn && listBtn) {
            gridBtn.addEventListener('click', () => this.setView('grid'));
            listBtn.addEventListener('click', () => this.setView('list'));
        }

        // Category filter
        const categoryFilter = document.getElementById('categoryFilter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.currentCategory = e.target.value;
                this.filterProducts();
            });
        }

        // Sort dropdown
        const sortBy = document.getElementById('sortBy');
        if (sortBy) {
            sortBy.addEventListener('change', (e) => {
                const [key, dir] = e.target.value.split(':');
                this.currentSort = { key, dir };
                this.filterProducts();
            });
        }

        // Search functionality
        const searchInput = document.getElementById('productSearch') || document.getElementById('featureSearch');
        if (searchInput) {
            const debouncedSearch = Utils.debounce((term) => {
                this.searchTerm = term;
                this.filterProducts();
            }, 300);

            searchInput.addEventListener('input', (e) => {
                debouncedSearch(e.target.value);
            });
        }

        // Clear search button
        const clearBtn = document.getElementById('clearSearch');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                this.searchTerm = '';
                this.filterProducts();
            });
        }

        // View mode toggle
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');
        if (gridView && listView) {
            gridView.addEventListener('change', () => {
                if (gridView.checked) {
                    this.currentView = 'grid';
                    this.renderProducts();
                }
            });
            
            listView.addEventListener('change', () => {
                if (listView.checked) {
                    this.currentView = 'list';
                    this.renderProducts();
                }
            });
        }

        // Sort options - delegate to document to handle dynamically added elements
        document.addEventListener('click', (e) => {
            if (e.target.matches('.sort-option')) {
                e.preventDefault();
                const sortBy = e.target.dataset.sort;
                if (sortBy) {
                    ProductManager.sortProducts(sortBy);
                }
            }
        });
    },

    async loadProducts() {
        try {
            // Show loading state
            this.showLoading();
            
            const response = await Utils.request('api/products/get.php');
            
            // Handle both response formats
            if (response.success) {
                this.products = response.products || [];
            } else {
                throw new Error(response.message || 'Failed to load products');
            }
            
            this.filterProducts();
            this.updateCategoryFilter();
            this.hideLoading();
            
        } catch (error) {
            this.hideLoading();
            this.showNoProducts();
            Utils.showToast('Failed to load products', 'danger');
            console.error('Error loading products:', error);
        }
    },

    filterProducts: function() {
        let filtered = [...this.products];

        // Filter by category
        if (this.currentCategory !== 'all') {
            filtered = filtered.filter(p => p.category === this.currentCategory);
        }

        // Filter by search term
        if (this.searchTerm.trim()) {
            const term = this.searchTerm.toLowerCase();
            filtered = filtered.filter(p => 
                p.name.toLowerCase().includes(term) ||
                p.description.toLowerCase().includes(term) ||
                p.category.toLowerCase().includes(term)
            );
        }

        // Sort products
        filtered.sort((a, b) => {
            let aVal = this.currentSort.key === 'name' ? a.name.toLowerCase() : parseFloat(a.price);
            let bVal = this.currentSort.key === 'name' ? b.name.toLowerCase() : parseFloat(b.price);
            
            const cmp = aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
            return this.currentSort.dir === 'asc' ? cmp : -cmp;
        });

        this.filteredProducts = filtered;
        this.renderProducts();
        this.updateResultsCount();
    },

    renderProducts: function() {
        // Check if we're on the products page
        const productsContainer = document.getElementById('productsContainer');
        if (productsContainer) {
            this.renderProductsPage();
            return;
        }

        // Handle home page or other pages
        const container = document.getElementById('productList') || document.getElementById('featuredGrid');
        if (!container) return;

        if (this.filteredProducts.length === 0) {
            container.innerHTML = this.getNoResultsHtml();
            return;
        }

        const productsToShow = container.id === 'featuredGrid' ? 
            this.filteredProducts.slice(0, 8) : this.filteredProducts;

        container.innerHTML = productsToShow.map((product, index) => 
            this.getProductCardHtml(product, index)).join('');
    },

    getProductCardHtml: function(product, index = 0) {
        const isListView = this.currentView === 'list' && document.getElementById('productList');
        
        if (isListView) {
            return `
                <div class="col-12 mb-3">
                    <article class="card product-card-list d-flex flex-row" aria-label="${Utils.escapeHtml(product.name)}">
                        <img src="${Utils.escapeHtml(product.image_path)}" class="product-img-list" alt="${Utils.escapeHtml(product.name)}">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <h3 class="h5 card-title mb-2">${Utils.escapeHtml(product.name)}</h3>
                                <p class="card-text text-muted mb-2">${Utils.escapeHtml(product.description)}</p>
                                <span class="badge bg-secondary mb-2">${Utils.escapeHtml(product.category)}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 fw-bold text-primary mb-0">${Utils.formatPrice(product.price)}</span>
                                <button class="btn btn-primary" onclick="Cart.addItem(${product.id}, '${Utils.escapeHtml(product.name)}', ${product.price}, '${Utils.escapeHtml(product.image_path)}')">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </article>
                </div>`;
        } else {
            return `
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4 delay-${index + 1}" data-category="${Utils.escapeHtml(product.category)}">
                    <article class="card product-card h-100" aria-label="${Utils.escapeHtml(product.name)}">
                        <img src="${Utils.escapeHtml(product.image_path)}" class="card-img-top product-img" alt="${Utils.escapeHtml(product.name)}" loading="lazy">
                        <div class="card-body d-flex flex-column">
                            <h3 class="h6 card-title">${Utils.escapeHtml(product.name)}</h3>
                            <p class="card-text text-muted small flex-grow-1">${Utils.escapeHtml(product.description)}</p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="fw-bold fs-5 text-gradient" data-price="${product.price}">${Utils.formatPrice(product.price)}</span>
                                <button class="btn btn-primary btn-sm" onclick="Cart.addItem(${product.id}, '${Utils.escapeHtml(product.name)}', ${product.price}, '${Utils.escapeHtml(product.image_path)}')">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </article>
                </div>`;
        }
    },

    getNoResultsHtml: function() {
        return `
            <div class="col-12 text-center py-5">
                <div class="no-results-message">
                    <i class="bi bi-search mb-3 no-results-icon"></i>
                    <h3 class="h5 mb-2">No products found</h3>
                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                    <button class="btn btn-outline-primary" id="clearSearch">Clear Search</button>
                </div>
            </div>`;
    },

    setView: function(view) {
        this.currentView = view;
        this.updateViewButtons();
        this.renderProducts();
    },

    updateViewButtons: function() {
        const gridBtn = document.getElementById('viewGrid');
        const listBtn = document.getElementById('viewList');
        
        if (gridBtn && listBtn) {
            gridBtn.classList.toggle('active', this.currentView === 'grid');
            listBtn.classList.toggle('active', this.currentView === 'list');
        }
    },

    updateCategoryFilter: function() {
        const categoryFilter = document.getElementById('categoryFilter');
        if (!categoryFilter) return;

        const categories = [...new Set(this.products.map(p => p.category))];
        
        categoryFilter.innerHTML = '<option value="all">All Categories</option>' +
            categories.map(cat => `<option value="${cat}">${cat}</option>`).join('');
    },

    updateResultsCount: function() {
        const countEl = document.getElementById('count');
        const resultsCountEl = document.getElementById('searchResultsCount');
        
        if (countEl) {
            countEl.textContent = this.filteredProducts.length;
        }
        
        if (resultsCountEl) {
            if (this.searchTerm && this.filteredProducts.length > 0) {
                resultsCountEl.textContent = `Showing ${this.filteredProducts.length} products`;
                resultsCountEl.classList.remove('d-none');
            } else {
                resultsCountEl.classList.add('d-none');
            }
        }

        // Show/hide no results message
        const noResultsEl = document.getElementById('noResults');
        const gridEl = document.getElementById('featuredGrid') || document.getElementById('productList');
        
        if (noResultsEl && gridEl) {
            if (this.searchTerm && this.filteredProducts.length === 0) {
                noResultsEl.classList.remove('d-none');
                gridEl.classList.add('d-none');
            } else {
                noResultsEl.classList.add('d-none');
                gridEl.classList.remove('d-none');
            }
        }
    },

    showLoading: function() {
        const loadingEl = document.getElementById('loadingSpinner');
        const containerEl = document.getElementById('productsContainer');
        
        if (loadingEl) {
            loadingEl.classList.remove('d-none');
        }
        if (containerEl) {
            containerEl.innerHTML = '';
        }
    },

    hideLoading: function() {
        const loadingEl = document.getElementById('loadingSpinner');
        
        if (loadingEl) {
            loadingEl.classList.add('d-none');
        }
    },

    showNoProducts: function() {
        const noProductsEl = document.getElementById('noProducts');
        const containerEl = document.getElementById('productsContainer');
        
        if (noProductsEl) {
            noProductsEl.classList.remove('d-none');
        }
        if (containerEl) {
            containerEl.innerHTML = '';
        }
    },

    setViewMode: function(mode) {
        this.currentView = mode;
        const container = document.getElementById('productsContainer');
        
        if (container) {
            if (mode === 'list') {
                container.className = 'row';
                container.setAttribute('data-view', 'list');
            } else {
                container.className = 'row';
                container.setAttribute('data-view', 'grid');
            }
        }
        
        this.renderProducts();
    },

    filterByCategory: function(categoryId) {
        this.currentCategory = categoryId;
        this.filterProducts();
    },

    searchProducts: function(query) {
        this.searchTerm = query;
        this.filterProducts();
    },

    sortProducts: function(sortBy) {
        console.log('Sorting products by:', sortBy);
        
        let sortedProducts = [...this.filteredProducts];
        
        switch(sortBy) {
            case 'name':
                sortedProducts.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'price-low':
                sortedProducts.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
                break;
            case 'price-high':
                sortedProducts.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
                break;
        }
        
        this.filteredProducts = sortedProducts;
        this.renderProductsPage();
        
        // Update sort button text
        const sortButton = document.querySelector('[data-bs-toggle="dropdown"]');
        if (sortButton) {
            const sortLabels = {
                'name': 'Name',
                'price-low': 'Price: Low to High',
                'price-high': 'Price: High to Low'
            };
            const buttonText = sortLabels[sortBy] || 'Sort By';
            sortButton.innerHTML = `${buttonText} <i class="bi bi-chevron-down ms-2"></i>`;
        }
    },

    renderProductsPage: function() {
        const container = document.getElementById('productsContainer');
        if (!container) return;

        // Hide loading and no products messages
        this.hideLoading();
        document.getElementById('noProducts')?.classList.add('d-none');

        if (this.filteredProducts.length === 0) {
            this.showNoProducts();
            return;
        }

        const viewMode = container.getAttribute('data-view') || 'grid';
        
        container.innerHTML = this.filteredProducts.map(product => {
            if (viewMode === 'list') {
                return this.getProductListHtml(product);
            } else {
                return this.getProductGridHtml(product);
            }
        }).join('');

        // Setup add to cart buttons
        container.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = btn.dataset.productId;
                if (Cart && productId) {
                    Cart.addToCart(productId, 1);
                }
            });
        });
    },

    getProductGridHtml: function(product) {
        return `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card product-card h-100">
                    <img src="${Utils.escapeHtml(product.image_path || 'assets/img/placeholder.svg')}" 
                         class="card-img-top product-img" alt="${Utils.escapeHtml(product.name)}">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${Utils.escapeHtml(product.name)}</h5>
                        <p class="card-text text-muted small">${Utils.escapeHtml(product.description || '')}</p>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="h6 text-primary mb-0">$${parseFloat(product.price).toFixed(2)}</span>
                                <span class="badge bg-secondary">${Utils.escapeHtml(product.category || 'General')}</span>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary add-to-cart-btn" data-product-id="${product.id}">
                                    <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                </button>
                                <a href="product/${product.id}" class="btn btn-outline-primary btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
    },

    getProductListHtml: function(product) {
        return `
            <div class="col-12 mb-3">
                <div class="card product-card">
                    <div class="row g-0">
                        <div class="col-md-2">
                            <img src="${Utils.escapeHtml(product.image_path || 'assets/img/placeholder.svg')}" 
                                 class="img-fluid rounded-start h-100" alt="${Utils.escapeHtml(product.name)}" style="object-fit: cover;">
                        </div>
                        <div class="col-md-10">
                            <div class="card-body d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">${Utils.escapeHtml(product.name)}</h5>
                                    <p class="card-text">${Utils.escapeHtml(product.description || '')}</p>
                                    <span class="badge bg-secondary">${Utils.escapeHtml(product.category || 'General')}</span>
                                </div>
                                <div class="text-end">
                                    <div class="h5 text-primary mb-3">$${parseFloat(product.price).toFixed(2)}</div>
                                    <div class="d-grid gap-2" style="min-width: 150px;">
                                        <button class="btn btn-primary add-to-cart-btn" data-product-id="${product.id}">
                                            <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                        </button>
                                        <a href="product/${product.id}" class="btn btn-outline-primary btn-sm">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
    }
};

// ============================================
// SHOPPING CART SYSTEM
// ============================================
const Cart = {
    items: [],

    init: function() {
        this.loadCart();
        this.updateCartDisplay();
        this.setupEventListeners();
    },

    setupEventListeners: function() {
        // Quantity change buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.quantity-btn[data-action="increase"]')) {
                const id = parseInt(e.target.dataset.id);
                this.updateQuantity(id, 1);
            } else if (e.target.matches('.quantity-btn[data-action="decrease"]')) {
                const id = parseInt(e.target.dataset.id);
                this.updateQuantity(id, -1);
            } else if (e.target.matches('.remove-item')) {
                const id = parseInt(e.target.dataset.id);
                this.removeItem(id);
            }
        });

        // Quantity input changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('.quantity-input')) {
                const id = parseInt(e.target.dataset.id);
                const quantity = parseInt(e.target.value);
                this.setQuantity(id, quantity);
            }
        });

        // Clear cart button
        document.addEventListener('click', (e) => {
            if (e.target.matches('#clearCart')) {
                this.clearCart();
            }
        });
    },

    addItem: function(id, name, price, image) {
        const existingItem = this.items.find(item => item.id === id);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.items.push({
                id: id,
                name: name,
                price: parseFloat(price),
                image: image,
                quantity: 1
            });
        }
        
        this.saveCart();
        this.updateCartDisplay();
        Utils.showToast(`${name} added to cart!`, 'success');
    },

    addToCart: async function(productId, quantity = 1) {
        try {
            console.log('Adding to cart:', productId, quantity);
            
            const response = await Utils.request('api/cart/add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            });

            console.log('Add to cart response:', response);

            if (response.success) {
                Utils.showToast(response.message, 'success');
                
                // Update cart count in header
                this.updateCartCount(response.cart_count);
                
                // Refresh cart if we're on cart page
                if (window.location.pathname.includes('cart')) {
                    this.syncWithServer();
                }
            } else {
                // If not authenticated, redirect to login with return URL
                if (response.message && response.message.includes('log in')) {
                    Utils.showToast('Please log in to add items to cart', 'warning');
                    const currentUrl = encodeURIComponent(window.location.href);
                    window.location.href = `login.php?redirect=${currentUrl}`;
                    return;
                }
                Utils.showToast(response.message || 'Failed to add item to cart', 'danger');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            Utils.showToast('Error adding item to cart', 'danger');
        }
    },

    removeItem: function(id) {
        const itemIndex = this.items.findIndex(item => item.id === id);
        if (itemIndex !== -1) {
            const itemName = this.items[itemIndex].name;
            this.items.splice(itemIndex, 1);
            this.saveCart();
            this.updateCartDisplay();
            Utils.showToast(`${itemName} removed from cart`, 'info');
        }
    },

    updateCartCount: function(count) {
        // Update cart badge in header
        const cartBadges = document.querySelectorAll('.cart-count, .cart-badge');
        cartBadges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        });

        // Update cart count in navigation
        const cartLinks = document.querySelectorAll('a[href*="cart"]');
        cartLinks.forEach(link => {
            const badge = link.querySelector('.badge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
    },

    updateQuantity: function(id, change) {
        const item = this.items.find(item => item.id === id);
        if (item) {
            item.quantity += change;
            if (item.quantity <= 0) {
                this.removeItem(id);
            } else {
                this.saveCart();
                this.updateCartDisplay();
            }
        }
    },

    setQuantity: function(id, quantity) {
        const item = this.items.find(item => item.id === id);
        if (item) {
            if (quantity <= 0) {
                this.removeItem(id);
            } else {
                item.quantity = quantity;
                this.saveCart();
                this.updateCartDisplay();
            }
        }
    },

    clearCart: function() {
        this.items = [];
        this.saveCart();
        this.updateCartDisplay();
        Utils.showToast('Cart cleared', 'info');
    },

    getTotal: function() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    },

    getItemCount: function() {
        return this.items.reduce((count, item) => count + item.quantity, 0);
    },

    updateCartDisplay: function() {
        this.updateCartBadge();
        this.updateCartTable();
        this.updateCartSummary();
    },

    updateCartBadge: function() {
        const badges = document.querySelectorAll('.cart-badge');
        const count = this.getItemCount();
        
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        });
    },

    updateCartTable: function() {
        const tbody = document.getElementById('cartItems');
        if (!tbody) return;

        if (this.items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="bi bi-cart-x fs-1 text-muted mb-3"></i>
                        <h5>Your cart is empty</h5>
                        <p class="text-muted">Add some products to get started!</p>
                        <a href="products.php" class="btn btn-primary">Browse Products</a>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = this.items.map(item => `
            <tr data-id="${item.id}">
                <td>
                    <img src="${Utils.escapeHtml(item.image)}" alt="${Utils.escapeHtml(item.name)}" class="cart-item-image rounded">
                </td>
                <td>
                    <h6 class="mb-0">${Utils.escapeHtml(item.name)}</h6>
                </td>
                <td>${Utils.formatPrice(item.price)}</td>
                <td>
                    <div class="quantity-control input-group" style="width: 120px;">
                        <button class="btn btn-outline-secondary quantity-btn" data-action="decrease" data-id="${item.id}">
                            <i class="bi bi-dash"></i>
                        </button>
                        <input type="number" class="form-control text-center quantity-input" 
                               value="${item.quantity}" min="1" data-id="${item.id}">
                        <button class="btn btn-outline-secondary quantity-btn" data-action="increase" data-id="${item.id}">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </td>
                <td class="fw-bold">${Utils.formatPrice(item.price * item.quantity)}</td>
                <td>
                    <button class="btn btn-outline-danger btn-sm remove-item" data-id="${item.id}" title="Remove item">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    },

    updateCartSummary: function() {
        const subtotalEl = document.getElementById('cartSubtotal');
        const totalEl = document.getElementById('cartTotal');
        
        const subtotal = this.getTotal();
        const shipping = subtotal > 50 ? 0 : 5.99; // Free shipping over $50
        const total = subtotal + shipping;
        
        if (subtotalEl) subtotalEl.textContent = Utils.formatPrice(subtotal);
        if (totalEl) totalEl.textContent = Utils.formatPrice(total);
        
        // Update shipping info
        const shippingEl = document.getElementById('cartShipping');
        if (shippingEl) {
            shippingEl.textContent = shipping === 0 ? 'FREE' : Utils.formatPrice(shipping);
        }
    },

    saveCart: function() {
        localStorage.setItem(AppConfig.cartStorage, JSON.stringify(this.items));
        
        // Also save to server if user is logged in
        const auth = Auth.getAuthState();
        if (auth.loggedIn) {
            Utils.request('api/cart/save.php', {
                method: 'POST',
                body: JSON.stringify({ items: this.items })
            }).catch(error => {
                console.error('Failed to save cart to server:', error);
            });
        }
    },

    loadCart: function() {
        if (Auth.isAuthenticated()) {
            this.syncWithServer();
        } else {
            try {
                const saved = localStorage.getItem(AppConfig.cartStorage);
                this.items = saved ? JSON.parse(saved) : [];
            } catch (error) {
                console.error('Error loading cart:', error);
                this.items = [];
            }
        }
    },

    async syncWithServer() {
        try {
            const response = await Utils.request('api/cart/get.php');
            console.log('Cart sync response:', response);
            
            if (response.success && response.items) {
                // Transform server items to client format
                this.items = response.items.map(item => ({
                    id: item.product_id,
                    name: item.product_name || item.name,
                    price: parseFloat(item.price),
                    image: item.image_url || item.image_path || 'assets/img/placeholder.svg',
                    quantity: item.quantity
                }));
                
                this.saveCart();
                this.updateCartDisplay();
                
                // Update cart count
                const totalQuantity = this.items.reduce((sum, item) => sum + item.quantity, 0);
                this.updateCartCount(totalQuantity);
            }
        } catch (error) {
            console.error('Failed to sync cart with server:', error);
            // Fall back to local storage
            try {
                const saved = localStorage.getItem(AppConfig.cartStorage);
                this.items = saved ? JSON.parse(saved) : [];
                this.updateCartDisplay();
            } catch (e) {
                this.items = [];
            }
        }
    },

    // ============================================
    // CART PAGE SPECIFIC FUNCTIONS
    // ============================================

    /**
     * Update quantity for cart page
     */
    updateCartQuantity: async function(productId, newQuantity) {
        if (newQuantity < 1) {
            this.removeCartItem(productId);
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'update_quantity');
        formData.append('product_id', productId);
        formData.append('quantity', newQuantity);
        formData.append('csrf_token', Utils.getCSRFToken());
        
        try {
            const response = await fetch('cart.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reload to show updated quantities and totals
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error updating quantity', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            Utils.showToast('An error occurred', 'danger');
        }
    },

    /**
     * Remove item from cart page
     */
    removeCartItem: async function(productId) {
        if (!confirm('Are you sure you want to remove this item from your cart?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'remove_item');
        formData.append('product_id', productId);
        formData.append('csrf_token', Utils.getCSRFToken());
        
        try {
            const response = await fetch('cart.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reload to show updated cart
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error removing item', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            Utils.showToast('An error occurred', 'danger');
        }
    },

    /**
     * Clear entire cart
     */
    clearEntireCart: async function() {
        if (!confirm('Are you sure you want to clear your entire cart?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'clear_cart');
        formData.append('csrf_token', Utils.getCSRFToken());
        
        try {
            const response = await fetch('cart.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                Utils.showToast(data.message || 'Error clearing cart', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            Utils.showToast('An error occurred', 'danger');
        }
    },

    /**
     * Apply promo code (placeholder for future implementation)
     */
    applyPromoCode: function() {
        const promoCodeInput = document.getElementById('promoCode');
        if (!promoCodeInput) return;
        
        const promoCode = promoCodeInput.value.trim();
        if (!promoCode) {
            Utils.showToast('Please enter a promo code', 'warning');
            return;
        }
        
        // This would normally send the promo code to the server for validation
        Utils.showToast('Promo code functionality will be implemented in the future', 'info');
    },

    /**
     * Save for later (placeholder for future implementation)
     */
    saveForLater: function() {
        Utils.showToast('Save for later functionality will be implemented in the future', 'info');
    }
};

// ============================================
// ADMIN PANEL FUNCTIONALITY
// ============================================
const AdminPanel = {
    products: [],
    filteredProducts: [],
    currentEditId: null,

    init: function() {
        if (!document.getElementById('adminDashboard')) return;
        
        this.setupEventListeners();
        this.loadProducts();
        this.updateDashboardStats();
    },

    setupEventListeners: function() {
        // Add product button
        const addProductBtn = document.getElementById('addProductBtn');
        if (addProductBtn) {
            addProductBtn.addEventListener('click', () => this.openProductModal());
        }

        // Product form
        const productForm = document.getElementById('productForm');
        if (productForm) {
            productForm.addEventListener('submit', (e) => this.handleProductSubmit(e));
        }

        // Search and filters
        const searchInput = document.getElementById('productSearch');
        if (searchInput) {
            searchInput.addEventListener('input', Utils.debounce((e) => {
                this.filterProducts(e.target.value);
            }, 300));
        }

        // Image upload
        const imageInput = document.getElementById('productImage');
        if (imageInput) {
            imageInput.addEventListener('change', (e) => this.handleImagePreview(e));
        }

        // Action buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-edit')) {
                const id = parseInt(e.target.dataset.id);
                this.editProduct(id);
            } else if (e.target.matches('.btn-delete')) {
                const id = parseInt(e.target.dataset.id);
                this.deleteProduct(id);
            } else if (e.target.matches('.btn-duplicate')) {
                const id = parseInt(e.target.dataset.id);
                this.duplicateProduct(id);
            }
        });
    },

    async loadProducts() {
        try {
            const response = await Utils.request('api/admin/products.php');
            this.products = response.products || [];
            this.filterProducts();
            this.updateDashboardStats();
        } catch (error) {
            Utils.showToast('Failed to load products', 'danger');
        }
    },

    filterProducts: function(searchTerm = '') {
        this.filteredProducts = this.products.filter(product => 
            product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            product.category.toLowerCase().includes(searchTerm.toLowerCase())
        );
        this.renderProductsTable();
    },

    renderProductsTable: function() {
        const tbody = document.getElementById('productsTableBody');
        if (!tbody) return;

        if (this.filteredProducts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-box-seam fs-1 d-block mb-3"></i>
                            <h5>No products found</h5>
                            <p class="mb-3">Add some products to get started</p>
                            <button class="btn btn-primary" onclick="AdminPanel.openProductModal()">
                                <i class="bi bi-plus-circle me-1"></i>Add First Product
                            </button>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = this.filteredProducts.map(product => `
            <tr>
                <td>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${product.id}">
                    </div>
                </td>
                <td>
                    <img src="${Utils.escapeHtml(product.image_path)}" alt="${Utils.escapeHtml(product.name)}" 
                         class="rounded shadow-sm" width="60" height="60" style="object-fit: cover;">
                </td>
                <td>
                    <div>
                        <h6 class="mb-1 fw-semibold">${Utils.escapeHtml(product.name)}</h6>
                        <small class="text-muted">SKU: ${Utils.escapeHtml(product.sku || 'N/A')}</small><br>
                        <small class="text-muted">Stock: ${product.stock_quantity || 0} units</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-secondary">${Utils.escapeHtml(product.category)}</span>
                </td>
                <td class="fw-semibold">${Utils.formatPrice(product.price)}</td>
                <td>
                    <span class="badge bg-success">Active</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-edit" data-id="${product.id}" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-info btn-duplicate" data-id="${product.id}" title="Duplicate">
                            <i class="bi bi-copy"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete" data-id="${product.id}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    openProductModal: function(productId = null) {
        this.currentEditId = productId;
        const modal = document.getElementById('productModal');
        const form = document.getElementById('productForm');
        
        if (productId) {
            const product = this.products.find(p => p.id === productId);
            if (product) {
                form.querySelector('#productName').value = product.name;
                form.querySelector('#productDescription').value = product.description;
                form.querySelector('#productPrice').value = product.price;
                form.querySelector('#productCategory').value = product.category;
                form.querySelector('#productStock').value = product.stock_quantity || 0;
            }
        } else {
            form.reset();
        }
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    },

    async handleProductSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const endpoint = this.currentEditId ? 
            `/api/admin/products.php?id=${this.currentEditId}` : 
            'api/admin/products.php';
        
        const method = this.currentEditId ? 'PUT' : 'POST';
        
        try {
            const response = await fetch(endpoint, {
                method: method,
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                Utils.showToast(
                    this.currentEditId ? 'Product updated successfully!' : 'Product added successfully!', 
                    'success'
                );
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
                modal.hide();
                
                this.loadProducts();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Utils.showToast(error.message || 'Failed to save product', 'danger');
        }
    },

    editProduct: function(id) {
        this.openProductModal(id);
    },

    async deleteProduct(id) {
        if (!confirm('Are you sure you want to delete this product?')) return;
        
        try {
            const response = await Utils.request(`/api/admin/products.php?id=${id}`, {
                method: 'DELETE'
            });
            
            if (response.success) {
                Utils.showToast('Product deleted successfully!', 'success');
                this.loadProducts();
            }
        } catch (error) {
            Utils.showToast(error.message || 'Failed to delete product', 'danger');
        }
    },

    duplicateProduct: function(id) {
        const product = this.products.find(p => p.id === id);
        if (!product) return;
        
        // Open modal with product data but clear the ID
        this.currentEditId = null;
        const modal = document.getElementById('productModal');
        const form = document.getElementById('productForm');
        
        form.querySelector('#productName').value = `${product.name} (Copy)`;
        form.querySelector('#productDescription').value = product.description;
        form.querySelector('#productPrice').value = product.price;
        form.querySelector('#productCategory').value = product.category;
        form.querySelector('#productStock').value = product.stock_quantity || 0;
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    },

    handleImagePreview: function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            if (preview) {
                preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">`;
            }
        };
        reader.readAsDataURL(file);
    },

    updateDashboardStats: function() {
        const totalProducts = document.getElementById('totalProducts');
        const totalOrders = document.getElementById('totalOrders');
        const totalRevenue = document.getElementById('totalRevenue');
        
        if (totalProducts) {
            totalProducts.textContent = this.products.length;
        }
        
        // These would be loaded from the server in a real application
        if (totalOrders) totalOrders.textContent = '0';
        if (totalRevenue) totalRevenue.textContent = '$0.00';
    }
};

// ============================================
// CHECKOUT SYSTEM
// ============================================
const Checkout = {
    currentStep: 1,
    orderData: {},

    init: function() {
        if (!document.getElementById('checkoutForm')) return;
        
        this.setupEventListeners();
        this.updateOrderSummary();
        this.updateProgress();
        this.initPaymentMethods();
        this.initCardFormatting();
    },

    setupEventListeners: function() {
        const form = document.getElementById('checkoutForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        // Shipping option changes
        const shippingOptions = document.querySelectorAll('input[name="shipping"]');
        shippingOptions.forEach(option => {
            option.addEventListener('change', () => this.updateOrderSummary());
        });
    },

    async handleSubmit(e) {
        e.preventDefault();

        const cartItems = Cart.items;

        // Prevent order placement if cart is empty
        if (cartItems.length === 0) {
            return;
        }

        if (!this.validateForm(e.target)) return;

        const formData = Utils.getFormData(e.target);

        this.orderData = {
            ...formData,
            items: cartItems,
            total: this.calculateTotal()
        };

        try {
            const response = await Utils.request('api/checkout/process.php', {
                method: 'POST',
                body: JSON.stringify(this.orderData)
            });
            
            if (response.success) {
                // Clear cart
                Cart.clearCart();
                
                // Show success message
                Utils.showToast('Order placed successfully!', 'success');
                
                // Redirect to confirmation page
                window.location.href = `checkout-confirmation.php?order_id=${response.order_id}`;
            }
        } catch (error) {
            Utils.showToast(error.message || 'Failed to process order', 'danger');
        }
    },

    validateForm: function(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });
        
        // Additional validation for payment method
        const paymentMethodInput = document.querySelector('input[name="payment_method"]:checked');
        if (paymentMethodInput && paymentMethodInput.value === 'credit_card') {
            const cardNumber = document.getElementById('cardNumber');
            const cardCvv = document.getElementById('cardCvv');
            const cardExpiry = document.getElementById('cardExpiry');
            const cardName = document.getElementById('cardName');
            
            if (!cardNumber || !cardNumber.value || cardNumber.value.replace(/\s/g, '').length < 13) {
                Utils.showToast('Please enter a valid card number', 'danger');
                isValid = false;
            }
            
            if (!cardCvv || !cardCvv.value || cardCvv.value.length < 3) {
                Utils.showToast('Please enter a valid CVV', 'danger');
                isValid = false;
            }
            
            if (!cardExpiry || !cardExpiry.value || cardExpiry.value.length < 5) {
                Utils.showToast('Please enter a valid expiry date', 'danger');
                isValid = false;
            }
            
            if (!cardName || !cardName.value.trim()) {
                Utils.showToast('Please enter the name on the card', 'danger');
                isValid = false;
            }
        }
        
        return isValid;
    },

    updateOrderSummary: function() {
        const itemsContainer = document.getElementById('orderItems');
        const subtotalEl = document.getElementById('orderSubtotal');
        const shippingEl = document.getElementById('orderShipping');
        const totalEl = document.getElementById('orderTotal');
        
        if (!itemsContainer) return;
        
        // Render order items
        itemsContainer.innerHTML = Cart.items.map(item => `
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                <div class="d-flex align-items-center">
                    <img src="${Utils.escapeHtml(item.image)}" alt="${Utils.escapeHtml(item.name)}" 
                         class="rounded me-3" width="50" height="50" style="object-fit: cover;">
                    <div>
                        <h6 class="mb-0">${Utils.escapeHtml(item.name)}</h6>
                        <small class="text-muted">Qty: ${item.quantity}</small>
                    </div>
                </div>
                <span class="fw-bold">${Utils.formatPrice(item.price * item.quantity)}</span>
            </div>
        `).join('');
        
        // Calculate totals
        const subtotal = Cart.getTotal();
        const shipping = this.getShippingCost();
        const total = subtotal + shipping;
        
        if (subtotalEl) subtotalEl.textContent = Utils.formatPrice(subtotal);
        if (shippingEl) shippingEl.textContent = shipping === 0 ? 'FREE' : Utils.formatPrice(shipping);
        if (totalEl) totalEl.textContent = Utils.formatPrice(total);
    },

    getShippingCost: function() {
        const selectedShipping = document.querySelector('input[name="shipping"]:checked');
        if (!selectedShipping) return 0;
        
        const cost = parseFloat(selectedShipping.dataset.cost || 0);
        return cost;
    },

    calculateTotal: function() {
        return Cart.getTotal() + this.getShippingCost();
    },

    updateProgress: function() {
        const steps = document.querySelectorAll('.progress-step');
        steps.forEach((step, index) => {
            if (index < this.currentStep) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });
    },

    initPaymentMethods: function() {
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        const creditCardForm = document.getElementById('creditCardForm');
        const paypalForm = document.getElementById('paypalForm');
        const applePayForm = document.getElementById('applePayForm');
        
        if (!paymentMethods.length) return;
        
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                // Hide all forms
                if (creditCardForm) creditCardForm.classList.add('d-none');
                if (paypalForm) paypalForm.classList.add('d-none');
                if (applePayForm) applePayForm.classList.add('d-none');
                
                // Show selected form
                switch(this.value) {
                    case 'credit_card':
                        if (creditCardForm) creditCardForm.classList.remove('d-none');
                        break;
                    case 'paypal':
                        if (paypalForm) paypalForm.classList.remove('d-none');
                        break;
                    case 'apple_pay':
                        if (applePayForm) applePayForm.classList.remove('d-none');
                        break;
                }
            });
        });
    },
    
    initCardFormatting: function() {
        // Card number formatting
        const cardNumber = document.getElementById('cardNumber');
        if (cardNumber) {
            cardNumber.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formattedValue;
            });
        }
        
        // Expiry date formatting
        const cardExpiry = document.getElementById('cardExpiry');
        if (cardExpiry) {
            cardExpiry.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });
        }
        
        // CVV input restriction
        const cardCvv = document.getElementById('cardCvv');
        if (cardCvv) {
            cardCvv.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
        }
    }
};

// ============================================
// NAVIGATION & UI MANAGEMENT
// ============================================
const Navigation = {
    init: function() {
        this.setupScrollEffects();
        this.setActiveNavigation();
        this.loadPartials();
    },

    setupScrollEffects: function() {
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                navbar.classList.toggle('scrolled', window.scrollY > 100);
            }
        });

        // Scroll to top button
        const scrollTopBtn = document.getElementById('scrollTop');
        if (scrollTopBtn) {
            window.addEventListener('scroll', () => {
                scrollTopBtn.style.display = window.scrollY > 300 ? 'block' : 'none';
            });
            
            scrollTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                // Skip empty or just '#' hrefs
                if (href && href.length > 1) {
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });
    },

    setActiveNavigation: function() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
    },

    async loadPartials() {
        await this.loadHeader();
        await this.loadFooter();
        this.refreshUI();
    },

    async loadHeader() {
        const headerPlaceholder = document.getElementById('header-placeholder');
        if (headerPlaceholder) {
            try {
                const response = await fetch('includes/header.php');
                headerPlaceholder.innerHTML = await response.text();
            } catch (error) {
                console.error('Failed to load header:', error);
            }
        }
    },

    async loadFooter() {
        const footerPlaceholder = document.getElementById('footer-placeholder');
        if (footerPlaceholder) {
            try {
                const response = await fetch('includes/footer.php');
                footerPlaceholder.innerHTML = await response.text();
            } catch (error) {
                console.error('Failed to load footer:', error);
            }
        }
    },

    refreshUI: function() {
        // Set dynamic year
        const yearEl = document.getElementById('year');
        if (yearEl) yearEl.textContent = new Date().getFullYear();
        
        // Set site name
        const siteNames = document.querySelectorAll('#siteName, #footerSiteName');
        siteNames.forEach(el => el.textContent = AppConfig.siteName);
        
        // Set navbar height CSS property
        const navbar = document.querySelector('header .navbar');
        if (navbar) {
            const navbarHeight = navbar.getBoundingClientRect().height;
            document.documentElement.style.setProperty('--nav-offset', `${Math.ceil(navbarHeight + 8)}px`);
        }
    }
};

// ============================================
// APPLICATION INITIALIZATION
// ============================================
const App = {
    init: function() {
        // Initialize core systems
        ThemeManager.init();
        Auth.init();
        Navigation.init();
        Cart.init();
        
        // Initialize page-specific functionality
        this.initPageSpecific();
        
        // Setup global event listeners
        this.setupGlobalEvents();
        
        console.log('Application initialized successfully');
    },

    initPageSpecific: function() {
        const path = window.location.pathname;
        
        if (path.includes('index') || path === '/') {
            ProductManager.init();
            this.initHomePage();
        } else if (path.includes('products')) {
            ProductManager.init();
            this.initProductsPage();
        } else if (path.includes('product/')) {
            this.initProductDetailPage();
        } else if (path.includes('about')) {
            this.initAboutPage();
        } else if (path.includes('profile')) {
            this.initProfilePage();
        } else if (path.includes('login')) {
            this.initLoginPage();
        } else if (path.includes('register')) {
            this.initRegisterPage();
        } else if (path.includes('admin')) {
            AdminPanel.init();
        } else if (path.includes('checkout')) {
            Checkout.init();
        }
        
        this.initFormValidation();
    },

    initHomePage: function() {
        // Initialize featured products search
        if (window.ProductManager && ProductManager.initFeaturedSearch) {
            ProductManager.initFeaturedSearch();
        }
        
        // Initialize add to cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                if (window.Cart && Cart.addToCart) {
                    Cart.addToCart(productId, 1);
                }
            });
        });
    },

    initLoginPage: function() {
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        
        if (togglePassword && passwordField) {
            togglePassword.addEventListener('click', function() {
                const icon = document.getElementById('passwordIcon');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    if (icon) icon.className = 'bi bi-eye-slash';
                } else {
                    passwordField.type = 'password';
                    if (icon) icon.className = 'bi bi-eye';
                }
            });
        }
    },

    initRegisterPage: function() {
        // Toggle password visibility for both fields
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        
        if (togglePassword && passwordField) {
            togglePassword.addEventListener('click', function() {
                const icon = document.getElementById('passwordIcon');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    if (icon) icon.className = 'bi bi-eye-slash';
                } else {
                    passwordField.type = 'password';
                    if (icon) icon.className = 'bi bi-eye';
                }
            });
        }

        if (toggleConfirmPassword && confirmPasswordField) {
            toggleConfirmPassword.addEventListener('click', function() {
                const icon = document.getElementById('confirmPasswordIcon');
                
                if (confirmPasswordField.type === 'password') {
                    confirmPasswordField.type = 'text';
                    if (icon) icon.className = 'bi bi-eye-slash';
                } else {
                    confirmPasswordField.type = 'password';
                    if (icon) icon.className = 'bi bi-eye';
                }
            });
        }

        // Password validation
        if (passwordField && confirmPasswordField) {
            const validatePasswords = () => {
                if (confirmPasswordField.value !== passwordField.value) {
                    confirmPasswordField.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordField.setCustomValidity('');
                }
            };

            passwordField.addEventListener('input', validatePasswords);
            confirmPasswordField.addEventListener('input', validatePasswords);
        }
    },

    initProductsPage: function() {
        // Initialize product filtering and search
        this.initProductFilter();
        this.initProductSearch();
        this.initViewToggle();
        this.initSortOptions();
        
        // Load products
        if (window.ProductManager && ProductManager.loadProducts) {
            ProductManager.loadProducts();
        }
    },

    initProductDetailPage: function() {
        // Get product ID from URL
        const pathParts = window.location.pathname.split('/');
        const productId = pathParts[pathParts.indexOf('product') + 1];
        
        if (productId) {
            this.loadProductDetails(productId);
        }
        
        // Initialize quantity controls
        this.initQuantityControls();
        
        // Initialize add to cart
        this.initProductActions();
    },

    initAboutPage: function() {
        // Initialize any about page specific functionality
        // Currently just static content, but could add animations or interactive elements
        console.log('About page initialized');
    },

    initProfilePage: function() {
        // Check if user is authenticated
        if (!Auth.isAuthenticated()) {
            window.location.href = 'login.php';
            return;
        }
        
        // Initialize profile tabs
        this.initProfileTabs();
        
        // Load user profile data
        this.loadUserProfile();
        
        // Initialize profile forms
        this.initProfileForms();
        
        // Load user orders for orders tab
        this.loadUserOrders();
        
        // Initialize profile picture upload
        this.initProfilePictureUpload();
    },

    initProductFilter: function() {
        const categoryFilter = document.getElementById('categoryFilter');
        if (categoryFilter) {
            // Load categories
            this.loadCategories();
            
            categoryFilter.addEventListener('change', function() {
                const category = this.value;
                ProductManager.filterByCategory(category);
            });
        }
    },

    initProductSearch: function() {
        const searchInput = document.getElementById('productSearch');
        const searchBtn = document.getElementById('searchBtn');
        
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    ProductManager.searchProducts(this.value);
                }, 300);
            });
        }
        
        if (searchBtn) {
            searchBtn.addEventListener('click', function() {
                const query = searchInput?.value || '';
                ProductManager.searchProducts(query);
            });
        }
    },

    initViewToggle: function() {
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');
        
        if (gridView && listView) {
            gridView.addEventListener('change', () => {
                if (gridView.checked) {
                    ProductManager.setViewMode('grid');
                }
            });
            
            listView.addEventListener('change', () => {
                if (listView.checked) {
                    ProductManager.setViewMode('list');
                }
            });
        }
    },

    initSortOptions: function() {
        document.querySelectorAll('.sort-option').forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                const sortBy = this.dataset.sort;
                ProductManager.sortProducts(sortBy);
            });
        });
    },

    loadProductDetails: function(productId) {
        Utils.request(`api/products/get.php?id=${productId}`)
            .then(response => {
                if (response.success && response.data) {
                    this.displayProductDetails(response.data);
                } else {
                    this.showProductNotFound();
                }
            })
            .catch(error => {
                console.error('Error loading product:', error);
                this.showProductNotFound();
            });
    },

    displayProductDetails: function(product) {
        document.getElementById('productLoading').classList.add('d-none');
        document.getElementById('productContent').classList.remove('d-none');
        
        // Update product information
        document.getElementById('productName').textContent = product.name;
        document.getElementById('productPrice').textContent = `$${parseFloat(product.price).toFixed(2)}`;
        document.getElementById('productDescription').textContent = product.description;
        document.getElementById('productCategory').textContent = product.category || 'General';
        
        // Update image
        const productImage = document.getElementById('productImage');
        if (product.image_path) {
            productImage.src = product.image_path;
            productImage.alt = product.name;
        }
        
        // Update breadcrumbs
        document.getElementById('productNameBreadcrumb').textContent = product.name;
        document.getElementById('productCategoryBreadcrumb').textContent = product.category || 'General';
        
        // Store product ID for cart actions
        document.getElementById('addToCartBtn').dataset.productId = product.id;
    },

    showProductNotFound: function() {
        document.getElementById('productLoading').classList.add('d-none');
        document.getElementById('productNotFound').classList.remove('d-none');
    },

    initQuantityControls: function() {
        const decreaseBtn = document.getElementById('decreaseQty');
        const increaseBtn = document.getElementById('increaseQty');
        const quantityInput = document.getElementById('quantity');
        
        if (decreaseBtn && increaseBtn && quantityInput) {
            decreaseBtn.addEventListener('click', () => {
                const current = parseInt(quantityInput.value);
                if (current > 1) {
                    quantityInput.value = current - 1;
                }
            });
            
            increaseBtn.addEventListener('click', () => {
                const current = parseInt(quantityInput.value);
                const max = parseInt(quantityInput.getAttribute('max')) || 10;
                if (current < max) {
                    quantityInput.value = current + 1;
                }
            });
        }
    },

    initProductActions: function() {
        const addToCartBtn = document.getElementById('addToCartBtn');
        const buyNowBtn = document.getElementById('buyNowBtn');
        
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', () => {
                const productId = addToCartBtn.dataset.productId;
                const quantity = document.getElementById('quantity')?.value || 1;
                
                if (productId && Cart) {
                    Cart.addToCart(productId, parseInt(quantity));
                }
            });
        }
        
        if (buyNowBtn) {
            buyNowBtn.addEventListener('click', () => {
                const productId = addToCartBtn?.dataset.productId;
                const quantity = document.getElementById('quantity')?.value || 1;
                
                if (productId && Cart) {
                    Cart.addToCart(productId, parseInt(quantity));
                    window.location.href = 'checkout.php';
                }
            });
        }
    },

    loadCategories: function() {
        Utils.request('api/products/categories.php')
            .then(response => {
                if (response.success) {
                    const categoryFilter = document.getElementById('categoryFilter');
                    if (categoryFilter && response.data) {
                        response.data.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category.id;
                            option.textContent = category.name;
                            categoryFilter.appendChild(option);
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error loading categories:', error);
            });
    },

    initProfileTabs: function() {
        document.querySelectorAll('.profile-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.dataset.tab;
                
                // Remove active class from all tabs and contents
                document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.profile-content').forEach(c => c.classList.add('d-none'));
                
                // Add active class to clicked tab and show content
                this.classList.add('active');
                document.getElementById(`${targetTab}-tab`).classList.remove('d-none');
            });
        });
    },

    loadUserProfile: function() {
        Utils.request('api/users/profile.php')
            .then(response => {
                if (response.success && response.user) {
                    this.populateProfileData(response.user);
                }
            })
            .catch(error => {
                console.error('Error loading profile:', error);
            });
    },

    populateProfileData: function(userData) {
        // Populate profile display
        const fullName = `${userData.first_name || ''} ${userData.last_name || ''}`.trim() || userData.username || 'User';
        const profileName = document.getElementById('profileName');
        const profileEmail = document.getElementById('profileEmail');
        const profileImage = document.getElementById('profileImage');
        
        if (profileName) profileName.textContent = fullName;
        if (profileEmail) profileEmail.textContent = userData.email || '';
        if (profileImage && userData.profile_picture) {
            profileImage.src = userData.profile_picture;
        }
        
        // Populate form fields (including address fields)
        const fields = ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'state', 'zipCode', 'country'];
        fields.forEach(field => {
            const element = document.getElementById(field);
            if (element) {
                const dbField = field === 'firstName' ? 'first_name' : 
                               field === 'lastName' ? 'last_name' :
                               field === 'zipCode' ? 'zip_code' : field;
                element.value = userData[dbField] || '';
            }
        });
    },

    initProfileForms: function() {
        const profileForm = document.getElementById('profileForm');
        const passwordForm = document.getElementById('passwordForm');
        
        if (profileForm) {
            profileForm.addEventListener('submit', this.handleProfileUpdate.bind(this));
        }
        
        if (passwordForm) {
            passwordForm.addEventListener('submit', this.handlePasswordChange.bind(this));
        }
    },

    handleProfileUpdate: function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {};
        
        // Convert FormData to object
        for (let [key, value] of formData.entries()) {
            // Map form field names to database field names
            const dbKey = key === 'firstName' ? 'first_name' : 
                         key === 'lastName' ? 'last_name' :
                         key === 'zipCode' ? 'zip_code' : key;
            data[dbKey] = value.trim();
        }
        
        Utils.request('api/users/profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (response.success) {
                Utils.showToast('Profile updated successfully!', 'success');
                // Reload profile data to update sidebar
                this.loadUserProfile();
            } else {
                Utils.showToast('Failed to update profile: ' + response.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            Utils.showToast('Error updating profile', 'danger');
        });
    },

    handlePasswordChange: function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {};
        
        // Convert FormData to object with proper field names
        for (let [key, value] of formData.entries()) {
            // Map form field names to API field names
            const apiKey = key === 'currentPassword' ? 'current_password' :
                          key === 'newPassword' ? 'new_password' :
                          key === 'confirmPassword' ? 'confirm_password' : key;
            data[apiKey] = value;
        }
        
        Utils.request('api/users/change-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (response.success) {
                Utils.showToast('Password updated successfully!', 'success');
                e.target.reset(); // Clear the form
            } else {
                Utils.showToast('Failed to update password: ' + response.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error updating password:', error);
            Utils.showToast('Error updating password', 'danger');
        });
    },

    loadUserOrders: function() {
        const container = document.getElementById('ordersContainer');
        const noOrders = document.getElementById('noOrders');
        
        if (!container) return;
        
        Utils.request('api/orders/get.php')
            .then(orders => {
                if (orders && orders.length > 0) {
                    container.innerHTML = this.renderOrders(orders);
                    container.classList.remove('d-none');
                    if (noOrders) noOrders.classList.add('d-none');
                } else {
                    container.classList.add('d-none');
                    if (noOrders) noOrders.classList.remove('d-none');
                }
            })
            .catch(error => {
                console.error('Error loading orders:', error);
                Utils.showToast('Error loading orders', 'danger');
            });
    },

    renderOrders: function(orders) {
        return orders.map(order => {
            const statusColor = this.getOrderStatusColor(order.status);
            const orderDate = new Date(order.created_at).toLocaleDateString();
            
            return `
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h6 class="mb-1">Order #${order.order_number || order.id}</h6>
                                <small class="text-muted">${orderDate}</small>
                            </div>
                            <div class="col-md-2">
                                <span class="badge bg-${statusColor}">${order.status}</span>
                            </div>
                            <div class="col-md-2">
                                <strong>$${parseFloat(order.total_amount).toFixed(2)}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">
                                    Items: ${order.item_count || 'N/A'}
                                </small>
                            </div>
                            <div class="col-md-2">
                                <a href="order-confirmation.php?id=${order.id}" class="btn btn-sm btn-outline-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },

    getOrderStatusColor: function(status) {
        const colors = {
            'pending': 'warning',
            'processing': 'info',
            'shipped': 'primary',
            'delivered': 'success',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    },

    initProfilePictureUpload: function() {
        const profilePictureForm = document.getElementById('profilePictureForm');
        const profilePictureFile = document.getElementById('profilePictureFile');
        
        if (profilePictureFile) {
            profilePictureFile.addEventListener('change', this.previewProfilePicture.bind(this));
        }
        
        if (profilePictureForm) {
            profilePictureForm.addEventListener('submit', this.handleProfilePictureUpload.bind(this));
        }
    },

    previewProfilePicture: function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                Utils.showToast('Invalid file type. Only JPG, PNG, and GIF are allowed.', 'danger');
                e.target.value = '';
                return;
            }
            
            // Validate file size (2MB)
            const maxSize = 2 * 1024 * 1024;
            if (file.size > maxSize) {
                Utils.showToast('File too large. Maximum size is 2MB.', 'danger');
                e.target.value = '';
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImage = document.getElementById('previewImage');
                if (previewImage) {
                    previewImage.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        }
    },

    handleProfilePictureUpload: function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        // Show progress
        const progressContainer = document.getElementById('uploadProgress');
        const progressBar = progressContainer.querySelector('.progress-bar');
        progressContainer.classList.remove('d-none');
        progressBar.style.width = '0%';
        
        // Use XMLHttpRequest for upload progress
        const xhr = new XMLHttpRequest();
        
        // Track upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
            }
        });
        
        xhr.addEventListener('load', () => {
            progressContainer.classList.add('d-none');
            
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        Utils.showToast('Profile picture updated successfully!', 'success');
                        
                        // Update profile image
                        const profileImage = document.getElementById('profileImage');
                        if (profileImage && result.profile_picture) {
                            profileImage.src = result.profile_picture + '?t=' + Date.now();
                        }
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('profilePictureModal'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Reset form
                        form.reset();
                        const previewImage = document.getElementById('previewImage');
                        if (previewImage) {
                            previewImage.src = 'assets/img/placeholder.svg';
                        }
                    } else {
                        Utils.showToast('Failed to upload: ' + result.message, 'danger');
                    }
                } catch (e) {
                    Utils.showToast('Invalid response from server', 'danger');
                }
            } else {
                Utils.showToast('Upload failed. Please try again.', 'danger');
            }
        });
        
        xhr.addEventListener('error', () => {
            progressContainer.classList.add('d-none');
            Utils.showToast('Upload failed. Please try again.', 'danger');
        });
        
        xhr.open('POST', 'api/users/upload-profile-picture.php');
        xhr.send(formData);
    },

    initFormValidation: function() {
        // Bootstrap form validation for all forms
        const forms = document.getElementsByClassName('needs-validation');
        Array.prototype.forEach.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    },

    setupGlobalEvents: function() {
        // Handle responsive updates
        window.addEventListener('resize', () => {
            Navigation.refreshUI();
        });
        
        // Sync cart when user logs in
        document.addEventListener('userLoggedIn', () => {
            Cart.syncWithServer();
        });
        
        // Error handling
        window.addEventListener('error', (e) => {
            console.error('Global error:', e.error);
        });
    }
};

// ============================================
// AUTO-INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});



// Make functions globally available for onclick handlers
window.Cart = Cart;
window.AdminPanel = AdminPanel;
window.ProductManager = ProductManager;
window.Checkout = Checkout;
window.Utils = Utils;

// Checkout Confirmation Specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
  const successIcon = document.querySelector('.success-animation i');
  if (successIcon) {
    successIcon.style.animation = 'bounceIn 1s ease-in-out';
  }
});