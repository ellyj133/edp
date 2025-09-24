<?php
/**
 * Test to verify RANDOM() function has been replaced with RAND() for MySQL compatibility
 */

function testResult($testName, $passed, $message = '') {
    echo ($passed ? '✅' : '❌') . " Test: $testName";
    if ($message) echo " - $message";
    echo "\n";
    return $passed;
}

echo "=== MySQL RANDOM() Function Fix Test ===\n\n";

$allPassed = true;

// Test 1: Check that RANDOM() is no longer present in models.php
echo "Test 1: RANDOM() function removal...\n";
$modelsContent = file_get_contents(__DIR__ . '/includes/models.php');
$hasRandomFunction = strpos($modelsContent, 'RANDOM()') !== false;
testResult("RANDOM() Removed", !$hasRandomFunction, 
    !$hasRandomFunction ? "No RANDOM() functions found" : "RANDOM() still present in code"
);
if ($hasRandomFunction) $allPassed = false;

// Test 2: Check that RAND() is present in getRandomProducts method
echo "\nTest 2: RAND() function presence...\n";
$hasRandFunction = strpos($modelsContent, 'ORDER BY RAND()') !== false;
testResult("RAND() Present", $hasRandFunction,
    $hasRandFunction ? "MySQL RAND() function found" : "RAND() function missing"
);
if (!$hasRandFunction) $allPassed = false;

// Test 3: Check that the getRandomProducts method structure is intact
echo "\nTest 3: getRandomProducts method structure...\n";
$hasGetRandomProducts = strpos($modelsContent, 'public function getRandomProducts($limit = 10)') !== false;
$hasCorrectQuery = preg_match('/SELECT p\.\*, v\.business_name as vendor_name.*FROM.*WHERE p\.status = \'active\'.*ORDER BY RAND\(\).*LIMIT \?/s', $modelsContent);
testResult("Method Structure", $hasGetRandomProducts && $hasCorrectQuery,
    ($hasGetRandomProducts && $hasCorrectQuery) ? "Method structure preserved" : "Method structure damaged"
);
if (!$hasGetRandomProducts || !$hasCorrectQuery) $allPassed = false;

// Test 4: Verify no other SQLite functions remain
echo "\nTest 4: Other SQLite function check...\n";
$sqliteFunctions = ['RANDOM()', 'datetime(', 'strftime('];
$foundSqliteFunctions = [];
foreach ($sqliteFunctions as $func) {
    if (strpos($modelsContent, $func) !== false) {
        $foundSqliteFunctions[] = $func;
    }
}
$noSqliteFunctions = empty($foundSqliteFunctions);
testResult("SQLite Functions Check", $noSqliteFunctions,
    $noSqliteFunctions ? "No SQLite functions found" : "Found SQLite functions: " . implode(', ', $foundSqliteFunctions)
);
if (!$noSqliteFunctions) $allPassed = false;

echo "\n=== Test Summary ===\n";
if ($allPassed) {
    echo "🎉 ALL TESTS PASSED! 🎉\n\n";
    echo "✅ RANDOM() function replaced with RAND()\n";
    echo "✅ getRandomProducts() method now uses MySQL syntax\n";
    echo "✅ Method structure preserved\n";
    echo "✅ No other SQLite functions detected\n\n";
    echo "The SQL syntax error should now be resolved!\n";
} else {
    echo "❌ Some tests failed. Please review the issues above.\n";
}

exit($allPassed ? 0 : 1);
?>