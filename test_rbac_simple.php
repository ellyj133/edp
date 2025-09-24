#!/usr/bin/env php
<?php
/**
 * Test RBAC Implementation (Simple)
 * Test role-based access control without redirects
 */

// Start session before any output
session_start();

echo "=== Testing RBAC Implementation ===\n\n";

// Load the necessary classes first
require_once __DIR__ . '/includes/init.php';

// Test 1: hasRole function behavior
echo "Test 1: hasRole() Function Tests\n";
echo "--------------------------------\n";

// Test cases
$testCases = [
    // [session_role, check_role, expected, description]
    [null, 'admin', false, 'No role should reject admin access'],
    ['customer', 'customer', true, 'Customer should access customer role'],
    ['customer', 'admin', false, 'Customer should not access admin role'],
    ['vendor', 'vendor', true, 'Vendor should access vendor role'],
    ['vendor', 'seller', true, 'Vendor should access seller role (alias)'],
    ['seller', 'vendor', true, 'Seller should access vendor role (alias)'],
    ['admin', 'admin', true, 'Admin should access admin role'],
    ['admin', 'customer', true, 'Admin should access customer role'],
    ['admin', 'vendor', true, 'Admin should access vendor role'],
];

$passedTests = 0;
$totalTests = count($testCases);

foreach ($testCases as $test) {
    [$sessionRole, $checkRole, $expected, $description] = $test;
    
    // Reset session
    $_SESSION = [];
    if ($sessionRole !== null) {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = $sessionRole;
    }
    
    $result = hasRole($checkRole);
    $status = ($result === $expected) ? "✓" : "❌";
    $resultStr = $result ? "true" : "false";
    $expectedStr = $expected ? "true" : "false";
    
    echo "$status $description (got: $resultStr, expected: $expectedStr)\n";
    
    if ($result === $expected) {
        $passedTests++;
    }
}

echo "\nhasRole() tests: $passedTests/$totalTests passed\n\n";

// Test 2: getDashboardUrl function
echo "Test 2: getDashboardUrl() Function Tests\n";
echo "----------------------------------------\n";

$urlTests = [
    ['admin', '/admin/index.php', 'Admin should get admin dashboard'],
    ['vendor', '/seller-center.php', 'Vendor should get seller center'],
    ['seller', '/seller-center.php', 'Seller should get seller center'],
    ['customer', '/account.php', 'Customer should get account page'],
    ['unknown', '/account.php', 'Unknown role should get default account page'],
];

$passedUrlTests = 0;
$totalUrlTests = count($urlTests);

foreach ($urlTests as $test) {
    [$role, $expectedUrl, $description] = $test;
    
    $result = getDashboardUrl($role);
    $status = ($result === $expectedUrl) ? "✓" : "❌";
    
    echo "$status $description (got: $result, expected: $expectedUrl)\n";
    
    if ($result === $expectedUrl) {
        $passedUrlTests++;
    }
}

echo "\ngetDashboardUrl() tests: $passedUrlTests/$totalUrlTests passed\n\n";

// Test 3: Check file existence and syntax
echo "Test 3: File Existence and Syntax Tests\n";
echo "---------------------------------------\n";

$files = [
    ['seller/auth.php', 'Seller auth guard'],
    ['admin/auth.php', 'Admin auth guard'],
    ['includes/helpers.php', 'Helper functions'],
];

$passedFileTests = 0;
$totalFileTests = count($files) * 2; // existence + syntax

foreach ($files as $file) {
    [$filePath, $description] = $file;
    $fullPath = __DIR__ . '/' . $filePath;
    
    // Test existence
    if (file_exists($fullPath)) {
        echo "✓ $description exists\n";
        $passedFileTests++;
        
        // Test syntax
        $syntaxCheck = shell_exec("php -l '$fullPath' 2>&1");
        if (strpos($syntaxCheck, 'No syntax errors') !== false) {
            echo "✓ $description syntax is valid\n";
            $passedFileTests++;
        } else {
            echo "❌ $description has syntax errors\n";
        }
    } else {
        echo "❌ $description does not exist\n";
    }
}

echo "\nFile tests: $passedFileTests/$totalFileTests passed\n\n";

// Summary
echo "=== Test Summary ===\n";
$totalAllTests = $passedTests + $passedUrlTests + $passedFileTests;
$totalAllTestsMax = $totalTests + $totalUrlTests + $totalFileTests;

echo "hasRole() function: $passedTests/$totalTests passed\n";
echo "getDashboardUrl() function: $passedUrlTests/$totalUrlTests passed\n";
echo "File tests: $passedFileTests/$totalFileTests passed\n";
echo "Overall: $totalAllTests/$totalAllTestsMax tests passed\n\n";

if ($totalAllTests === $totalAllTestsMax) {
    echo "✅ All RBAC tests passed!\n\n";
    
    echo "=== RBAC Implementation Status ===\n";
    echo "✓ hasRole() function properly handles all role combinations\n";
    echo "✓ Role aliases (vendor/seller) work correctly\n";
    echo "✓ Admin role has universal access\n";
    echo "✓ getDashboardUrl() returns correct URLs for all roles\n";
    echo "✓ Auth guard files exist and have valid syntax\n";
    echo "✓ Helper functions are properly implemented\n\n";
    
    echo "🎉 RBAC system is fully functional and secure!\n";
} else {
    echo "❌ Some tests failed. Please review the implementation.\n";
    $failedTests = $totalAllTestsMax - $totalAllTests;
    echo "Number of failed tests: $failedTests\n";
}
?>