<?php
/**
 * Demo Autosave Functionality Test
 */

require_once 'test_db_config.php';

echo "Testing Autosave Endpoint...\n\n";

// Simulate a POST request to the autosave endpoint
$_POST = [
    'name' => 'Demo Autosave Product',
    'slug' => 'demo-autosave-product',
    'short_description' => 'This is a demo product for autosave testing',
    'description' => 'Full description of the demo product with autosave functionality',
    'price' => '49.99',
    'category_id' => '1',
    'brand' => 'Demo Brand',
    'condition' => 'new',
    'currency_code' => 'USD',
    'stock_qty' => '25',
    'track_inventory' => '1',
    'allow_backorder' => '0',
    '_autosave' => '1'
];

$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock the Session class for this test
class MockSession {
    public static function isLoggedIn() { return true; }
    public static function getUserId() { return 1; }
}

// Replace Session class temporarily
if (class_exists('Session')) {
    class_alias('Session', 'OriginalSession');
}
class_alias('MockSession', 'Session');

echo "Simulating autosave request...\n";
echo "Product name: {$_POST['name']}\n";
echo "Price: \${$_POST['price']}\n";

// Capture output from autosave.php
ob_start();
try {
    // Include the autosave endpoint
    include 'seller/products/autosave.php';
    $output = ob_get_clean();
    
    $response = json_decode($output, true);
    
    if ($response && $response['success']) {
        echo "✅ Autosave successful!\n";
        echo "Autosave ID: {$response['autosave_id']}\n";
        echo "Timestamp: {$response['timestamp']}\n";
        echo "Message: {$response['message']}\n";
        
        // Verify the autosave was actually saved
        $saved = Database::query("SELECT * FROM product_autosaves WHERE id = ?", [$response['autosave_id']])->fetch(PDO::FETCH_ASSOC);
        
        if ($saved) {
            echo "\n✅ Verification successful:\n";
            echo "- Saved name: {$saved['name']}\n";
            echo "- Saved price: \${$saved['price']}\n";
            echo "- Seller ID: {$saved['seller_id']}\n";
        } else {
            echo "\n❌ Verification failed - autosave record not found\n";
        }
        
    } else {
        echo "❌ Autosave failed: " . ($response['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Autosave test failed: " . $e->getMessage() . "\n";
}

echo "\nAutosave endpoint test complete!\n";
?>