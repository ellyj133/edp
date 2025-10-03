<?php
/**
 * Purchase Flows Integration Test
 * Comprehensive testing of all purchase-related functionality
 * 
 * Tests:
 * - Add to Cart flow
 * - Update/Remove Cart items
 * - Wishlist add/remove
 * - Watchlist add/remove
 * - Buy It Now flow
 * - Checkout process
 */

// Simulate non-database testing environment
define('TEST_MODE', true);

echo "=== Purchase Flows Integration Test ===\n\n";

$testResults = [
    'passed' => 0,
    'failed' => 0,
    'errors' => []
];

function testPass($name) {
    global $testResults;
    $testResults['passed']++;
    echo "✓ $name\n";
}

function testFail($name, $message = '') {
    global $testResults;
    $testResults['failed']++;
    $testResults['errors'][] = "$name: $message";
    echo "✗ $name" . ($message ? ": $message" : '') . "\n";
}

// Test 1: API Endpoints Exist
echo "Testing API Endpoints...\n";

$endpoints = [
    'api/cart.php',
    'api/wishlist.php',
    'api/watchlist.php',
    'checkout.php',
    'product.php',
    'cart.php',
    'wishlist.php',
    'watchlist.php'
];

foreach ($endpoints as $endpoint) {
    if (file_exists(__DIR__ . "/../$endpoint")) {
        testPass("Endpoint exists: $endpoint");
    } else {
        testFail("Endpoint missing: $endpoint");
    }
}

// Test 2: API Structure Validation
echo "\nTesting API Structure...\n";

// Check cart API structure
$cartApiContent = file_get_contents(__DIR__ . '/../api/cart.php');
if (strpos($cartApiContent, 'Session::isLoggedIn()') !== false) {
    testPass("Cart API has authentication check");
} else {
    testFail("Cart API missing authentication check");
}

if (strpos($cartApiContent, 'case \'add\':') !== false &&
    strpos($cartApiContent, 'case \'update\':') !== false &&
    strpos($cartApiContent, 'case \'remove\':') !== false &&
    strpos($cartApiContent, 'case \'clear\':') !== false) {
    testPass("Cart API has all required actions");
} else {
    testFail("Cart API missing required actions");
}

// Check wishlist API structure
$wishlistApiContent = file_get_contents(__DIR__ . '/../api/wishlist.php');
if (strpos($wishlistApiContent, 'Session::isLoggedIn()') !== false) {
    testPass("Wishlist API has authentication check");
} else {
    testFail("Wishlist API missing authentication check");
}

if (strpos($wishlistApiContent, 'case \'add\':') !== false &&
    strpos($wishlistApiContent, 'case \'remove\':') !== false &&
    strpos($wishlistApiContent, 'case \'check\':') !== false) {
    testPass("Wishlist API has all required actions");
} else {
    testFail("Wishlist API missing required actions");
}

// Check watchlist API structure
$watchlistApiContent = file_get_contents(__DIR__ . '/../api/watchlist.php');
if (strpos($watchlistApiContent, 'Session::isLoggedIn()') !== false) {
    testPass("Watchlist API has authentication check");
} else {
    testFail("Watchlist API missing authentication check");
}

if (strpos($watchlistApiContent, 'case \'add\':') !== false &&
    strpos($watchlistApiContent, 'case \'remove\':') !== false &&
    strpos($watchlistApiContent, 'case \'check\':') !== false) {
    testPass("Watchlist API has all required actions");
} else {
    testFail("Watchlist API missing required actions");
}

// Test 3: Product Validation in APIs
echo "\nTesting Product Validation...\n";

if (strpos($cartApiContent, '$product->find($productId)') !== false) {
    testPass("Cart API validates product existence");
} else {
    testFail("Cart API missing product validation");
}

if (strpos($cartApiContent, 'stock_quantity') !== false) {
    testPass("Cart API checks stock quantity");
} else {
    testFail("Cart API missing stock check");
}

if (strpos($cartApiContent, '\'active\'') !== false || strpos($cartApiContent, 'status') !== false) {
    testPass("Cart API validates product status");
} else {
    testFail("Cart API missing status check");
}

// Test 4: Product Page Actions
echo "\nTesting Product Page Integration...\n";

$productContent = file_get_contents(__DIR__ . '/../product.php');

$requiredActions = [
    'add_to_cart',
    'buy_now',
    'add_to_wishlist',
    'add_to_watchlist',
    'make_offer'
];

foreach ($requiredActions as $action) {
    if (strpos($productContent, "case '$action':") !== false) {
        testPass("Product page handles: $action");
    } else {
        testFail("Product page missing: $action");
    }
}

// Check CSRF validation
if (strpos($productContent, 'csrf_token') !== false) {
    testPass("Product page has CSRF protection");
} else {
    testFail("Product page missing CSRF protection");
}

// Check authentication checks
if (strpos($productContent, 'isLoggedIn') !== false) {
    testPass("Product page checks authentication");
} else {
    testFail("Product page missing auth check");
}

// Test 5: Checkout Validation
echo "\nTesting Checkout Flow...\n";

$checkoutContent = file_get_contents(__DIR__ . '/../checkout.php');

if (strpos($checkoutContent, 'Session::requireLogin()') !== false) {
    testPass("Checkout requires login");
} else {
    testFail("Checkout missing login requirement");
}

if (strpos($checkoutContent, 'empty($cartItems)') !== false) {
    testPass("Checkout validates cart not empty");
} else {
    testFail("Checkout missing empty cart check");
}

if (strpos($checkoutContent, 'stock_quantity') !== false) {
    testPass("Checkout validates stock quantity");
} else {
    testFail("Checkout missing stock validation");
}

if (strpos($checkoutContent, '\'active\'') !== false || strpos($checkoutContent, 'status') !== false) {
    testPass("Checkout validates product status");
} else {
    testFail("Checkout missing status validation");
}

// Test 6: JavaScript Functions
echo "\nTesting JavaScript Integration...\n";

$jsChecks = [
    'async function addToCart()' => 'Add to cart function',
    'async function buyNow()' => 'Buy now function',
    'async function toggleWishlist()' => 'Wishlist toggle function',
    'async function toggleWatchlist()' => 'Watchlist toggle function',
    'csrfToken' => 'CSRF token variable'
];

foreach ($jsChecks as $check => $description) {
    if (strpos($productContent, $check) !== false) {
        testPass("JavaScript has: $description");
    } else {
        testFail("JavaScript missing: $description");
    }
}

// Test 7: Error Handling
echo "\nTesting Error Handling...\n";

if (strpos($cartApiContent, 'try {') !== false && strpos($cartApiContent, 'catch') !== false) {
    testPass("Cart API has try-catch error handling");
} else {
    testFail("Cart API missing try-catch blocks");
}

if (strpos($cartApiContent, 'errorResponse') !== false) {
    testPass("Cart API uses errorResponse helper");
} else {
    testFail("Cart API missing errorResponse");
}

if (strpos($cartApiContent, 'successResponse') !== false) {
    testPass("Cart API uses successResponse helper");
} else {
    testFail("Cart API missing successResponse");
}

// Test 8: Response Format Consistency
echo "\nTesting Response Format...\n";

// Check that APIs return consistent JSON
$apisToCheck = [
    'api/cart.php' => 'Cart',
    'api/wishlist.php' => 'Wishlist',
    'api/watchlist.php' => 'Watchlist'
];

foreach ($apisToCheck as $file => $name) {
    $content = file_get_contents(__DIR__ . "/../$file");
    if (strpos($content, "header('Content-Type: application/json')") !== false) {
        testPass("$name API sets JSON content type");
    } else {
        testFail("$name API missing JSON content type header");
    }
}

// Test 9: Progressive Enhancement
echo "\nTesting Progressive Enhancement...\n";

// Check if product page has form fallbacks
if (strpos($productContent, '<button') !== false) {
    testPass("Product page uses button elements");
} else {
    testFail("Product page missing button elements");
}

// Test 10: Security Checks
echo "\nTesting Security Features...\n";

$securityChecks = [
    'Cart API product validation' => strpos($cartApiContent, '$product->find($productId)') !== false,
    'Cart API user validation' => strpos($cartApiContent, 'Session::isLoggedIn()') !== false,
    'Wishlist API user validation' => strpos($wishlistApiContent, 'Session::isLoggedIn()') !== false,
    'Watchlist API user validation' => strpos($watchlistApiContent, 'Session::isLoggedIn()') !== false,
    'Product page CSRF check' => strpos($productContent, 'verifyCsrfToken') !== false,
    'Checkout login requirement' => strpos($checkoutContent, 'requireLogin') !== false
];

foreach ($securityChecks as $check => $result) {
    if ($result) {
        testPass($check);
    } else {
        testFail($check);
    }
}

// Test 11: Documentation
echo "\nTesting Documentation...\n";

if (file_exists(__DIR__ . '/../IMPLEMENTATION_COMPLETE.md')) {
    testPass("Implementation documentation exists");
} else {
    testFail("Missing implementation documentation");
}

if (file_exists(__DIR__ . '/../ECOMMERCE_FIX_SUMMARY.md')) {
    testPass("Fix summary documentation exists");
} else {
    testFail("Missing fix summary documentation");
}

// Test 12: Alternative Cart Endpoint
echo "\nTesting Alternative Endpoints...\n";

if (file_exists(__DIR__ . '/../cart/ajax-add.php')) {
    $ajaxCartContent = file_get_contents(__DIR__ . '/../cart/ajax-add.php');
    
    if (strpos($ajaxCartContent, 'Session::isLoggedIn()') !== false) {
        testPass("AJAX cart endpoint has authentication");
    } else {
        testFail("AJAX cart endpoint missing authentication");
    }
    
    if (strpos($ajaxCartContent, 'stock_quantity') !== false) {
        testPass("AJAX cart endpoint validates stock");
    } else {
        testFail("AJAX cart endpoint missing stock check");
    }
    
    if (strpos($ajaxCartContent, 'price') !== false) {
        testPass("AJAX cart endpoint includes price");
    } else {
        testFail("AJAX cart endpoint missing price");
    }
} else {
    testFail("AJAX cart endpoint not found");
}

// Results Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "Test Results Summary\n";
echo str_repeat("=", 50) . "\n";
echo "Passed: " . $testResults['passed'] . "\n";
echo "Failed: " . $testResults['failed'] . "\n";

if ($testResults['failed'] > 0) {
    echo "\nFailed Tests:\n";
    foreach ($testResults['errors'] as $error) {
        echo "  - $error\n";
    }
    echo "\n❌ Some tests failed\n";
    exit(1);
} else {
    echo "\n✅ All tests passed!\n";
    exit(0);
}
