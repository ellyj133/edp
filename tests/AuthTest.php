<?php
/**
 * Authentication Flow Tests
 * Tests login/register/redirect functionality
 */

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    public function testPasswordHashingUsesArgon2ID()
    {
        // Load functions
        require_once __DIR__ . '/../includes/functions.php';
        
        $password = 'test123456';
        $hash = hashPassword($password);
        
        // Verify it's a valid hash
        $this->assertNotEmpty($hash);
        $this->assertTrue(password_verify($password, $hash));
        
        // If PHP supports Argon2ID, verify it's using it
        if (defined('PASSWORD_ARGON2ID')) {
            $this->assertStringStartsWith('$argon2id$', $hash,
                'Password should be hashed with Argon2ID when available');
        }
    }
    
    public function testCsrfTokenGeneration()
    {
        require_once __DIR__ . '/../includes/functions.php';
        
        // Start a mock session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token1 = csrfToken();
        $token2 = csrfToken();
        
        // Same session should return same token
        $this->assertEquals($token1, $token2);
        
        // Token should be hex string of reasonable length
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token1);
        
        // Token verification should work
        $this->assertTrue(verifyCsrfToken($token1));
        $this->assertFalse(verifyCsrfToken('invalid'));
    }
    
    public function testCsrfTokenInput()
    {
        require_once __DIR__ . '/../includes/functions.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $input = csrfTokenInput();
        
        // Should generate proper HTML input
        $this->assertStringContainsString('<input type="hidden"', $input);
        $this->assertStringContainsString('name="csrf_token"', $input);
        $this->assertStringContainsString('value="', $input);
    }
    
    public function testSessionClassBasicFunctionality()
    {
        require_once __DIR__ . '/../includes/functions.php';
        
        // Test basic session operations
        Session::set('test_key', 'test_value');
        $this->assertEquals('test_value', Session::get('test_key'));
        
        // Test default values
        $this->assertEquals('default', Session::get('nonexistent', 'default'));
        
        // Test removal
        Session::remove('test_key');
        $this->assertNull(Session::get('test_key'));
    }
    
    public function testUserRoleChecking()
    {
        require_once __DIR__ . '/../includes/functions.php';
        
        // Mock a logged-in user session
        Session::set('user_id', 123);
        Session::set('user_role', 'customer');
        
        $this->assertTrue(Session::isLoggedIn());
        $this->assertEquals(123, Session::getUserId());
        $this->assertEquals('customer', Session::getUserRole());
    }
}