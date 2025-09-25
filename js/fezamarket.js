/**
 * FezaMarket E-Commerce Platform JavaScript
 * Frontend functionality and interactions
 */

// Application object
var FezaMarket = {
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
        
        if (!searchInput) return;
        
        // Create suggestions container if it doesn't exist
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
            searchInput.closest('.search-form-container').appendChild(container);
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
            'Nintendo Switch',
            'Apple Watch',
            'AirPods Pro',
            'Gaming Laptop',
            'Wireless Headphones'
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
    
    // Cart functionality
    addToCart: function(button) {
        const productId = button.getAttribute('data-product-id');
        const quantity = 1; // Default quantity
        
        if (!productId) return;
        
        button.disabled = true;
        button.textContent = 'Adding...';
        
        this.fetchAPI('/api/cart/add.php', {
            method: 'POST',
            body: JSON.stringify({
                product_id: parseInt(productId),
                quantity: quantity
            })
        })
        .then(data => {
            if (data.success) {
                this.showNotification('Product added to cart!', 'success');
                this.updateCartDisplay();
                button.textContent = 'Added!';
                setTimeout(() => {
                    button.textContent = 'Add to Cart';
                    button.disabled = false;
                }, 1500);
            } else {
                this.showNotification(data.message || 'Error adding to cart', 'error');
                button.disabled = false;
                button.textContent = 'Add to Cart';
            }
        })
        .catch(error => {
            console.error('Cart error:', error);
            this.showNotification('Error adding to cart', 'error');
            button.disabled = false;
            button.textContent = 'Add to Cart';
        });
    },
    
    removeFromCart: function(button) {
        const productId = button.getAttribute('data-product-id');
        
        if (!productId || !confirm('Remove this item from cart?')) return;
        
        this.fetchAPI('/api/cart/remove.php', {
            method: 'POST',
            body: JSON.stringify({
                product_id: parseInt(productId)
            })
        })
        .then(data => {
            if (data.success) {
                this.showNotification('Item removed from cart', 'success');
                this.updateCartDisplay();
                // Remove the row from cart page if exists
                const row = button.closest('tr');
                if (row) row.remove();
            } else {
                this.showNotification(data.message || 'Error removing item', 'error');
            }
        })
        .catch(error => {
            console.error('Cart error:', error);
            this.showNotification('Error removing item', 'error');
        });
    },
    
    updateCartQuantity: function(input) {
        const productId = input.getAttribute('data-product-id');
        const quantity = parseInt(input.value);
        
        if (!productId || quantity < 1) return;
        
        this.fetchAPI('/api/cart/update.php', {
            method: 'POST',
            body: JSON.stringify({
                product_id: parseInt(productId),
                quantity: quantity
            })
        })
        .then(data => {
            if (data.success) {
                this.updateCartDisplay();
                // Update total if on cart page
                const totalElement = document.querySelector('.cart-total');
                if (totalElement && data.cart_total) {
                    totalElement.textContent = '$' + data.cart_total;
                }
            }
        })
        .catch(error => {
            console.error('Cart update error:', error);
        });
    },
    
    addToWishlist: function(button) {
        const productId = button.getAttribute('data-product-id');
        
        if (!productId) return;
        
        button.disabled = true;
        
        this.fetchAPI('/api/wishlist/add.php', {
            method: 'POST',
            body: JSON.stringify({
                product_id: parseInt(productId)
            })
        })
        .then(data => {
            if (data.success) {
                this.showNotification('Added to wishlist!', 'success');
                button.innerHTML = '💗';
                button.title = 'Added to wishlist';
            } else {
                this.showNotification(data.message || 'Error adding to wishlist', 'error');
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Wishlist error:', error);
            this.showNotification('Error adding to wishlist', 'error');
            button.disabled = false;
        });
    },
    
    updateCartDisplay: function() {
        this.fetchAPI('/api/cart/count.php')
            .then(data => {
                const cartCountElements = document.querySelectorAll('.cart-count');
                cartCountElements.forEach(element => {
                    if (data.count > 0) {
                        element.textContent = data.count;
                        element.style.display = 'flex';
                    } else {
                        element.style.display = 'none';
                    }
                });
            })
            .catch(error => {
                console.error('Cart count error:', error);
            });
    },
    
    // Utility functions
    fetchAPI: function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        return fetch(url, {...defaultOptions, ...options})
            .then(response => response.json());
    },
    
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
    
    showNotification: function(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#16a34a' : type === 'error' ? '#dc2626' : '#0654ba'};
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    },
    
    initModals: function() {
        // Modal functionality can be added here
    },
    
    initTooltips: function() {
        // Tooltip functionality can be added here
    }
};

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    FezaMarket.init();
});

// Backward compatibility
const ECommerce = FezaMarket;