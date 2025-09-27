<?php
/**
 * Additional Models
 * E-Commerce Platform
 */

/**
 * Order Model
 */
class Order extends BaseModel {
    protected $table = 'orders';
    
    public function createOrder($userId, $orderData) {
        try {
            $this->db->beginTransaction();
            
            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Insert order
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} 
                (user_id, order_number, subtotal, tax_amount, shipping_amount, discount_amount, total, 
                 shipping_address, billing_address, payment_method) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $orderNumber,
                $orderData['subtotal'],
                $orderData['tax_amount'],
                $orderData['shipping_amount'],
                $orderData['discount_amount'] ?? 0,
                $orderData['total'],
                $orderData['shipping_address'],
                $orderData['billing_address'],
                $orderData['payment_method']
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            // Add order items
            $cart = new Cart();
            $cartItems = $cart->getCartItems($userId);
            
            foreach ($cartItems as $item) {
                $this->addOrderItem($orderId, $item);
                
                // Decrease product stock
                $product = new Product();
                $product->decreaseStock($item['product_id'], $item['quantity']);
            }
            
            // Clear cart
            $cart->clearCart($userId);
            
            $this->db->commit();
            return $orderId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function addOrderItem($orderId, $item) {
        $stmt = $this->db->prepare("
            INSERT INTO order_items 
            (order_id, product_id, vendor_id, sku, qty, price, subtotal, product_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $product = new Product();
        $productData = $product->find($item['product_id']);
        
        return $stmt->execute([
            $orderId,
            $item['product_id'],
            $productData['vendor_id'],
            $item['sku'] ?? $productData['sku'],
            $item['quantity'],
            $item['price'],
            $item['quantity'] * $item['price'],
            $item['name'] ?? $productData['name']
        ]);
    }
    
    public function getUserOrders($userId, $limit = ORDERS_PER_PAGE, $offset = 0) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
        ";
        
        $params = [$userId];
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getOrderItems($orderId) {
        $stmt = $this->db->prepare("
            SELECT oi.*, p.name as current_product_name 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    
    public function updateStatus($orderId, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $orderId]);
    }
    
    public function updatePaymentStatus($orderId, $status, $transactionId = null) {
        $sql = "UPDATE {$this->table} SET payment_status = ?, updated_at = NOW()";
        $params = [$status];
        
        if ($transactionId) {
            $sql .= ", payment_transaction_id = ?";
            $params[] = $transactionId;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $orderId;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function getVendorOrders($vendorId, $limit = null, $offset = 0) {
        $sql = "
            SELECT DISTINCT o.*, u.first_name, u.last_name, u.email 
            FROM {$this->table} o 
            JOIN order_items oi ON o.id = oi.order_id 
            JOIN users u ON o.user_id = u.id 
            WHERE oi.vendor_id = ? 
            ORDER BY o.created_at DESC
        ";
        
        $params = [$vendorId];

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getOrderStats($vendorId = null) {
        $whereClause = $vendorId ? "WHERE oi.vendor_id = ?" : "";
        $params = $vendorId ? [$vendorId] : [];
        
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(oi.subtotal) as total_revenue,
                AVG(oi.subtotal) as average_order_value,
                COUNT(CASE WHEN o.status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN o.status = 'processing' THEN 1 END) as processing_orders,
                COUNT(CASE WHEN o.status = 'shipped' THEN 1 END) as shipped_orders,
                COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as delivered_orders
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id 
            {$whereClause}
        ");
        
        $stmt->execute($params);
        return $stmt->fetch();
    }
}

/**
 * Vendor Model
 */
class Vendor extends BaseModel {
    protected $table = 'vendors';
    
    public function findByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function createVendorApplication($userId, $vendorData) {
        $vendorData['user_id'] = $userId;
        $vendorData['status'] = 'pending';
        return $this->create($vendorData);
    }
    
    public function getApproved($limit = null, $offset = 0) {
        $sql = "
            SELECT v.*, u.username, u.email, u.first_name, u.last_name 
            FROM {$this->table} v 
            JOIN users u ON v.user_id = u.id 
            WHERE v.status = 'approved'
            ORDER BY v.created_at DESC
        ";
        
        $params = [];

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getPending() {
        $stmt = $this->db->prepare("
            SELECT v.*, u.username, u.email, u.first_name, u.last_name 
            FROM {$this->table} v 
            JOIN users u ON v.user_id = u.id 
            WHERE v.status = 'pending'
            ORDER BY v.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function approve($vendorId) {
        return $this->update($vendorId, ['status' => 'approved']);
    }
    
    public function suspend($vendorId) {
        return $this->update($vendorId, ['status' => 'suspended']);
    }
    
    public function getVendorStats($vendorId) {
        $product = new Product();
        $order = new Order();
        
        $productCount = $product->count("vendor_id = {$vendorId}");
        $orderStats = $order->getOrderStats($vendorId);
        
        return [
            'product_count' => $productCount,
            'total_orders' => $orderStats['total_orders'] ?? 0,
            'total_revenue' => $orderStats['total_revenue'] ?? 0,
            'average_order_value' => $orderStats['average_order_value'] ?? 0
        ];
    }
}

/**
 * Review Model
 */
class Review extends BaseModel {
    protected $table = 'reviews';
    
    public function addReview($userId, $productId, $rating, $title, $comment, $orderItemId = null) {
        $data = [
            'user_id' => $userId,
            'product_id' => $productId,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'order_item_id' => $orderItemId,
            'status' => 'pending'
        ];
        
        return $this->create($data);
    }
    
    public function getUserReviews($userId, $limit = null, $offset = 0) {
        $sql = "
            SELECT r.*, p.name as product_name 
            FROM {$this->table} r 
            JOIN products p ON r.product_id = p.id 
            WHERE r.user_id = ? 
            ORDER BY r.created_at DESC
        ";
        
        $params = [$userId];

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function approve($reviewId) {
        return $this->update($reviewId, ['status' => 'approved']);
    }
    
    public function reject($reviewId) {
        return $this->update($reviewId, ['status' => 'rejected']);
    }
    
    public function getPending() {
        $stmt = $this->db->prepare("
            SELECT r.*, p.name as product_name, u.first_name, u.last_name 
            FROM {$this->table} r 
            JOIN products p ON r.product_id = p.id 
            JOIN users u ON r.user_id = u.id 
            WHERE r.status = 'pending' 
            ORDER BY r.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

/**
 * Wishlist Model
 */
class Wishlist extends BaseModel {
    protected $table = 'wishlists';
    
    public function addToWishlist($userId, $productId) {
        try {
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, product_id) VALUES (?, ?)");
            return $stmt->execute([$userId, $productId]);
        } catch (PDOException $e) {
            // Handle duplicate entry
            return false;
        }
    }
    
    public function removeFromWishlist($userId, $productId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    }
    
    public function getUserWishlist($userId) {
        $stmt = $this->db->prepare("
            SELECT w.*, p.name, p.price, p.status, pi.image_url 
            FROM {$this->table} w 
            JOIN products p ON w.product_id = p.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE w.user_id = ? 
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function isInWishlist($userId, $productId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetchColumn() > 0;
    }
}

/**
 * AI Recommendations Model
 */
class Recommendation extends BaseModel {
    protected $table = 'recommendation_logs';
    
    /**
     * Single source of truth for allowed activity types
     * Must match DB CHECK constraint: activity_type IN ('view_product','add_to_cart','purchase','search','review')
     */
    const ALLOWED_ACTIVITY_TYPES = ['view_product', 'add_to_cart', 'purchase', 'search', 'review'];
    
    /**
     * Log user activity with validation and error handling
     * 
     * @param int|null $userId User ID (can be null for anonymous users)
     * @param int|null $productId Product ID (can be null for non-product activities like search)
     * @param string $activityType Activity type - must be valid or mappable
     * @param array $metadata Additional activity metadata
     * @return bool True on success, false on failure
     */
    public function logActivity($userId, $productId, $activityType, $metadata = []) {
        // Validate activity type before DB interaction
        if (!in_array($activityType, self::ALLOWED_ACTIVITY_TYPES, true)) {
            // Map common aliases to valid activity types
            $aliasMap = [
                'view' => 'view_product',
                'view_item' => 'view_product',
                'view_homepage' => 'view_product', // Fix for index.php call
                'cart' => 'add_to_cart',
                'add' => 'add_to_cart',
                'buy' => 'purchase',
                'order' => 'purchase'
            ];
            
            $activityType = $aliasMap[$activityType] ?? null;
        }
        
        // If still invalid after mapping, log warning and return false
        if ($activityType === null) {
            error_log("Invalid activity_type provided to logActivity(): " . ($activityType ?? 'null'));
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_activities 
                (user_id, activity_type, product_id, metadata, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            // Create variables for bindParam (must be passed by reference)
            $metadataJson = json_encode($metadata);
            $ipAddress = getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Bind parameters with explicit types
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $activityType, PDO::PARAM_STR);
            $stmt->bindParam(3, $productId, PDO::PARAM_INT);
            $stmt->bindParam(4, $metadataJson, PDO::PARAM_STR);
            $stmt->bindParam(5, $ipAddress, PDO::PARAM_STR);
            $stmt->bindParam(6, $userAgent, PDO::PARAM_STR);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            // Log PDO error info for debugging
            $errorInfo = $this->db->errorInfo();
            error_log("PDO Error in logActivity(): " . $e->getMessage() . 
                     " | Error Info: " . implode(' | ', $errorInfo));
            return false;
        }
    }
    
    public function getViewedTogether($productId, $limit = 6) {
        $stmt = $this->db->prepare("
            SELECT p.*, COUNT(*) as view_count, pi.image_url
            FROM user_activities ua1 
            JOIN user_activities ua2 ON ua1.user_id = ua2.user_id 
                AND ua1.product_id != ua2.product_id 
                AND ua1.activity_type = 'view_product' 
                AND ua2.activity_type = 'view_product'
            JOIN products p ON ua2.product_id = p.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE ua1.product_id = ? AND p.status = 'active'
            GROUP BY p.id 
            ORDER BY view_count DESC, p.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    public function getPurchasedTogether($productId, $limit = 6) {
        $stmt = $this->db->prepare("
            SELECT p.*, COUNT(*) as purchase_count, pi.image_url
            FROM order_items oi1 
            JOIN order_items oi2 ON oi1.order_id = oi2.order_id 
                AND oi1.product_id != oi2.product_id 
            JOIN products p ON oi2.product_id = p.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE oi1.product_id = ? AND p.status = 'active'
            GROUP BY p.id 
            ORDER BY purchase_count DESC, p.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    public function getTrendingProducts($limit = 8) {
        // Fixed MariaDB compatible trending products query
        // Updated to use existing columns and proper MariaDB syntax
        $sql = "
            SELECT p.id, p.name AS title, p.price,
                   COALESCE(SUM(oi.qty), 0) AS sold,
                   MAX(pi.image_url) AS image
            FROM products p 
            LEFT JOIN order_items oi ON oi.product_id = p.id
            LEFT JOIN orders o ON o.id = oi.order_id 
              AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND o.status IN ('paid','shipped','delivered')
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE p.status = 'active'
            GROUP BY p.id, p.name, p.price
            ORDER BY sold DESC, p.created_at DESC 
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getPersonalizedRecommendations($userId, $limit = 8) {
        // Get user's purchase history and preferences
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   COUNT(DISTINCT ua.id) as user_interest_score,
                   AVG(r.rating) as avg_rating,
                   pi.image_url
            FROM products p 
            LEFT JOIN user_activities ua ON p.id = ua.product_id 
                AND ua.user_id = ?
            LEFT JOIN reviews r ON p.id = r.product_id AND r.status = 'approved'
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.status = 'active'
                AND p.id NOT IN (
                    SELECT oi.product_id 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE o.user_id = ?
                )
            GROUP BY p.id 
            ORDER BY user_interest_score DESC, avg_rating DESC, p.featured DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }
    
    public function getRecommendations($userId, $productId, $type = 'viewed_together', $limit = 6) {
        switch ($type) {
            case 'viewed_together':
                return $this->getViewedTogether($productId, $limit);
            case 'purchased_together':
                return $this->getPurchasedTogether($productId, $limit);
            case 'trending':
                return $this->getTrendingProducts($limit);
            case 'personalized':
                return $userId ? $this->getPersonalizedRecommendations($userId, $limit) : [];
            default:
                return $this->getViewedTogether($productId, $limit);
        }
    }
    
    public function logRecommendationClick($userId, $productId, $recommendationType) {
        return $this->create([
            'user_id' => $userId,
            'product_id' => $productId,
            'recommendation_type' => $recommendationType,
            'clicked' => 1
        ]);
    }
}

/**
 * Settings Model
 */
class Settings extends BaseModel {
    protected $table = 'settings';
    
    public function getSetting($key, $default = null) {
        $stmt = $this->db->prepare("SELECT setting_value FROM {$this->table} WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    }
    
    public function setSetting($key, $value, $description = '') {
        // Use MySQL UPSERT syntax
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (setting_key, setting_value, description) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value), 
                updated_at = NOW()
        ");
        return $stmt->execute([$key, $value, $description]);
    }
    
    public function getAllSettings() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY setting_key");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}
?>