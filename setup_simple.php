<?php
// Simple SQLite database initialization
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('USE_SQLITE', true);
define('SQLITE_PATH', __DIR__ . '/database/ecommerce.db');

// Create database directory if it doesn't exist
if (!is_dir(__DIR__ . '/database')) {
    mkdir(__DIR__ . '/database', 0755, true);
}

// Database connection function
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    
    $sqlitePath = SQLITE_PATH;
    $dsn = "sqlite:{$sqlitePath}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, null, null, $options);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

// Create basic tables for wallet system
$pdo = db();

// Create users table
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    pass_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Create buyers table
$pdo->exec("CREATE TABLE IF NOT EXISTS buyers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// Create buyer_wallets table
$pdo->exec("CREATE TABLE IF NOT EXISTS buyer_wallets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    buyer_id INTEGER NOT NULL,
    balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES buyers(id)
)");

// Create buyer_wallet_entries table
$pdo->exec("CREATE TABLE IF NOT EXISTS buyer_wallet_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wallet_id INTEGER NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    reference_type VARCHAR(50),
    reference_id INTEGER,
    description VARCHAR(500) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES buyer_wallets(id)
)");

// Create test admin user
$adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ($adminExists == 0) {
    $pdo->prepare("INSERT INTO users (username, email, pass_hash, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute(['admin', 'admin@example.com', password_hash('admin123', PASSWORD_DEFAULT), 'Admin', 'User', 'admin', 'active']);
}

// Create categories table
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    parent_id INTEGER,
    description TEXT,
    is_active BOOLEAN DEFAULT 1,
    status VARCHAR(20) DEFAULT 'active',
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
)");

// Create vendors table
$pdo->exec("CREATE TABLE IF NOT EXISTS vendors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    business_name VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// Create products table
$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vendor_id INTEGER NOT NULL DEFAULT 1,
    category_id INTEGER NOT NULL DEFAULT 1,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
)");

// Create cart table
$pdo->exec("CREATE TABLE IF NOT EXISTS cart (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)");

// Create orders table
$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// Create order_items table - Fix #1: Add missing order_items table
$pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    vendor_id INTEGER,
    quantity INTEGER NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100),
    product_image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
)");

// Create wishlists table - Fix #2: Add missing wishlists table
$pdo->exec("CREATE TABLE IF NOT EXISTS wishlists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)");

// Insert demo data
// Create default category
$categoryExists = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
if ($categoryExists == 0) {
    $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)")
        ->execute(['Electronics', 'electronics', 'Electronic products and gadgets']);
}

// Create test admin user
$adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ($adminExists == 0) {
    $pdo->prepare("INSERT INTO users (username, email, pass_hash, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute(['admin', 'admin@example.com', password_hash('admin123', PASSWORD_DEFAULT), 'Admin', 'User', 'admin', 'active']);
}

// Create test customer user
$customerExists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
if ($customerExists == 0) {
    $customerId = $pdo->prepare("INSERT INTO users (username, email, pass_hash, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute(['customer', 'customer@example.com', password_hash('customer123', PASSWORD_DEFAULT), 'Test', 'Customer', 'customer', 'active']);
    
    $customerId = $pdo->lastInsertId();
    
    // Create buyer record for the customer
    $pdo->prepare("INSERT INTO buyers (user_id) VALUES (?)")->execute([$customerId]);
    $buyerId = $pdo->lastInsertId();
    
    // Create wallet for the buyer
    $pdo->prepare("INSERT INTO buyer_wallets (buyer_id, balance, currency, status) VALUES (?, ?, ?, ?)")
        ->execute([$buyerId, 100.00, 'USD', 'active']);
}

// Create default vendor
$vendorExists = $pdo->query("SELECT COUNT(*) FROM vendors")->fetchColumn();
if ($vendorExists == 0) {
    $adminId = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
    $pdo->prepare("INSERT INTO vendors (user_id, business_name, status) VALUES (?, ?, ?)")
        ->execute([$adminId, 'Demo Store', 'approved']);
}

// Create sample products
$productExists = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
if ($productExists == 0) {
    $vendorId = $pdo->query("SELECT id FROM vendors LIMIT 1")->fetchColumn();
    $categoryId = $pdo->query("SELECT id FROM categories LIMIT 1")->fetchColumn();
    
    $products = [
        ['Smartphone', 'Latest model smartphone with advanced features', 599.99, 50],
        ['Laptop', 'High-performance laptop for work and gaming', 1299.99, 25],
        ['Headphones', 'Wireless noise-canceling headphones', 199.99, 100],
        ['Tablet', '10-inch tablet with stylus support', 399.99, 75]
    ];
    
    foreach ($products as $product) {
        $pdo->prepare("INSERT INTO products (vendor_id, category_id, name, description, price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$vendorId, $categoryId, $product[0], $product[1], $product[2], $product[3]]);
    }
}

// Create user_sessions table for login tracking
$pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_token VARCHAR(128) NOT NULL UNIQUE,
    csrf_token VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

echo "Database setup complete!\n";
echo "Admin user: admin@example.com / admin123\n";
echo "Customer user: customer@example.com / customer123\n";