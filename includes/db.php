<?php
// /includes/db.php
declare(strict_types=1);

if (!function_exists('db')) {
    function db(): PDO {
        static $pdo = null;
        if ($pdo instanceof PDO) return $pdo;

        // Check if SQLite is enabled
        if (defined('USE_SQLITE') && USE_SQLITE) {
            $sqlitePath = defined('SQLITE_PATH') ? SQLITE_PATH : __DIR__ . '/../test_ecommerce.db';
            $dsn = "sqlite:{$sqlitePath}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                $pdo = new PDO($dsn, null, null, $options);
                // Enable foreign keys for SQLite
                $pdo->exec('PRAGMA foreign_keys = ON');
            } catch (PDOException $e) {
                error_log('SQLite connection failed: '.$e->getMessage());
                throw $e;
            }
        } else {
            // Use MySQL/MariaDB configuration
            $host     = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
            $port     = defined('DB_PORT') ? '3306' : (getenv('DB_PORT') ?: '3306');
            $dbname   = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'ecommerce_platform');
            $user     = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'duns1');
            $pass     = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: 'Tumukunde');
            $charset  = defined('DB_CHARSET') ? DB_CHARSET : (getenv('DB_CHARSET') ?: 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                $pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log('DB connection failed: '.$e->getMessage());
                throw $e;
            }
        }
        
        return $pdo;
    }
}

if (!function_exists('db_transaction')) {
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
}

if (!function_exists('db_ping')) {
    function db_ping(): bool {
        try {
            db()->query('SELECT 1')->fetchColumn();
            return true;
        } catch (Throwable $t) {
            return false;
        }
    }
}