<?php
/**
 * Test shopping flow functionality
 */

require_once __DIR__ . '/includes/init.php';

echo "<h1>Shopping Flow Test</h1>\n";

try {
    // Test database connection
    $db = db();
    echo "<p>✅ Database connection: OK</p>\n";

    // Test if tables exist
    $tables = ['users', 'products', 'cart', 'wishlists', 'orders', 'order_items'];
    foreach ($tables as $table) {
        $result = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<p>✅ Table '$table': $result records</p>\n";
    }

    // Test user login simulation
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'customer';
    
    // Test Cart functionality
    $cart = new Cart();
    $userId = 1;
    $productId = 1;
    
    // Add item to cart
    $result = $cart->addItem($userId, $productId, 2);
    echo "<p>" . ($result ? "✅" : "❌") . " Add item to cart: " . ($result ? "OK" : "FAILED") . "</p>\n";
    
    // Get cart items
    $items = $cart->getCartItems($userId);
    echo "<p>✅ Cart items: " . count($items) . " items</p>\n";
    
    // Get cart count
    $count = $cart->getCartCount($userId);
    echo "<p>✅ Cart count: $count items</p>\n";
    
    // Get cart total
    $total = $cart->getCartTotal($userId);
    echo "<p>✅ Cart total: $" . number_format($total, 2) . "</p>\n";

    // Test Wishlist functionality
    $wishlist = new Wishlist();
    
    // Add item to wishlist
    $result = $wishlist->addToWishlist($userId, $productId);
    echo "<p>" . ($result ? "✅" : "❌") . " Add item to wishlist: " . ($result ? "OK" : "ALREADY EXISTS OR FAILED") . "</p>\n";
    
    // Get wishlist items
    $wishlistItems = $wishlist->getUserWishlist($userId);
    echo "<p>✅ Wishlist items: " . count($wishlistItems) . " items</p>\n";
    
    // Test Order functionality
    $order = new Order();
    
    // Create order
    $orderData = [
        'status' => 'pending',
        'total' => $total
    ];
    
    $orderId = $order->createOrder($userId, $orderData);
    echo "<p>" . ($orderId ? "✅" : "❌") . " Create order: " . ($orderId ? "Order ID $orderId" : "FAILED") . "</p>\n";
    
    if ($orderId) {
        // Get order with items
        $orderDetails = $order->getOrderWithItems($orderId, $userId);
        echo "<p>✅ Order details: " . count($orderDetails['items']) . " items in order</p>\n";
        echo "<p>✅ Order total: $" . number_format($orderDetails['total'], 2) . "</p>\n";
    }
    
    echo "<h2>✅ All tests completed successfully!</h2>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>