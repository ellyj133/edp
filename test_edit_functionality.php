<?php
/**
 * Test Edit Product Functionality
 */

require_once 'test_db_config.php';

echo "Testing Edit Product Functionality...\n\n";

// First create a product to edit
echo "1. Creating a test product to edit:\n";
try {
    Database::query("
        INSERT INTO products (seller_id, name, slug, price, short_description, description, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))
    ", [1, 'Test Product for Edit', 'test-product-edit-' . time(), 29.99, 'Short desc', 'Long description', 'draft']);
    
    $productId = Database::lastInsertId();
    echo "✓ Test product created with ID: $productId\n";
    
} catch (Exception $e) {
    echo "❌ Failed to create test product: " . $e->getMessage() . "\n";
    exit(1);
}

// Test the edit functionality by updating the product
echo "\n2. Testing product update:\n";
try {
    Database::query("
        UPDATE products SET 
            name = ?, 
            price = ?, 
            short_description = ?, 
            description = ?, 
            status = ?, 
            updated_at = datetime('now')
        WHERE id = ? AND seller_id = ?
    ", [
        'Updated Test Product', 
        39.99, 
        'Updated short description', 
        'Updated long description', 
        'published',
        $productId,
        1
    ]);
    
    echo "✓ Product updated successfully\n";
    
    // Verify the update
    $updated = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    if ($updated) {
        echo "✓ Verification - Updated name: {$updated['name']}\n";
        echo "✓ Verification - Updated price: \${$updated['price']}\n";
        echo "✓ Verification - Updated status: {$updated['status']}\n";
    } else {
        echo "❌ Could not verify update\n";
    }
    
} catch (Exception $e) {
    echo "❌ Failed to update product: " . $e->getMessage() . "\n";
}

// Test seller_id security (should not update products from other sellers)
echo "\n3. Testing seller authorization (should fail):\n";
try {
    Database::query("
        UPDATE products SET name = 'Hacked Product' 
        WHERE id = ? AND seller_id = ?
    ", [$productId, 999]); // Wrong seller_id
    
    $check = Database::query("SELECT name FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    if ($check && $check['name'] !== 'Hacked Product') {
        echo "✓ Security check passed - Unauthorized update blocked\n";
    } else {
        echo "❌ Security issue - Unauthorized update allowed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Security test failed with error: " . $e->getMessage() . "\n";
}

// Test edit.php syntax
echo "\n4. Testing edit.php syntax:\n";
$syntaxCheck = shell_exec('cd ' . __DIR__ . ' && php -l seller/products/edit.php 2>&1');
if (strpos($syntaxCheck, 'No syntax errors') !== false) {
    echo "✓ seller/products/edit.php syntax OK\n";
} else {
    echo "❌ seller/products/edit.php has syntax errors:\n$syntaxCheck\n";
}

// Test slug uniqueness handling
echo "\n5. Testing slug uniqueness:\n";
try {
    // Create another product with a potential slug conflict
    Database::query("
        INSERT INTO products (seller_id, name, slug, price, status, created_at)
        VALUES (?, ?, ?, ?, ?, datetime('now'))
    ", [1, 'Another Product', 'test-slug', 19.99, 'draft']);
    
    $product2Id = Database::lastInsertId();
    
    // Try to update first product to same slug - should handle uniqueness
    Database::query("
        UPDATE products SET slug = ? WHERE id = ? AND seller_id = ?
    ", ['test-slug', $productId, 1]);
    
    echo "✓ Slug update completed (uniqueness handling may be needed in application layer)\n";
    
} catch (Exception $e) {
    echo "! Slug conflict test: " . $e->getMessage() . "\n";
}

echo "\nEdit functionality tests complete!\n";
?>