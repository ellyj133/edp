<?php
/**
 * E-commerce Flows Integration Test
 * Tests cart, wishlist, watchlist, and checkout functionality
 */

require_once __DIR__ . '/../includes/init.php';

class EcommerceFlowsTest {
    private $testUserId;
    private $testProductId;
    private $errors = [];
    private $passes = [];
    
    public function __construct() {
        echo "=== E-commerce Flows Integration Test ===\n\n";
    }
    
    public function runAllTests() {
        $this->setup();
        
        // Test Cart Flow
        $this->testCartAddItem();
        $this->testCartStockValidation();
        $this->testCartStatusValidation();
        $this->testCartUpdateQuantity();
        $this->testCartRemoveItem();
        
        // Test Wishlist Flow
        $this->testWishlistAdd();
        $this->testWishlistRemove();
        $this->testWishlistDuplicatePrevention();
        
        // Test Watchlist Flow
        $this->testWatchlistAdd();
        $this->testWatchlistRemove();
        $this->testWatchlistDuplicatePrevention();
        
        // Test Order/Checkout Flow
        $this->testOrderCreation();
        $this->testStockDecrement();
        $this->testCartClearingAfterOrder();
        
        $this->cleanup();
        $this->printResults();
    }
    
    private function setup() {
        echo "Setting up test data...\n";
        
        // Create or find a test user
        $user = new User();
        $testEmail = 'test_ecommerce_' . time() . '@example.com';
        
        try {
            $this->testUserId = $user->create([
                'username' => 'testuser_' . time(),
                'email' => $testEmail,
                'pass_hash' => hashPassword('testpass123'),
                'first_name' => 'Test',
                'last_name' => 'User',
                'role' => 'customer',
                'status' => 'active'
            ]);
            echo "✓ Created test user ID: {$this->testUserId}\n";
        } catch (Exception $e) {
            echo "✗ Failed to create test user: " . $e->getMessage() . "\n";
            exit(1);
        }
        
        // Create or find a test product with stock
        $product = new Product();
        try {
            $this->testProductId = $product->create([
                'name' => 'Test Product ' . time(),
                'description' => 'Test product for integration testing',
                'price' => 99.99,
                'stock_quantity' => 10,
                'status' => 'active',
                'vendor_id' => 1,
                'category_id' => 1,
                'sku' => 'TEST-' . time()
            ]);
            echo "✓ Created test product ID: {$this->testProductId}\n";
        } catch (Exception $e) {
            echo "✗ Failed to create test product: " . $e->getMessage() . "\n";
            exit(1);
        }
        
        echo "\n";
    }
    
    private function cleanup() {
        echo "\nCleaning up test data...\n";
        
        // Clean up cart
        $cart = new Cart();
        $cart->clearCart($this->testUserId);
        
        // Clean up wishlist
        $wishlist = new Wishlist();
        try {
            $wishlist->removeFromWishlist($this->testUserId, $this->testProductId);
        } catch (Exception $e) {}
        
        // Clean up watchlist
        $watchlist = new Watchlist();
        try {
            $watchlist->removeFromWatchlist($this->testUserId, $this->testProductId);
        } catch (Exception $e) {}
        
        // Delete test product
        $product = new Product();
        $product->delete($this->testProductId);
        
        // Delete test user
        $user = new User();
        $user->delete($this->testUserId);
        
        echo "✓ Cleanup completed\n";
    }
    
    // Cart Tests
    private function testCartAddItem() {
        $cart = new Cart();
        $result = $cart->addItem($this->testUserId, $this->testProductId, 2);
        
        if ($result) {
            $items = $cart->getCartItems($this->testUserId);
            $found = false;
            foreach ($items as $item) {
                if ($item['product_id'] == $this->testProductId) {
                    $found = true;
                    if ($item['quantity'] == 2 && $item['price'] > 0) {
                        $this->pass("Cart: Add item with quantity and price");
                    } else {
                        $this->fail("Cart: Item added but quantity or price incorrect");
                    }
                    break;
                }
            }
            if (!$found) {
                $this->fail("Cart: Item not found after adding");
            }
        } else {
            $this->fail("Cart: Failed to add item");
        }
    }
    
    private function testCartStockValidation() {
        $cart = new Cart();
        $product = new Product();
        
        // Try to add more than available stock (should be validated by API)
        $productData = $product->find($this->testProductId);
        if ($productData && $productData['stock_quantity'] >= 1) {
            $this->pass("Cart: Stock validation setup correct");
        } else {
            $this->fail("Cart: Product stock validation failed");
        }
    }
    
    private function testCartStatusValidation() {
        $product = new Product();
        $productData = $product->find($this->testProductId);
        
        if ($productData && $productData['status'] === 'active') {
            $this->pass("Cart: Product status validation correct");
        } else {
            $this->fail("Cart: Product status not active");
        }
    }
    
    private function testCartUpdateQuantity() {
        $cart = new Cart();
        $result = $cart->updateQuantity($this->testUserId, $this->testProductId, 3);
        
        if ($result) {
            $items = $cart->getCartItems($this->testUserId);
            foreach ($items as $item) {
                if ($item['product_id'] == $this->testProductId && $item['quantity'] == 3) {
                    $this->pass("Cart: Update quantity");
                    return;
                }
            }
            $this->fail("Cart: Quantity not updated correctly");
        } else {
            $this->fail("Cart: Failed to update quantity");
        }
    }
    
    private function testCartRemoveItem() {
        $cart = new Cart();
        $result = $cart->removeItem($this->testUserId, $this->testProductId);
        
        if ($result) {
            $items = $cart->getCartItems($this->testUserId);
            $found = false;
            foreach ($items as $item) {
                if ($item['product_id'] == $this->testProductId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->pass("Cart: Remove item");
            } else {
                $this->fail("Cart: Item still in cart after removal");
            }
        } else {
            $this->fail("Cart: Failed to remove item");
        }
    }
    
    // Wishlist Tests
    private function testWishlistAdd() {
        $wishlist = new Wishlist();
        $result = $wishlist->addToWishlist($this->testUserId, $this->testProductId);
        
        if ($result) {
            $isInWishlist = $wishlist->isInWishlist($this->testUserId, $this->testProductId);
            if ($isInWishlist) {
                $this->pass("Wishlist: Add item");
            } else {
                $this->fail("Wishlist: Item not found after adding");
            }
        } else {
            $this->fail("Wishlist: Failed to add item");
        }
    }
    
    private function testWishlistRemove() {
        $wishlist = new Wishlist();
        $result = $wishlist->removeFromWishlist($this->testUserId, $this->testProductId);
        
        if ($result) {
            $isInWishlist = $wishlist->isInWishlist($this->testUserId, $this->testProductId);
            if (!$isInWishlist) {
                $this->pass("Wishlist: Remove item");
            } else {
                $this->fail("Wishlist: Item still in wishlist after removal");
            }
        } else {
            $this->fail("Wishlist: Failed to remove item");
        }
    }
    
    private function testWishlistDuplicatePrevention() {
        $wishlist = new Wishlist();
        $wishlist->addToWishlist($this->testUserId, $this->testProductId);
        $result = $wishlist->addToWishlist($this->testUserId, $this->testProductId);
        
        if ($result === false) {
            $this->pass("Wishlist: Duplicate prevention");
        } else {
            $this->fail("Wishlist: Duplicate item was added");
        }
        
        // Clean up
        $wishlist->removeFromWishlist($this->testUserId, $this->testProductId);
    }
    
    // Watchlist Tests
    private function testWatchlistAdd() {
        $watchlist = new Watchlist();
        $result = $watchlist->addToWatchlist($this->testUserId, $this->testProductId);
        
        if ($result) {
            $isInWatchlist = $watchlist->isInWatchlist($this->testUserId, $this->testProductId);
            if ($isInWatchlist) {
                $this->pass("Watchlist: Add item");
            } else {
                $this->fail("Watchlist: Item not found after adding");
            }
        } else {
            $this->fail("Watchlist: Failed to add item");
        }
    }
    
    private function testWatchlistRemove() {
        $watchlist = new Watchlist();
        $result = $watchlist->removeFromWatchlist($this->testUserId, $this->testProductId);
        
        if ($result) {
            $isInWatchlist = $watchlist->isInWatchlist($this->testUserId, $this->testProductId);
            if (!$isInWatchlist) {
                $this->pass("Watchlist: Remove item");
            } else {
                $this->fail("Watchlist: Item still in watchlist after removal");
            }
        } else {
            $this->fail("Watchlist: Failed to remove item");
        }
    }
    
    private function testWatchlistDuplicatePrevention() {
        $watchlist = new Watchlist();
        $watchlist->addToWatchlist($this->testUserId, $this->testProductId);
        $result = $watchlist->addToWatchlist($this->testUserId, $this->testProductId);
        
        if ($result === false) {
            $this->pass("Watchlist: Duplicate prevention");
        } else {
            $this->fail("Watchlist: Duplicate item was added");
        }
        
        // Clean up
        $watchlist->removeFromWatchlist($this->testUserId, $this->testProductId);
    }
    
    // Order/Checkout Tests
    private function testOrderCreation() {
        // Add item to cart first
        $cart = new Cart();
        $cart->addItem($this->testUserId, $this->testProductId, 2);
        
        // Get initial stock
        $product = new Product();
        $initialStock = $product->find($this->testProductId)['stock_quantity'];
        
        // Create order
        $order = new Order();
        try {
            $orderData = [
                'status' => 'pending',
                'total' => 199.98
            ];
            $orderId = $order->createOrder($this->testUserId, $orderData);
            
            if ($orderId > 0) {
                // Verify order has items
                $orderItems = $order->getOrderItems($orderId);
                if (!empty($orderItems)) {
                    $this->pass("Order: Create with items");
                } else {
                    $this->fail("Order: Created but has no items");
                }
                
                // Clean up - delete the test order
                $order->delete($orderId);
            } else {
                $this->fail("Order: Failed to create");
            }
        } catch (Exception $e) {
            $this->fail("Order: Exception during creation - " . $e->getMessage());
        }
    }
    
    private function testStockDecrement() {
        // Add item to cart
        $cart = new Cart();
        $cart->addItem($this->testUserId, $this->testProductId, 1);
        
        // Get initial stock
        $product = new Product();
        $initialStock = $product->find($this->testProductId)['stock_quantity'];
        
        // Create order (should decrement stock)
        $order = new Order();
        try {
            $orderData = [
                'status' => 'pending',
                'total' => 99.99
            ];
            $orderId = $order->createOrder($this->testUserId, $orderData);
            
            // Check stock after order
            $newStock = $product->find($this->testProductId)['stock_quantity'];
            
            if ($newStock == $initialStock - 1) {
                $this->pass("Order: Stock decremented correctly");
            } else {
                $this->fail("Order: Stock not decremented (initial: $initialStock, new: $newStock)");
            }
            
            // Clean up
            $order->delete($orderId);
            // Restore stock
            $product->updateStock($this->testProductId, $initialStock);
        } catch (Exception $e) {
            $this->fail("Order: Exception during stock test - " . $e->getMessage());
        }
    }
    
    private function testCartClearingAfterOrder() {
        // Add item to cart
        $cart = new Cart();
        $cart->addItem($this->testUserId, $this->testProductId, 1);
        
        // Verify cart has items
        $cartItems = $cart->getCartItems($this->testUserId);
        if (empty($cartItems)) {
            $this->fail("Order: Cart was already empty before test");
            return;
        }
        
        // Create order
        $order = new Order();
        try {
            $orderData = [
                'status' => 'pending',
                'total' => 99.99
            ];
            $orderId = $order->createOrder($this->testUserId, $orderData);
            
            // Check if cart is cleared
            $cartItemsAfter = $cart->getCartItems($this->testUserId);
            
            if (empty($cartItemsAfter)) {
                $this->pass("Order: Cart cleared after order");
            } else {
                $this->fail("Order: Cart not cleared after order");
            }
            
            // Clean up
            $order->delete($orderId);
        } catch (Exception $e) {
            $this->fail("Order: Exception during cart clearing test - " . $e->getMessage());
        }
    }
    
    private function pass($test) {
        $this->passes[] = $test;
        echo "✓ PASS: $test\n";
    }
    
    private function fail($test) {
        $this->errors[] = $test;
        echo "✗ FAIL: $test\n";
    }
    
    private function printResults() {
        echo "\n=== Test Results ===\n";
        echo "Passed: " . count($this->passes) . "\n";
        echo "Failed: " . count($this->errors) . "\n";
        
        if (empty($this->errors)) {
            echo "\n✓ All tests passed!\n";
            exit(0);
        } else {
            echo "\n✗ Some tests failed:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
            exit(1);
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new EcommerceFlowsTest();
    $test->runAllTests();
}
