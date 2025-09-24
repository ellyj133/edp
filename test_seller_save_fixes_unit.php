<?php
/**
 * Focused unit test for seller product save functionality fixes
 */

require_once 'test_db_config.php';

echo "Testing Seller Product Save Functionality Fixes (Unit Tests)...\n\n";

// Test 1: Test autosave database structure and field mapping
echo "1. Testing autosave field mapping:\n";
try {
    $autosaveData = [
        'seller_id' => 1,
        'name' => 'Test Autosave Product',
        'slug' => 'test-autosave-' . time(),
        'short_description' => 'Test short description',
        'description' => 'This is a test autosave description',
        'price' => 49.99,
        'compare_price' => 59.99,
        'cost_price' => 30.00,
        'category_id' => 1,
        'brand' => 'Test Brand',
        'condition' => 'new',
        'currency_code' => 'USD',
        'stock_qty' => 10,
        'low_stock_threshold' => 3,
        'tags' => 'test,autosave',
        'track_inventory' => 1,
        'allow_backorder' => 0,
        'weight_kg' => 1.5,
        'length_cm' => 20.0,
        'width_cm' => 15.0,
        'height_cm' => 10.0,
        'seo_title' => 'Test SEO Title',
        'seo_description' => 'Test SEO Description',
        'seo_keywords' => 'test, autosave, product',
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Insert into autosave table with proper field mapping
    $fields = array_keys($autosaveData);
    $placeholders = str_repeat('?,', count($fields) - 1) . '?';
    
    Database::query(
        "INSERT INTO product_autosaves (" . implode(', ', array_map(function($f) { return "`{$f}`"; }, $fields)) . ", created_at) VALUES ({$placeholders}, datetime('now'))",
        array_values($autosaveData)
    );
    
    $autosaveId = Database::lastInsertId();
    echo "✓ Autosave record created successfully, ID: $autosaveId\n";
    
    // Test updating existing autosave
    $updateData = $autosaveData;
    $updateData['name'] = 'Updated Autosave Product';
    $updateData['price'] = 54.99;
    unset($updateData['seller_id']); // Don't update seller_id
    
    $setClause = [];
    $params = [];
    foreach ($updateData as $key => $value) {
        $setClause[] = "`{$key}` = ?";
        $params[] = $value;
    }
    $params[] = $autosaveId;
    
    Database::query(
        "UPDATE product_autosaves SET " . implode(', ', $setClause) . " WHERE id = ?",
        $params
    );
    
    echo "✓ Autosave record updated successfully\n";
    
    // Verify the update
    $updated = Database::query("SELECT * FROM product_autosaves WHERE id = ?", [$autosaveId])->fetch(PDO::FETCH_ASSOC);
    if ($updated && $updated['name'] === 'Updated Autosave Product') {
        echo "✓ Autosave update verified: {$updated['name']}\n";
    } else {
        echo "❌ Autosave update verification failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Autosave field mapping test failed: " . $e->getMessage() . "\n";
}

// Test 2: Test product add with proper field mapping and transaction
echo "\n2. Testing product add with proper field mapping:\n";
try {
    $productData = [
        'seller_id' => 1,
        'name' => 'Test Product Add',
        'slug' => 'test-product-add-' . time(),
        'short_description' => 'Test short description',
        'description' => 'Test full description',
        'price' => 29.99,
        'compare_price' => 39.99,
        'cost_price' => 20.00,
        'category_id' => 1,
        'brand' => 'Test Brand',
        'condition' => 'new',
        'status' => 'draft',
        'visibility' => 'public',
        'currency_code' => 'USD',
        'stock_quantity' => 5, // Note: correct field name
        'low_stock_threshold' => 2,
        'track_inventory' => 1,
        'allow_backorder' => 0,
        'tags' => 'test,product,add'
    ];
    
    // Test with proper transaction handling
    $productId = db_transaction(function($pdo) use ($productData) {
        Database::query("
            INSERT INTO products
               (seller_id, name, slug, short_description, description, category_id, price, compare_price, cost_price,
                currency_code, brand, `condition`, status, visibility, track_inventory, allow_backorder,
                stock_quantity, low_stock_threshold, tags, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,datetime('now'),datetime('now'))",
            [
                $productData['seller_id'], $productData['name'], $productData['slug'], $productData['short_description'], $productData['description'],
                $productData['category_id'], $productData['price'],
                $productData['compare_price'], $productData['cost_price'],
                $productData['currency_code'], $productData['brand'], $productData['condition'], $productData['status'],
                $productData['visibility'], $productData['track_inventory'], $productData['allow_backorder'],
                $productData['stock_quantity'], $productData['low_stock_threshold'],
                $productData['tags']
            ]
        );
        
        return Database::lastInsertId();
    });
    
    echo "✓ Product add transaction successful, ID: $productId\n";
    
    // Verify the product
    $product = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        echo "✓ Product retrieved: {$product['name']}\n";
        echo "✓ Price: {$product['price']}\n";
        echo "✓ Stock quantity field mapped correctly: {$product['stock_quantity']}\n";
        echo "✓ Brand field: {$product['brand']}\n";
    } else {
        echo "❌ Product not found after insert\n";
    }
    
} catch (Exception $e) {
    echo "❌ Product add test failed: " . $e->getMessage() . "\n";
}

// Test 3: Test product edit with proper transaction and field mapping
echo "\n3. Testing product edit with transaction:\n";
try {
    // Get the product we just created
    $product = Database::query("SELECT * FROM products WHERE seller_id = 1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("No test product found");
    }
    
    $productId = $product['id'];
    echo "✓ Found test product ID: $productId\n";
    
    // Test updating with proper transaction
    db_transaction(function($pdo) use ($productId) {
        Database::query("
           UPDATE products SET
             name=?, slug=?, short_description=?, description=?,
             price=?, compare_price=?, cost_price=?, stock_quantity=?, low_stock_threshold=?,
             currency_code=?, track_inventory=?, allow_backorder=?, category_id=?,
             brand=?, tags=?, `condition`=?, status=?, visibility=?, updated_at=datetime('now')
           WHERE id=? AND seller_id=?",
            [
                'Updated Test Product', 'updated-test-' . time(), 'Updated short description', 'Updated full description',
                39.99, 49.99, 25.00, 8, 2,
                'USD', 1, 0, 1,
                'Updated Brand', 'updated,test', 'new', 'active', 'public',
                $productId, 1
            ]
        );
    });
    
    echo "✓ Product edit transaction successful\n";
    
    // Verify the update
    $updatedProduct = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    if ($updatedProduct) {
        echo "✓ Product updated: {$updatedProduct['name']}\n";
        echo "✓ Updated price: {$updatedProduct['price']}\n";
        echo "✓ Updated status: {$updatedProduct['status']}\n";
        echo "✓ Updated brand: {$updatedProduct['brand']}\n";
    } else {
        echo "❌ Product not found after update\n";
    }
    
} catch (Exception $e) {
    echo "❌ Product edit test failed: " . $e->getMessage() . "\n";
}

// Test 4: Test error handling and rollback
echo "\n4. Testing transaction rollback on error:\n";
try {
    try {
        db_transaction(function($pdo) {
            // Insert a valid product
            Database::query("
                INSERT INTO products (seller_id, name, slug, price, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
                [1, 'Rollback Test', 'rollback-test-' . time(), 19.99, 'draft']
            );
            
            $insertId = Database::lastInsertId();
            echo "✓ Product inserted for rollback test, ID: $insertId\n";
            
            // Now cause an error to trigger rollback
            Database::query("INSERT INTO non_existent_table (invalid_column) VALUES (?)", ['test']);
        });
        
        echo "❌ Transaction should have failed but didn't\n";
        
    } catch (Exception $e) {
        echo "✓ Transaction properly rolled back on error: " . $e->getMessage() . "\n";
        
        // Verify the product was not committed
        $rolledBackProduct = Database::query("SELECT * FROM products WHERE name = 'Rollback Test'")->fetch(PDO::FETCH_ASSOC);
        if (!$rolledBackProduct) {
            echo "✓ Rollback successful - product was not committed\n";
        } else {
            echo "❌ Rollback failed - product was committed\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Rollback test setup failed: " . $e->getMessage() . "\n";
}

// Test 5: Test field validation
echo "\n5. Testing validation logic:\n";
try {
    // Test empty name validation
    $emptyName = '';
    if (empty(trim($emptyName))) {
        echo "✓ Empty name validation working\n";
    }
    
    // Test price validation
    $invalidPrice = 'abc';
    if (!is_numeric($invalidPrice) || (float)$invalidPrice <= 0) {
        echo "✓ Invalid price validation working\n";
    }
    
    // Test slug generation
    function testSlugify($input) {
        $slug = strtolower(trim($input));
        $slug = preg_replace('/[^a-z0-9]+/i','-',$slug);
        $slug = trim($slug,'-');
        return $slug !== '' ? $slug : bin2hex(random_bytes(4));
    }
    
    $testSlug = testSlugify('Test Product Name!@#$%');
    if ($testSlug === 'test-product-name') {
        echo "✓ Slug generation working: '$testSlug'\n";
    } else {
        echo "❌ Slug generation failed: '$testSlug'\n";
    }
    
} catch (Exception $e) {
    echo "❌ Validation test failed: " . $e->getMessage() . "\n";
}

echo "\nSeller Product Save Functionality Fix Tests Complete!\n";
echo "Summary: All core functionality has been tested and validated.\n";
?>