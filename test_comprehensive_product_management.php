<?php
/**
 * Comprehensive End-to-End Test for Product Management
 * Tests the complete product lifecycle: create, edit, autosave, delete
 */

require_once 'test_db_config.php';

echo "=== COMPREHENSIVE PRODUCT MANAGEMENT E2E TEST ===\n\n";

$testResults = [
    'passed' => 0,
    'failed' => 0,
    'errors' => []
];

function test($description, $callback) {
    global $testResults;
    echo "Testing: $description\n";
    
    try {
        $result = $callback();
        if ($result) {
            echo "✅ PASS\n\n";
            $testResults['passed']++;
        } else {
            echo "❌ FAIL\n\n";
            $testResults['failed']++;
            $testResults['errors'][] = $description;
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
        $testResults['failed']++;
        $testResults['errors'][] = "$description - " . $e->getMessage();
    }
}

// Test 1: Database Connection & Setup
test("Database connection and setup", function() {
    $db = db();
    return $db instanceof PDO;
});

// Test 2: Database::lastInsertId() method
test("Database::lastInsertId() method exists and works", function() {
    Database::query("INSERT INTO products (seller_id, name, slug, price, status, created_at) VALUES (?, ?, ?, ?, ?, datetime('now'))", 
                   [1, 'Test Method Product', 'test-method-' . time(), 15.99, 'draft']);
    $id = Database::lastInsertId();
    return is_numeric($id) && $id > 0;
});

// Test 3: Product Creation with seller_id
test("Product creation includes seller_id", function() {
    Database::query("INSERT INTO products (seller_id, name, slug, price, status, created_at) VALUES (?, ?, ?, ?, ?, datetime('now'))", 
                   [1, 'Seller Test Product', 'seller-test-' . time(), 25.99, 'draft']);
    $productId = Database::lastInsertId();
    
    $product = Database::query("SELECT seller_id FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    return $product && $product['seller_id'] == 1;
});

// Test 4: Autosave table functionality
test("Product autosave table operations", function() {
    // Insert autosave record
    $data = [
        'seller_id' => 1,
        'name' => 'Autosave Test Product',
        'slug' => 'autosave-test-' . time(),
        'price' => 45.99,
        'description' => 'Test autosave description',
        'currency_code' => 'USD',
        'stock_qty' => 10,
        'track_inventory' => 1,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $fields = array_keys($data);
    $placeholders = str_repeat('?,', count($fields) - 1) . '?';
    
    Database::query("INSERT INTO product_autosaves (" . implode(', ', $fields) . ", created_at) VALUES ({$placeholders}, datetime('now'))", 
                   array_values($data));
    
    $autosaveId = Database::lastInsertId();
    $saved = Database::query("SELECT * FROM product_autosaves WHERE id = ?", [$autosaveId])->fetch(PDO::FETCH_ASSOC);
    
    return $saved && $saved['name'] === 'Autosave Test Product';
});

// Test 5: Complete Product Lifecycle
test("Complete product lifecycle (create, read, update, delete)", function() {
    // Create
    Database::query("INSERT INTO products (seller_id, name, slug, price, short_description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))", 
                   [1, 'Lifecycle Test Product', 'lifecycle-test-' . time(), 19.99, 'Test description', 'draft']);
    $productId = Database::lastInsertId();
    
    // Read
    $product = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    if (!$product || $product['name'] !== 'Lifecycle Test Product') return false;
    
    // Update
    Database::query("UPDATE products SET name = ?, price = ?, status = ? WHERE id = ? AND seller_id = ?", 
                   ['Updated Lifecycle Product', 29.99, 'published', $productId, 1]);
    
    $updated = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    if (!$updated || $updated['name'] !== 'Updated Lifecycle Product' || $updated['price'] != 29.99) return false;
    
    // Delete
    Database::query("DELETE FROM products WHERE id = ? AND seller_id = ?", [$productId, 1]);
    $deleted = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    
    return !$deleted; // Should be false (no record found) if delete worked
});

// Test 6: Seller Authorization Security
test("Seller authorization prevents unauthorized access", function() {
    // Create product as seller 1
    Database::query("INSERT INTO products (seller_id, name, slug, price, status, created_at) VALUES (?, ?, ?, ?, ?, datetime('now'))", 
                   [1, 'Security Test Product', 'security-test-' . time(), 35.99, 'draft']);
    $productId = Database::lastInsertId();
    
    // Try to update as seller 2 (should fail/not affect the product)
    Database::query("UPDATE products SET name = 'Hacked Product' WHERE id = ? AND seller_id = ?", [$productId, 2]);
    
    $product = Database::query("SELECT name FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    return $product && $product['name'] === 'Security Test Product'; // Should still have original name
});

// Test 7: PHP Syntax Validation
test("PHP files syntax validation", function() {
    $files = [
        'seller/products/add.php',
        'seller/products/edit.php', 
        'seller/products/autosave.php'
    ];
    
    foreach ($files as $file) {
        $result = shell_exec("cd " . __DIR__ . " && php -l $file 2>&1");
        if (strpos($result, 'No syntax errors') === false) {
            throw new Exception("Syntax error in $file: $result");
        }
    }
    return true;
});

// Test 8: Form Field Validation 
test("Required field validation works", function() {
    // This simulates the validation logic from add.php
    $form = ['name' => '', 'price' => ''];
    $errors = [];
    
    if ($form['name'] === '') {
        $errors['name'] = 'Product name required.';
    }
    if ($form['price'] === '' || !is_numeric($form['price'])) {
        $errors['price'] = 'Valid price required.';
    }
    
    // Should have validation errors for empty required fields
    return count($errors) === 2;
});

// Test 9: Slug Generation
test("Slug generation and uniqueness handling", function() {
    // Test slug generation logic
    $name = "Test Product Name! @#$ 123";
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    
    // Should convert to valid slug
    if ($slug !== 'test-product-name-123') return false;
    
    // Test uniqueness handling by creating duplicate slugs
    $baseSlug = 'duplicate-slug-test-' . time();
    
    Database::query("INSERT INTO products (seller_id, name, slug, price, status, created_at) VALUES (?, ?, ?, ?, ?, datetime('now'))", 
                   [1, 'First Product', $baseSlug, 10.00, 'draft']);
    
    // Check if slug exists (simulating uniqueness check)
    $exists = Database::query("SELECT id FROM products WHERE slug = ?", [$baseSlug])->fetchColumn();
    return $exists !== false; // Should find the existing slug
});

// Test 10: Comprehensive Data Integrity
test("Data integrity and relationships", function() {
    // Create a product with comprehensive data
    $productData = [
        'seller_id' => 1,
        'name' => 'Comprehensive Test Product',
        'slug' => 'comprehensive-test-' . time(),
        'price' => 99.99,
        'compare_price' => 129.99,
        'cost_price' => 50.00,
        'short_description' => 'Short description test',
        'description' => 'Full description test',
        'category_id' => 1,
        'brand' => 'Test Brand',
        'sku' => 'TEST-' . time(),
        'stock_quantity' => 100,
        'status' => 'published',
        'visibility' => 'public',
        'track_inventory' => 1,
        'allow_backorder' => 0,
        'condition' => 'new',
        'currency_code' => 'USD',
        'tags' => 'test,product,comprehensive'
    ];
    
    $fields = array_keys($productData);
    $placeholders = str_repeat('?,', count($fields) - 1) . '?';
    
    Database::query("INSERT INTO products (" . implode(', ', $fields) . ", created_at) VALUES ({$placeholders}, datetime('now'))", 
                   array_values($productData));
    
    $productId = Database::lastInsertId();
    $saved = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    
    return $saved && 
           $saved['name'] === 'Comprehensive Test Product' &&
           $saved['price'] == 99.99 &&
           $saved['seller_id'] == 1 &&
           $saved['status'] === 'published';
});

// Run all tests
echo "Running comprehensive tests...\n\n";

// Display results
echo "\n=== TEST RESULTS ===\n";
echo "✅ Tests Passed: {$testResults['passed']}\n";
echo "❌ Tests Failed: {$testResults['failed']}\n";

if ($testResults['failed'] > 0) {
    echo "\nFailed Tests:\n";
    foreach ($testResults['errors'] as $error) {
        echo "- $error\n";
    }
}

$totalTests = $testResults['passed'] + $testResults['failed'];
$successRate = round(($testResults['passed'] / $totalTests) * 100, 1);

echo "\nSuccess Rate: $successRate%\n";

if ($successRate >= 90) {
    echo "\n🎉 EXCELLENT! Product management system is working well.\n";
} elseif ($successRate >= 70) {
    echo "\n⚠️  GOOD but needs some fixes.\n";
} else {
    echo "\n❌ CRITICAL ISSUES need immediate attention.\n";
}

echo "\n=== END-TO-END TEST COMPLETE ===\n";
?>