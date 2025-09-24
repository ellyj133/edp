<?php
// Test configuration for SQLite
define('DB_HOST', 'localhost');
define('DB_NAME', 'test_ecommerce');  
define('DB_USER', 'test');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('USE_SQLITE', true);  // Override to use SQLite

// Override db() function to use SQLite for testing
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    // Use SQLite for testing
    $dbPath = __DIR__ . '/test_ecommerce.db';
    $dsn = "sqlite:$dbPath";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, null, null, $options);
        // SQLite specific settings
        $pdo->exec("PRAGMA foreign_keys = ON");
    } catch (PDOException $e) {
        error_log('SQLite connection failed: '.$e->getMessage());
        throw $e;
    }
    return $pdo;
}

// Override database transaction function
function db_transaction(callable $fn) {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $fn($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $t) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $t;
    }
}

// Mock Database class methods for compatibility
class Database {
    public static function query(string $sql, array $params = []): PDOStatement {
        $pdo = db();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public static function lastInsertId(): string {
        $pdo = db();
        return $pdo->lastInsertId();
    }
    
    public static function getInstance() {
        return new self();
    }
    
    public function getConnection(): PDO {
        return db();
    }
}

// Mock Session class for testing
class Session {
    private static $data = ['user_id' => 1, 'user_role' => 'seller'];
    
    public static function start() {
        // Mock start - no actual session in CLI
    }
    
    public static function isLoggedIn(): bool {
        return true; // Always logged in for testing
    }
    
    public static function getUserId() {
        return 1; // Test seller ID
    }
    
    public static function get($key, $default = null) {
        return self::$data[$key] ?? $default;
    }
    
    public static function set($key, $value) {
        self::$data[$key] = $value;
    }
    
    public static function remove($key) {
        unset(self::$data[$key]);
    }
    
    public static function getUserRole() {
        return 'seller';
    }
}

// Helper functions
if (!function_exists('h')) {
    function h($s) { 
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
    }
}

if (!function_exists('slugify')) {
    function slugify($s){
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/i','-',$s);
        $s = trim($s,'-');
        return $s !== '' ? $s : bin2hex(random_bytes(4));
    }
}

// Test database connection
try {
    $testDb = db();
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

?>