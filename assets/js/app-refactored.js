/**
 * Simplified JavaScript Application - UI Interactions Only
 * E-commerce Store - Server-Side Operations
 * Version: 4.0 - OOP and Modular
 */

// ============================================
// UTILITY MODULE
// ============================================
const Utils = {
    /**
     * Display toast notification
     */
    showToast(message, type = 'primary') {
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
    escapeHtml(str) {
        const div = document.createElement('div');
        div.innerText = String(str);
        return div.innerHTML;
    },

    /**
     * Get CSRF token from meta tag
     */
    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : null;
    }
};

// ============================================
// THEME MANAGEMENT
// ============================================
const ThemeManager = {
    storageKey: 'color-mode',

    init() {
        this.loadSavedTheme();
        this.setupToggleListener();
        this.updateIcon();
    },

    loadSavedTheme() {
        const savedTheme = localStorage.getItem(this.storageKey);
        const root = document.documentElement;
        
        if (savedTheme === 'dark') {
            root.setAttribute('data-bs-theme', 'dark');
        } else {
            root.removeAttribute('data-bs-theme');
        }
    },

    setupToggleListener() {
        document.addEventListener('click', (e) => {
            const toggleBtn = e.target.closest('[data-action="theme-toggle"]');
            if (toggleBtn) {
                this.toggleTheme();
            }
        });
    },

    toggleTheme() {
        const root = document.documentElement;
        const isDark = root.getAttribute('data-bs-theme') === 'dark';
        const newTheme = isDark ? 'light' : 'dark';
        
        if (newTheme === 'dark') {
            root.setAttribute('data-bs-theme', 'dark');
        } else {
            root.removeAttribute('data-bs-theme');
        }
        
        localStorage.setItem(this.storageKey, newTheme);
        this.updateIcon();
    },

    updateIcon() {
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const lightIcons = document.querySelectorAll('.theme-icon[data-theme="light"]');
        const darkIcons = document.querySelectorAll('.theme-icon[data-theme="dark"]');
        
        lightIcons.forEach(icon => icon.classList.toggle('d-none', isDark));
        darkIcons.forEach(icon => icon.classList.toggle('d-none', !isDark));
    }
};

// ============================================
// FORM ENHANCEMENT
// ============================================
const FormEnhancement = {
    init() {
        this.setupPasswordToggles();
        this.setupFormValidation();
        this.setupPasswordMatch();
    },

    setupPasswordToggles() {
        // Password visibility toggles
        document.addEventListener('click', (e) => {
            const toggle = e.target.closest('.password-toggle');
            if (!toggle) return;
            
            const targetId = toggle.dataset.target;
            const passwordField = document.getElementById(targetId);
            const icon = toggle.querySelector('i');
            
            if (passwordField && icon) {
                const isPassword = passwordField.type === 'password';
                passwordField.type = isPassword ? 'text' : 'password';
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });

        // Legacy support for existing toggle buttons
        ['togglePassword', 'toggleConfirmPassword'].forEach(id => {
            const toggle = document.getElementById(id);
            if (toggle) {
                toggle.addEventListener('click', () => {
                    const field = id === 'togglePassword' ? 
                        document.getElementById('password') : 
                        document.getElementById('confirmPassword') || document.getElementById('confirm_password');
                    const icon = toggle.querySelector('i');
                    
                    if (field && icon) {
                        const isPassword = field.type === 'password';
                        field.type = isPassword ? 'text' : 'password';
                        icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
                    }
                });
            }
        });
    },

    setupFormValidation() {
        // Bootstrap form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    },

    setupPasswordMatch() {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword') || 
                              document.getElementById('confirm_password');
        
        if (password && confirmPassword) {
            const validateMatch = () => {
                if (confirmPassword.value && password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            };
            
            password.addEventListener('input', validateMatch);
            confirmPassword.addEventListener('input', validateMatch);
        }
    }
};

// ============================================
// NAVIGATION & UI MANAGEMENT
// ============================================
const Navigation = {
    init() {
        this.setupScrollEffects();
        this.setupSmoothScroll();
        this.setupScrollToTop();
        this.setDynamicYear();
    },

    setupScrollEffects() {
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                navbar.classList.toggle('scrolled', window.scrollY > 100);
            }
        });
    },

    setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href && href.length > 1) {
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });
    },

    setupScrollToTop() {
        const scrollTopBtn = document.getElementById('scrollTop');
        if (scrollTopBtn) {
            window.addEventListener('scroll', () => {
                scrollTopBtn.style.display = window.scrollY > 300 ? 'block' : 'none';
            });
            
            scrollTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    },

    setDynamicYear() {
        const yearEl = document.getElementById('year');
        if (yearEl) {
            yearEl.textContent = new Date().getFullYear();
        }
    }
};

// ============================================
// CART UI HELPERS  (Server-side operations only)
// ============================================
const CartUI = {
    init() {
        this.updateCartBadges();
    },

    updateCartBadges() {
        // Cart count is rendered server-side, just ensure visibility
        const badges = document.querySelectorAll('.cart-badge, .cart-count');
        badges.forEach(badge => {
            const count = parseInt(badge.textContent) || 0;
            badge.style.display = count > 0 ? 'inline' : 'none';
        });
    },

    // Show loading state on cart operations
    showLoadingOnForms() {
        document.querySelectorAll('form[action*="cart"]').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Adding...';
                    
                    // Re-enable after timeout as fallback
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 3000);
                }
            });
        });
    }
};

// ============================================
// CHECKOUT UI ENHANCEMENTS
// ============================================
const CheckoutUI = {
    init() {
        if (!document.getElementById('checkoutForm')) return;
        
        this.initPaymentMethods();
        this.initCardFormatting();
    },

    initPaymentMethods() {
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

    initCardFormatting() {
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
// IMAGE UPLOAD PREVIEW
// ============================================
const ImageUpload = {
    init() {
        document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
            input.addEventListener('change', this.previewImage.bind(this));
        });
    },

    previewImage(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            Utils.showToast('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.', 'danger');
            e.target.value = '';
            return;
        }
        
        // Validate file size (5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            Utils.showToast('File too large. Maximum size is 5MB.', 'danger');
            e.target.value = '';
            return;
        }
        
        // Show preview if preview element exists
        const previewId = e.target.dataset.preview || 'imagePreview';
        const preview = document.getElementById(previewId);
        
        if (preview) {
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.innerHTML = `<img src="${event.target.result}" class="img-fluid rounded" style="max-height: 200px;">`;
            };
            reader.readAsDataURL(file);
        }
    }
};

// ============================================
// QUANTITY CONTROLS
// ============================================
const QuantityControls = {
    init() {
        document.addEventListener('click', (e) => {
            const decreaseBtn = e.target.closest('.qty-decrease');
            const increaseBtn = e.target.closest('.qty-increase');
            
            if (decreaseBtn) {
                this.adjustQuantity(decreaseBtn, -1);
            } else if (increaseBtn) {
                this.adjustQuantity(increaseBtn, 1);
            }
        });
    },

    adjustQuantity(button, change) {
        const input = button.closest('.quantity-control')?.querySelector('input[type="number"]');
        if (!input) return;
        
        const current = parseInt(input.value) || 1;
        const min = parseInt(input.min) || 1;
        const max = parseInt(input.max) || 999;
        
        let newValue = current + change;
        newValue = Math.max(min, Math.min(max, newValue));
        
        input.value = newValue;
        
        // Trigger change event for form handling
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
};

// ============================================
// APPLICATION INITIALIZATION
// ============================================
const App = {
    init() {
        // Initialize all modules
        ThemeManager.init();
        FormEnhancement.init();
        Navigation.init();
        CartUI.init();
        CheckoutUI.init();
        ImageUpload.init();
        QuantityControls.init();
        
        // Show flash messages if any
        this.showFlashMessages();
        
        // Handle form loading states
        CartUI.showLoadingOnForms();
        
        console.log('Application initialized - Server-side operations mode');
    },

    showFlashMessages() {
        // If flash messages are rendered server-side, ensure they auto-dismiss
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            if (alert.classList.contains('alert-dismissible')) {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            }
        });
    }
};

// ============================================
// AUTO-INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Export for use in other scripts if needed
window.Utils = Utils;
window.ThemeManager = ThemeManager;
