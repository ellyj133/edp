<?php
/**
 * Sample Data Creator for Testing Hero Banner and Products
 */

require_once __DIR__ . '/includes/init.php';

try {
    $pdo = db();
    
    // Create sample hero banner if none exists
    $check_sql = "SELECT COUNT(*) FROM homepage_banners WHERE position = 'hero' AND status = 'active'";
    $count = $pdo->query($check_sql)->fetchColumn();
    
    if ($count == 0) {
        echo "Creating sample hero banner...\n";
        $insert_sql = "INSERT INTO homepage_banners (title, subtitle, description, image_url, link_url, button_text, position, status, created_by, created_at, updated_at) 
                       VALUES (?, ?, ?, ?, ?, ?, 'hero', 'active', 1, NOW(), NOW())";
        $stmt = $pdo->prepare($insert_sql);
        $stmt->execute([
            'Welcome to FezaMarket',
            'Save Money. Live Better.',
            'Discover amazing deals on everything you need. Free shipping on orders over $35.',
            'https://picsum.photos/1200/400?random=hero',
            '/deals',
            'Shop Now'
        ]);
        echo "Hero banner created!\n";
    } else {
        echo "Hero banner already exists.\n";
    }
    
    // Check if we have any products
    $check_products_sql = "SELECT COUNT(*) FROM products WHERE status = 'active'";
    $product_count = $pdo->query($check_products_sql)->fetchColumn();
    
    if ($product_count == 0) {
        echo "Creating sample products...\n";
        
        // Create sample categories first
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Latest electronics and gadgets'],
            ['name' => 'Fashion', 'slug' => 'fashion', 'description' => 'Trending fashion and apparel'],
            ['name' => 'Home & Garden', 'slug' => 'home-garden', 'description' => 'Home improvement and gardening'],
            ['name' => 'Furniture', 'slug' => 'furniture', 'description' => 'Quality furniture for your home']
        ];
        
        foreach ($categories as $i => $category) {
            $cat_id = $i + 1;
            $check_cat = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
            $check_cat->execute([$cat_id]);
            if ($check_cat->fetchColumn() == 0) {
                $cat_sql = "INSERT INTO categories (id, name, slug, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', NOW(), NOW())";
                $cat_stmt = $pdo->prepare($cat_sql);
                $cat_stmt->execute([$cat_id, $category['name'], $category['slug'], $category['description']]);
                echo "Created category: {$category['name']}\n";
            }
        }
        
        // Create sample products
        $products = [
            ['name' => 'Wireless Bluetooth Headphones', 'price' => 59.99, 'compare_price' => 89.99, 'category_id' => 1, 'slug' => 'bluetooth-headphones'],
            ['name' => 'Smart Fitness Watch', 'price' => 199.99, 'compare_price' => 299.99, 'category_id' => 1, 'slug' => 'fitness-watch'],
            ['name' => 'Women\'s Casual Dress', 'price' => 34.99, 'compare_price' => 49.99, 'category_id' => 2, 'slug' => 'casual-dress'],
            ['name' => 'Men\'s Polo Shirt', 'price' => 24.99, 'compare_price' => 39.99, 'category_id' => 2, 'slug' => 'polo-shirt'],
            ['name' => 'Garden Tool Set', 'price' => 79.99, 'compare_price' => 119.99, 'category_id' => 3, 'slug' => 'garden-tools'],
            ['name' => 'Indoor Plant Collection', 'price' => 49.99, 'compare_price' => 69.99, 'category_id' => 3, 'slug' => 'indoor-plants'],
            ['name' => 'Modern Office Chair', 'price' => 149.99, 'compare_price' => 199.99, 'category_id' => 4, 'slug' => 'office-chair'],
            ['name' => 'Coffee Table Set', 'price' => 299.99, 'compare_price' => 399.99, 'category_id' => 4, 'slug' => 'coffee-table']
        ];
        
        foreach ($products as $product) {
            $prod_sql = "INSERT INTO products (name, description, price, compare_price, image_url, slug, category_id, stock_quantity, status, created_by, created_at, updated_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, NOW(), NOW())";
            $prod_stmt = $pdo->prepare($prod_sql);
            $prod_stmt->execute([
                $product['name'],
                "High quality {$product['name']} with excellent features and warranty.",
                $product['price'],
                $product['compare_price'],
                'https://picsum.photos/400/400?random=' . rand(1, 1000),
                $product['slug'],
                $product['category_id'],
                rand(10, 100)
            ]);
            echo "Created product: {$product['name']}\n";
        }
        
        echo "Sample products created!\n";
    } else {
        echo "Products already exist ($product_count found).\n";
    }
    
    echo "Sample data setup complete!\n";
    
} catch (Exception $e) {
    echo "Error creating sample data: " . $e->getMessage() . "\n";
}
?>