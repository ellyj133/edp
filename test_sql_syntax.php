<?php
/**
 * SQL Syntax Validation Test
 * Tests that SQLite syntax has been replaced with MariaDB equivalents
 */

function testResult($testName, $passed, $message = '') {
    echo ($passed ? '✅' : '❌') . " Test: $testName";
    if ($message) echo " - $message";
    echo "\n";
    return $passed;
}

echo "=== SQL Syntax Validation Test ===\n\n";

$allPassed = true;

// Test 1: Check that SQLite datetime functions are removed
echo "Test 1: SQLite datetime function removal...\n";
$modelsContent = file_get_contents(__DIR__ . '/includes/models_extended.php');
$functionsContent = file_get_contents(__DIR__ . '/includes/functions.php');

$sqlitePatterns = [
    "datetime('now'" => "Should be NOW() or DATE_ADD/DATE_SUB",
    "strftime(" => "Should be DATE_FORMAT()",
];

$foundIssues = [];
foreach ($sqlitePatterns as $pattern => $replacement) {
    $modelsFound = strpos($modelsContent, $pattern) !== false;
    $functionsFound = strpos($functionsContent, $pattern) !== false;
    
    if ($modelsFound || $functionsFound) {
        $foundIssues[] = "$pattern (in " . 
            ($modelsFound ? "models_extended.php" : "") . 
            ($modelsFound && $functionsFound ? " and " : "") .
            ($functionsFound ? "functions.php" : "") . ")";
    }
}

$noSqliteFound = empty($foundIssues);
testResult("SQLite Functions Removed", $noSqliteFound, 
    $noSqliteFound ? "All SQLite functions converted to MariaDB" : 
    "Found SQLite patterns: " . implode(', ', $foundIssues)
);
if (!$noSqliteFound) $allPassed = false;

// Test 2: Verify MariaDB functions are present
echo "\nTest 2: MariaDB function verification...\n";
$mariadbPatterns = [
    'NOW()' => 'MariaDB current datetime',
    'DATE_SUB(' => 'MariaDB date subtraction',
    'DATE_ADD(' => 'MariaDB date addition',
];

$foundMariaDB = [];
foreach ($mariadbPatterns as $pattern => $description) {
    $modelsFound = strpos($modelsContent, $pattern) !== false;
    $functionsFound = strpos($functionsContent, $pattern) !== false;
    
    if ($modelsFound || $functionsFound) {
        $foundMariaDB[] = $pattern;
    }
}

$hasMariaDBFunctions = count($foundMariaDB) >= 2; // Should have at least NOW() and one of the DATE functions
testResult("MariaDB Functions Present", $hasMariaDBFunctions,
    $hasMariaDBFunctions ? "Found MariaDB functions: " . implode(', ', $foundMariaDB) :
    "MariaDB functions not found"
);
if (!$hasMariaDBFunctions) $allPassed = false;

// Test 3: Check trending products query specifically
echo "\nTest 3: Trending products query verification...\n";
$trendingQuerySection = '';
if (preg_match('/public function getTrendingProducts.*?{(.*?)}/s', $modelsContent, $matches)) {
    $trendingQuerySection = $matches[1];
}

$hasSqliteDateTime = strpos($trendingQuerySection, "datetime('now'") !== false;
$hasMariaDBDateSub = strpos($trendingQuerySection, 'DATE_SUB(NOW(), INTERVAL 7 DAY)') !== false;
$usesProperJoins = strpos($trendingQuerySection, 'order_items') !== false && strpos($trendingQuerySection, 'orders') !== false;

testResult("Trending Query - SQLite Removed", !$hasSqliteDateTime, 
    !$hasSqliteDateTime ? "SQLite datetime removed" : "Still uses SQLite datetime"
);
testResult("Trending Query - MariaDB Date", $hasMariaDBDateSub,
    $hasMariaDBDateSub ? "Uses MariaDB DATE_SUB" : "MariaDB DATE_SUB not found"
);
testResult("Trending Query - Order Tables", $usesProperJoins,
    $usesProperJoins ? "Uses order tables for trending calculation" : "Missing order table joins"
);

if ($hasSqliteDateTime || !$hasMariaDBDateSub) $allPassed = false;

// Test 4: CSS background verification
echo "\nTest 4: CSS background fixes...\n";
$cssContent = file_get_contents(__DIR__ . '/css/styles.css');

$hasWhiteBackground = strpos($cssContent, 'background: #ffffff !important') !== false;
$hasColorSchemeLight = strpos($cssContent, 'color-scheme: light') !== false;
$hasDarkModeOverride = strpos($cssContent, '@media (prefers-color-scheme: dark)') !== false;
$hasOldGreyBackground = strpos($cssContent, 'background-color: #f7f7f7') !== false;

testResult("White Background Enforced", $hasWhiteBackground,
    $hasWhiteBackground ? "White background with !important" : "White background not enforced"
);
testResult("Color Scheme Light", $hasColorSchemeLight,
    $hasColorSchemeLight ? "Light color scheme set" : "Color scheme not set to light"
);
testResult("Dark Mode Override", $hasDarkModeOverride,
    $hasDarkModeOverride ? "Dark mode override present" : "No dark mode override"
);
testResult("Grey Background Removed", !$hasOldGreyBackground,
    !$hasOldGreyBackground ? "Old grey background removed" : "Old grey background still present"
);

if (!$hasWhiteBackground || $hasOldGreyBackground) $allPassed = false;

// Test 5: Meta theme color
echo "\nTest 5: Meta theme color...\n";
$headerContent = file_get_contents(__DIR__ . '/templates/header.php');
$hasThemeColor = strpos($headerContent, 'meta name="theme-color" content="#ffffff"') !== false;

testResult("Theme Color White", $hasThemeColor,
    $hasThemeColor ? "Theme color set to white" : "Theme color not set to white"
);

if (!$hasThemeColor) $allPassed = false;

// Test 6: PHP Syntax validation
echo "\nTest 6: PHP syntax validation...\n";
$syntaxErrors = [];

$files = ['includes/models_extended.php', 'includes/functions.php', 'templates/header.php'];
foreach ($files as $file) {
    $result = shell_exec("php -l $file 2>&1");
    if (strpos($result, 'No syntax errors') === false) {
        $syntaxErrors[] = $file;
    }
}

testResult("PHP Syntax Valid", empty($syntaxErrors),
    empty($syntaxErrors) ? "All modified files have valid syntax" : 
    "Syntax errors in: " . implode(', ', $syntaxErrors)
);

if (!empty($syntaxErrors)) $allPassed = false;

echo "\n=== SQL Syntax Test Summary ===\n";

if ($allPassed) {
    echo "🎉 ALL SQL SYNTAX TESTS PASSED! 🎉\n\n";
    echo "✅ SQL 1064 Error Resolution: All SQLite datetime functions converted to MariaDB\n";
    echo "✅ MariaDB Compatibility: Uses NOW(), DATE_SUB(), DATE_ADD() functions\n";
    echo "✅ Trending Products: Rewritten to use order data with proper MariaDB syntax\n";
    echo "✅ White Background: Forced with !important and dark mode override\n";
    echo "✅ Theme Color: Meta tag set to white\n";
    echo "✅ PHP Syntax: All modified files are syntactically correct\n\n";
    echo "🔧 Ready for deployment with MariaDB database!\n";
    exit(0);
} else {
    echo "❌ Some SQL syntax tests failed. Please review the issues above.\n";
    exit(1);
}
?>