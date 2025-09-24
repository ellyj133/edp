<?php
/**
 * Simple SQLite Database Setup for Testing Seller Functionality
 */

echo "Setting up SQLite database for testing...\n";

// Create SQLite database
$dbPath = __DIR__ . '/test_ecommerce.db';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create essential tables for seller functionality
$sql = "
-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table  
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120),
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table (simplified version)
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    seller_id INTEGER,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(275),
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2),
    cost_price DECIMAL(10,2),
    category_id INTEGER,
    brand VARCHAR(100),
    sku VARCHAR(100),
    stock_quantity INTEGER DEFAULT 0,
    low_stock_threshold INTEGER DEFAULT 5,
    status VARCHAR(20) DEFAULT 'draft',
    visibility VARCHAR(20) DEFAULT 'public',
    track_inventory INTEGER DEFAULT 1,
    allow_backorder INTEGER DEFAULT 0,
    condition VARCHAR(20) DEFAULT 'new',
    currency_code VARCHAR(3) DEFAULT 'USD',
    tags TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Product images table
CREATE TABLE IF NOT EXISTS product_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    file_path VARCHAR(500),
    alt_text VARCHAR(255),
    is_primary INTEGER DEFAULT 0,
    sort INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Product variants table
CREATE TABLE IF NOT EXISTS product_variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    option_name VARCHAR(100),
    option_value VARCHAR(100), 
    sku VARCHAR(100),
    price DECIMAL(10,2),
    stock INTEGER,
    active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
";

$pdo->exec($sql);

// Insert sample data
$pdo->exec("
INSERT OR IGNORE INTO users (id, username, email, password, role) VALUES 
(1, 'seller1', 'seller@test.com', 'hashed_password', 'seller'),
(2, 'admin', 'admin@test.com', 'hashed_password', 'admin');

INSERT OR IGNORE INTO categories (id, name, slug) VALUES 
(1, 'Electronics', 'electronics'),
(2, 'Clothing', 'clothing'),
(3, 'Books', 'books');
");

echo "✓ SQLite database created: $dbPath\n";
echo "✓ Tables created: users, categories, products, product_images, product_variants\n";
echo "✓ Sample data inserted\n";

// Test the connection
$stmt = $pdo->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
$result = $stmt->fetch();
echo "✓ Total tables created: " . $result['count'] . "\n";

// Test inserting a product
try {
    $stmt = $pdo->prepare("INSERT INTO products (seller_id, name, slug, price, category_id, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'Test Product', 'test-product', 19.99, 1, 'draft']);
    $productId = $pdo->lastInsertId();
    echo "✓ Test product created with ID: $productId\n";
} catch (Exception $e) {
    echo "❌ Error creating test product: " . $e->getMessage() . "\n";
}

echo "\nDatabase setup complete! Path: $dbPath\n";
?>