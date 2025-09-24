<?php
/**
 * Comprehensive Integration Tests
 * Tests core eCommerce flows end-to-end
 */

require_once __DIR__ . '/includes/init.php';

class ECommerceIntegrationTest {
    private $testUsers = [];
    private $testProducts = [];
    private $testOrders = [];
    
    public function __construct() {
        echo "🧪 Starting E-Commerce Integration Tests\n";
        echo "=====================================\n\n";
    }
    
    public function runAllTests() {
        $results = [];
        
        try {
            // Core functionality tests
            $results['database'] = $this->testDatabaseConnection();
            $results['url_helper'] = $this->testUrlHelper();
            $results['user_registration'] = $this->testUserRegistration();
            $results['email_verification'] = $this->testEmailVerification();
            $results['user_login'] = $this->testUserLogin();
            $results['product_creation'] = $this->testProductCreation();
            $results['shopping_cart'] = $this->testShoppingCart();
            $results['checkout_process'] = $this->testCheckoutProcess();
            $results['order_management'] = $this->testOrderManagement();
            $results['payment_processing'] = $this->testPaymentProcessing();
            $results['admin_access'] = $this->testAdminAccess();
            $results['seller_registration'] = $this->testSellerRegistration();
            
            // Clean up test data
            $this->cleanup();
            
        } catch (Exception $e) {
            echo "❌ Critical test failure: " . $e->getMessage() . "\n";
            $results['critical_error'] = false;
        }
        
        $this->generateReport($results);
        return $results;
    }
    
    private function testDatabaseConnection() {
        echo "🔌 Testing database connection...\n";
        
        try {
            $pdo = db();
            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetchColumn();
            
            if ($result == 1) {
                echo "✅ Database connection successful\n\n";
                return true;
            } else {
                echo "❌ Database connection failed\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testUrlHelper() {
        echo "🔗 Testing URL helper function...\n";
        
        try {
            $baseUrl = url();
            $pathUrl = url('/test/path');
            
            if (strpos($baseUrl, 'https://duns1.fezalogistics.com') !== false && 
                strpos($pathUrl, 'https://duns1.fezalogistics.com/test/path') !== false) {
                echo "✅ URL helper working correctly\n";
                echo "   Base URL: $baseUrl\n";
                echo "   Path URL: $pathUrl\n\n";
                return true;
            } else {
                echo "❌ URL helper not working correctly\n";
                echo "   Base URL: $baseUrl\n";
                echo "   Path URL: $pathUrl\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ URL helper error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testUserRegistration() {
        echo "👤 Testing user registration...\n";
        
        try {
            $user = new User();
            $testEmail = 'test_' . uniqid() . '@example.com';
            
            $userData = [
                'username' => 'testuser_' . uniqid(),
                'email' => $testEmail,
                'pass_hash' => password_hash('testpassword123', PASSWORD_ARGON2ID),
                'first_name' => 'Test',
                'last_name' => 'User',
                'role' => 'customer',
                'status' => 'pending'
            ];
            
            $userId = $user->create($userData);
            
            if ($userId) {
                $this->testUsers[] = $userId;
                echo "✅ User registration successful (ID: $userId)\n";
                echo "   Email: $testEmail\n\n";
                return true;
            } else {
                echo "❌ User registration failed\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ User registration error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testEmailVerification() {
        echo "📧 Testing email verification system...\n";
        
        try {
            if (empty($this->testUsers)) {
                throw new Exception('No test users available');
            }
            
            $userId = $this->testUsers[0];
            $user = new User();
            $userData = $user->find($userId);
            
            $tokenManager = new EmailTokenManager();
            $otp = $tokenManager->generateToken($userId, 'email_verification', $userData['email']);
            
            if ($otp && strlen($otp) === 6 && is_numeric($otp)) {
                echo "✅ OTP generation successful: $otp\n";
                
                // Test verification
                $verifyResult = $tokenManager->verifyToken($otp, 'email_verification', $userId, $userData['email']);
                
                if ($verifyResult['success']) {
                    echo "✅ OTP verification successful\n\n";
                    return true;
                } else {
                    echo "❌ OTP verification failed: " . $verifyResult['message'] . "\n\n";
                    return false;
                }
            } else {
                echo "❌ OTP generation failed\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Email verification error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testUserLogin() {
        echo "🔐 Testing user login...\n";
        
        try {
            if (empty($this->testUsers)) {
                throw new Exception('No test users available');
            }
            
            $user = new User();
            $userId = $this->testUsers[0];
            
            // Mark user as verified and active
            $user->update($userId, [
                'status' => 'active',
                'verified_at' => date('Y-m-d H:i:s')
            ]);
            
            $userData = $user->find($userId);
            $loginResult = $user->authenticate($userData['email'], 'testpassword123');
            
            if (isset($loginResult['user'])) {
                echo "✅ User login successful\n";
                echo "   User ID: " . $loginResult['user']['id'] . "\n\n";
                return true;
            } else {
                echo "❌ User login failed: " . ($loginResult['error'] ?? 'Unknown error') . "\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ User login error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testProductCreation() {
        echo "🛍️ Testing product creation...\n";
        
        try {
            $product = new Product();
            
            $productData = [
                'name' => 'Test Product ' . uniqid(),
                'description' => 'This is a test product for integration testing',
                'price' => 29.99,
                'category_id' => 1, // Assuming category 1 exists
                'vendor_id' => 1,   // Assuming vendor 1 exists
                'stock_quantity' => 100,
                'status' => 'active'
            ];
            
            $productId = $product->create($productData);
            
            if ($productId) {
                $this->testProducts[] = $productId;
                echo "✅ Product creation successful (ID: $productId)\n";
                echo "   Name: " . $productData['name'] . "\n\n";
                return true;
            } else {
                echo "❌ Product creation failed\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Product creation error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testShoppingCart() {
        echo "🛒 Testing shopping cart functionality...\n";
        
        try {
            if (empty($this->testUsers) || empty($this->testProducts)) {
                throw new Exception('No test users or products available');
            }
            
            $cart = new Cart();
            $userId = $this->testUsers[0];
            $productId = $this->testProducts[0];
            
            // Add item to cart
            $cartItemId = $cart->addItem($userId, $productId, 2);
            
            if ($cartItemId) {
                echo "✅ Item added to cart (ID: $cartItemId)\n";
                
                // Get cart items
                $cartItems = $cart->getCartItems($userId);
                
                if (count($cartItems) > 0) {
                    echo "✅ Cart retrieval successful (" . count($cartItems) . " items)\n\n";
                    return true;
                } else {
                    echo "❌ Cart retrieval failed\n\n";
                    return false;
                }
            } else {
                echo "❌ Failed to add item to cart\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Shopping cart error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testCheckoutProcess() {
        echo "💳 Testing checkout process...\n";
        
        try {
            if (empty($this->testUsers)) {
                throw new Exception('No test users available');
            }
            
            $order = new Order();
            $userId = $this->testUsers[0];
            
            $orderData = [
                'user_id' => $userId,
                'order_number' => 'TEST-' . strtoupper(uniqid()),
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => 59.98,
                'tax_amount' => 4.95,
                'shipping_amount' => 9.99,
                'total' => 74.92,
                'currency' => 'USD',
                'billing_address' => json_encode(['test' => 'address']),
                'shipping_address' => json_encode(['test' => 'address']),
                'placed_at' => date('Y-m-d H:i:s')
            ];
            
            $orderId = $order->create($orderData);
            
            if ($orderId) {
                $this->testOrders[] = $orderId;
                echo "✅ Order creation successful (ID: $orderId)\n";
                echo "   Order Number: " . $orderData['order_number'] . "\n\n";
                return true;
            } else {
                echo "❌ Order creation failed\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Checkout process error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testOrderManagement() {
        echo "📦 Testing order management...\n";
        
        try {
            if (empty($this->testOrders)) {
                throw new Exception('No test orders available');
            }
            
            $order = new Order();
            $orderId = $this->testOrders[0];
            
            // Test order status update
            $updated = $order->update($orderId, ['status' => 'processing']);
            
            if ($updated) {
                $orderData = $order->find($orderId);
                
                if ($orderData['status'] === 'processing') {
                    echo "✅ Order status update successful\n";
                    echo "   New status: " . $orderData['status'] . "\n\n";
                    return true;
                } else {
                    echo "❌ Order status not updated correctly\n\n";
                    return false;
                }
            } else {
                echo "❌ Order update failed\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Order management error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testPaymentProcessing() {
        echo "💰 Testing payment processing...\n";
        
        try {
            // Test mock payment gateway
            $mockGateway = PaymentGatewayFactory::create('mock');
            
            $paymentResult = $mockGateway->processPayment(100.00, 'test_token', [
                'order_number' => 'TEST-ORDER',
                'customer_email' => 'test@example.com'
            ]);
            
            if ($paymentResult['success']) {
                echo "✅ Mock payment processing successful\n";
                echo "   Transaction ID: " . $paymentResult['transaction_id'] . "\n\n";
                return true;
            } else {
                echo "❌ Mock payment processing failed: " . $paymentResult['error'] . "\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Payment processing error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testAdminAccess() {
        echo "🔧 Testing admin functionality...\n";
        
        try {
            // Test if admin models are working
            $user = new User();
            $product = new Product();
            $order = new Order();
            
            $userCount = $user->count();
            $productCount = $product->count();
            $orderCount = $order->count();
            
            echo "✅ Admin model access successful\n";
            echo "   Users: $userCount\n";
            echo "   Products: $productCount\n";
            echo "   Orders: $orderCount\n\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Admin functionality error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function testSellerRegistration() {
        echo "🏪 Testing seller registration...\n";
        
        try {
            if (empty($this->testUsers)) {
                throw new Exception('No test users available');
            }
            
            $vendor = new Vendor();
            $userId = $this->testUsers[0];
            
            $vendorData = [
                'user_id' => $userId,
                'business_name' => 'Test Store ' . uniqid(),
                'business_type' => 'individual',
                'description' => 'This is a test store for integration testing',
                'status' => 'pending'
            ];
            
            $vendorId = $vendor->create($vendorData);
            
            if ($vendorId) {
                echo "✅ Seller registration successful (ID: $vendorId)\n";
                echo "   Business Name: " . $vendorData['business_name'] . "\n\n";
                return true;
            } else {
                echo "❌ Seller registration failed\n\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Seller registration error: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
    
    private function cleanup() {
        echo "🧹 Cleaning up test data...\n";
        
        try {
            $db = db();
            
            // Clean up test orders
            foreach ($this->testOrders as $orderId) {
                $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
            }
            
            // Clean up test products
            foreach ($this->testProducts as $productId) {
                $db->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
            }
            
            // Clean up test users
            foreach ($this->testUsers as $userId) {
                $db->prepare("DELETE FROM vendors WHERE user_id = ?")->execute([$userId]);
                $db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
                $db->prepare("DELETE FROM email_tokens WHERE user_id = ?")->execute([$userId]);
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            }
            
            echo "✅ Test data cleanup completed\n\n";
        } catch (Exception $e) {
            echo "⚠️ Cleanup warning: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function generateReport($results) {
        echo "📊 TEST RESULTS SUMMARY\n";
        echo "=======================\n\n";
        
        $passed = 0;
        $total = 0;
        
        foreach ($results as $test => $result) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo sprintf("%-25s %s\n", ucwords(str_replace('_', ' ', $test)) . ':', $status);
            
            if ($result) $passed++;
            $total++;
        }
        
        echo "\n";
        echo "OVERALL SCORE: $passed/$total tests passed\n";
        
        if ($passed === $total) {
            echo "🎉 ALL TESTS PASSED! Platform is ready for deployment.\n";
        } else {
            echo "⚠️ Some tests failed. Please review and fix issues before deployment.\n";
        }
        
        echo "\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new ECommerceIntegrationTest();
    $results = $tester->runAllTests();
    
    // Exit with appropriate code for CI/CD
    $allPassed = array_reduce($results, function($carry, $result) {
        return $carry && $result;
    }, true);
    
    exit($allPassed ? 0 : 1);
}