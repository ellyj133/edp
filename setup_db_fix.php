<?php
/**
 * Database Configuration Fix
 * Creates a fallback database configuration when main database is unavailable
 */

// Helper function to test database connectivity
function testDatabaseConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // Test with a simple query
        $pdo->query("SELECT 1");
        return ['status' => 'success', 'connection' => $pdo];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Create demo admin user in the users table if it doesn't exist
function createDemoAdminUser($pdo) {
    try {
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute(['admin@test.com']);
        
        if ($stmt->rowCount() == 0) {
            // Create demo admin user
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, pass_hash, first_name, last_name, role, status, verified_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt->execute([
                'admin',
                'admin@test.com', 
                $hashedPassword,
                'System',
                'Administrator',
                'admin',
                'active'
            ]);
            
            return $pdo->lastInsertId();
        } else {
            return $stmt->fetch()['id'];
        }
    } catch (Exception $e) {
        error_log("Failed to create demo admin user: " . $e->getMessage());
        return 1; // Return a default ID
    }
}

// Setup basic tables if they don't exist
function setupBasicTables($pdo) {
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `email` varchar(100) NOT NULL,
            `pass_hash` varchar(255) NOT NULL,
            `first_name` varchar(50) NOT NULL,
            `last_name` varchar(50) NOT NULL,
            `role` enum('customer','vendor','admin') NOT NULL DEFAULT 'customer',
            `status` enum('active','inactive','pending','suspended') NOT NULL DEFAULT 'active',
            `verified_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_email` (`email`),
            UNIQUE KEY `idx_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        'products' => "CREATE TABLE IF NOT EXISTS `products` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `vendor_id` int(11) NOT NULL DEFAULT 1,
            `category_id` int(11) NOT NULL DEFAULT 1,
            `name` varchar(255) NOT NULL,
            `description` text DEFAULT NULL,
            `price` decimal(10,2) NOT NULL,
            `stock_quantity` int(11) NOT NULL DEFAULT 0,
            `status` enum('active','inactive','draft') NOT NULL DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        'orders' => "CREATE TABLE IF NOT EXISTS `orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `order_number` varchar(50) NOT NULL,
            `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
            `total` decimal(10,2) NOT NULL DEFAULT 0.00,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_order_number` (`order_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        'vendors' => "CREATE TABLE IF NOT EXISTS `vendors` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `business_name` varchar(100) NOT NULL,
            `status` enum('pending','approved','suspended','rejected') NOT NULL DEFAULT 'pending',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];
    
    foreach ($tables as $name => $sql) {
        try {
            $pdo->exec($sql);
            echo "<!-- Created/verified table: $name -->\n";
        } catch (Exception $e) {
            error_log("Failed to create table $name: " . $e->getMessage());
        }
    }
}

// Insert sample data for demo
function insertSampleData($pdo) {
    try {
        // Check if we need sample data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $adminCount = $stmt->fetch()['count'];
        
        if ($adminCount == 0) {
            createDemoAdminUser($pdo);
        }
        
        // Add some sample products if empty
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $productCount = $stmt->fetch()['count'];
        
        if ($productCount == 0) {
            $sampleProducts = [
                ['Sample Product 1', 'A great sample product for testing', 29.99, 100],
                ['Sample Product 2', 'Another sample product', 19.99, 50],
                ['Sample Product 3', 'Premium sample product', 99.99, 25]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock_quantity) VALUES (?, ?, ?, ?)");
            foreach ($sampleProducts as $product) {
                $stmt->execute($product);
            }
            echo "<!-- Added sample products -->\n";
        }
        
    } catch (Exception $e) {
        error_log("Failed to insert sample data: " . $e->getMessage());
    }
}

// Main database setup function
function initializeDatabase() {
    $result = testDatabaseConnection();
    
    if ($result['status'] === 'success') {
        $pdo = $result['connection'];
        setupBasicTables($pdo);
        insertSampleData($pdo);
        return ['status' => 'success', 'message' => 'Database initialized successfully'];
    } else {
        return ['status' => 'error', 'message' => $result['message']];
    }
}

// Run database setup if this file is called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "<!DOCTYPE html><html><head><title>Database Setup</title></head><body>";
    echo "<h1>Database Setup</h1>";
    
    $result = initializeDatabase();
    if ($result['status'] === 'success') {
        echo "<p style='color: green;'>✓ " . $result['message'] . "</p>";
        echo "<p><a href='/admin_fixed.php'>Go to Admin Dashboard</a></p>";
    } else {
        echo "<p style='color: red;'>✗ Database Error: " . $result['message'] . "</p>";
        echo "<p>Please check your database configuration in .env file.</p>";
    }
    
    echo "</body></html>";
}
?>