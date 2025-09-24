<?php
/**
 * Test and Demo the E-Commerce Platform
 * Creates sample data for demonstration
 */

require_once __DIR__ . '/includes/init.php';

try {
    echo "Setting up E-Commerce Platform demo...\n\n";
    
    // Set up the database
    if (defined('USE_SQLITE') && USE_SQLITE) {
        $schema = file_get_contents(__DIR__ . '/database/schema_sqlite.sql');
    } else {
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    }
    
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    $db = Database::getInstance()->getConnection();
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }
    
    echo "✓ Database schema created\n";
    
    // Create sample vendors
    $vendor = new Vendor();
    $user = new User();
    
    // Create vendor users
    $vendorUsers = [
        [
            'username' => 'techstore',
            'email' => 'vendor1@example.com',
            'password' => 'vendor123',
            'first_name' => 'Tech',
            'last_name' => 'Store',
            'role' => 'vendor'
        ],
        [
            'username' => 'fashionhub',
            'email' => 'vendor2@example.com',
            'password' => 'vendor123',
            'first_name' => 'Fashion',
            'last_name' => 'Hub',
            'role' => 'vendor'
        ]
    ];
    
    $vendorIds = [];
    foreach ($vendorUsers as $userData) {
        // Check if user already exists
        $existingUser = $user->findByEmail($userData['email']);
        if ($existingUser) {
            $userId = $existingUser['id'];
        } else {
            $userId = $user->register($userData);
        }
        
        $existingVendor = $vendor->findByUserId($userId);
        if (!$existingVendor) {
            $vendorId = $vendor->createVendorApplication($userId, [
                'business_name' => $userData['first_name'] . ' ' . $userData['last_name'],
                'business_description' => 'Quality products from ' . $userData['first_name'] . ' ' . $userData['last_name'],
                'business_address' => '123 Business St, Commerce City, CC 12345'
            ]);
            
            // Auto-approve vendors for demo
            $vendor->approve($vendorId);
            $vendorIds[] = $vendorId;
        } else {
            $vendorIds[] = $existingVendor['id'];
        }
    }
    
    echo "✓ Sample vendors created and approved\n";
    
    // Create sample products
    $product = new Product();
    
    // Check if products already exist
    $existingProducts = $product->findAll(1);
    if (empty($existingProducts)) {
        $sampleProducts = [
            [
                'vendor_id' => $vendorIds[0],
                'category_id' => 1, // Electronics
                'name' => 'Smartphone Pro Max',
                'description' => 'Latest flagship smartphone with advanced features and premium design.',
                'sku' => 'PHONE-001',
                'price' => 999.99,
                'stock_quantity' => 50,
                'featured' => 1,
                'tags' => 'smartphone,mobile,electronics,tech'
            ],
            [
                'vendor_id' => $vendorIds[0],
                'category_id' => 1, // Electronics
                'name' => 'Wireless Headphones',
                'description' => 'Premium noise-canceling wireless headphones with long battery life.',
                'sku' => 'HEADPHONE-001',
                'price' => 299.99,
                'stock_quantity' => 75,
                'featured' => 1,
                'tags' => 'headphones,audio,wireless,music'
            ],
            [
                'vendor_id' => $vendorIds[1],
                'category_id' => 2, // Clothing
                'name' => 'Designer T-Shirt',
                'description' => 'Comfortable cotton t-shirt with modern design and perfect fit.',
                'sku' => 'SHIRT-001',
                'price' => 29.99,
                'stock_quantity' => 100,
                'featured' => 0,
                'tags' => 'tshirt,clothing,cotton,casual'
            ],
            [
                'vendor_id' => $vendorIds[1],
                'category_id' => 2, // Clothing
                'name' => 'Premium Jeans',
                'description' => 'High-quality denim jeans with comfortable fit and durable construction.',
                'sku' => 'JEANS-001',
                'price' => 89.99,
                'stock_quantity' => 60,
                'featured' => 1,
                'tags' => 'jeans,denim,clothing,pants'
            ],
            [
                'vendor_id' => $vendorIds[0],
                'category_id' => 3, // Home & Garden
                'name' => 'Smart Home Hub',
                'description' => 'Control all your smart devices from one central hub with voice commands.',
                'sku' => 'SMARTHUB-001',
                'price' => 149.99,
                'stock_quantity' => 30,
                'featured' => 0,
                'tags' => 'smart home,automation,IoT,tech'
            ],
            [
                'vendor_id' => $vendorIds[1],
                'category_id' => 4, // Books
                'name' => 'Complete Programming Guide',
                'description' => 'Comprehensive guide to modern programming languages and best practices.',
                'sku' => 'BOOK-001',
                'price' => 49.99,
                'stock_quantity' => 25,
                'featured' => 0,
                'tags' => 'programming,book,education,coding'
            ]
        ];
        
        foreach ($sampleProducts as $productData) {
            $productId = $product->create($productData);
            
            // Add a default image for each product
            $product->addImage($productId, '/images/placeholder-product.jpg', $productData['name'], true);
        }
        
        echo "✓ Sample products created\n";
    } else {
        echo "✓ Sample products already exist\n";
    }
    
    // Create a test customer
    $existingCustomer = $user->findByEmail('customer@example.com');
    if (!$existingCustomer) {
        $customerId = $user->register([
            'username' => 'testcustomer',
            'email' => 'customer@example.com',
            'password' => 'customer123',
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'role' => 'customer'
        ]);
        echo "✓ Test customer created\n";
    } else {
        echo "✓ Test customer already exists\n";
    }
    
    echo "\n=== DEMO ACCOUNTS ===\n";
    echo "Admin Login:\n";
    echo "  Email: admin@ecommerce.com\n";
    echo "  Password: admin123\n\n";
    
    echo "Vendor Login:\n";
    echo "  Email: vendor1@example.com\n";
    echo "  Password: vendor123\n\n";
    
    echo "Customer Login:\n";
    echo "  Email: customer@example.com\n";
    echo "  Password: customer123\n\n";
    
    echo "✓ E-Commerce Platform demo setup complete!\n";
    echo "Visit the homepage to see the platform in action.\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    if (DEBUG_MODE) {
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}
?>