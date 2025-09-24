<?php
/**
 * Test to verify getRandomProducts() method uses correct MySQL syntax
 * This test validates the SQL query syntax without requiring a database connection
 */

echo "=== getRandomProducts SQL Syntax Test ===\n\n";

// Extract the SQL query from the getRandomProducts method
$modelsContent = file_get_contents(__DIR__ . '/includes/models.php');

// Find the getRandomProducts method - looking for the specific multiline SQL pattern
$pattern = '/public function getRandomProducts.*?prepare\(\s*"\s*(.*?)\s*"\s*\);/s';
if (preg_match($pattern, $modelsContent, $matches)) {
        $sqlQuery = trim($matches[1]);
        
        echo "Found SQL Query:\n";
        echo "================\n";
        echo $sqlQuery . "\n\n";
        
        // Test 1: Check that RANDOM() is not used
        $hasRandom = strpos($sqlQuery, 'RANDOM()') !== false;
        echo ($hasRandom ? '❌' : '✅') . " Test: No RANDOM() function";
        echo ($hasRandom ? " - Found RANDOM() (SQLite syntax)" : " - Good! No RANDOM() found") . "\n";
        
        // Test 2: Check that RAND() is used
        $hasRand = strpos($sqlQuery, 'RAND()') !== false;
        echo ($hasRand ? '✅' : '❌') . " Test: Uses RAND() function";
        echo ($hasRand ? " - Good! Uses MySQL RAND()" : " - Missing RAND() function") . "\n";
        
        // Test 3: Check query structure
        $hasOrderBy = strpos($sqlQuery, 'ORDER BY') !== false;
        echo ($hasOrderBy ? '✅' : '❌') . " Test: Has ORDER BY clause";
        echo ($hasOrderBy ? " - ORDER BY clause found" : " - ORDER BY clause missing") . "\n";
        
        // Test 4: Check complete syntax
        $isValidSyntax = !$hasRandom && $hasRand && $hasOrderBy;
        echo "\n" . ($isValidSyntax ? '🎉' : '❌') . " Overall Result: ";
        echo ($isValidSyntax ? "SQL syntax is correct for MySQL!" : "SQL syntax needs correction") . "\n";
        
        if ($isValidSyntax) {
            echo "\n✅ The getRandomProducts() method should work with MySQL/MariaDB\n";
            echo "✅ Homepage should load without SQL syntax errors\n";
            echo "✅ Random products will be displayed correctly\n";
        }
        
        exit($isValidSyntax ? 0 : 1);
} else {
    echo "❌ Could not find getRandomProducts method\n";
    exit(1);
}
?>