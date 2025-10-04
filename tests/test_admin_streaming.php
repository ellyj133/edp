<?php
/**
 * Admin Streaming Dashboard - Test Script
 * Verify implementation and database connectivity
 */

require_once __DIR__ . '/../includes/init.php';

echo "=== Admin Streaming Dashboard Test Script ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    $pdo = db();
    echo "   ✓ Database connected successfully\n\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check Required Tables
echo "2. Checking Required Tables...\n";
$requiredTables = [
    'live_streams',
    'stream_viewers',
    'stream_orders',
    'stream_interactions',
    'vendors',
    'vendor_settings',
    'system_settings'
];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        echo "   ✓ Table '$table' exists\n";
    } catch (Exception $e) {
        echo "   ✗ Table '$table' missing or inaccessible\n";
    }
}
echo "\n";

// Test 3: Check Model Classes
echo "3. Checking Model Classes...\n";
$requiredClasses = [
    'LiveStream',
    'StreamViewer',
    'StreamOrder',
    'StreamInteraction',
    'SavedStream'
];

foreach ($requiredClasses as $class) {
    if (class_exists($class)) {
        echo "   ✓ Class '$class' loaded\n";
    } else {
        echo "   ✗ Class '$class' not found\n";
    }
}
echo "\n";

// Test 4: Test LiveStream Model Methods
echo "4. Testing LiveStream Model...\n";
try {
    $liveStream = new LiveStream();
    
    // Test getActiveStreams
    $streams = $liveStream->getActiveStreams(5);
    echo "   ✓ getActiveStreams() works - returned " . count($streams) . " streams\n";
    
} catch (Exception $e) {
    echo "   ✗ LiveStream model error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Check Vendors
echo "5. Checking Vendors...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vendors");
    $result = $stmt->fetch();
    $vendorCount = $result['count'];
    echo "   ✓ Found $vendorCount vendor(s) in database\n";
    
    if ($vendorCount === 0) {
        echo "   ⚠ Warning: No vendors found. You may need to create vendors for testing.\n";
    }
} catch (Exception $e) {
    echo "   ✗ Vendor check failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Check Streams
echo "6. Checking Streams...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM live_streams");
    $result = $stmt->fetch();
    $streamCount = $result['count'];
    echo "   ✓ Found $streamCount stream(s) in database\n";
    
    if ($streamCount === 0) {
        echo "   ⚠ Warning: No streams found. Dashboard will show empty state.\n";
    }
    
    // Check by status
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM live_streams 
        GROUP BY status
    ");
    $statusCounts = $stmt->fetchAll();
    
    if (!empty($statusCounts)) {
        echo "   Stream status breakdown:\n";
        foreach ($statusCounts as $status) {
            echo "      - {$status['status']}: {$status['count']}\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Stream check failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Test Statistics Query
echo "7. Testing Statistics Query...\n";
try {
    $stats = [];
    
    // Total streams
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM live_streams");
    $stats['total_streams'] = $stmt->fetch()['count'];
    
    // Live streams
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM live_streams WHERE status = 'live'");
    $stats['live_streams'] = $stmt->fetch()['count'];
    
    // Active viewers
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT sv.id) as count 
        FROM stream_viewers sv
        JOIN live_streams ls ON sv.stream_id = ls.id
        WHERE sv.is_active = 1 AND ls.status = 'live'
    ");
    $stats['total_viewers'] = $stmt->fetch()['count'];
    
    // Total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM stream_orders");
    $stats['total_revenue'] = $stmt->fetch()['total'];
    
    echo "   ✓ Statistics calculated successfully:\n";
    echo "      - Total Streams: {$stats['total_streams']}\n";
    echo "      - Live Streams: {$stats['live_streams']}\n";
    echo "      - Current Viewers: {$stats['total_viewers']}\n";
    echo "      - Total Revenue: $" . number_format($stats['total_revenue'], 2) . "\n";
} catch (Exception $e) {
    echo "   ✗ Statistics query failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 8: Check API Files
echo "8. Checking API Endpoint Files...\n";
$apiFiles = [
    'list.php',
    'stats.php',
    'control.php',
    'schedule.php',
    'stream-key.php',
    'export.php',
    'moderation.php',
    'settings.php',
    'vendors.php'
];

$apiDir = __DIR__ . '/../api/admin/streams/';
foreach ($apiFiles as $file) {
    $path = $apiDir . $file;
    if (file_exists($path)) {
        echo "   ✓ API file '$file' exists\n";
    } else {
        echo "   ✗ API file '$file' missing\n";
    }
}
echo "\n";

// Test 9: Check Admin Page
echo "9. Checking Admin Page...\n";
$adminPage = __DIR__ . '/../admin/streaming/index.php';
if (file_exists($adminPage)) {
    echo "   ✓ Admin streaming page exists\n";
    
    // Check for key elements in the file
    $content = file_get_contents($adminPage);
    $checks = [
        'streamsTable' => strpos($content, 'id="streamsTable"') !== false,
        'applyFilters' => strpos($content, 'id="applyFilters"') !== false,
        'scheduleStreamModal' => strpos($content, 'id="scheduleStreamModal"') !== false,
        'streamSettingsModal' => strpos($content, 'id="streamSettingsModal"') !== false,
        'moderationModal' => strpos($content, 'id="moderationModal"') !== false,
    ];
    
    foreach ($checks as $element => $exists) {
        if ($exists) {
            echo "   ✓ Element '$element' found\n";
        } else {
            echo "   ✗ Element '$element' missing\n";
        }
    }
} else {
    echo "   ✗ Admin streaming page missing\n";
}
echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "All critical components have been checked.\n";
echo "If any tests failed, review the error messages above.\n";
echo "\n";
echo "To fully test the dashboard:\n";
echo "1. Ensure database is populated with test data\n";
echo "2. Log in as admin user\n";
echo "3. Navigate to /admin/streaming/\n";
echo "4. Test all filters and actions\n";
echo "\n";
echo "Documentation:\n";
echo "- Feature docs: /docs/ADMIN_STREAMING_DASHBOARD.md\n";
echo "- Implementation summary: /docs/ADMIN_STREAMING_IMPLEMENTATION_SUMMARY.md\n";
echo "\n";

echo "Test completed!\n";
