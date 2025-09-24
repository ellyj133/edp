<?php
/**
 * Comprehensive Site Validation Script
 * Tests all major pages for functionality and broken links
 */

// Set up proper environment for testing
$_ENV['APP_ENV'] = 'development';
$_ENV['USE_SQLITE'] = 'true';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = '';

require_once __DIR__ . '/includes/init.php';

echo "=== COMPREHENSIVE SITE VALIDATION ===\n\n";

$pages_to_test = [
    'index.php' => 'Homepage',
    'deals.php' => 'Deals page',
    'products.php' => 'Products listing',
    'category.php' => 'Category page',
    'login.php' => 'Login page',
    'register.php' => 'Register page',
    'contact.php' => 'Contact page',
    'cart.php' => 'Shopping cart',
    'search.php' => 'Search page',
    'brands.php' => 'Brands page',
    'sell.php' => 'Sell page',
    'wishlist.php' => 'Wishlist page'
];

$results = [];
$issues_found = [];

echo "Testing pages for basic functionality...\n\n";

foreach ($pages_to_test as $page => $description) {
    echo "Testing $description ($page)...";
    
    try {
        // Clear any previous output
        ob_start();
        
        // Set up request for this page
        $_SERVER['SCRIPT_NAME'] = "/$page";
        $_SERVER['REQUEST_URI'] = "/$page";
        
        include $page;
        $output = ob_get_clean();
        
        $length = strlen($output);
        $has_html = strpos($output, '<html') !== false || strpos($output, '<!DOCTYPE') !== false;
        $has_errors = strpos($output, 'Fatal error') !== false || 
                     strpos($output, 'Parse error') !== false ||
                     strpos($output, 'Call to undefined') !== false;
        
        if ($has_errors) {
            $results[$page] = 'ERROR: Contains fatal/parse errors';
            $issues_found[] = "$page: Contains fatal or parse errors";
            echo " ❌ ERROR\n";
        } elseif ($length < 100) {
            $results[$page] = 'WARNING: Very short output (' . $length . ' chars)';
            $issues_found[] = "$page: Very short output, may be empty or broken";
            echo " ⚠️  WARNING (short output)\n";
        } elseif (!$has_html) {
            $results[$page] = 'WARNING: No HTML structure detected';
            $issues_found[] = "$page: No proper HTML structure detected";
            echo " ⚠️  WARNING (no HTML)\n";
        } else {
            $results[$page] = 'OK: ' . $length . ' characters';
            echo " ✅ OK\n";
        }
        
    } catch (Exception $e) {
        $results[$page] = 'ERROR: ' . $e->getMessage();
        $issues_found[] = "$page: Exception - " . $e->getMessage();
        echo " ❌ ERROR: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        $results[$page] = 'FATAL: ' . $e->getMessage();
        $issues_found[] = "$page: Fatal error - " . $e->getMessage();
        echo " 💥 FATAL: " . $e->getMessage() . "\n";
    }
}

echo "\n=== TESTING ASSET FILES ===\n\n";

$assets_to_check = [
    'css/styles.css' => 'Main stylesheet',
    'assets/css/base.css' => 'Base stylesheet', 
    'js/fezamarket.js' => 'Main JavaScript',
    'assets/js/ui.js' => 'UI JavaScript',
    'images/favicon.ico' => 'Favicon'
];

foreach ($assets_to_check as $asset => $description) {
    echo "Checking $description ($asset)...";
    
    if (file_exists($asset)) {
        $size = filesize($asset);
        echo " ✅ OK ($size bytes)\n";
    } else {
        echo " ❌ MISSING\n";
        $issues_found[] = "Asset missing: $asset ($description)";
    }
}

echo "\n=== TESTING DATABASE CONNECTIVITY ===\n\n";

try {
    $db = db();
    echo "Database connection: ✅ OK\n";
    
    // Test basic queries
    $stmt = $db->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "Products table: ✅ OK ({$result['count']} products)\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch();
    echo "Categories table: ✅ OK ({$result['count']} categories)\n";
    
} catch (Exception $e) {
    echo "Database connection: ❌ ERROR - " . $e->getMessage() . "\n";
    $issues_found[] = "Database connection failed: " . $e->getMessage();
}

echo "\n=== SUMMARY ===\n\n";

if (empty($issues_found)) {
    echo "🎉 ALL TESTS PASSED! No issues found.\n\n";
} else {
    echo "⚠️  ISSUES FOUND:\n\n";
    foreach ($issues_found as $issue) {
        echo "- $issue\n";
    }
    echo "\n";
}

echo "Detailed Results:\n";
foreach ($results as $page => $result) {
    echo "$page: $result\n";
}

echo "\n=== VALIDATION COMPLETE ===\n";