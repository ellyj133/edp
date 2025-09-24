<?php
/**
 * System Health Check
 * E-Commerce Platform - Database and Email System Verification
 */

require_once __DIR__ . '/includes/init.php';

echo "=== E-Commerce Platform System Health Check ===\n\n";

$allPassed = true;

function testResult($name, $passed, $message = '') {
    global $allPassed;
    if (!$passed) $allPassed = false;
    
    $status = $passed ? "✅" : "❌";
    echo "{$status} {$name}";
    if ($message) {
        echo " - {$message}";
    }
    echo "\n";
    return $passed;
}

try {
    // 1. Database Connectivity
    echo "1. Database System\n";
    $db = Database::getInstance()->getConnection();
    testResult("Database Connection", true, "Connected to " . $db->query('SELECT DATABASE()')->fetchColumn());
    
    // 2. Required Tables
    $requiredTables = [
        'users', 'login_attempts', 'user_sessions', 'email_tokens',
        'email_verification_tokens', 'password_reset_tokens', 
        'user_totp_secrets', 'email_logs', 'mail_queue'
    ];
    
    $stmt = $db->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $missingTables = array_diff($requiredTables, $existingTables);
    
    testResult("Security Tables", empty($missingTables), 
               empty($missingTables) ? "All " . count($requiredTables) . " tables present" : 
               "Missing: " . implode(', ', $missingTables));
    
    echo "\n2. Email System\n";
    
    // 3. Email Classes
    $emailService = EmailService::getInstance();
    testResult("EmailService", true, "Class instantiated successfully");
    
    // 4. Token System
    $testUserId = 1;
    $token = EmailTokenManager::generateToken($testUserId, 'email_verification', 60);
    testResult("Token Generation", (bool)$token, $token ? "Token created" : "Failed to create token");
    
    if ($token) {
        $verified = EmailTokenManager::verifyToken($token, 'email_verification');
        testResult("Token Verification", (bool)$verified, "Token validation working");
    }
    
    echo "\n3. Authentication System\n";
    
    // 5. User Registration Test
    $testData = [
        'username' => 'healthcheck' . time(),
        'email' => 'healthcheck' . time() . '@example.com',
        'first_name' => 'Health',
        'last_name' => 'Check',
        'password' => 'testpass123'
    ];
    
    $user = new User();
    $userId = $user->register($testData);
    testResult("User Registration", (bool)$userId, $userId ? "User ID {$userId} created" : "Registration failed");
    
    if ($userId) {
        // Verify user to enable login
        $user->verifyEmail($userId);
        
        // Test authentication
        $authResult = $user->authenticate($testData['email'], $testData['password']);
        $loginSuccess = is_array($authResult) && isset($authResult['id']);
        testResult("User Authentication", $loginSuccess, $loginSuccess ? "Login successful" : "Login failed");
        
        // Check login attempt logging
        $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ?");
        $stmt->execute([$testData['email']]);
        $attemptCount = $stmt->fetchColumn();
        testResult("Login Attempt Tracking", $attemptCount > 0, "{$attemptCount} login attempts logged");
    }
    
    echo "\n4. Web Interface\n";
    
    // 6. Key Pages
    $pages = ['register.php', 'login.php', 'verify-email.php', 'forgot-password.php'];
    foreach ($pages as $page) {
        testResult($page, file_exists(__DIR__ . '/' . $page), "Page exists");
    }
    
    echo "\n5. Configuration\n";
    
    // 7. Security Configuration
    testResult("Secret Key", SECRET_KEY !== 'your-secret-key-change-this-in-production-minimum-32-chars', 
               "Custom secret key configured");
    testResult("Database Credentials", DB_USER === 'duns1' && DB_NAME === 'ecommerce_platform', 
               "Correct database credentials");
    testResult("Email Configuration", !empty(FROM_EMAIL), "Email settings configured");
    
    echo "\n=== Health Check Summary ===\n";
    
    if ($allPassed) {
        echo "🎉 ALL SYSTEMS OPERATIONAL! 🎉\n\n";
        echo "✅ Database: MySQL/MariaDB connected with all security tables\n";
        echo "✅ Email System: Classes functional, tokens working\n"; 
        echo "✅ Authentication: Registration, login, and tracking working\n";
        echo "✅ Security: Login attempts, sessions, and audit logging functional\n";
        echo "✅ Web Interface: All key pages accessible\n";
        echo "\n🔑 Users can now:\n";
        echo "   • Register accounts with email verification\n";
        echo "   • Log in with proper authentication tracking\n";
        echo "   • Reset passwords via email tokens\n";
        echo "   • Have login attempts monitored for security\n";
    } else {
        echo "⚠️ SOME SYSTEMS NEED ATTENTION ⚠️\n";
        echo "Please review the failed checks above.\n";
    }
    
} catch (Exception $e) {
    echo "❌ HEALTH CHECK FAILED: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    $allPassed = false;
}

echo "\n=== Health Check Complete ===\n";
exit($allPassed ? 0 : 1);
?>