#!/usr/bin/env php
<?php
/**
 * Integration Test for Database Standardization
 * Tests that both new functional API and old Database class work together
 */

echo "=== Database Standardization Integration Test ===\n\n";

// Test 1: Load the new functional API
echo "Test 1: Loading functional API...\n";
require_once __DIR__ . '/includes/db.php';
echo "✓ Functional API loaded\n";

// Test 2: Load the backward-compatible Database class
echo "\nTest 2: Loading Database class...\n";
require_once __DIR__ . '/includes/database.php';
echo "✓ Database class loaded\n";

// Test 3: Test both APIs return the same connection
echo "\nTest 3: Checking API consistency...\n";
try {
    $pdo_functional = db();
    $pdo_class = Database::getInstance()->getConnection();
    
    if ($pdo_functional === $pdo_class) {
        echo "✓ Both APIs return the same PDO instance\n";
    } else {
        echo "❌ APIs return different PDO instances\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ API consistency test failed: " . $e->getMessage() . "\n";
    echo "Note: This is expected if no database is available\n";
}

// Test 4: Test BaseModel uses standardized connection
echo "\nTest 4: Testing BaseModel compatibility...\n";
class TestModel extends BaseModel {
    protected $table = 'test';
}

try {
    $model = new TestModel();
    echo "✓ BaseModel instantiated successfully\n";
} catch (Exception $e) {
    echo "❌ BaseModel test failed: " . $e->getMessage() . "\n";
}

// Test 5: Test helper functions
echo "\nTest 5: Testing helper functions...\n";
if (function_exists('db_ping') && function_exists('db_transaction')) {
    echo "✓ Helper functions available\n";
} else {
    echo "❌ Helper functions missing\n";
}

// Test 6: Test health check functionality
echo "\nTest 6: Testing health check...\n";
ob_start();
include __DIR__ . '/healthz.php';
$health_output = ob_get_clean();

if (strpos($health_output, '"status"') !== false) {
    echo "✓ Health check endpoint working\n";
} else {
    echo "❌ Health check endpoint failed\n";
}

echo "\n=== Integration Test Complete ===\n";