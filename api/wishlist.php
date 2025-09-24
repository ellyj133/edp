<?php
/**
 * Wishlist API Endpoint
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!Session::isLoggedIn()) {
    errorResponse('Please login to manage your wishlist', 401);
}

$userId = Session::getUserId();
$wishlist = new Wishlist();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $items = $wishlist->getUserWishlist($userId);
        successResponse(['items' => $items]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $productId = (int)($input['product_id'] ?? 0);
                
                if ($productId <= 0) {
                    errorResponse('Invalid product');
                }
                
                // Check if product exists
                $product = new Product();
                $productData = $product->find($productId);
                
                if (!$productData) {
                    errorResponse('Product not found');
                }
                
                $result = $wishlist->addToWishlist($userId, $productId);
                
                if ($result) {
                    successResponse(['message' => 'Item added to wishlist']);
                } else {
                    errorResponse('Item already in wishlist or failed to add');
                }
                break;
                
            case 'remove':
                $productId = (int)($input['product_id'] ?? 0);
                
                if ($productId <= 0) {
                    errorResponse('Invalid product');
                }
                
                $result = $wishlist->removeFromWishlist($userId, $productId);
                
                if ($result) {
                    successResponse(['message' => 'Item removed from wishlist']);
                } else {
                    errorResponse('Failed to remove item');
                }
                break;
                
            case 'check':
                $productId = (int)($input['product_id'] ?? 0);
                
                if ($productId <= 0) {
                    errorResponse('Invalid product');
                }
                
                $inWishlist = $wishlist->isInWishlist($userId, $productId);
                successResponse(['in_wishlist' => $inWishlist]);
                break;
                
            default:
                errorResponse('Invalid action');
                break;
        }
    } else {
        errorResponse('Method not allowed', 405);
    }
} catch (Exception $e) {
    Logger::error('Wishlist API error: ' . $e->getMessage());
    errorResponse('An error occurred', 500);
}
?>