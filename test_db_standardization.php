#!/usr/bin/env php
<?php
/**
 * Test Standardized Database Access
 * Validates the new db() function and helpers
 */

require_once __DIR__ . '/includes/db.php';

echo "=== Testing Standardized Database Access ===\n\n";

// Test 1: db() function returns PDO instance
echo "Test 1: db() function...\n";
try {
    $pdo = db();
    if ($pdo instanceof PDO) {
        echo "✓ db() returns PDO instance\n";
    } else {
        echo "❌ db() does not return PDO instance\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ db() failed: " . $e->getMessage() . "\n";
    echo "Note: This is expected if no database is available\n";
}

// Test 2: Singleton behavior
echo "\nTest 2: Singleton behavior...\n";
try {
    $pdo1 = db();
    $pdo2 = db();
    if ($pdo1 === $pdo2) {
        echo "✓ db() returns same instance (singleton)\n";
    } else {
        echo "❌ db() creates new instances\n";
    }
} catch (Exception $e) {
    echo "❌ Singleton test failed: " . $e->getMessage() . "\n";
}

// Test 3: db_ping() function
echo "\nTest 3: db_ping() function...\n";
$pingResult = db_ping();
if ($pingResult === true) {
    echo "✓ Database is accessible (db_ping() returned true)\n";
} elseif ($pingResult === false) {
    echo "❌ Database is not accessible (db_ping() returned false)\n";
    echo "Note: This is expected if no database is available\n";
} else {
    echo "❌ db_ping() returned unexpected value\n";
}

// Test 4: db_transaction() function exists
echo "\nTest 4: db_transaction() function...\n";
if (function_exists('db_transaction')) {
    echo "✓ db_transaction() function is available\n";
} else {
    echo "❌ db_transaction() function not found\n";
    exit(1);
}

// Test 5: Simple transaction test (if database is available)
if ($pingResult) {
    echo "\nTest 5: Transaction test...\n";
    try {
        $result = db_transaction(function($pdo) {
            // Simple test query
            $stmt = $pdo->query('SELECT 1 as test');
            return $stmt->fetch();
        });
        
        if ($result && $result['test'] == 1) {
            echo "✓ Transaction test passed\n";
        } else {
            echo "❌ Transaction test failed - unexpected result\n";
        }
    } catch (Exception $e) {
        echo "❌ Transaction test failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";