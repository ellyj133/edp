<?php
/**
 * User Model
 * E-Commerce Platform
 */

require_once __DIR__ . '/database.php';

class User extends BaseModel {
    protected $table = 'users';
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function authenticate($email, $password) {
        // Check rate limiting
        if (!checkLoginAttempts($email)) {
            logSecurityEvent(null, 'login_blocked_rate_limit', 'user', null, ['email' => $email]);
            return ['error' => 'Too many login attempts. Please try again later.'];
        }
        
        $user = $this->findByEmail($email);
        
        if ($user && verifyPassword($password, $user['pass_hash'])) {
            // Check if user account is active
            if ($user['status'] !== 'active') {
                logLoginAttempt($email, false);
                logSecurityEvent($user['id'], 'login_failed_inactive', 'user', $user['id']);
                
                // Check if user is pending email verification
                if ($user['status'] === 'pending' && empty($user['verified_at'])) {
                    return ['error' => 'Please verify your email address before logging in. <a href="/resend-verification.php">Resend verification email</a>.'];
                } else {
                    return ['error' => 'Account is not active. Please contact support.'];
                }
            }
            
            // Successful login
            logLoginAttempt($email, true);
            clearLoginAttempts($email);
            logSecurityEvent($user['id'], 'login_success', 'user', $user['id']);
            
            return $user;
        } else {
            // Failed login
            logLoginAttempt($email, false);
            logSecurityEvent(null, 'login_failed', 'user', null, ['email' => $email]);
            return ['error' => 'Invalid email or password.'];
        }
    }
    
    public function register($data) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Prepare user data with pending status
            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => hashPassword($data['password']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'role' => 'customer',
                'status' => 'pending', // User starts as pending
                'verified_at' => null,  // Not verified yet
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert user
            $userId = $this->create($userData);
            
            if (!$userId) {
                $this->db->rollBack();
                return false;
            }
            
            // Commit user creation first
            $this->db->commit();
            
            // Generate secure OTP using EmailTokenManager
            $tokenManager = new EmailTokenManager();
            $otp = $tokenManager->generateToken($userId, 'email_verification', $userData['email'], 15);
            
            if (!$otp) {
                Logger::error("Failed to generate secure OTP for user {$userId}");
                return false;
            }
            
            // Send verification email with OTP (simple mail function like reference)
            $subject = "Verify Your Email Address - " . FROM_NAME;
            $message = "Hello {$userData['first_name']},\n\n";
            $message .= "Thank you for registering with " . FROM_NAME . ". ";
            $message .= "Your 6-digit verification code is: {$otp}\n\n";
            $message .= "This code will expire in 15 minutes.\n\n";
            $message .= "Please use this code to verify your email address.\n\n";
            $message .= "Regards,\n" . FROM_NAME;
            
            $headers = "From: " . FROM_EMAIL;
            
            $emailSent = mail($userData['email'], $subject, $message, $headers);
            
            if (!$emailSent) {
                Logger::error("Failed to send verification email to user {$userId}");
                // Don't return false here - user is created and OTP is stored
                Logger::info("User registered successfully but email sending failed: {$userData['email']}");
            } else {
                Logger::info("User registered successfully with OTP email sent: {$userData['email']}");
            }
            
            return $userId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::error("Registration error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updatePassword($userId, $newPassword) {
        $data = ['pass_hash' => hashPassword($newPassword)];
        return $this->update($userId, $data);
    }
    
    public function verifyEmail($userId) {
        return $this->update($userId, [
            'verified_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ]);
    }
    
    public function getAddresses($userId) {
        $stmt = $this->db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function addAddress($userId, $addressData) {
        $addressData['user_id'] = $userId;
        
        $stmt = $this->db->prepare("INSERT INTO addresses (user_id, type, address_line1, address_line2, city, state, postal_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        return $stmt->execute([
            $userId,
            $addressData['type'],
            $addressData['address_line1'],
            $addressData['address_line2'] ?? '',
            $addressData['city'],
            $addressData['state'],
            $addressData['postal_code'],
            $addressData['country'],
            $addressData['is_default'] ?? 0
        ]);
    }
    
    public function getUsersByRole($role, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE role = ?";
        if ($limit) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
}

/**
 * Product Model
 */
class Product extends BaseModel {
    protected $table = 'products';
    
    public function findWithVendor($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name, c.name as category_name 
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findByCategory($categoryId, $limit = PRODUCTS_PER_PAGE, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name 
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            WHERE p.category_id = ? AND p.status = 'active' 
            ORDER BY p.featured DESC, p.created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
    
    public function search($query, $limit = PRODUCTS_PER_PAGE, $offset = 0) {
        $searchTerm = "%{$query}%";
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name 
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            WHERE (p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?) 
            AND p.status = 'active' 
            ORDER BY p.featured DESC, p.created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get products by vendor ID with prepared statements
     * Fixes seller center fatal error
     */
    public function getByVendorId($vendorId, $limit = null, $offset = 0) {
        $sql = "
            SELECT p.*, c.name as category_name 
            FROM {$this->table} p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.vendor_id = ? 
            ORDER BY p.created_at DESC
        ";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }
    
    public function getFeatured($limit = 8) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name 
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            WHERE p.featured = 1 AND p.status = 'active' 
            ORDER BY p.created_at DESC 
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getByVendor($vendorId, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE vendor_id = ?";
        if ($limit) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }
    
    public function updateStock($productId, $quantity) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET stock_quantity = ? WHERE id = ?");
        return $stmt->execute([$quantity, $productId]);
    }
    
    public function decreaseStock($productId, $quantity) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
        return $stmt->execute([$quantity, $productId, $quantity]);
    }
    
    public function getImages($productId) {
        $stmt = $this->db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    public function addImage($productId, $imageUrl, $altText = '', $isPrimary = false) {
        $stmt = $this->db->prepare("INSERT INTO product_images (product_id, image_url, alt_text, is_primary) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$productId, $imageUrl, $altText, $isPrimary ? 1 : 0]);
    }
    
    public function getReviews($productId, $limit = REVIEWS_PER_PAGE, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT r.*, u.first_name, u.last_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ? AND r.status = 'approved' 
            ORDER BY r.created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    public function getAverageRating($productId) {
        $stmt = $this->db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ? AND status = 'approved'");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }
    
    public function getRandomProducts($limit = 10) {
        // Use RANDOM() for SQLite, RAND() for MySQL
        $randomFunc = defined('USE_SQLITE') && USE_SQLITE ? 'RANDOM()' : 'RAND()';
        
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name 
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            WHERE p.status = 'active' 
            ORDER BY {$randomFunc}
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getLatest($limit = 8) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name 
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            WHERE p.status = 'active' 
            ORDER BY p.created_at DESC 
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function findByFilters($filters = [], $sort = 'name', $limit = PRODUCTS_PER_PAGE, $offset = 0) {
        $where = ["p.status = 'active'"];
        $params = [];
        
        // Build WHERE conditions based on filters
        if (isset($filters['category_id']) && $filters['category_id'] > 0) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $where[] = "p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $where[] = "p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        if (isset($filters['on_sale']) && $filters['on_sale']) {
            $where[] = "p.sale_price IS NOT NULL AND p.sale_price > 0";
        }
        
        // Build ORDER BY clause
        $orderBy = match($sort) {
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC', 
            'newest' => 'p.created_at DESC',
            'rating' => 'p.id DESC', // Placeholder for rating sort
            default => 'p.name ASC'
        };
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT p.*, v.business_name as vendor_name 
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function countByFilters($filters = []) {
        $where = ["p.status = 'active'"];
        $params = [];
        
        // Build WHERE conditions based on filters (same logic as findByFilters)
        if (isset($filters['category_id']) && $filters['category_id'] > 0) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $where[] = "p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $where[] = "p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        if (isset($filters['on_sale']) && $filters['on_sale']) {
            $where[] = "p.sale_price IS NOT NULL AND p.sale_price > 0";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) FROM {$this->table} p WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    public function findBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name, c.name as category_name 
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.slug = ? AND p.status = 'active'
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
}

/**
 * Category Model
 */
class Category extends BaseModel {
    protected $table = 'categories';
    
    public function getActive() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getChildren($parentId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE parent_id = ? AND status = 'active' ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll();
    }
    
    public function getParents() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE parent_id IS NULL AND status = 'active' ORDER BY sort_order ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getProductCount($categoryId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ? AND status = 'active'");
        $stmt->execute([$categoryId]);
        return $stmt->fetchColumn();
    }
    
    public function findBySlug($slug) {
        // Since there's no slug column, we'll find by matching the slugified name
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'active'");
        $stmt->execute();
        $categories = $stmt->fetchAll();
        
        foreach ($categories as $category) {
            if (slugify($category['name']) === $slug) {
                return $category;
            }
        }
        
        return null;
    }
}

/**
 * Cart Model
 */
class Cart extends BaseModel {
    protected $table = 'cart';
    
    public function getCartItems($userId) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.name, p.price, p.stock_quantity, p.sku, 
                   pi.image_url as product_image, v.business_name as vendor_name
            FROM {$this->table} c 
            JOIN products p ON c.product_id = p.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN vendors v ON p.vendor_id = v.id
            WHERE c.user_id = ? AND p.status = 'active'
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function addItem($userId, $productId, $quantity = 1) {
        // Check if item already exists
        $stmt = $this->db->prepare("SELECT id, quantity FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $newQuantity = $existing['quantity'] + $quantity;
            $stmt = $this->db->prepare("UPDATE {$this->table} SET quantity = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$newQuantity, $existing['id']]);
        } else {
            // Add new item
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, product_id, quantity) VALUES (?, ?, ?)");
            return $stmt->execute([$userId, $productId, $quantity]);
        }
    }
    
    public function updateQuantity($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($userId, $productId);
        }
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$quantity, $userId, $productId]);
    }
    
    public function removeItem($userId, $productId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    }
    
    public function clearCart($userId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    public function getCartTotal($userId) {
        $stmt = $this->db->prepare("
            SELECT SUM(c.quantity * p.price) as total 
            FROM {$this->table} c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ? AND p.status = 'active'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }
    
    public function getCartCount($userId) {
        if (!$this->db) {
            return 0; // Return 0 if no database connection
        }
        $stmt = $this->db->prepare("SELECT SUM(quantity) FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }
}
?>