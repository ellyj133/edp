<?php
/**
 * Test Database Insert with lastInsertId fix
 */

require_once 'test_db_config.php';

echo "Testing Database fixes...\n\n";

// Test 1: Test lastInsertId method exists
echo "1. Testing Database::lastInsertId() method:\n";
try {
    // Insert a test record
    Database::query(
        "INSERT INTO products (seller_id, name, slug, price, status, created_at) 
         VALUES (?, ?, ?, ?, ?, datetime('now'))",
        [1, 'Test Product', 'test-product-' . time(), 19.99, 'draft']
    );
    
    $productId = Database::lastInsertId();
    echo "✓ Insert successful, Product ID: $productId\n";
    
    // Verify the record was inserted
    $product = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        echo "✓ Product retrieved: {$product['name']}\n";
        echo "✓ seller_id properly set: {$product['seller_id']}\n";
    } else {
        echo "❌ Product not found after insert\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database insert failed: " . $e->getMessage() . "\n";
}

// Test 2: Test autosave functionality
echo "\n2. Testing autosave table:\n";
try {
    $autosaveData = [
        'seller_id' => 1,
        'name' => 'Autosave Test Product',
        'slug' => 'autosave-test-' . time(),
        'price' => 29.99,
        'description' => 'This is a test autosave',
        'category_id' => 1,
        'brand' => 'Test Brand',
        'currency_code' => 'USD',
        'stock_qty' => 10,
        'track_inventory' => 1,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $fields = array_keys($autosaveData);
    $placeholders = str_repeat('?,', count($fields) - 1) . '?';
    
    Database::query(
        "INSERT INTO product_autosaves (" . implode(', ', $fields) . ", created_at) VALUES ({$placeholders}, datetime('now'))",
        array_values($autosaveData)
    );
    
    $autosaveId = Database::lastInsertId();
    echo "✓ Autosave record created, ID: $autosaveId\n";
    
    // Retrieve the autosave
    $autosave = Database::query("SELECT * FROM product_autosaves WHERE id = ?", [$autosaveId])->fetch(PDO::FETCH_ASSOC);
    if ($autosave) {
        echo "✓ Autosave retrieved: {$autosave['name']}\n";
    } else {
        echo "❌ Autosave not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Autosave test failed: " . $e->getMessage() . "\n";
}

echo "\n3. Testing syntax of main seller add.php file:\n";
$syntaxCheck = shell_exec('cd ' . __DIR__ . ' && php -l seller/products/add.php 2>&1');
if (strpos($syntaxCheck, 'No syntax errors') !== false) {
    echo "✓ seller/products/add.php syntax OK\n";
} else {
    echo "❌ seller/products/add.php has syntax errors:\n$syntaxCheck\n";
}

echo "\nDatabase fix tests complete!\n";
?>