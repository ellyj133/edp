<?php
/**
 * Setup Vendor Test Data
 */

require_once 'includes/init.php';

try {
    $pdo = db();
    echo "Connected to database successfully\n";

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'customer',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create vendors table
    $pdo->exec("CREATE TABLE IF NOT EXISTS vendors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        business_name VARCHAR(100) NOT NULL,
        business_type VARCHAR(50) DEFAULT 'individual',
        status VARCHAR(20) DEFAULT 'pending',
        approved_at DATETIME,
        approved_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert admin user
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO users (id, username, email, password, role, created_at) 
                          VALUES (1, 'admin', 'admin@test.com', ?, 'admin', datetime('now'))");
    $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);

    // Insert vendor users
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO users (id, username, email, password, role, created_at) 
                          VALUES (?, ?, ?, ?, 'vendor', datetime('now'))");
    
    $stmt->execute([2, 'vendor1', 'vendor1@test.com', password_hash('vendor123', PASSWORD_DEFAULT)]);
    $stmt->execute([3, 'vendor2', 'vendor2@test.com', password_hash('vendor123', PASSWORD_DEFAULT)]);
    $stmt->execute([4, 'vendor3', 'vendor3@test.com', password_hash('vendor123', PASSWORD_DEFAULT)]);

    // Clear and insert vendor applications
    $pdo->exec("DELETE FROM vendors");
    
    $stmt = $pdo->prepare("INSERT INTO vendors (user_id, business_name, business_type, status, created_at) 
                          VALUES (?, ?, ?, ?, ?)");
    
    // Pending applications
    $stmt->execute([2, 'Johns Electronics', 'business', 'pending', date('Y-m-d H:i:s')]);
    $stmt->execute([4, 'Bobs Hardware Store', 'business', 'pending', date('Y-m-d H:i:s', strtotime('-2 days'))]);
    
    // Approved application
    $stmt = $pdo->prepare("INSERT INTO vendors (user_id, business_name, business_type, status, approved_at, approved_by, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([3, 'Janes Boutique', 'business', 'approved', date('Y-m-d H:i:s'), 1, date('Y-m-d H:i:s', strtotime('-7 days'))]);

    echo "Test data created successfully\n";

    // Verify data
    $vendors = $pdo->query("SELECT v.*, u.username, u.email FROM vendors v JOIN users u ON v.user_id = u.id ORDER BY v.created_at DESC")->fetchAll();
    echo "Vendors in database:\n";
    foreach ($vendors as $vendor) {
        echo "- " . $vendor['username'] . " (" . $vendor['business_name'] . ") - Status: " . $vendor['status'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}