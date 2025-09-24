#!/usr/bin/env php
<?php
/**
 * Test MySQL Database Connection
 * E-Commerce Platform
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

echo "=== Testing MySQL/MariaDB Connection ===\n\n";

// Test configuration
echo "Database Configuration:\n";
echo "- Host: " . DB_HOST . "\n";
echo "- Database: " . DB_NAME . "\n";
echo "- User: " . DB_USER . "\n";
echo "- Charset: " . DB_CHARSET . "\n";
echo "- SQLite Mode: " . (USE_SQLITE ? 'Enabled' : 'Disabled') . "\n\n";

// Test PDO MySQL extension
if (!extension_loaded('pdo_mysql')) {
    echo "❌ PDO MySQL extension not found!\n";
    echo "Install it with: sudo apt install php-mysql\n";
    exit(1);
}
echo "✓ PDO MySQL extension is loaded\n";

// Test database connection
try {
    $pdo = db();
    echo "✓ Connected to MySQL server\n";
    
    // Test database existence
    try {
        $pdo->exec("USE `" . DB_NAME . "`");
        echo "✓ Database '" . DB_NAME . "' exists and accessible\n";
        
        // Test if tables exist
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "✓ Found " . count($tables) . " tables\n";
            
            // Check key tables
            $requiredTables = ['users', 'products', 'orders', 'user_activities'];
            $missingTables = [];
            
            foreach ($requiredTables as $table) {
                if (!in_array($table, $tables)) {
                    $missingTables[] = $table;
                }
            }
            
            if (empty($missingTables)) {
                echo "✓ All required tables found\n";
                
                // Test admin user
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                $stmt->execute();
                $adminCount = $stmt->fetchColumn();
                
                if ($adminCount > 0) {
                    echo "✓ Admin user exists\n";
                    echo "\n=== Connection Test PASSED ===\n";
                    echo "Your database is ready for use!\n\n";
                    echo "Default Admin Credentials:\n";
                    echo "Email: admin@ecommerce.com\n";
                    echo "Password: admin123\n";
                } else {
                    echo "⚠️  No admin user found\n";
                    echo "Run: php setup_database.php\n";
                }
            } else {
                echo "❌ Missing tables: " . implode(', ', $missingTables) . "\n";
                echo "Run: php setup_database.php\n";
            }
        } else {
            echo "⚠️  No tables found in database\n";
            echo "Run: php setup_database.php\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ Database '" . DB_NAME . "' not found or not accessible\n";
        echo "Create it with: CREATE DATABASE " . DB_NAME . ";\n";
        echo "Or run: php setup_database.php\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n\n";
    echo "Troubleshooting:\n";
    echo "1. Check if MySQL/MariaDB is running\n";
    echo "2. Verify credentials in config/config.php\n";
    echo "3. Ensure user has database privileges\n";
    exit(1);
}

// Test the Database class
echo "\n=== Testing Database Class ===\n";
try {
    require_once __DIR__ . '/includes/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "✓ Database class initialization successful\n";
    
    // Test a simple query
    $stmt = $conn->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result['test'] == 1) {
        echo "✓ Database query execution works\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database class error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>