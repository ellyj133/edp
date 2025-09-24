<?php
/**
 * Simple test script for logActivity() method
 * Tests valid activity types, invalid types, and alias mapping
 */

// Include necessary files
require_once __DIR__ . '/includes/init.php';

echo "Testing Recommendation->logActivity() method\n";
echo "============================================\n\n";

// Initialize recommendation model
$recommendation = new Recommendation();

// Test cases
$testCases = [
    // Valid activity types
    ['view_product', true, 'Valid activity type'],
    ['add_to_cart', true, 'Valid activity type'],
    ['purchase', true, 'Valid activity type'],
    ['search', true, 'Valid activity type'],
    ['review', true, 'Valid activity type'],
    
    // Valid aliases (should be mapped)
    ['view', true, 'Should map to view_product'],
    ['view_item', true, 'Should map to view_product'],
    ['view_homepage', true, 'Should map to view_product'],
    ['cart', true, 'Should map to add_to_cart'],
    ['add', true, 'Should map to add_to_cart'],
    ['buy', true, 'Should map to purchase'],
    ['order', true, 'Should map to purchase'],
    
    // Invalid activity types
    ['invalid_activity', false, 'Should return false for invalid type'],
    ['', false, 'Should return false for empty string'],
    [null, false, 'Should return false for null'],
];

$passCount = 0;
$totalCount = count($testCases);

foreach ($testCases as $index => $testCase) {
    list($activityType, $expectedResult, $description) = $testCase;
    
    echo "Test " . ($index + 1) . ": {$description}\n";
    echo "Activity Type: " . ($activityType ?? 'null') . "\n";
    
    // Test the method (using test user ID 1, product ID 1)
    $result = $recommendation->logActivity(1, 1, $activityType, ['test' => true]);
    
    if ($result === $expectedResult) {
        echo "✓ PASS\n";
        $passCount++;
    } else {
        echo "✗ FAIL - Expected: " . ($expectedResult ? 'true' : 'false') . 
             ", Got: " . ($result ? 'true' : 'false') . "\n";
    }
    echo "\n";
}

echo "============================================\n";
echo "Results: {$passCount}/{$totalCount} tests passed\n";

if ($passCount === $totalCount) {
    echo "🎉 All tests passed!\n";
} else {
    echo "❌ Some tests failed. Please check the implementation.\n";
}