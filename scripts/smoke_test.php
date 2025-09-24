<?php
/**
 * Smoke Test Script for EPD Platform
 * Tests critical endpoints to ensure they return HTTP 200 and have essential DOM elements
 */

require_once __DIR__ . '/../includes/init.php';

// Test configuration
$tests = [
    // Admin endpoints
    [
        'url' => '/admin/',
        'name' => 'Admin Dashboard',
        'expected_elements' => ['Admin Dashboard', 'Total Users', 'Total Products']
    ],
    [
        'url' => '/admin/integrations/',
        'name' => 'Admin Integrations',
        'expected_elements' => ['API']
    ],
    [
        'url' => '/admin/coupons/',
        'name' => 'Admin Coupons',
        'expected_elements' => ['Coupons']
    ],
    [
        'url' => '/admin/dashboards/',
        'name' => 'Admin Custom Dashboards',
        'expected_elements' => ['Dashboard']
    ],
    [
        'url' => '/admin/streaming/',
        'name' => 'Admin Streaming',
        'expected_elements' => ['Stream']
    ],
    
    // Seller endpoints
    [
        'url' => '/seller/dashboard.php',
        'name' => 'Seller Dashboard',
        'expected_elements' => ['Seller Dashboard'],
        'requires_auth' => true
    ],
    
    // Buyer endpoints
    [
        'url' => '/buyer/dashboard.php',
        'name' => 'Buyer Dashboard',
        'expected_elements' => ['Buyer Dashboard', 'Total Orders'],
        'requires_auth' => true
    ],
    
    // Public endpoints
    [
        'url' => '/',
        'name' => 'Homepage',
        'expected_elements' => []
    ],
    [
        'url' => '/products.php',
        'name' => 'Products Page',
        'expected_elements' => []
    ]
];

// Test results
$passed = 0;
$failed = 0;
$errors = [];

echo "=== EPD Platform Smoke Test ===\n";
echo "Testing " . count($tests) . " endpoints...\n\n";

foreach ($tests as $test) {
    $url = 'http://localhost:8000' . $test['url'];
    $name = $test['name'];
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EPD-SmokeTest/1.0');
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Check for cURL errors
    if ($error) {
        $failed++;
        $errors[] = "$name: cURL Error - $error";
        echo "❌ $name: cURL Error\n";
        continue;
    }
    
    // Check HTTP status code
    if ($httpCode !== 200) {
        // Allow 302 redirects for auth-protected pages
        if ($httpCode === 302 && isset($test['requires_auth'])) {
            $passed++;
            echo "✅ $name: HTTP $httpCode (Auth redirect - OK)\n";
            continue;
        }
        
        $failed++;
        $errors[] = "$name: HTTP $httpCode (expected 200)";
        echo "❌ $name: HTTP $httpCode\n";
        continue;
    }
    
    // Check for expected elements in response
    $elementsMissing = [];
    foreach ($test['expected_elements'] as $element) {
        if (strpos($response, $element) === false) {
            $elementsMissing[] = $element;
        }
    }
    
    if (!empty($elementsMissing)) {
        $failed++;
        $errors[] = "$name: Missing elements - " . implode(', ', $elementsMissing);
        echo "❌ $name: Missing elements\n";
        continue;
    }
    
    $passed++;
    echo "✅ $name: OK\n";
}

echo "\n=== Test Results ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if (!empty($errors)) {
    echo "\n=== Errors ===\n";
    foreach ($errors as $error) {
        echo "• $error\n";
    }
}

// Exit with appropriate code
exit($failed > 0 ? 1 : 0);
?>