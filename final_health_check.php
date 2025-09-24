<?php
/**
 * Final Site Health Check
 * Comprehensive validation of all components
 */

// Set up proper environment
$_ENV['APP_ENV'] = 'development';
$_ENV['USE_SQLITE'] = 'true';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';

require_once __DIR__ . '/includes/init.php';

echo "=== FINAL SITE HEALTH CHECK ===\n\n";

$checks = [];
$issues = [];

// 1. Database connectivity and data
echo "1. Database & Data Health:\n";
try {
    $db = db();
    $checks['database'] = '✅ Connected';
    
    // Check essential tables and data
    $tables = ['products', 'categories', 'users', 'vendors'];
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetchColumn();
        $checks["table_$table"] = $count > 0 ? "✅ $count records" : "⚠️  Empty ($count records)";
    }
    
} catch (Exception $e) {
    $checks['database'] = '❌ Failed: ' . $e->getMessage();
    $issues[] = 'Database connection issue';
}

// 2. Core classes and methods
echo "2. Core Classes & Methods:\n";
$classes = ['Product', 'Category', 'User', 'Cart', 'BaseModel'];
foreach ($classes as $class) {
    if (class_exists($class)) {
        $checks["class_$class"] = '✅ Available';
        
        // Test Product methods specifically
        if ($class === 'Product') {
            try {
                $product = new Product();
                $methods = ['findAll', 'getFeatured', 'getLatest', 'getRandomProducts', 'search'];
                foreach ($methods as $method) {
                    if (method_exists($product, $method)) {
                        $checks["method_$method"] = '✅ Available';
                    } else {
                        $checks["method_$method"] = '❌ Missing';
                        $issues[] = "Product::$method method missing";
                    }
                }
            } catch (Exception $e) {
                $issues[] = "Product class instantiation failed: " . $e->getMessage();
            }
        }
    } else {
        $checks["class_$class"] = '❌ Missing';
        $issues[] = "$class class missing";
    }
}

// 3. Asset files
echo "3. Asset Files:\n";
$assets = [
    'css/styles.css' => 'Main stylesheet',
    'assets/css/base.css' => 'Base CSS',
    'js/fezamarket.js' => 'Main JS',
    'assets/js/ui.js' => 'UI JS',
    'images/favicon.ico' => 'Favicon'
];

foreach ($assets as $file => $desc) {
    if (file_exists($file)) {
        $size = filesize($file);
        $checks["asset_$file"] = $size > 0 ? "✅ Present ($size bytes)" : "⚠️  Empty file";
    } else {
        $checks["asset_$file"] = '❌ Missing';
        $issues[] = "Missing asset: $file ($desc)";
    }
}

// 4. Critical directories
echo "4. Directory Structure:\n";
$dirs = ['uploads', 'uploads/products', 'uploads/vendors', 'images', 'images/banners', 'css', 'js'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $checks["dir_$dir"] = is_writable($dir) ? '✅ Writable' : '⚠️  Not writable';
        if (!is_writable($dir)) {
            $issues[] = "Directory not writable: $dir";
        }
    } else {
        $checks["dir_$dir"] = '❌ Missing';
        $issues[] = "Missing directory: $dir";
    }
}

// 5. Configuration
echo "5. Configuration:\n";
$configs = [
    'USE_SQLITE' => defined('USE_SQLITE') && USE_SQLITE,
    'DEBUG_MODE' => defined('DEBUG_MODE') && DEBUG_MODE,
    'SECRET_KEY' => defined('SECRET_KEY') && SECRET_KEY !== 'your-secret-key-change-this-in-production-minimum-32-chars'
];

foreach ($configs as $config => $value) {
    $checks["config_$config"] = $value ? '✅ Set' : '⚠️  Default/Missing';
}

// Display results
echo "\n=== HEALTH CHECK RESULTS ===\n\n";

foreach ($checks as $item => $status) {
    echo sprintf("%-30s %s\n", ucfirst(str_replace('_', ' ', $item)), $status);
}

echo "\n=== SUMMARY ===\n\n";
if (empty($issues)) {
    echo "🎉 EXCELLENT! All health checks passed.\n";
    echo "The site is fully functional with no critical issues found.\n\n";
} else {
    echo "⚠️  Issues found that should be addressed:\n\n";
    foreach ($issues as $issue) {
        echo "- $issue\n";
    }
    echo "\nNote: Some issues may be warnings and not critical for functionality.\n\n";
}

// Final functionality test
echo "=== FINAL FUNCTIONALITY TEST ===\n\n";
echo "Testing core page loads...\n";

$test_pages = ['deals.php', 'products.php', 'category.php'];
$working_pages = 0;

foreach ($test_pages as $page) {
    try {
        ob_start();
        $_SERVER['SCRIPT_NAME'] = "/$page";
        include $page;
        $output = ob_get_clean();
        
        if (strlen($output) > 1000 && strpos($output, 'Fatal error') === false) {
            echo "✅ $page - Working correctly\n";
            $working_pages++;
        } else {
            echo "⚠️  $page - Short output or errors\n";
        }
    } catch (Exception $e) {
        echo "❌ $page - Error: " . $e->getMessage() . "\n";
    }
}

echo "\nFunctionality Summary: $working_pages/" . count($test_pages) . " critical pages working\n";

echo "\n=== HEALTH CHECK COMPLETE ===\n";