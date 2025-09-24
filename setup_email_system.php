<?php
/**
 * Email System Setup
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

echo "=== Setting up Email System ===\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Creating mail_queue table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS mail_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            to_email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            template_name VARCHAR(100) NOT NULL,
            template_data TEXT,
            options TEXT,
            status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'failed')),
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL
        )
    ");
    
    echo "Creating email_tokens table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL CHECK (type IN ('email_verification', 'password_reset', 'account_deletion', 'email_change')),
            token_hash VARCHAR(64) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    echo "Creating email_log table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            to_email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            template_name VARCHAR(100),
            status TEXT NOT NULL CHECK (status IN ('sent', 'failed', 'bounced')),
            error_message TEXT,
            sent_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Creating notifications table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            payload TEXT,
            read_at TIMESTAMP NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    echo "✅ All email system tables created successfully\n";
    
    // Test email functionality
    echo "\n=== Testing Email System ===\n";
    
    $emailService = EmailService::getInstance();
    
    // Queue a test email (won't actually send without proper SMTP)
    $testResult = $emailService->send(
        'test@example.com',
        'Email System Test',
        'welcome',
        [
            'user' => [
                'first_name' => 'Test',
                'email' => 'test@example.com',
                'role' => 'customer'
            ]
        ]
    );
    
    if ($testResult) {
        echo "✅ Test email queued successfully\n";
    } else {
        echo "❌ Test email queueing failed\n";
    }
    
    // Test token generation
    $token = EmailTokenManager::generateToken(1, 'email_verification');
    if ($token) {
        echo "✅ Email token generation working (token: " . substr($token, 0, 10) . "...)\n";
        
        // Test token verification
        $verifyResult = EmailTokenManager::verifyToken($token, 'email_verification');
        if ($verifyResult) {
            echo "✅ Email token verification working\n";
        } else {
            echo "❌ Email token verification failed\n";
        }
    } else {
        echo "❌ Email token generation failed\n";
    }
    
    echo "\n=== Setup Complete ===\n";
    echo "Email system is ready for use!\n";
    echo "Next steps:\n";
    echo "1. Configure SMTP settings in config/config.php\n";
    echo "2. Set up cron job: '*/5 * * * * php " . __DIR__ . "/process_email_queue.php'\n";
    echo "3. Test email delivery in production\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>