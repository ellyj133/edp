<?php
/**
 * Configuration Management
 * E-Commerce Platform - PHP 8 with Environment Variables Support
 */

// Load environment variables from .env file
function loadEnvironmentVariables() {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue; // Skip comments and invalid lines
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Load environment variables
loadEnvironmentVariables();

// Helper function to get environment variable with fallback
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = $_ENV[$key] ?? getenv($key) ?: $default;
        
        // Convert string boolean to actual boolean
        if (is_string($value)) {
            if (strtolower($value) === 'true') return true;
            if (strtolower($value) === 'false') return false;
        }
        
        return $value;
    }
}

// Database connection settings - with SQLite support for development
if (env('APP_ENV', 'development') === 'development' && env('USE_SQLITE', false)) {
    // Development with SQLite
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'sqlite');
    define('DB_USER', '');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
    define('USE_SQLITE', true);
    define('SQLITE_PATH', __DIR__ . '/../' . env('SQLITE_DATABASE_PATH', 'database/ecommerce.db'));
} else {
    // Production configuration for duns1.fezalogistics.com
    define('DB_HOST', env('DB_HOST', 'localhost'));
    define('DB_NAME', env('DB_NAME', 'ecommerce_platform'));
    define('DB_USER', env('DB_USER', 'duns1'));
    define('DB_PASS', env('DB_PASS', 'Tumukunde'));
    define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
    define('USE_SQLITE', false);
}

// Application settings
define('APP_NAME', env('APP_NAME', 'E-Commerce Platform'));
define('APP_URL', env('APP_URL', 'http://localhost'));
define('APP_VERSION', env('APP_VERSION', '2.0.0'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', true));

// Security settings
define('SECRET_KEY', env('SECRET_KEY', 'your-secret-key-change-this-in-production-minimum-32-chars'));
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 3600)); // 1 hour
define('BCRYPT_COST', (int)env('BCRYPT_COST', 12));

// Password policy
define('PASSWORD_MIN_LENGTH', (int)env('PASSWORD_MIN_LENGTH', 8));
define('MAX_LOGIN_ATTEMPTS', (int)env('MAX_LOGIN_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_DURATION', (int)env('LOGIN_LOCKOUT_DURATION', 15)); // minutes

// Two-Factor Authentication
define('TWO_FA_ENABLED', env('TWO_FA_ENABLED', false));
define('REQUIRE_EMAIL_VERIFICATION', env('REQUIRE_EMAIL_VERIFICATION', true));

// File upload settings
define('MAX_UPLOAD_SIZE', (int)env('MAX_UPLOAD_SIZE', 5242880)); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', __DIR__ . '/../' . env('UPLOAD_PATH', 'uploads/'));

// Pagination settings
define('PRODUCTS_PER_PAGE', (int)env('PRODUCTS_PER_PAGE', 12));
define('ORDERS_PER_PAGE', (int)env('ORDERS_PER_PAGE', 20));
define('REVIEWS_PER_PAGE', (int)env('REVIEWS_PER_PAGE', 10));

// Email settings (production ready with FezaLogistics)
define('SMTP_HOST', env('SMTP_HOST', 'smtp.fezalogistics.com'));
define('SMTP_PORT', (int)env('SMTP_PORT', 587));
define('SMTP_USERNAME', env('SMTP_USERNAME', 'no-reply@fezalogistics.com'));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls'));
define('FROM_EMAIL', env('FROM_EMAIL', 'no-reply@fezalogistics.com'));
define('FROM_NAME', env('FROM_NAME', APP_NAME));

// Payment gateway settings (sandbox/testing)
define('PAYMENT_GATEWAY', env('PAYMENT_GATEWAY', 'mock')); // 'mock', 'stripe', 'paypal'
define('STRIPE_PUBLISHABLE_KEY', env('STRIPE_PUBLISHABLE_KEY', ''));
define('STRIPE_SECRET_KEY', env('STRIPE_SECRET_KEY', ''));
define('PAYPAL_CLIENT_ID', env('PAYPAL_CLIENT_ID', ''));
define('PAYPAL_CLIENT_SECRET', env('PAYPAL_CLIENT_SECRET', ''));

// AI/ML settings
define('AI_RECOMMENDATIONS_ENABLED', env('AI_RECOMMENDATIONS_ENABLED', true));
define('MIN_RECOMMENDATIONS', (int)env('MIN_RECOMMENDATIONS', 4));
define('MAX_RECOMMENDATIONS', (int)env('MAX_RECOMMENDATIONS', 12));

// Cache settings
define('CACHE_ENABLED', env('CACHE_ENABLED', false));
define('CACHE_LIFETIME', (int)env('CACHE_LIFETIME', 3600));

// Rate limiting
define('RATE_LIMIT_ENABLED', env('RATE_LIMIT_ENABLED', true));
define('RATE_LIMIT_REQUESTS', (int)env('RATE_LIMIT_REQUESTS', 60));
define('RATE_LIMIT_WINDOW', (int)env('RATE_LIMIT_WINDOW', 60));

// Debug settings
define('DEBUG_MODE', env('APP_DEBUG', true));
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', __DIR__ . '/../storage/logs/');

// Admin Panel Settings
define('ADMIN_BYPASS', env('ADMIN_BYPASS', true));

// Timezone
date_default_timezone_set('UTC');

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Ensure logs directory exists
if (!is_dir(ERROR_LOG_PATH)) {
    mkdir(ERROR_LOG_PATH, 0755, true);
}

// Ensure uploads directory exists
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
?>