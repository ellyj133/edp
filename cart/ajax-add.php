<?php
/**
 * AJAX Add to Cart Handler
 * Handles adding products to user's shopping cart via AJAX
 */

require_once __DIR__ . '/../includes/init.php';

// Set JSON content type
header('Content-Type: application/json');

// Ensure this is an AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Check if user is logged in
    if (!Session::isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'login_required' => true,
            'message' => 'Please log in to add items to cart'
        ]);
        exit;
    }
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (empty($data['product_id'])) {
        throw new Exception('Product ID is required');
    }
    
    $product_id = (int)$data['product_id'];
    $quantity = (int)($data['quantity'] ?? 1);
    $user_id = Session::getUserId();
    
    if ($quantity <= 0) {
        throw new Exception('Quantity must be greater than 0');
    }
    
    // Use existing Cart model if available
    if (class_exists('Cart')) {
        $cart = new Cart();
        $productModel = new Product();
        
        // Validate product exists and is available
        $product = $productModel->find($product_id);
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        if ($product['status'] !== 'active') {
            throw new Exception('Product is not available');
        }
        
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception('Insufficient stock available');
        }
        
        // Check if product already exists in cart
        $existingItems = $cart->getCartItems($user_id);
        $existingCartItem = null;
        foreach ($existingItems as $item) {
            if ($item['product_id'] == $product_id) {
                $existingCartItem = $item;
                break;
            }
        }
        
        if ($existingCartItem) {
            // Update quantity if item already exists
            $newQuantity = $existingCartItem['quantity'] + $quantity;
            
            // Check if new quantity exceeds stock
            if ($newQuantity > $product['stock_quantity']) {
                throw new Exception('Cannot add more items - insufficient stock');
            }
            
            $cart->updateQuantity($user_id, $product_id, $newQuantity);
        } else {
            // Add new item to cart
            $cart->addItem($user_id, $product_id, $quantity);
        }
        
        // Get updated cart count
        $cart_count = $cart->getCartCount($user_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'product_name' => $product['name'],
            'cart_count' => (int)$cart_count
        ]);
        
    } else {
        // Fallback to direct database operations
        $pdo = db();
        
        // Check if product exists and is available
        $product_sql = "SELECT id, name, price, stock_quantity, status FROM products WHERE id = ?";
        $product_stmt = $pdo->prepare($product_sql);
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch();
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        if ($product['status'] !== 'active') {
            throw new Exception('Product is not available');
        }
        
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception('Insufficient stock available');
        }
        
        // Check if item already exists in cart
        $cart_check_sql = "SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?";
        $cart_check_stmt = $pdo->prepare($cart_check_sql);
        $cart_check_stmt->execute([$user_id, $product_id]);
        $existing_item = $cart_check_stmt->fetch();
        
        if ($existing_item) {
            // Update existing cart item
            $new_quantity = $existing_item['quantity'] + $quantity;
            
            // Check stock again for total quantity
            if ($product['stock_quantity'] < $new_quantity) {
                throw new Exception('Cannot add more items - insufficient stock');
            }
            
            $update_sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$new_quantity, $existing_item['id']]);
        } else {
            // Add new item to cart
            $insert_sql = "INSERT INTO cart_items (user_id, product_id, quantity, created_at, updated_at) 
                           VALUES (?, ?, ?, NOW(), NOW())";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([$user_id, $product_id, $quantity]);
        }
        
        // Get updated cart count
        $count_sql = "SELECT COALESCE(SUM(quantity), 0) as cart_count FROM cart_items WHERE user_id = ?";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute([$user_id]);
        $cart_count = $count_stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'product_name' => $product['name'],
            'cart_count' => (int)$cart_count
        ]);
    }

} catch (Exception $e) {
    error_log("AJAX Add to cart error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}