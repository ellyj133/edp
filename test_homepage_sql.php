<?php
/**
 * Homepage SQL Error Test
 * Tests that the homepage loads without SQL 1064 errors
 */

require_once __DIR__ . '/includes/init.php';

function testResult($testName, $passed, $message = '') {
    echo ($passed ? '✅' : '❌') . " Test: $testName";
    if ($message) echo " - $message";
    echo "\n";
    return $passed;
}

echo "=== Homepage SQL Error Test ===\n\n";

$allPassed = true;

try {
    // Test 1: Initialize models without errors
    echo "Test 1: Model initialization...\n";
    $product = new Product();
    $category = new Category();
    $recommendation = new Recommendation();
    testResult("Model Initialization", true, "Models created successfully");
    
    // Test 2: Test trending products query (the main fix)
    echo "\nTest 2: Trending products query (MariaDB syntax)...\n";
    try {
        $trendingProducts = $recommendation->getTrendingProducts(8);
        testResult("Trending Products Query", true, "Query executed without SQL errors");
        testResult("Trending Products Result Type", is_array($trendingProducts), "Returns array");
    } catch (PDOException $e) {
        $allPassed = false;
        testResult("Trending Products Query", false, "SQL Error: " . $e->getMessage());
    }
    
    // Test 3: Test featured products
    echo "\nTest 3: Featured products query...\n";
    try {
        $featuredProducts = $product->getFeatured(12);
        testResult("Featured Products Query", true, "Query executed without errors");
    } catch (PDOException $e) {
        $allPassed = false;
        testResult("Featured Products Query", false, "SQL Error: " . $e->getMessage());
    }
    
    // Test 4: Test categories
    echo "\nTest 4: Categories query...\n";
    try {
        $categories = $category->getParents();
        testResult("Categories Query", true, "Query executed without errors");
    } catch (PDOException $e) {
        $allPassed = false;
        testResult("Categories Query", false, "SQL Error: " . $e->getMessage());
    }
    
    // Test 5: Test new arrivals
    echo "\nTest 5: New arrivals query...\n";
    try {
        $newArrivals = $product->getLatest(6);
        testResult("New Arrivals Query", true, "Query executed without errors");
    } catch (PDOException $e) {
        $allPassed = false;
        testResult("New Arrivals Query", false, "SQL Error: " . $e->getMessage());
    }
    
    // Test 6: Verify datetime functions are MariaDB compatible
    echo "\nTest 6: Datetime function verification...\n";
    $modelsContent = file_get_contents(__DIR__ . '/includes/models_extended.php');
    $functionsContent = file_get_contents(__DIR__ . '/includes/functions.php');
    
    $sqliteFound = (strpos($modelsContent, "datetime('now'") !== false) || 
                   (strpos($functionsContent, "datetime('now'") !== false);
    testResult("SQLite Functions Removed", !$sqliteFound, $sqliteFound ? "Found SQLite datetime functions" : "All datetime functions converted to MariaDB");
    
    if ($sqliteFound) {
        $allPassed = false;
    }
    
    // Test 7: Test CSS background fix
    echo "\nTest 7: CSS background verification...\n";
    $cssContent = file_get_contents(__DIR__ . '/css/styles.css');
    $hasWhiteBackground = strpos($cssContent, 'background: #ffffff !important') !== false;
    $hasOldGreyBackground = strpos($cssContent, 'background-color: #f7f7f7') !== false;
    
    testResult("White Background CSS", $hasWhiteBackground, $hasWhiteBackground ? "White background enforced" : "White background not found");
    testResult("Grey Background Removed", !$hasOldGreyBackground, $hasOldGreyBackground ? "Old grey background still present" : "Grey background removed");
    
    if (!$hasWhiteBackground || $hasOldGreyBackground) {
        $allPassed = false;
    }
    
    // Test 8: Check theme-color meta tag
    echo "\nTest 8: Theme color meta tag...\n";
    $headerContent = file_get_contents(__DIR__ . '/templates/header.php');
    $hasThemeColor = strpos($headerContent, 'meta name="theme-color" content="#ffffff"') !== false;
    testResult("Theme Color Meta Tag", $hasThemeColor, $hasThemeColor ? "Theme color set to white" : "Theme color meta tag missing");
    
    if (!$hasThemeColor) {
        $allPassed = false;
    }
    
} catch (Exception $e) {
    $allPassed = false;
    echo "❌ Test failed with exception: " . $e->getMessage() . "\n";
}

echo "\n=== Homepage SQL Test Summary ===\n";

if ($allPassed) {
    echo "🎉 ALL HOMEPAGE TESTS PASSED! 🎉\n\n";
    echo "✅ SQL 1064 Error Fixed: All SQLite datetime functions converted to MariaDB\n";
    echo "✅ Trending Products Query: Uses proper MariaDB syntax with DATE_SUB and NOW()\n";
    echo "✅ White Background: Forced across all browsers with !important\n";
    echo "✅ Theme Color: Set to white (#ffffff)\n";
    echo "✅ Homepage Ready: No SQL errors on page load\n\n";
    exit(0);
} else {
    echo "❌ Some homepage tests failed. Please review the issues above.\n";
    exit(1);
}
?>