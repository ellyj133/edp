<?php
/**
 * Comprehensive Test Suite for E-Commerce Platform
 * Tests seller, customer, and admin workflows
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/security.php';

class TestSuite {
    private $results = [];
    private $db;
    
    public function __construct() {
        $this->db = getDatabase();
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "<h1>E-Commerce Platform Test Suite</h1>\n";
        
        $this->testDatabaseConnection();
        $this->testUserRegistration();
        $this->testUserAuthentication();
        $this->testVendorCreation();
        $this->testProductManagement();
        $this->testOrderCreation();
        $this->testKycSubmission();
        $this->testAdminFunctions();
        $this->testSecurityMeasures();
        $this->testDataScoping();
        
        $this->displayResults();
    }
    
    /**
     * Test database connection and schema
     */
    private function testDatabaseConnection() {
        $this->startTest("Database Connection");
        
        try {
            // Test connection
            $this->assert($this->db !== null, "Database connection exists");
            
            // Test key tables exist
            $tables = ['users', 'vendors', 'products', 'orders', 'seller_kyc'];
            foreach ($tables as $table) {
                $stmt = $this->db->query("SHOW TABLES LIKE '$table'");
                $this->assert($stmt->rowCount() > 0, "Table '$table' exists");
            }
            
            $this->passTest();
        } catch (Exception $e) {
            $this->failTest($e->getMessage());
        }
    }
    
    /**
     * Test user registration workflow
     */
    private function testUserRegistration() {
        $this->startTest("User Registration");
        
        try {
            $testEmail = 'test_user_' . time() . '@example.com';
            $testPassword = 'TestPassword123!';
            
            // Test User class creation
            $user = new User();
            $this->assert($user !== null, "User class instantiated");
            
            // Test user creation
            $userData = [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $testEmail,
                'username' => 'testuser' . time(),
                'password' => $testPassword
            ];
            
            $userId = $user->create($userData);
            $this->assert($userId > 0, "User created successfully");
            
            // Test user retrieval
            $retrievedUser = $user->find($userId);
            $this->assert($retrievedUser['email'] === $testEmail, "User data retrieved correctly");
            
            // Clean up
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            
            $this->passTest();
        } catch (Exception $e) {
            $this->failTest($e->getMessage());
        }
    }
    
    /**
     * Test user authentication
     */
    private function testUserAuthentication() {
        $this->startTest("User Authentication");
        
        try {
            // Create test user
            $testEmail = 'auth_test_' . time() . '@example.com';
            $testPassword = 'TestPassword123!';
            
            $user = new User();
            $userData = [
                'first_name' => 'Auth',
                'last_name' => 'Test',
                'email' => $testEmail,
                'username' => 'authtest' . time(),
                'password' => $testPassword
            ];
            
            $userId = $user->create($userData);
            
            // Test login
            $loginResult = $user->login($testEmail, $testPassword);
            $this->assert($loginResult !== false, "User login successful");
            
            // Test session
            $this->assert(Session::isLoggedIn(), "Session established");
            $this->assert(Session::getUserId() == $userId, "Correct user ID in session");
            
            // Test logout
            Session::logout();
            $this->assert(!Session::isLoggedIn(), "Session cleared on logout");
            
            // Clean up
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            
            $this->passTest();
        } catch (Exception $e) {
            $this->failTest($e->getMessage());
        }
    }
    
    /**
     * Test vendor creation and management
     */
    private function testVendorCreation() {
        $this->startTest("Vendor Creation");
        
        try {
            // Create test user first
            $user = new User();
            $userData = [
                'first_name' => 'Vendor',
                'last_name' => 'Test',
                'email' => 'vendor_test_' . time() . '@example.com',
                'username' => 'vendortest' . time(),
                'password' => 'VendorPass123!'
            ];
            
            $userId = $user->create($userData);
            
            // Create vendor
            $vendor = new Vendor();
            $vendorData = [
                'user_id' => $userId,
                'business_name' => 'Test Business',
                'business_type' => 'LLC',
                'business_email' => 'business@example.com'
            ];
            
            $vendorId = $vendor->create($vendorData);
            $this->assert($vendorId > 0, "Vendor created successfully");
            
            // Test vendor retrieval
            $retrievedVendor = $vendor->find($vendorId);
            $this->assert($retrievedVendor['business_name'] === 'Test Business', "Vendor data retrieved correctly");
            
            // Test vendor-user relationship
            $userVendor = $vendor->findByUserId($userId);
            $this->assert($userVendor['id'] == $vendorId, "Vendor-user relationship established");
            
            // Clean up
            $this->db->prepare("DELETE FROM vendors WHERE id = ?")->execute([$vendorId]);
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            
            $this->passTest();
        } catch (Exception $e) {
            $this->failTest($e->getMessage());
        }
    }
    
    /**
     * Test product management
     */
    private function testProductManagement() {
        $this->startTest("Product Management");
        
        try {
            // Create test vendor
            $user = new User();
            $userData = [
                'first_name' => 'Product',
                'last_name' => 'Vendor',
                'email' => 'product_vendor_' . time() . '@example.com',
                'username' => 'productvendor' . time(),
                'password' => 'ProductPass123!'
            ];
            
            $userId = $user->create($userData);
            
            $vendor = new Vendor();
            $vendorData = [
                'user_id' => $userId,
                'business_name' => 'Product Business',
                'business_type' => 'LLC'
            ];
            
            $vendorId = $vendor->create($vendorData);
            
            // Create product
            $product = new Product();
            $productData = [
                'vendor_id' => $vendorId,
                'name' => 'Test Product',
                'description' => 'A test product for testing',
                'price' => 19.99,
                'category_id' => 1,
                'status' => 'active'
            ];
            
            $productId = $product->create($productData);
            $this->assert($productId > 0, "Product created successfully");
            
            // Test product retrieval
            $retrievedProduct = $product->find($productId);
            $this->assert($retrievedProduct['name'] === 'Test Product', "Product data retrieved correctly");
            
            // Test vendor product listing
            $vendorProducts = $product->getByVendor($vendorId);
            $this->assert(count($vendorProducts) > 0, "Vendor products retrieved");
            $this->assert($vendorProducts[0]['id'] == $productId, "Correct product in vendor listing");
            
            // Clean up
            $this->db->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
            $this->db->prepare("DELETE FROM vendors WHERE id = ?")->execute([$vendorId]);
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            
            $this->passTest();
        } catch (Exception $e) {
            $this->failTest($e->getMessage());
        }
    }
    
    /**
     * Test order creation and management
     */
    private function testOrderCreation() {
        $this->startTest("Order Management");
        
        try {
            // Create test users and vendor
            $user = new User();
            $customer = $user->create([
                'first_name' => 'Customer',
                'last_name' => 'Test',
                'email' => 'customer_' . time() . '@example.com',
                'username' => 'customer' . time(),
                'password' => 'CustomerPass123!'
            ]);
            
            $vendorUser = $user->create([
                'first_name' => 'Order',
                'last_name' => 'Vendor',
                'email' => 'order_vendor_' . time() . '@example.com',
                'username' => 'ordervendor' . time(),
                'password' => 'OrderPass123!'
            ]);
            
            $vendor = new Vendor();
            $vendorId = $vendor->create([
                'user_id' => $vendorUser,
                'business_name' => 'Order Business'
            ]);
            
            // Create order
            $order = new Order();
            $orderData = [
                'user_id' => $customer,
                'vendor_id' => $vendorId,
                'total' => 29.99,
                'status' => 'pending'
            ];
            
            $orderId = $order->create($orderData);
            $this->assert($orderId > 0, "Order created successfully");
            
            // Test order retrieval
            $retrievedOrder = $order->find($orderId);
            $this->assert($retrievedOrder['total'] == 29.99, "Order data retrieved correctly");
            
            // Test customer orders
            $customerOrders = $order->getUserOrders($customer);
            $this->assert(count($customerOrders) > 0, "Customer orders retrieved");
            
            // Test vendor orders
            $vendorOrders = $order->getVendorOrders($vendorId);
            $this->assert(count($vendorOrders) > 0, "Vendor orders retrieved");
            
            // Clean up
            $this->db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
            $this->db->prepare("DELETE FROM vendors WHERE id = ?")->execute([$vendorId]);
            $this->db->prepare("DELETE FROM users WHERE id IN (?, ?)")->execute([$customer, $vendorUser]);
            
            $this->passTest();
        } catch (Exception $e) {
            $this->failTest($e->getMessage());
        }
    }
    
    /**
     * Test KYC submission
     */
    private function testKycSubmission() {
        $this->startTest("KYC Submission");
        
        try {
            // Create test vendor
            $user = new User();
            $userId = $user->create([
                'first_name' => 'KYC',
                'last_name' => 'Test',
                'email' => 'kyc_test_' . time() . '@example.com',
                'username' => 'kyctest' . time(),
                'password' => 'KycPass123!'
            ]);
            
            $vendor = new Vendor();
            $vendorId = $vendor->create([
                'user_id' => $userId,
                'business_name' => 'KYC Business'
            ]);
            
            // Submit KYC
            $stmt = $this->db->prepare("
                INSERT INTO seller_kyc 
                (vendor_id, verification_type, verification_status)
                VALUES (?, 'business', 'pending')
            ");
            $stmt->execute([$vendorId]);
            $kycId = $this->db->lastInsertId();
            
            $this->assert($kycId > 0, "KYC submission created");
            
            // Test KYC retrieval
            $stmt = $this->db->prepare("SELECT * FROM seller_kyc WHERE id = ?");
            $stmt->execute([$kycId]);
            $kyc = $stmt->fetch();
            
            $this->assert($kyc['verification_status'] === 'pending', "KYC status correct");
            $this->assert($kyc['vendor_id'] == $vendorId, "KYC linked to correct vendor");
            
            // Clean up
            $this->db->prepare("DELETE FROM seller_kyc WHERE id = ?")->execute([$kycId]);
            $this->db->prepare("DELETE FROM vendors WHERE id = ?")->execute([$vendorId]);
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            
            $this->passTest();
        } catch (Exception $e) {
            $this->failTest($e->getMessage());
        }
    }
    
    /**
     * Test admin functions
     */
    private function testAdminFunctions() {
        $this->startTest("Admin Functions");
        
        try {
            // Test admin pages exist
            $adminPages = [
                '/admin/index.php',
                '/admin/kyc/index.php',
                '/admin/users.php'
            ];
            
            foreach ($adminPages as $page) {
                $this->assert(file_exists(__DIR__ . '/..' . $page), "Admin page $page exists");
            }
            
            // Test auth include
            $this->assert(file_exists(__DIR__ . '/../includes/auth.php'), "Auth include exists");
            
            $this->passTest();
        } catch (Exception $e) {
            $this->failTest($e->getMessage());
        }
    }
    
    /**
     * Test security measures
     */
    private function testSecurityMeasures() {
        $this->startTest("Security Measures");
        
        try {
            // Test CSRF protection
            $token = CsrfProtection::generateToken();
            $this->assert(!empty($token), "CSRF token generated");
            $this->assert(CsrfProtection::verifyToken($token), "CSRF token verified");
            
            // Test input validation
            $email = InputValidator::validateEmail('test@example.com');
            $this->assert($email === 'test@example.com', "Email validation works");
            
            $invalidEmail = InputValidator::validateEmail('invalid-email');
            $this->assert($invalidEmail === false, "Invalid email rejected");
            
            // Test string sanitization
            $dirty = '<script>alert("xss")</script>Hello';
            $clean = InputValidator::sanitizeString($dirty);
            $this->assert(strpos($clean, '<script>') === false, "XSS content removed");
            
            $this->passTest();
        } catch (Exception $e) {
            $this->failTest($e->getMessage());
        }
    }
    
    /**
     * Test data scoping
     */
    private function testDataScoping() {
        $this->startTest("Data Scoping");
        
        try {
            // Test vendor ID retrieval
            $middleware = new DataScopeMiddleware();
            $this->assert(method_exists($middleware, 'getCurrentVendorId'), "DataScopeMiddleware exists");
            
            // Test ownership verification methods
            $this->assert(method_exists($middleware, 'verifyVendorOwnership'), "Vendor ownership verification exists");
            $this->assert(method_exists($middleware, 'verifyUserOwnership'), "User ownership verification exists");
            
            // Test scoped database
            $this->assert(class_exists('ScopedDatabase'), "ScopedDatabase class exists");
            
            $this->passTest();
        } catch (Exception $e) {
            $this->failTest($e->getMessage());
        }
    }
    
    /**
     * Helper methods for testing
     */
    private function startTest($name) {
        echo "<h3>Testing: $name</h3>\n";
        $this->currentTest = $name;
        $this->currentAssertions = 0;
    }
    
    private function assert($condition, $message) {
        $this->currentAssertions++;
        if ($condition) {
            echo "<span style='color: green;'>✓ $message</span><br>\n";
        } else {
            echo "<span style='color: red;'>✗ $message</span><br>\n";
            throw new Exception("Assertion failed: $message");
        }
    }
    
    private function passTest() {
        $this->results[$this->currentTest] = [
            'status' => 'PASS',
            'assertions' => $this->currentAssertions
        ];
        echo "<strong style='color: green;'>PASS ({$this->currentAssertions} assertions)</strong><br><br>\n";
    }
    
    private function failTest($error) {
        $this->results[$this->currentTest] = [
            'status' => 'FAIL',
            'error' => $error,
            'assertions' => $this->currentAssertions
        ];
        echo "<strong style='color: red;'>FAIL: $error</strong><br><br>\n";
    }
    
    private function displayResults() {
        echo "<h2>Test Results Summary</h2>\n";
        
        $passed = 0;
        $failed = 0;
        $totalAssertions = 0;
        
        foreach ($this->results as $test => $result) {
            $status = $result['status'];
            $assertions = $result['assertions'];
            $totalAssertions += $assertions;
            
            if ($status === 'PASS') {
                $passed++;
                echo "<div style='color: green;'>✓ $test ($assertions assertions)</div>\n";
            } else {
                $failed++;
                echo "<div style='color: red;'>✗ $test - {$result['error']}</div>\n";
            }
        }
        
        echo "<hr>\n";
        echo "<h3>Summary:</h3>\n";
        echo "<p><strong>Total Tests:</strong> " . count($this->results) . "</p>\n";
        echo "<p><strong style='color: green;'>Passed:</strong> $passed</p>\n";
        echo "<p><strong style='color: red;'>Failed:</strong> $failed</p>\n";
        echo "<p><strong>Total Assertions:</strong> $totalAssertions</p>\n";
        
        if ($failed === 0) {
            echo "<h2 style='color: green;'>🎉 All tests passed!</h2>\n";
        } else {
            echo "<h2 style='color: red;'>❌ Some tests failed. Please review the errors above.</h2>\n";
        }
    }
}

// Run tests if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'test_suite.php') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>E-Commerce Platform Test Suite</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            h1 { color: #333; }
            h2 { color: #666; }
            h3 { color: #999; }
            hr { margin: 20px 0; }
        </style>
    </head>
    <body>
    <?php
    
    $testSuite = new TestSuite();
    $testSuite->runAllTests();
    
    ?>
    </body>
    </html>
    <?php
}