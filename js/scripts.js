/**
 * FezaMarket E-Commerce Platform JavaScript
 * Frontend functionality and interactions
 */

// Application object
const FezaMarket = {
    config: {
        apiUrl: '/api',
        cartUpdateDelay: 500
    },
    
    init: function() {
        this.bindEvents();
        this.initModals();
        this.initTooltips();
        this.updateCartDisplay();
        this.initSearchSuggestions();
        this.initQuantityControls();
        this.initModalControls();
        this.initProductGallery();
        this.initMobileMenu();
    },
    
    bindEvents: function() {
        // Search functionality
        const searchForm = document.querySelector('.search-form');
        const searchInput = document.getElementById('search-input');
        
        if (searchForm && searchInput) {
            searchForm.addEventListener('submit', this.handleSearch.bind(this));
            searchInput.addEventListener('input', this.debounce(this.handleSearchSuggestions.bind(this), 300));
            searchInput.addEventListener('focus', this.showSearchSuggestions.bind(this));
            searchInput.addEventListener('blur', this.hideSearchSuggestions.bind(this));
        }
        
        // Cart functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('.add-to-cart')) {
                e.preventDefault();
                this.addToCart(e.target);
            }
            
            if (e.target.matches('.remove-from-cart')) {
                e.preventDefault();
                this.removeFromCart(e.target);
            }
            
            if (e.target.matches('.update-quantity')) {
                this.updateCartQuantity(e.target);
            }
            
            if (e.target.matches('.add-to-wishlist')) {
                e.preventDefault();
                this.addToWishlist(e.target);
            }
        });
        
        // Category navigation hover effects
        const categoryNavItems = document.querySelectorAll('.category-nav-item');
        categoryNavItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.color = '#0654ba';
                this.style.borderBottomColor = '#0654ba';
            });
            item.addEventListener('mouseleave', function() {
                this.style.color = '#767676';
                this.style.borderBottomColor = 'transparent';
            });
        });
        
        // Banner hover effects
        const banners = document.querySelectorAll('[onclick*="window.location"]');
        banners.forEach(banner => {
            banner.addEventListener('mouseenter', function() {
                const bg = this.querySelector('.banner-bg');
                if (bg) {
                    bg.style.transform = 'scale(1.05)';
                }
                this.style.transform = 'translateY(-2px)';
            });
            banner.addEventListener('mouseleave', function() {
                const bg = this.querySelector('.banner-bg');
                if (bg) {
                    bg.style.transform = 'scale(1)';
                }
                this.style.transform = 'translateY(0)';
            });
        });
    },
    
    initSearchSuggestions: function() {
        const searchInput = document.getElementById('search-input');
        const suggestionsContainer = document.getElementById('search-suggestions');
        
        if (!searchInput || !suggestionsContainer) return;
        
        // Create suggestions if container doesn't exist
        if (!suggestionsContainer) {
            const container = document.createElement('div');
            container.id = 'search-suggestions';
            container.className = 'search-suggestions';
            container.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-top: none;
                border-radius: 0 0 4px 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                z-index: 1000;
                max-height: 300px;
                overflow-y: auto;
                display: none;
            `;
            searchInput.parentElement.appendChild(container);
        }
    },
    
    handleSearch: function(e) {
        e.preventDefault();
        const searchInput = document.getElementById('search-input');
        const categorySelect = document.getElementById('category-select');
        
        if (!searchInput.value.trim()) return;
        
        const query = searchInput.value.trim();
        const category = categorySelect ? categorySelect.value : '';
        
        let url = '/search.php?q=' + encodeURIComponent(query);
        if (category) {
            url += '&category=' + encodeURIComponent(category);
        }
        
        window.location.href = url;
    },
    
    handleSearchSuggestions: function(e) {
        const query = e.target.value.trim();
        const suggestionsContainer = document.getElementById('search-suggestions');
        
        if (!query || query.length < 2) {
            this.hideSearchSuggestions();
            return;
        }
        
        // Mock search suggestions (in real app, this would be an API call)
        const suggestions = [
            'iPhone 15 Pro',
            'Samsung Galaxy S24',
            'MacBook Air M2',
            'Nike Air Max',
            'PlayStation 5',
            'Nintendo Switch'
        ].filter(item => item.toLowerCase().includes(query.toLowerCase()));
        
        this.displaySearchSuggestions(suggestions);
    },
    
    displaySearchSuggestions: function(suggestions) {
        const container = document.getElementById('search-suggestions');
        if (!container) return;
        
        if (suggestions.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.innerHTML = suggestions.map(suggestion => 
            `<div class="suggestion-item" style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0;" 
                  onclick="FezaMarket.selectSuggestion('${suggestion}')"
                  onmouseenter="this.style.backgroundColor='#f7f7f7'"
                  onmouseleave="this.style.backgroundColor='white'">
                ${suggestion}
             </div>`
        ).join('');
        
        container.style.display = 'block';
    },
    
    selectSuggestion: function(suggestion) {
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.value = suggestion;
            this.hideSearchSuggestions();
            searchInput.form.submit();
        }
    },
    
    showSearchSuggestions: function() {
        const container = document.getElementById('search-suggestions');
        const input = document.getElementById('search-input');
        
        if (container && input.value.trim().length >= 2) {
            container.style.display = 'block';
        }
    },
    
    hideSearchSuggestions: function() {
        setTimeout(() => {
            const container = document.getElementById('search-suggestions');
            if (container) {
                container.style.display = 'none';
            }
        }, 150);
    },
    
    initQuantityControls: function() {
        // Quantity controls
        document.addEventListener('change', (e) => {
            if (e.target.matches('.quantity-input')) {
                this.handleQuantityChange(e.target);
            }
        });
    },
    
    initModalControls: function() {
        // Modal controls
        document.addEventListener('click', (e) => {
            if (e.target.matches('.modal-trigger')) {
                e.preventDefault();
                this.openModal(e.target.getAttribute('data-modal'));
            }
            
            if (e.target.matches('.modal-close') || e.target.matches('.modal-backdrop')) {
                this.closeModal();
            }
        });
    },
    
    initProductGallery: function() {
        // Product image gallery
        document.addEventListener('click', (e) => {
            if (e.target.matches('.product-thumbnail')) {
                this.changeProductImage(e.target);
            }
        });
        
        // Filter and sort
        const filterForm = document.querySelector('.filter-form');
        const sortSelect = document.querySelector('.sort-select');
        
        if (filterForm) {
            filterForm.addEventListener('change', this.handleFilterChange.bind(this));
        }
        
        if (sortSelect) {
            sortSelect.addEventListener('change', this.handleSortChange.bind(this));
        }
        
        // Form validation
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.validate-form')) {
                if (!this.validateForm(e.target)) {
                    e.preventDefault();
                }
            }
        });
    },
    
    // Search functionality
    handleSearch: function(e) {
        e.preventDefault();
        const query = e.target.querySelector('.search-input').value.trim();
        if (query) {
            window.location.href = `/search.php?q=${encodeURIComponent(query)}`;
        }
    },
    
    handleSearchSuggestions: function(e) {
        const query = e.target.value.trim();
        if (query.length < 2) {
            this.hideSearchSuggestions();
            return;
        }
        
        this.fetchAPI(`/api/search-suggestions.php?q=${encodeURIComponent(query)}`)
            .then(data => {
                this.showSearchSuggestions(data.suggestions);
            })
            .catch(err => {
                console.error('Search suggestions error:', err);
            });
    },
    
    showSearchSuggestions: function(suggestions) {
        let suggestionBox = document.querySelector('.search-suggestions');
        if (!suggestionBox) {
            suggestionBox = document.createElement('div');
            suggestionBox.className = 'search-suggestions';
            document.querySelector('.search-container').appendChild(suggestionBox);
        }
        
        suggestionBox.innerHTML = suggestions.map(item => 
            `<div class="suggestion-item" data-url="${item.url}">${item.name}</div>`
        ).join('');
        
        suggestionBox.style.display = 'block';
        
        // Add click handlers
        suggestionBox.addEventListener('click', (e) => {
            if (e.target.matches('.suggestion-item')) {
                window.location.href = e.target.getAttribute('data-url');
            }
        });
    },
    
    hideSearchSuggestions: function() {
        const suggestionBox = document.querySelector('.search-suggestions');
        if (suggestionBox) {
            suggestionBox.style.display = 'none';
        }
    },
    
    // Cart functionality
    addToCart: function(button) {
        const productId = button.getAttribute('data-product-id');
        const quantity = parseInt(button.getAttribute('data-quantity') || '1');
        
        button.disabled = true;
        button.innerHTML = 'Adding...';
        
        this.fetchAPI('/api/cart.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'add',
                product_id: productId,
                quantity: quantity
            })
        })
        .then(data => {
            this.showNotification('Product added to cart!', 'success');
            this.updateCartDisplay();
            button.innerHTML = 'Added!';
            
            setTimeout(() => {
                button.disabled = false;
                button.innerHTML = 'Add to Cart';
            }, 2000);
        })
        .catch(err => {
            this.showNotification('Error adding to cart', 'error');
            button.disabled = false;
            button.innerHTML = 'Add to Cart';
        });
    },
    
    removeFromCart: function(button) {
        const productId = button.getAttribute('data-product-id');
        
        if (confirm('Remove this item from cart?')) {
            this.fetchAPI('/api/cart.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'remove',
                    product_id: productId
                })
            })
            .then(data => {
                this.showNotification('Item removed from cart', 'success');
                this.updateCartDisplay();
                
                // Remove the cart item element
                const cartItem = button.closest('.cart-item');
                if (cartItem) {
                    cartItem.remove();
                }
            })
            .catch(err => {
                this.showNotification('Error removing item', 'error');
            });
        }
    },
    
    updateCartQuantity: function(input) {
        const productId = input.getAttribute('data-product-id');
        const quantity = parseInt(input.value);
        
        if (quantity < 1) {
            input.value = 1;
            return;
        }
        
        clearTimeout(this.cartUpdateTimeout);
        this.cartUpdateTimeout = setTimeout(() => {
            this.fetchAPI('/api/cart.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'update',
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(data => {
                this.updateCartDisplay();
                this.updateCartTotals();
            })
            .catch(err => {
                this.showNotification('Error updating quantity', 'error');
            });
        }, this.config.cartUpdateDelay);
    },
    
    updateCartDisplay: function() {
        this.fetchAPI('/api/cart.php?action=count')
            .then(data => {
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.count;
                    cartCount.style.display = data.count > 0 ? 'flex' : 'none';
                }
            })
            .catch(err => {
                console.error('Cart count error:', err);
            });
    },
    
    updateCartTotals: function() {
        this.fetchAPI('/api/cart.php?action=totals')
            .then(data => {
                const totalElement = document.querySelector('.cart-total');
                if (totalElement) {
                    totalElement.textContent = data.total;
                }
            })
            .catch(err => {
                console.error('Cart totals error:', err);
            });
    },
    
    // Wishlist functionality
    addToWishlist: function(button) {
        const productId = button.getAttribute('data-product-id');
        
        this.fetchAPI('/api/wishlist.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'add',
                product_id: productId
            })
        })
        .then(data => {
            button.innerHTML = '❤️ Added to Wishlist';
            button.classList.add('in-wishlist');
            this.showNotification('Added to wishlist!', 'success');
        })
        .catch(err => {
            this.showNotification('Error adding to wishlist', 'error');
        });
    },
    
    // Modal functionality
    initModals: function() {
        // Close modal when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.matches('.modal')) {
                this.closeModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    },
    
    openModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    },
    
    closeModal: function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    },
    
    // Product image gallery
    changeProductImage: function(thumbnail) {
        const mainImage = document.querySelector('.product-main-image');
        const newSrc = thumbnail.getAttribute('data-full-image');
        
        if (mainImage && newSrc) {
            mainImage.src = newSrc;
            
            // Update active thumbnail
            document.querySelectorAll('.product-thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }
    },
    
    // Filter and sort
    handleFilterChange: function(e) {
        const form = e.target.closest('.filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        // Update URL and reload
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.location.href = newUrl;
    },
    
    handleSortChange: function(e) {
        const sortValue = e.target.value;
        const url = new URL(window.location);
        url.searchParams.set('sort', sortValue);
        window.location.href = url.toString();
    },
    
    // Form validation
    validateForm: function(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            this.clearFieldError(field);
            
            if (!field.value.trim()) {
                this.showFieldError(field, 'This field is required');
                isValid = false;
            } else if (field.type === 'email' && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'Please enter a valid email address');
                isValid = false;
            } else if (field.type === 'password' && field.value.length < 6) {
                this.showFieldError(field, 'Password must be at least 6 characters');
                isValid = false;
            }
        });
        
        // Check password confirmation
        const password = form.querySelector('input[name="password"]');
        const confirmPassword = form.querySelector('input[name="confirm_password"]');
        
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            this.showFieldError(confirmPassword, 'Passwords do not match');
            isValid = false;
        }
        
        return isValid;
    },
    
    showFieldError: function(field, message) {
        field.classList.add('error');
        
        let errorDiv = field.parentNode.querySelector('.form-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'form-error';
            field.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    },
    
    clearFieldError: function(field) {
        field.classList.remove('error');
        const errorDiv = field.parentNode.querySelector('.form-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    },
    
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    // Notifications
    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        // Add to page
        let container = document.querySelector('.notification-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        
        container.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
    },
    
    // Tooltips
    initTooltips: function() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip.bind(this));
            element.addEventListener('mouseleave', this.hideTooltip.bind(this));
        });
    },
    
    showTooltip: function(e) {
        const element = e.target;
        const text = element.getAttribute('data-tooltip');
        
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
        
        element._tooltip = tooltip;
    },
    
    hideTooltip: function(e) {
        const element = e.target;
        if (element._tooltip) {
            element._tooltip.remove();
            delete element._tooltip;
        }
    },
    
    // API helper
    fetchAPI: function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const mergedOptions = { ...defaultOptions, ...options };
        
        return fetch(url, mergedOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    },
    
    // Utility functions
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
    
    formatPrice: function(price) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(price);
    },
    
    formatDate: function(date) {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }).format(new Date(date));
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    ECommerce.init();
});

// Global search function for the search button
function searchFunction() {
    const searchInput = document.getElementById('search');
    if (searchInput && searchInput.value.trim()) {
        window.location.href = `/search.php?q=${encodeURIComponent(searchInput.value.trim())}`;
    }
}

// Mobile menu functionality
FezaMarket.initMobileMenu = function() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');
    const mobileNav = document.querySelector('.mobile-nav');
    
    if (!mobileMenuToggle) return;
    
    // Create mobile nav overlay if it doesn't exist
    if (!mobileNavOverlay) {
        const overlay = document.createElement('div');
        overlay.className = 'mobile-nav-overlay';
        overlay.innerHTML = `
            <div class="mobile-nav">
                <div class="mobile-nav-header">
                    <h3>Menu</h3>
                    <button class="mobile-nav-close">&times;</button>
                </div>
                <div class="mobile-nav-content">
                    <a href="/" class="mobile-nav-link">Home</a>
                    <a href="/categories.php" class="mobile-nav-link">Categories</a>
                    <a href="/deals.php" class="mobile-nav-link">Deals</a>
                    <a href="/sell.php" class="mobile-nav-link">Sell</a>
                    <a href="/help.php" class="mobile-nav-link">Help</a>
                    ${document.querySelector('.user-menu') ? 
                        '<a href="/account.php" class="mobile-nav-link">My Account</a>' : 
                        '<a href="/login.php" class="mobile-nav-link">Sign In</a>'
                    }
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    
    // Toggle mobile menu
    mobileMenuToggle.addEventListener('click', function() {
        document.body.classList.add('mobile-nav-active');
        document.querySelector('.mobile-nav-overlay').style.display = 'block';
    });
    
    // Close mobile menu
    document.addEventListener('click', function(e) {
        if (e.target.matches('.mobile-nav-close') || e.target.matches('.mobile-nav-overlay')) {
            document.body.classList.remove('mobile-nav-active');
            document.querySelector('.mobile-nav-overlay').style.display = 'none';
        }
    });
};

// Additional CSS for notifications and tooltips
const style = document.createElement('style');
style.textContent = `
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
    
    .notification {
        background: white;
        border-radius: 4px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    }
    
    .notification-success {
        border-left: 4px solid #28a745;
    }
    
    .notification-error {
        border-left: 4px solid #dc3545;
    }
    
    .notification-info {
        border-left: 4px solid #17a2b8;
    }
    
    .notification-close {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: #999;
    }
    
    .tooltip {
        position: absolute;
        background: #333;
        color: white;
        padding: 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
        z-index: 1000;
        white-space: nowrap;
    }
    
    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 4px 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 1000;
        display: none;
    }
    
    .suggestion-item {
        padding: 0.75rem;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }
    
    .suggestion-item:hover {
        background: #f8f9fa;
    }
    
    .suggestion-item:last-child {
        border-bottom: none;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .form-control.error {
        border-color: #dc3545;
    }
    
    .product-thumbnail.active {
        border: 2px solid #007bff;
    }
    
    /* Mobile Navigation Styles */
    .mobile-nav-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: none;
    }
    
    .mobile-nav {
        position: fixed;
        top: 0;
        right: 0;
        width: 280px;
        height: 100%;
        background: white;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
        transform: translateX(100%);
        transition: transform 0.3s ease;
    }
    
    .mobile-nav-active .mobile-nav {
        transform: translateX(0);
    }
    
    .mobile-nav-header {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .mobile-nav-header h3 {
        margin: 0;
        font-size: 1.25rem;
    }
    
    .mobile-nav-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .mobile-nav-content {
        padding: 1rem 0;
    }
    
    .mobile-nav-link {
        display: block;
        padding: 0.75rem 1rem;
        color: #333;
        text-decoration: none;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s ease;
    }
    
    .mobile-nav-link:hover {
        background-color: #f8f9fa;
        color: #0654ba;
    }
    
    .mobile-nav-link:last-child {
        border-bottom: none;
    }
`;
document.head.appendChild(style);