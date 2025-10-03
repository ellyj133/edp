<?php
/**
 * E-commerce Code Validation
 * Validates code structure and logic without database
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models.php';
require_once __DIR__ . '/../includes/models_extended.php';

echo "=== E-commerce Code Validation ===\n\n";

$errors = [];
$passes = [];

function validatePass($test) {
    global $passes;
    $passes[] = $test;
    echo "✓ $test\n";
}

function validateFail($test, $message = '') {
    global $errors;
    $errors[] = $test . ($message ? ": $message" : '');
    echo "✗ $test" . ($message ? ": $message" : '') . "\n";
}

// 1. Check Cart Model has required methods
echo "Validating Cart Model...\n";
$cartMethods = get_class_methods('Cart');
if (in_array('addItem', $cartMethods)) {
    validatePass("Cart has addItem method");
} else {
    validateFail("Cart missing addItem method");
}

if (in_array('getCartItems', $cartMethods)) {
    validatePass("Cart has getCartItems method");
} else {
    validateFail("Cart missing getCartItems method");
}

if (in_array('clearCart', $cartMethods)) {
    validatePass("Cart has clearCart method");
} else {
    validateFail("Cart missing clearCart method");
}

// 2. Check Wishlist Model
echo "\nValidating Wishlist Model...\n";
$wishlistMethods = get_class_methods('Wishlist');
if (in_array('addToWishlist', $wishlistMethods)) {
    validatePass("Wishlist has addToWishlist method");
} else {
    validateFail("Wishlist missing addToWishlist method");
}

if (in_array('removeFromWishlist', $wishlistMethods)) {
    validatePass("Wishlist has removeFromWishlist method");
} else {
    validateFail("Wishlist missing removeFromWishlist method");
}

if (in_array('isInWishlist', $wishlistMethods)) {
    validatePass("Wishlist has isInWishlist method");
} else {
    validateFail("Wishlist missing isInWishlist method");
}

// Check table name
$wishlist = new Wishlist();
$reflection = new ReflectionClass($wishlist);
$tableProperty = $reflection->getProperty('table');
$tableProperty->setAccessible(true);
$tableName = $tableProperty->getValue($wishlist);
if ($tableName === 'wishlists') {
    validatePass("Wishlist uses correct table name: wishlists");
} else {
    validateFail("Wishlist uses incorrect table name", "Expected 'wishlists', got '$tableName'");
}

// 3. Check Watchlist Model
echo "\nValidating Watchlist Model...\n";
$watchlistMethods = get_class_methods('Watchlist');
if (in_array('addToWatchlist', $watchlistMethods)) {
    validatePass("Watchlist has addToWatchlist method");
} else {
    validateFail("Watchlist missing addToWatchlist method");
}

if (in_array('removeFromWatchlist', $watchlistMethods)) {
    validatePass("Watchlist has removeFromWatchlist method");
} else {
    validateFail("Watchlist missing removeFromWatchlist method");
}

if (in_array('isInWatchlist', $watchlistMethods)) {
    validatePass("Watchlist has isInWatchlist method");
} else {
    validateFail("Watchlist missing isInWatchlist method");
}

// Check table name
$watchlist = new Watchlist();
$reflection = new ReflectionClass($watchlist);
$tableProperty = $reflection->getProperty('table');
$tableProperty->setAccessible(true);
$tableName = $tableProperty->getValue($watchlist);
if ($tableName === 'watchlist') {
    validatePass("Watchlist uses correct table name: watchlist");
} else {
    validateFail("Watchlist uses incorrect table name", "Expected 'watchlist', got '$tableName'");
}

// 4. Check Order Model
echo "\nValidating Order Model...\n";
$orderMethods = get_class_methods('Order');
if (in_array('createOrder', $orderMethods)) {
    validatePass("Order has createOrder method");
} else {
    validateFail("Order missing createOrder method");
}

if (in_array('getOrderWithItems', $orderMethods)) {
    validatePass("Order has getOrderWithItems method");
} else {
    validateFail("Order missing getOrderWithItems method");
}

// 5. Check Product Model has stock methods
echo "\nValidating Product Model...\n";
$productMethods = get_class_methods('Product');
if (in_array('decreaseStock', $productMethods)) {
    validatePass("Product has decreaseStock method");
} else {
    validateFail("Product missing decreaseStock method");
}

if (in_array('updateStock', $productMethods)) {
    validatePass("Product has updateStock method");
} else {
    validateFail("Product missing updateStock method");
}

// 6. Validate API files exist
echo "\nValidating API Endpoints...\n";
$apiFiles = [
    'api/cart.php',
    'api/wishlist.php',
    'api/watchlist.php'
];

foreach ($apiFiles as $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        validatePass("API file exists: $file");
        
        // Check for required functions in the file
        $content = file_get_contents($path);
        if (strpos($content, 'errorResponse') !== false || strpos($content, 'error_log') !== false) {
            validatePass("API has error handling: $file");
        } else {
            validateFail("API missing error handling", $file);
        }
    } else {
        validateFail("API file missing", $file);
    }
}

// 7. Validate checkout.php exists and has validation
echo "\nValidating Checkout Flow...\n";
$checkoutPath = __DIR__ . '/../checkout.php';
if (file_exists($checkoutPath)) {
    validatePass("Checkout file exists");
    
    $content = file_get_contents($checkoutPath);
    
    if (strpos($content, 'empty($cartItems)') !== false) {
        validatePass("Checkout validates empty cart");
    } else {
        validateFail("Checkout missing empty cart validation");
    }
    
    if (strpos($content, "status'] !== 'active'") !== false || strpos($content, "status'] === 'active'") !== false) {
        validatePass("Checkout validates product status");
    } else {
        validateFail("Checkout missing product status validation");
    }
    
    if (strpos($content, 'stock_quantity') !== false) {
        validatePass("Checkout validates stock quantity");
    } else {
        validateFail("Checkout missing stock validation");
    }
} else {
    validateFail("Checkout file missing");
}

// 8. Check SQL queries use correct column names
echo "\nValidating SQL Column Names...\n";
$modelsExtendedContent = file_get_contents(__DIR__ . '/../includes/models_extended.php');

// Check for correct order_items columns
if (strpos($modelsExtendedContent, "order_id, product_id, vendor_id, qty, price, subtotal") !== false) {
    validatePass("Order model uses correct order_items columns");
} else {
    validateFail("Order model may use incorrect order_items columns");
}

// Check Cart model includes price
$modelsContent = file_get_contents(__DIR__ . '/../includes/models.php');
if (strpos($modelsContent, "user_id, product_id, quantity, price") !== false) {
    validatePass("Cart model includes price column in INSERT");
} else {
    validateFail("Cart model missing price column in INSERT");
}

echo "\n=== Validation Results ===\n";
echo "Passed: " . count($passes) . "\n";
echo "Failed: " . count($errors) . "\n";

if (empty($errors)) {
    echo "\n✓ All validations passed!\n";
    exit(0);
} else {
    echo "\n✗ Some validations failed:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}
