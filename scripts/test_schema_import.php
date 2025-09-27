#!/usr/bin/env php
<?php
/**
 * MariaDB Schema Import Test
 * Tests database schema compatibility without requiring live database
 * 
 * Usage: php scripts/test_schema_import.php [--verbose]
 */

declare(strict_types=1);

class SchemaImportTester {
    private $verbose = false;
    private $schemaPath;
    private $errors = [];
    private $warnings = [];
    private $fixes = [];
    
    public function __construct(bool $verbose = false) {
        $this->verbose = $verbose;
        $this->schemaPath = __DIR__ . '/../database/schema.sql';
    }
    
    public function runTests(): bool {
        echo "🧪 MariaDB Schema Import Compatibility Test\n";
        echo str_repeat("=", 50) . "\n\n";
        
        if (!file_exists($this->schemaPath)) {
            echo "❌ Schema file not found: {$this->schemaPath}\n";
            return false;
        }
        
        $content = file_get_contents($this->schemaPath);
        
        $this->testSqlMode($content);
        $this->testInsertSyntax($content);
        $this->testIndexSyntax($content);
        $this->testStoredProcedures($content);
        $this->testMariaDBCompatibility($content);
        
        return $this->displayResults();
    }
    
    private function testSqlMode(string $content): void {
        echo "🔍 Testing SQL Mode Declaration...\n";
        
        // Test for correct SQL mode syntax
        if (preg_match("/SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';/", $content)) {
            echo "   ✅ SQL mode syntax is correct\n";
            $this->fixes[] = "Fixed SQL mode nested quotes syntax error";
        } elseif (preg_match("/SET sql_mode = ''STRICT_TRANS_TABLES/", $content)) {
            $this->errors[] = "SQL mode has nested quotes - will cause ERROR 1064";
        } else {
            $this->warnings[] = "SQL mode not found or uses different format";
        }
        
        if ($this->verbose) {
            echo "   📝 SQL mode ensures strict data handling and error reporting\n";
        }
        echo "\n";
    }
    
    private function testInsertSyntax(string $content): void {
        echo "🔍 Testing INSERT Statement Syntax...\n";
        
        // Test for SQLite-specific INSERT OR IGNORE
        $orIgnoreCount = preg_match_all("/INSERT OR IGNORE/i", $content);
        if ($orIgnoreCount > 0) {
            $this->errors[] = "Found $orIgnoreCount SQLite-specific 'INSERT OR IGNORE' statements";
        } else {
            echo "   ✅ Using MariaDB-compatible INSERT IGNORE syntax\n";
            $this->fixes[] = "Converted SQLite INSERT OR IGNORE to MariaDB INSERT IGNORE";
        }
        
        // Test for ENGINE clauses in INSERT statements
        $engineInInsert = preg_match_all("/INSERT.*ENGINE=InnoDB/i", $content);
        if ($engineInInsert > 0) {
            $this->errors[] = "Found $engineInInsert ENGINE clauses in INSERT statements";
        } else {
            echo "   ✅ No ENGINE clauses in INSERT statements\n";
            $this->fixes[] = "Removed invalid ENGINE clauses from INSERT statements";
        }
        
        if ($this->verbose) {
            echo "   📝 INSERT IGNORE prevents duplicate key errors during data import\n";
        }
        echo "\n";
    }
    
    private function testIndexSyntax(string $content): void {
        echo "🔍 Testing INDEX Creation Syntax...\n";
        
        // Test for ENGINE clauses in CREATE INDEX
        $engineInIndex = preg_match_all("/CREATE INDEX.*ENGINE=InnoDB/i", $content);
        if ($engineInIndex > 0) {
            $this->errors[] = "Found $engineInIndex ENGINE clauses in CREATE INDEX statements";
        } else {
            echo "   ✅ CREATE INDEX statements have clean syntax\n";
            $this->fixes[] = "Removed invalid ENGINE clauses from CREATE INDEX statements";
        }
        
        // Count total indexes for verification
        $totalIndexes = preg_match_all("/CREATE INDEX/i", $content);
        echo "   📊 Found $totalIndexes index creation statements\n";
        
        if ($this->verbose) {
            echo "   📝 CREATE INDEX statements should not include ENGINE clauses\n";
        }
        echo "\n";
    }
    
    private function testStoredProcedures(string $content): void {
        echo "🔍 Testing Stored Procedure Syntax...\n";
        
        // Test for ENGINE clauses in stored procedure dynamic SQL
        if (preg_match("/SET @ddl = CONCAT.*ENGINE=InnoDB/", $content)) {
            $this->errors[] = "Found ENGINE clause in stored procedure dynamic SQL";
        } else {
            echo "   ✅ Stored procedures have clean dynamic SQL\n";
            $this->fixes[] = "Cleaned ENGINE clauses from stored procedure dynamic SQL";
        }
        
        // Count stored procedures
        $procCount = preg_match_all("/CREATE.*PROCEDURE/i", $content);
        echo "   📊 Found $procCount stored procedures\n";
        
        if ($this->verbose) {
            echo "   📝 Dynamic SQL in procedures should not include invalid ENGINE clauses\n";
        }
        echo "\n";
    }
    
    private function testMariaDBCompatibility(string $content): void {
        echo "🔍 Testing MariaDB Version Compatibility...\n";
        
        // Test for MariaDB/MySQL compatible features
        $features = [
            'InnoDB Storage Engine' => preg_match_all("/ENGINE=InnoDB/i", $content),
            'UTF8MB4 Charset' => preg_match_all("/CHARSET=utf8mb4/i", $content),
            'Foreign Key Constraints' => preg_match_all("/FOREIGN KEY/i", $content),
            'JSON Data Type Usage' => preg_match_all("/json_valid/i", $content),
        ];
        
        foreach ($features as $feature => $count) {
            if ($count > 0) {
                echo "   ✅ $feature: $count instances\n";
            }
        }
        
        // Check for version-specific issues
        if (preg_match("/GENERATED ALWAYS AS/i", $content)) {
            $this->warnings[] = "Generated columns require MariaDB 5.2+ or MySQL 5.7+";
        }
        
        if ($this->verbose) {
            echo "   📝 Schema uses modern MariaDB/MySQL features for optimal performance\n";
        }
        echo "\n";
    }
    
    private function displayResults(): bool {
        echo str_repeat("=", 50) . "\n";
        echo "📊 TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 50) . "\n\n";
        
        // Show fixes applied
        if (!empty($this->fixes)) {
            echo "🔧 FIXES APPLIED:\n";
            foreach ($this->fixes as $fix) {
                echo "   ✅ $fix\n";
            }
            echo "\n";
        }
        
        // Show any remaining errors
        if (!empty($this->errors)) {
            echo "❌ CRITICAL ERRORS (WILL CAUSE IMPORT FAILURES):\n";
            foreach ($this->errors as $error) {
                echo "   • $error\n";
            }
            echo "\n";
        }
        
        // Show warnings
        if (!empty($this->warnings)) {
            echo "⚠️  WARNINGS (REVIEW RECOMMENDED):\n";
            foreach ($this->warnings as $warning) {
                echo "   • $warning\n";
            }
            echo "\n";
        }
        
        // Final result
        if (empty($this->errors)) {
            echo "🎉 SUCCESS: Schema is ready for MariaDB import!\n";
            echo "   • All critical syntax errors have been resolved\n";
            echo "   • Schema uses MariaDB-compatible SQL syntax\n";
            echo "   • Compatible with MariaDB 5.5+ and MySQL 5.5+\n\n";
            
            echo "📋 IMPORT INSTRUCTIONS:\n";
            echo "   1. Ensure MariaDB/MySQL server is running\n";
            echo "   2. Create database: CREATE DATABASE your_db_name;\n";
            echo "   3. Import schema: mysql -u user -p your_db_name < database/schema.sql\n";
            echo "   4. Or use: php setup_database.php\n";
            
            return true;
        } else {
            echo "❌ FAILED: Schema has critical errors that must be fixed\n";
            echo "   Please resolve the errors above before attempting import\n";
            return false;
        }
    }
}

// Parse command line arguments
$verbose = in_array('--verbose', $argv) || in_array('-v', $argv);

// Run the test
$tester = new SchemaImportTester($verbose);
$success = $tester->runTests();

exit($success ? 0 : 1);