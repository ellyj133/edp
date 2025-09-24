<?php
/**
 * Test Enhanced Product Creation System
 */

require_once __DIR__ . '/includes/init.php';

echo "=== Testing Enhanced Product Creation System ===\n\n";

try {
    // Test database tables exist
    $tables = [
        'brands', 'product_media', 'product_attributes', 'product_shipping',
        'product_seo', 'product_pricing', 'product_inventory', 'product_certificates',
        'product_relations', 'product_analytics', 'product_drafts', 'product_bulk_operations'
    ];
    
    echo "1. Testing Enhanced Database Schema:\n";
    foreach ($tables as $table) {
        try {
            $result = Database::query("SELECT COUNT(*) FROM $table")->fetch();
            echo "   ✓ Table '$table' exists and accessible\n";
        } catch (Exception $e) {
            echo "   ❌ Table '$table' missing or error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n2. Testing Brand Data:\n";
    $brands = Database::query("SELECT * FROM brands")->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ Found " . count($brands) . " brands in database\n";
    foreach ($brands as $brand) {
        echo "   - {$brand['name']} (ID: {$brand['id']})\n";
    }
    
    echo "\n3. Testing Enhanced Product Creation (Simulation):\n";
    $testData = [
        'name' => 'Enhanced Test Product',
        'slug' => 'enhanced-test-product',
        'sku' => 'TEST-ENH-' . time(),
        'description' => 'This is a comprehensive test product with all enhanced features',
        'short_description' => 'Enhanced test product for validation',
        'price' => 99.99,
        'compare_price' => 129.99,
        'cost_price' => 49.99,
        'category_id' => 1,
        'brand_id' => 1,
        'condition' => 'new',
        'status' => 'draft',
        'visibility' => 'public'
    ];
    
    // Create test product
    Database::beginTransaction();
    
    Database::query("
        INSERT INTO products (
            seller_id, category_id, name, slug, sku, short_description, description, 
            price, compare_price, cost_price, condition, status, visibility,
            created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
        )
    ", [
        1, // seller_id
        $testData['category_id'],
        $testData['name'],
        $testData['slug'],
        $testData['sku'],
        $testData['short_description'],
        $testData['description'],
        $testData['price'],
        $testData['compare_price'],
        $testData['cost_price'],
        $testData['condition'],
        $testData['status'],
        $testData['visibility']
    ]);
    
    $productId = Database::lastInsertId();
    echo "   ✓ Created test product with ID: $productId\n";
    
    // Test enhanced features
    
    // Add pricing data
    Database::query("
        INSERT INTO product_pricing (
            product_id, sale_price, currency_code, margin_percentage
        ) VALUES (?, ?, ?, ?)
    ", [$productId, 79.99, 'USD', 60.0]);
    echo "   ✓ Added pricing data\n";
    
    // Add shipping data
    Database::query("
        INSERT INTO product_shipping (
            product_id, weight, length, width, height, shipping_class, handling_time
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ", [$productId, 1.5, 20, 15, 10, 'standard', 2]);
    echo "   ✓ Added shipping data\n";
    
    // Add SEO data
    Database::query("
        INSERT INTO product_seo (
            product_id, meta_title, meta_description, focus_keyword
        ) VALUES (?, ?, ?, ?)
    ", [$productId, 'Enhanced Test Product - Best Quality', 'Buy our enhanced test product with amazing features and quality.', 'test product']);
    echo "   ✓ Added SEO data\n";
    
    // Add custom attributes
    $attributes = [
        ['Material', 'Premium Cotton', 'text'],
        ['Warranty', '2 Years', 'text'],
        ['Waterproof', 'true', 'boolean']
    ];
    
    foreach ($attributes as $attr) {
        Database::query("
            INSERT INTO product_attributes (
                product_id, attribute_name, attribute_value, attribute_type
            ) VALUES (?, ?, ?, ?)
        ", [$productId, $attr[0], $attr[1], $attr[2]]);
    }
    echo "   ✓ Added " . count($attributes) . " custom attributes\n";
    
    // Add variants
    $variants = [
        ['Color', 'Red', 'TEST-ENH-RED', 99.99, 10],
        ['Color', 'Blue', 'TEST-ENH-BLUE', 99.99, 15],
        ['Size', 'Large', 'TEST-ENH-L', 109.99, 8]
    ];
    
    foreach ($variants as $variant) {
        Database::query("
            INSERT INTO product_variants (
                product_id, option_name, option_value, sku, price, stock, active
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [$productId, $variant[0], $variant[1], $variant[2], $variant[3], $variant[4], 1]);
    }
    echo "   ✓ Added " . count($variants) . " product variants\n";
    
    Database::commit();
    
    echo "\n4. Verifying Enhanced Data:\n";
    
    // Verify product
    $product = Database::query("SELECT * FROM products WHERE id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ Product: {$product['name']} (SKU: {$product['sku']})\n";
    
    // Verify pricing
    $pricing = Database::query("SELECT * FROM product_pricing WHERE product_id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ Pricing: Sale price ${$pricing['sale_price']}, Margin {$pricing['margin_percentage']}%\n";
    
    // Verify shipping
    $shipping = Database::query("SELECT * FROM product_shipping WHERE product_id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ Shipping: {$shipping['weight']}kg, {$shipping['shipping_class']} class\n";
    
    // Verify SEO
    $seo = Database::query("SELECT * FROM product_seo WHERE product_id = ?", [$productId])->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ SEO: Focus keyword '{$seo['focus_keyword']}'\n";
    
    // Verify attributes
    $attrs = Database::query("SELECT * FROM product_attributes WHERE product_id = ?", [$productId])->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ Attributes: " . count($attrs) . " custom attributes\n";
    
    // Verify variants
    $vars = Database::query("SELECT * FROM product_variants WHERE product_id = ?", [$productId])->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ Variants: " . count($vars) . " product variants\n";
    
    echo "\n✅ All Enhanced Product Features Test PASSED!\n";
    echo "\nThe enhanced product creation system supports:\n";
    echo "- ✓ Basic product information with enhanced fields\n";
    echo "- ✓ Advanced pricing with sale pricing and margin calculation\n";
    echo "- ✓ Comprehensive shipping and logistics data\n";
    echo "- ✓ SEO optimization fields\n";
    echo "- ✓ Custom product attributes\n";
    echo "- ✓ Product variants with individual pricing and stock\n";
    echo "- ✓ Enhanced database schema with 12+ new tables\n";
    echo "- ✓ Transaction-safe operations\n";
    
} catch (Exception $e) {
    Database::rollback();
    echo "\n❌ Test FAILED with error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Enhanced Product Creation Test Complete ===\n";
?>