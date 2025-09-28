<?php
/**
 * Demo Shopping Flow - Shows cart, wishlist, and checkout working
 * Bypasses authentication for demonstration purposes
 */

require_once __DIR__ . '/includes/init.php';

// Force login simulation for demo
$_SESSION['user_id'] = 2; // Customer user ID
$_SESSION['user_role'] = 'customer';
$_SESSION['logged_in'] = true;
$_SESSION['user_email'] = 'customer@example.com';

$userId = 2;

echo "<!DOCTYPE html>";
echo "<html><head><title>Shopping Flow Demo</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
    .demo-section { border: 2px solid #ddd; margin: 20px 0; padding: 15px; border-radius: 8px; }
    .demo-section h2 { color: #333; margin-top: 0; }
    .product-card { border: 1px solid #eee; padding: 10px; margin: 10px; display: inline-block; width: 200px; }
    .btn { background: #0066cc; color: white; padding: 8px 16px; border: none; cursor: pointer; margin: 5px; }
    .btn:hover { background: #0052a3; }
    .btn-wishlist { background: #ff6b6b; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .cart-items, .wishlist-items { margin: 15px 0; }
</style>";
echo "<script>
function addToCart(productId) {
    fetch('/api/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'add', product_id: productId, quantity: 1})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Added to cart! Total items: ' + data.count);
            location.reload();
        } else {
            alert('Error: ' + (data.message || data.error));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding to cart');
    });
}

function addToWishlist(productId) {
    fetch('/api/wishlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'add', product_id: productId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Added to wishlist!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || data.error));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding to wishlist');
    });
}

function removeFromCart(productId) {
    fetch('/api/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'remove', product_id: productId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Removed from cart!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || data.error));
        }
    });
}
</script>";
echo "</head><body>";

echo "<h1>🛒 Shopping Flow Demo</h1>";
echo "<p><strong>Logged in as:</strong> customer@example.com (User ID: $userId)</p>";

// Show available products
try {
    $product = new Product();
    $products = $product->findAll();
    
    echo "<div class='demo-section'>";
    echo "<h2>📦 Available Products</h2>";
    
    foreach ($products as $prod) {
        echo "<div class='product-card'>";
        echo "<h3>{$prod['name']}</h3>";
        echo "<p>Price: $" . number_format($prod['price'], 2) . "</p>";
        echo "<p>Stock: {$prod['stock_quantity']}</p>";
        echo "<button class='btn' onclick='addToCart({$prod['id']})'>Add to Cart</button>";
        echo "<button class='btn btn-wishlist' onclick='addToWishlist({$prod['id']})'>♡ Wishlist</button>";
        echo "</div>";
    }
    echo "</div>";

    // Show current cart
    $cart = new Cart();
    $cartItems = $cart->getCartItems($userId);
    
    echo "<div class='demo-section'>";
    echo "<h2>🛒 Current Cart</h2>";
    if (empty($cartItems)) {
        echo "<p>Your cart is empty.</p>";
    } else {
        $total = 0;
        echo "<div class='cart-items'>";
        foreach ($cartItems as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $total += $itemTotal;
            echo "<div style='border-bottom: 1px solid #eee; padding: 10px 0;'>";
            echo "<strong>{$item['name']}</strong><br>";
            echo "Quantity: {$item['quantity']} × $" . number_format($item['price'], 2) . " = $" . number_format($itemTotal, 2);
            echo "<button class='btn' onclick='removeFromCart({$item['product_id']})'>Remove</button>";
            echo "</div>";
        }
        echo "</div>";
        echo "<p><strong>Total: $" . number_format($total, 2) . "</strong></p>";
        echo "<a href='checkout.php' class='btn' style='background: #28a745; text-decoration: none; display: inline-block;'>Proceed to Checkout</a>";
    }
    echo "</div>";

    // Show current wishlist
    $wishlist = new Wishlist();
    $wishlistItems = $wishlist->getUserWishlist($userId);
    
    echo "<div class='demo-section'>";
    echo "<h2>💝 Current Wishlist</h2>";
    if (empty($wishlistItems)) {
        echo "<p>Your wishlist is empty.</p>";
    } else {
        echo "<div class='wishlist-items'>";
        foreach ($wishlistItems as $item) {
            echo "<div style='border-bottom: 1px solid #eee; padding: 10px 0;'>";
            echo "<strong>{$item['name']}</strong><br>";
            echo "Price: $" . number_format($item['price'], 2);
            echo "<button class='btn' onclick='addToCart({$item['product_id']})'>Move to Cart</button>";
            echo "</div>";
        }
        echo "</div>";
    }
    echo "</div>";

    // Show recent orders - simplified version
    echo "<div class='demo-section'>";
    echo "<h2>📋 Recent Orders</h2>";
    try {
        $order = new Order();
        $orders = $order->getUserOrders($userId, 5);
        
        if (empty($orders)) {
            echo "<p>No orders yet.</p>";
        } else {
            foreach ($orders as $ord) {
                echo "<div style='border-bottom: 1px solid #eee; padding: 10px 0;'>";
                echo "<strong>Order #{$ord['order_number']}</strong><br>";
                echo "Status: {$ord['status']} | Total: $" . number_format($ord['total'], 2);
                echo "<br>Date: " . date('M j, Y g:i A', strtotime($ord['created_at']));
                echo "</div>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>Orders section error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>This is expected if no orders exist yet.</p>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<div class='demo-section'>";
echo "<h2>🔗 Navigation</h2>";
echo "<a href='/' class='btn'>← Homepage</a> ";
echo "<a href='cart.php' class='btn'>View Cart</a> ";
echo "<a href='wishlist.php' class='btn'>View Wishlist</a> ";
echo "<a href='checkout.php' class='btn'>Checkout</a>";
echo "</div>";

echo "</body></html>";
?>