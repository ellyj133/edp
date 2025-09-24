#!/usr/bin/env php
<?php
/**
 * Database Schema Verification Script
 * E-Commerce Platform - Verify database consistency with codebase
 * 
 * Usage: php scripts/verify_schema.php [options]
 * Options:
 *   --verbose  Show detailed output
 *   --fix      Attempt to fix missing indexes (use with caution)
 *   --report   Generate detailed report file
 *   --help     Show help message
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

class SchemaVerifier {
    private $pdo;
    private $verbose = false;
    private $issues = [];
    private $suggestions = [];
    private $codebaseQueries = [];
    
    public function __construct($options = []) {
        $this->pdo = db();
        $this->verbose = isset($options['verbose']);
        
        $this->info("=== FezaMarket Database Schema Verifier ===\n");
    }
    
    public function verify($options = []) {
        if (isset($options['help'])) {
            $this->showHelp();
            return;
        }
        
        $this->info("🔍 Starting database schema verification...\n");
        
        // Step 1: Scan codebase for SQL usage
        $this->scanCodebase();
        
        // Step 2: Verify database structure
        $this->verifyTables();
        
        // Step 3: Verify columns exist
        $this->verifyColumns();
        
        // Step 4: Check indexes for performance
        $this->checkIndexes();
        
        // Step 5: Verify foreign keys
        $this->verifyForeignKeys();
        
        // Step 6: Check for unused tables/columns
        $this->checkUnusedElements();
        
        // Generate report
        $this->generateReport($options);
        
        if (isset($options['fix'])) {
            $this->applyFixes();
        }
        
        $this->showSummary();
    }
    
    private function scanCodebase() {
        $this->info("📁 Scanning codebase for SQL usage...");
        
        $directories = [
            __DIR__ . '/../',
            __DIR__ . '/../includes/',
            __DIR__ . '/../api/',
            __DIR__ . '/../admin/'
        ];
        
        $sqlPatterns = [
            '/SELECT\s+.*?\s+FROM\s+`?(\w+)`?/i',
            '/INSERT\s+INTO\s+`?(\w+)`?/i',
            '/UPDATE\s+`?(\w+)`?\s+SET/i',
            '/DELETE\s+FROM\s+`?(\w+)`?/i',
            '/JOIN\s+`?(\w+)`?\s+/i',
            '/LEFT\s+JOIN\s+`?(\w+)`?\s+/i',
            '/RIGHT\s+JOIN\s+`?(\w+)`?\s+/i',
            '/INNER\s+JOIN\s+`?(\w+)`?\s+/i'
        ];
        
        $columnPatterns = [
            '/WHERE\s+`?(\w+)`?\.`?(\w+)`?\s*[=<>]/i',
            '/ORDER\s+BY\s+`?(\w+)`?\.?`?(\w+)`?/i',
            '/GROUP\s+BY\s+`?(\w+)`?\.?`?(\w+)`?/i',
            '/SET\s+`?(\w+)`?\s*=/i',
            '/SELECT\s+.*?`?(\w+)`?\.`?(\w+)`?/i'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) continue;
            
            $files = $this->getPhpFiles($dir);
            
            foreach ($files as $file) {
                $content = file_get_contents($file);
                
                // Find table references
                foreach ($sqlPatterns as $pattern) {
                    if (preg_match_all($pattern, $content, $matches)) {
                        foreach ($matches[1] as $table) {
                            $this->codebaseQueries['tables'][$table][] = basename($file);
                        }
                    }
                }
                
                // Find column references
                foreach ($columnPatterns as $pattern) {
                    if (preg_match_all($pattern, $content, $matches)) {
                        if (isset($matches[2])) {
                            foreach ($matches[2] as $i => $column) {
                                $table = isset($matches[1][$i]) ? $matches[1][$i] : 'unknown';
                                $this->codebaseQueries['columns'][$table][$column][] = basename($file);
                            }
                        }
                    }
                }
            }
        }
        
        $tableCount = count($this->codebaseQueries['tables'] ?? []);
        $this->success("   ✅ Found references to $tableCount tables in codebase");
    }
    
    private function verifyTables() {
        $this->info("\n🗂️  Verifying table existence...");
        
        // Get existing tables
        $stmt = $this->pdo->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $expectedTables = array_keys($this->codebaseQueries['tables'] ?? []);
        
        foreach ($expectedTables as $table) {
            if (!in_array($table, $existingTables)) {
                $this->addIssue('missing_table', $table, "Table '$table' is referenced in code but doesn't exist in database");
            }
        }
        
        $this->success("   ✅ Verified " . count($existingTables) . " tables");
    }
    
    private function verifyColumns() {
        $this->info("\n🏛️  Verifying column existence...");
        
        $columnIssues = 0;
        
        foreach ($this->codebaseQueries['columns'] ?? [] as $table => $columns) {
            // Get table columns
            try {
                $stmt = $this->pdo->query("DESCRIBE `$table`");
                $existingColumns = array_column($stmt->fetchAll(), 'Field');
                
                foreach ($columns as $column => $files) {
                    if (!in_array($column, $existingColumns) && $column !== 'unknown') {
                        $this->addIssue('missing_column', "$table.$column", 
                            "Column '$column' in table '$table' is referenced in code but doesn't exist");
                        $columnIssues++;
                    }
                }
                
            } catch (Exception $e) {
                $this->addIssue('table_error', $table, "Could not verify columns for table '$table': " . $e->getMessage());
            }
        }
        
        if ($columnIssues === 0) {
            $this->success("   ✅ All referenced columns exist");
        } else {
            $this->warning("   ⚠️  Found $columnIssues column issues");
        }
    }
    
    private function checkIndexes() {
        $this->info("\n⚡ Analyzing indexes for performance...");
        
        $indexSuggestions = 0;
        
        // Common WHERE clause patterns that should have indexes
        $indexableColumns = [
            'users' => ['email', 'username', 'role', 'status', 'created_at'],
            'products' => ['vendor_id', 'category_id', 'status', 'featured', 'name', 'price', 'created_at'],
            'orders' => ['user_id', 'status', 'payment_status', 'created_at'],
            'order_items' => ['order_id', 'product_id', 'vendor_id'],
            'reviews' => ['user_id', 'product_id', 'status', 'rating'],
            'notifications' => ['user_id', 'type', 'read_at', 'created_at'],
            'cart' => ['user_id', 'product_id'],
            'wishlists' => ['user_id', 'product_id']
        ];
        
        foreach ($indexableColumns as $table => $columns) {
            try {
                // Get existing indexes
                $stmt = $this->pdo->query("SHOW INDEX FROM `$table`");
                $existingIndexes = $stmt->fetchAll();
                $indexedColumns = [];
                
                foreach ($existingIndexes as $index) {
                    $indexedColumns[] = $index['Column_name'];
                }
                
                foreach ($columns as $column) {
                    if (!in_array($column, $indexedColumns)) {
                        $this->addSuggestion('missing_index', "$table.$column", 
                            "Consider adding index on $table($column) for better query performance");
                        $indexSuggestions++;
                    }
                }
                
            } catch (Exception $e) {
                // Table might not exist, skip
            }
        }
        
        if ($indexSuggestions === 0) {
            $this->success("   ✅ Index optimization looks good");
        } else {
            $this->info("   💡 Found $indexSuggestions index optimization opportunities");
        }
    }
    
    private function verifyForeignKeys() {
        $this->info("\n🔗 Verifying foreign key relationships...");
        
        $expectedFKs = [
            'vendors' => ['user_id' => 'users.id'],
            'products' => ['vendor_id' => 'vendors.id', 'category_id' => 'categories.id'],
            'product_images' => ['product_id' => 'products.id'],
            'addresses' => ['user_id' => 'users.id'],
            'cart' => ['user_id' => 'users.id', 'product_id' => 'products.id'],
            'orders' => ['user_id' => 'users.id'],
            'order_items' => ['order_id' => 'orders.id', 'product_id' => 'products.id', 'vendor_id' => 'vendors.id'],
            'reviews' => ['user_id' => 'users.id', 'product_id' => 'products.id'],
            'wishlists' => ['user_id' => 'users.id', 'product_id' => 'products.id']
        ];
        
        $fkIssues = 0;
        
        foreach ($expectedFKs as $table => $foreignKeys) {
            try {
                $stmt = $this->pdo->query("
                    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = '$table' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $existingFKs = $stmt->fetchAll();
                
                $existingFKMap = [];
                foreach ($existingFKs as $fk) {
                    $existingFKMap[$fk['COLUMN_NAME']] = $fk['REFERENCED_TABLE_NAME'] . '.' . $fk['REFERENCED_COLUMN_NAME'];
                }
                
                foreach ($foreignKeys as $column => $reference) {
                    if (!isset($existingFKMap[$column])) {
                        $this->addIssue('missing_foreign_key', "$table.$column", 
                            "Missing foreign key constraint: $table.$column should reference $reference");
                        $fkIssues++;
                    }
                }
                
            } catch (Exception $e) {
                // Table might not exist, skip
            }
        }
        
        if ($fkIssues === 0) {
            $this->success("   ✅ Foreign key relationships verified");
        } else {
            $this->warning("   ⚠️  Found $fkIssues foreign key issues");
        }
    }
    
    private function checkUnusedElements() {
        $this->info("\n🗑️  Checking for unused database elements...");
        
        // Get all tables
        $stmt = $this->pdo->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $referencedTables = array_keys($this->codebaseQueries['tables'] ?? []);
        $unusedTables = array_diff($allTables, $referencedTables);
        
        // Exclude system tables
        $systemTables = ['migrations', 'sessions'];
        $unusedTables = array_diff($unusedTables, $systemTables);
        
        foreach ($unusedTables as $table) {
            $this->addSuggestion('unused_table', $table, 
                "Table '$table' exists in database but is not referenced in codebase");
        }
        
        if (empty($unusedTables)) {
            $this->success("   ✅ No unused tables found");
        } else {
            $this->info("   💡 Found " . count($unusedTables) . " potentially unused tables");
        }
    }
    
    private function generateReport($options) {
        if (!isset($options['report'])) {
            return;
        }
        
        $reportFile = __DIR__ . '/../logs/schema_verification_' . date('Y-m-d_H-i-s') . '.txt';
        
        $report = "FezaMarket Database Schema Verification Report\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= str_repeat("=", 60) . "\n\n";
        
        $report .= "ISSUES FOUND:\n";
        $report .= str_repeat("-", 20) . "\n";
        foreach ($this->issues as $issue) {
            $report .= "• [{$issue['type']}] {$issue['element']}: {$issue['message']}\n";
        }
        
        $report .= "\n\nSUGGESTIONS:\n";
        $report .= str_repeat("-", 20) . "\n";
        foreach ($this->suggestions as $suggestion) {
            $report .= "• [{$suggestion['type']}] {$suggestion['element']}: {$suggestion['message']}\n";
        }
        
        file_put_contents($reportFile, $report);
        $this->success("\n📄 Report saved to: $reportFile");
    }
    
    private function applyFixes() {
        $this->warning("\n🔧 Applying automatic fixes...");
        
        foreach ($this->suggestions as $suggestion) {
            if ($suggestion['type'] === 'missing_index') {
                [$table, $column] = explode('.', $suggestion['element']);
                
                try {
                    $indexName = "idx_" . $column;
                    $this->pdo->exec("CREATE INDEX `$indexName` ON `$table` (`$column`)");
                    $this->success("   ✅ Added index $indexName on $table($column)");
                } catch (Exception $e) {
                    $this->error("   ❌ Failed to add index on $table($column): " . $e->getMessage());
                }
            }
        }
    }
    
    private function getPhpFiles($directory) {
        $files = [];
        
        if (is_file($directory) && pathinfo($directory, PATHINFO_EXTENSION) === 'php') {
            return [$directory];
        }
        
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    private function addIssue($type, $element, $message) {
        $this->issues[] = [
            'type' => $type,
            'element' => $element,
            'message' => $message
        ];
    }
    
    private function addSuggestion($type, $element, $message) {
        $this->suggestions[] = [
            'type' => $type,
            'element' => $element,
            'message' => $message
        ];
    }
    
    private function showSummary() {
        $this->info("\n" . str_repeat("=", 50));
        $this->info("VERIFICATION SUMMARY");
        $this->info(str_repeat("=", 50));
        
        $issueCount = count($this->issues);
        $suggestionCount = count($this->suggestions);
        
        if ($issueCount === 0) {
            $this->success("✅ No critical issues found!");
        } else {
            $this->error("❌ Found $issueCount critical issues:");
            foreach ($this->issues as $issue) {
                $this->error("   • [{$issue['type']}] {$issue['element']}: {$issue['message']}");
            }
        }
        
        if ($suggestionCount > 0) {
            $this->info("\n💡 $suggestionCount optimization suggestions:");
            foreach (array_slice($this->suggestions, 0, 5) as $suggestion) {
                $this->info("   • {$suggestion['message']}");
            }
            
            if ($suggestionCount > 5) {
                $this->info("   • ... and " . ($suggestionCount - 5) . " more (use --report for full list)");
            }
        }
        
        $this->info("\n" . str_repeat("=", 50));
    }
    
    private function showHelp() {
        echo "FezaMarket Database Schema Verifier\n\n";
        echo "Usage: php scripts/verify_schema.php [options]\n\n";
        echo "Options:\n";
        echo "  --verbose  Show detailed output during verification\n";
        echo "  --fix      Attempt to fix missing indexes automatically\n";
        echo "  --report   Generate detailed report file in logs/\n";
        echo "  --help     Show this help message\n\n";
        echo "Examples:\n";
        echo "  php scripts/verify_schema.php\n";
        echo "  php scripts/verify_schema.php --verbose --report\n";
        echo "  php scripts/verify_schema.php --fix\n";
    }
    
    private function info($message) {
        echo $message . "\n";
    }
    
    private function success($message) {
        echo "\033[32m" . $message . "\033[0m\n";
    }
    
    private function warning($message) {
        echo "\033[33m" . $message . "\033[0m\n";
    }
    
    private function error($message) {
        echo "\033[31m" . $message . "\033[0m\n";
    }
}

// Parse command line arguments
$options = [];
$args = array_slice($argv, 1);

foreach ($args as $arg) {
    if (strpos($arg, '--') === 0) {
        $options[substr($arg, 2)] = true;
    }
}

try {
    $verifier = new SchemaVerifier($options);
    $verifier->verify($options);
    exit(0);
} catch (Exception $e) {
    echo "\033[31m❌ Schema verification failed: " . $e->getMessage() . "\033[0m\n";
    exit(1);
}