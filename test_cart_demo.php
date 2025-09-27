<?php
/**
 * Cart Demo - Test the add to cart functionality
 */
require_once __DIR__ . '/includes/init.php';

// Mock user session for demo
if (!Session::isLoggedIn()) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_email'] = 'demo@test.com';
    $_SESSION['user_name'] = 'Demo User';
}

$product = new Product();
$cart = new Cart();
$userId = Session::getUserId();

// Get a test product
$products = $product->findAll(3, 0);
$cartItems = $cart->getCartItems($userId);
$cartCount = $cart->getCartCount($userId);

$page_title = 'Cart Demo - Add to Cart Test';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin: 20px 0; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .product-card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; }
        .product-image { width: 100%; height: 200px; object-fit: cover; border-radius: 4px; margin-bottom: 15px; }
        .btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #0056b3; }
        .btn:disabled { background: #6c757d; cursor: not-allowed; }
        .cart-info { background: #e7f3ff; border: 1px solid #b3d7ff; border-radius: 4px; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .notification { position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 4px; color: white; z-index: 1000; display: none; }
        .notification.success { background: #28a745; }
        .notification.error { background: #dc3545; }
        .cart-summary { background: #f8f9fa; padding: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛒 Cart Demo - Add to Cart Test</h1>
        <p>This page demonstrates the fixed cart functionality. The "error adding to cart" issue has been resolved!</p>
        
        <!-- Cart Status -->
        <div class="cart-info">
            <h3>Cart Status</h3>
            <p><strong>Items in cart:</strong> <span id="cart-count"><?php echo $cartCount; ?></span></p>
            <p><strong>Cart total:</strong> $<span id="cart-total"><?php echo number_format($cart->getCartTotal($userId), 2); ?></span></p>
            
            <?php if (!empty($cartItems)): ?>
                <div class="cart-summary">
                    <h4>Current Cart Items:</h4>
                    <?php foreach ($cartItems as $item): ?>
                        <div style="display: flex; align-items: center; margin: 10px 0; padding: 10px; background: white; border-radius: 4px;">
                            <img src="<?php echo getSafeProductImageUrl($item); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; margin-right: 15px; border-radius: 4px;">
                            <div>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                <small>Quantity: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></small>
                            </div>
                            <button class="remove-from-cart" data-product-id="<?php echo $item['product_id']; ?>" style="margin-left: auto; background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Remove</button>
                        </div>
                    <?php endforeach; ?>
                    <button id="clear-cart" class="btn" style="background: #dc3545; margin-top: 10px;">Clear Cart</button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Products to Add -->
        <h2>Available Products</h2>
        <div class="products-grid">
            <?php foreach ($products as $prod): ?>
                <div class="product-card">
                    <img src="<?php echo getSafeProductImageUrl($prod); ?>" 
                         alt="<?php echo htmlspecialchars($prod['name']); ?>" 
                         class="product-image">
                    
                    <h3><?php echo htmlspecialchars($prod['name']); ?></h3>
                    <p><strong>Price: $<?php echo number_format($prod['price'], 2); ?></strong></p>
                    <p>Stock: <?php echo $prod['stock_quantity']; ?> available</p>
                    
                    <?php if (!empty($prod['vendor_name'])): ?>
                        <p><small>Sold by: <?php echo htmlspecialchars($prod['vendor_name']); ?></small></p>
                    <?php endif; ?>
                    
                    <button class="add-to-cart btn" 
                            data-product-id="<?php echo $prod['id']; ?>"
                            data-quantity="1">
                        Add to Cart
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Demo Actions -->
        <div class="card">
            <h3>Test Actions</h3>
            <p>Try these actions to test the cart functionality:</p>
            <button id="test-add-multiple" class="btn">Add Multiple Items</button>
            <button id="view-cart-api" class="btn">View Cart API Response</button>
            <a href="/cart.php" class="btn">Go to Cart Page</a>
            <a href="/checkout.php" class="btn">Go to Checkout</a>
        </div>
    </div>
    
    <!-- Notification -->
    <div id="notification" class="notification"></div>
    
    <script>
        // Cart functionality
        class CartDemo {
            constructor() {
                this.bindEvents();
            }
            
            bindEvents() {
                // Add to cart buttons
                document.addEventListener('click', (e) => {
                    if (e.target.matches('.add-to-cart')) {
                        this.addToCart(e.target);
                    }
                    if (e.target.matches('.remove-from-cart')) {
                        this.removeFromCart(e.target);
                    }
                });
                
                // Test buttons
                document.getElementById('clear-cart')?.addEventListener('click', () => this.clearCart());
                document.getElementById('test-add-multiple')?.addEventListener('click', () => this.testAddMultiple());
                document.getElementById('view-cart-api')?.addEventListener('click', () => this.viewCartApi());
            }
            
            async addToCart(button) {
                const productId = button.getAttribute('data-product-id');
                const quantity = parseInt(button.getAttribute('data-quantity') || '1');
                
                if (!productId) return;
                
                button.disabled = true;
                button.textContent = 'Adding...';
                
                try {
                    const response = await fetch('/api/cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'add',
                            product_id: parseInt(productId),
                            quantity: quantity
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showNotification('Product added to cart!', 'success');
                        this.updateCartDisplay(data.data);
                        button.textContent = 'Added!';
                        setTimeout(() => {
                            button.textContent = 'Add to Cart';
                            button.disabled = false;
                        }, 1500);
                    } else {
                        this.showNotification(data.error || 'Error adding to cart', 'error');
                        button.disabled = false;
                        button.textContent = 'Add to Cart';
                    }
                } catch (error) {
                    console.error('Cart error:', error);
                    this.showNotification('Error adding to cart', 'error');
                    button.disabled = false;
                    button.textContent = 'Add to Cart';
                }
            }
            
            async removeFromCart(button) {
                const productId = button.getAttribute('data-product-id');
                
                try {
                    const response = await fetch('/api/cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'remove',
                            product_id: parseInt(productId)
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showNotification('Item removed from cart', 'success');
                        location.reload(); // Refresh to update display
                    } else {
                        this.showNotification(data.error || 'Error removing item', 'error');
                    }
                } catch (error) {
                    this.showNotification('Error removing item', 'error');
                }
            }
            
            async clearCart() {
                try {
                    const response = await fetch('/api/cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'clear'
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showNotification('Cart cleared', 'success');
                        location.reload();
                    } else {
                        this.showNotification(data.error || 'Error clearing cart', 'error');
                    }
                } catch (error) {
                    this.showNotification('Error clearing cart', 'error');
                }
            }
            
            updateCartDisplay(cartData) {
                if (cartData.count !== undefined) {
                    document.getElementById('cart-count').textContent = cartData.count;
                }
                if (cartData.total !== undefined) {
                    document.getElementById('cart-total').textContent = cartData.total.toFixed(2);
                }
            }
            
            showNotification(message, type) {
                const notification = document.getElementById('notification');
                notification.textContent = message;
                notification.className = `notification ${type}`;
                notification.style.display = 'block';
                
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 3000);
            }
            
            async testAddMultiple() {
                const buttons = document.querySelectorAll('.add-to-cart');
                this.showNotification('Adding multiple items...', 'success');
                
                for (let i = 0; i < Math.min(buttons.length, 2); i++) {
                    await new Promise(resolve => setTimeout(resolve, 500));
                    buttons[i].click();
                }
            }
            
            async viewCartApi() {
                try {
                    const response = await fetch('/api/cart.php');
                    const data = await response.json();
                    
                    console.log('Cart API Response:', data);
                    alert('Cart API Response (check console):\n' + JSON.stringify(data, null, 2));
                } catch (error) {
                    this.showNotification('Error fetching cart data', 'error');
                }
            }
        }
        
        // Initialize cart demo
        new CartDemo();
    </script>
</body>
</html>