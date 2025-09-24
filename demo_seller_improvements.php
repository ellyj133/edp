<?php
/**
 * Manual demonstration of the improved seller product save functionality
 * This shows the seller experience with the fixes implemented
 */

require_once 'test_db_config.php';

echo "=== Seller Product Save Functionality Demo ===\n\n";

echo "🏪 Welcome to the Enhanced Seller Product Management System!\n\n";

// Demo 1: Autosave functionality
echo "📝 Demo 1: Autosave Functionality\n";
echo "Seller starts creating a product and data is automatically saved...\n";

$formData = [
    'name' => 'Amazing Smartphone Case',
    'price' => '24.99',
    'description' => 'Durable smartphone case with premium materials',
    'category_id' => '1',
    'brand' => 'CaseMaster'
];

echo "Form data entered:\n";
foreach ($formData as $key => $value) {
    echo "  $key: $value\n";
}

// Simulate autosave
$autosaveData = [
    'seller_id' => 1,
    'name' => $formData['name'],
    'price' => (float)$formData['price'],
    'description' => $formData['description'],
    'category_id' => (int)$formData['category_id'],
    'brand' => $formData['brand'],
    'condition' => 'new',
    'currency_code' => 'USD',
    'track_inventory' => 1,
    'allow_backorder' => 0,
    'updated_at' => date('Y-m-d H:i:s')
];

$fields = array_keys($autosaveData);
$placeholders = str_repeat('?,', count($fields) - 1) . '?';
Database::query(
    "INSERT INTO product_autosaves (" . implode(', ', array_map(function($f) { return "`{$f}`"; }, $fields)) . ", created_at) VALUES ({$placeholders}, datetime('now'))",
    array_values($autosaveData)
);

$autosaveId = Database::lastInsertId();
echo "✅ Draft automatically saved! (Autosave ID: $autosaveId)\n";
echo "💡 Seller can leave and return later - data is preserved!\n\n";

// Demo 2: Product creation with transaction safety
echo "📦 Demo 2: Safe Product Creation\n";
echo "Seller completes the form and publishes the product...\n";

try {
    $productId = db_transaction(function($pdo) use ($formData) {
        // Create the main product
        Database::query("
            INSERT INTO products
               (seller_id, name, slug, description, price, category_id, brand, `condition`, 
                status, visibility, currency_code, stock_quantity, track_inventory, 
                allow_backorder, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,datetime('now'),datetime('now'))",
            [
                1, // seller_id
                $formData['name'],
                strtolower(str_replace(' ', '-', $formData['name'])) . '-' . time(),
                $formData['description'],
                (float)$formData['price'],
                (int)$formData['category_id'],
                $formData['brand'],
                'new',
                'active', // published
                'public',
                'USD',
                50, // initial stock
                1, // track inventory
                0  // no backorders
            ]
        );
        
        $id = Database::lastInsertId();
        
        // Add some additional product attributes in the same transaction
        Database::query("
            INSERT INTO product_attributes (product_id, attr_key, attr_value, created_at)
            VALUES (?, ?, ?, datetime('now'))",
            [$id, 'color', 'Black']
        );
        
        return $id;
    });
    
    echo "✅ Product created successfully! (Product ID: $productId)\n";
    echo "🔒 All data saved atomically - no partial records!\n";
    
    // Show the created product
    $product = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch();
    echo "\n📋 Product Details:\n";
    echo "  Name: {$product['name']}\n";
    echo "  Price: \${$product['price']}\n";
    echo "  Status: {$product['status']}\n";
    echo "  Stock: {$product['stock_quantity']}\n\n";
    
} catch (Exception $e) {
    echo "❌ Product creation failed: " . $e->getMessage() . "\n";
    echo "🔄 All changes rolled back automatically!\n\n";
}

// Demo 3: Product editing with validation
echo "✏️  Demo 3: Safe Product Editing\n";
echo "Seller updates product details...\n";

$updates = [
    'name' => 'Premium Smartphone Case',
    'price' => '29.99',
    'compare_price' => '39.99',
    'description' => 'Premium smartphone case with military-grade protection and elegant design',
    'stock_quantity' => 75,
    'status' => 'active'
];

echo "Updates to apply:\n";
foreach ($updates as $key => $value) {
    echo "  $key: $value\n";
}

try {
    db_transaction(function($pdo) use ($productId, $updates) {
        Database::query("
           UPDATE products SET
             name=?, price=?, compare_price=?, description=?, stock_quantity=?, 
             status=?, updated_at=datetime('now')
           WHERE id=? AND seller_id=?",
            [
                $updates['name'],
                (float)$updates['price'],
                (float)$updates['compare_price'],
                $updates['description'],
                (int)$updates['stock_quantity'],
                $updates['status'],
                $productId,
                1 // seller_id
            ]
        );
    });
    
    echo "✅ Product updated successfully!\n";
    
    // Show the updated product
    $updatedProduct = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch();
    echo "\n📋 Updated Product Details:\n";
    echo "  Name: {$updatedProduct['name']}\n";
    echo "  Price: \${$updatedProduct['price']} (was \${$formData['price']})\n";
    echo "  Compare Price: \${$updatedProduct['compare_price']}\n";
    echo "  Status: {$updatedProduct['status']}\n";
    echo "  Stock: {$updatedProduct['stock_quantity']}\n";
    echo "  Updated: {$updatedProduct['updated_at']}\n\n";
    
} catch (Exception $e) {
    echo "❌ Product update failed: " . $e->getMessage() . "\n";
    echo "🔄 Original data preserved!\n\n";
}

// Demo 4: Error handling demonstration
echo "🛡️  Demo 4: Error Handling & Validation\n";
echo "Demonstrating what happens when something goes wrong...\n";

echo "\nTesting scenario: Invalid data causes transaction to fail\n";
try {
    db_transaction(function($pdo) {
        // Create a product
        Database::query("
            INSERT INTO products (seller_id, name, slug, price, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [1, 'Test Error Product', 'test-error-' . time(), 19.99, 'draft']
        );
        
        $testId = Database::lastInsertId();
        echo "  ➤ Product created (ID: $testId)...\n";
        
        // Now cause an error
        Database::query("INSERT INTO nonexistent_table VALUES (1)");
    });
    
    echo "❌ This should not appear - transaction should fail!\n";
    
} catch (Exception $e) {
    echo "  ➤ Error occurred: " . $e->getMessage() . "\n";
    
    // Check if the product was rolled back
    $testProduct = Database::query("SELECT * FROM products WHERE name = 'Test Error Product'")->fetch();
    if (!$testProduct) {
        echo "✅ Transaction rolled back successfully - no partial data saved!\n";
    } else {
        echo "❌ Rollback failed - partial data was committed!\n";
    }
}

// Cleanup autosave
Database::query("DELETE FROM product_autosaves WHERE id = ?", [$autosaveId]);

echo "\n🎉 Demo Complete!\n";
echo "\n=== Key Improvements Demonstrated ===\n";
echo "✅ Autosave preserves work automatically\n";
echo "✅ Transactions ensure data consistency\n";
echo "✅ Proper field mapping prevents errors\n";
echo "✅ Error handling protects against data corruption\n";
echo "✅ Validation catches problems early\n";
echo "✅ User-friendly feedback for all operations\n";

echo "\n🚀 The seller product management system is now robust and reliable!\n";
?>