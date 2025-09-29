<?php
/**
 * Homepage - FezaMarket E-Commerce Platform
 * Complete Walmart Layout with Dynamic Product Integration
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/template-helpers.php';

/* ---------- Admin Authorization Check ---------- */
$is_admin_logged_in = false;
try {
    if (Session::isLoggedIn()) {
        $user_role = Session::getUserRole();
        $is_admin_logged_in = ($user_role === 'admin');
    }
} catch (Exception $e) {
    // Fallback: check session directly if database fails
    $is_admin_logged_in = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
    error_log("Admin check fallback: " . ($is_admin_logged_in ? 'true' : 'false'));
}

// For demo purposes, enable admin mode when database is not available
if (!$is_admin_logged_in && !function_exists('db')) {
    $is_admin_logged_in = true; // Temporary demo mode
    error_log("Demo admin mode enabled");
}

/* ---------- Safe Helpers ---------- */
if (!function_exists('h')) {
    function h($v): string { 
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); 
    }
}

if (!function_exists('safeNormalizeProduct')) {
    function safeNormalizeProduct($p): array {
        if (!is_array($p)) {
            return [
                'id' => rand(1, 1000),
                'title' => 'Sample Product',
                'price' => '$' . number_format(rand(10, 200), 2),
                'original_price' => '$' . number_format(rand(210, 300), 2),
                'discount_percent' => rand(10, 50),
                'image' => 'https://picsum.photos/400/400?random=' . rand(1, 1000),
                'url' => '#',
                'store_name' => 'FezaMarket Store',
                'seller_name' => 'FezaMarket',
                'rating' => rand(4, 5),
                'reviews_count' => rand(10, 100),
                'featured' => true
            ];
        }
        
        return [
            'id' => isset($p['id']) ? (int)$p['id'] : rand(1, 1000),
            'title' => isset($p['title']) ? (string)$p['title'] : 'Sample Product',
            'price' => isset($p['price']) ? (string)$p['price'] : '$' . number_format(rand(10, 200), 2),
            'original_price' => isset($p['original_price']) ? (string)$p['original_price'] : null,
            'discount_percent' => isset($p['discount_percent']) ? (int)$p['discount_percent'] : null,
            'image' => isset($p['image']) ? (string)$p['image'] : 'https://picsum.photos/400/400?random=' . ($p['id'] ?? rand(1, 1000)),
            'url' => isset($p['url']) ? (string)$p['url'] : '#',
            'store_name' => isset($p['store_name']) ? (string)$p['store_name'] : 'FezaMarket Store',
            'seller_name' => isset($p['seller_name']) ? (string)$p['seller_name'] : 'FezaMarket',
            'rating' => isset($p['rating']) ? (float)$p['rating'] : rand(4, 5),
            'reviews_count' => isset($p['reviews_count']) ? (int)$p['reviews_count'] : rand(10, 100),
            'featured' => isset($p['featured']) ? (bool)$p['featured'] : false
        ];
    }
}

/* ---------- Safe Fallback Product Generator ---------- */
if (!function_exists('createSampleProducts')) {
    function createSampleProducts($count = 12): array {
        $sample_products = [];
        $product_names = [
            'Wireless Bluetooth Headphones',
            'Smartphone Case with Card Holder',
            'Portable Power Bank 10000mAh',
            'LED Desk Lamp with USB Charging',
            'Water Resistant Fitness Tracker',
            'Premium Coffee Mug Set',
            'Ergonomic Laptop Stand',
            'Wireless Charging Pad',
            'Bluetooth Speaker Waterproof',
            'USB-C Cable 6ft Braided',
            'Phone Car Mount Magnetic',
            'Laptop Backpack Professional'
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $price = rand(15, 199);
            $original_price = rand($price + 10, $price + 50);
            $discount = round((($original_price - $price) / $original_price) * 100);
            
            $sample_products[] = [
                'id' => $i + 1,
                'title' => $product_names[$i % count($product_names)],
                'price' => '$' . number_format($price, 2),
                'original_price' => '$' . number_format($original_price, 2),
                'discount_percent' => $discount,
                'image' => '/images/placeholder-product.jpg',
                'url' => '/product/' . ($i + 1),
                'store_name' => 'FezaMarket',
                'seller_name' => 'FezaMarket',
                'rating' => 4 + (rand(0, 10) / 10),
                'reviews_count' => rand(15, 250),
                'featured' => true
            ];
        }
        
        return $sample_products;
    }
}

/* ---------- Real Product Fetcher from Database ---------- */
if (!function_exists('fetchRealProducts')) {
    function fetchRealProducts($limit = 12, $category_id = null): array {
        try {
            $pdo = db();
            
            // Build query to fetch real products
            $sql = "SELECT p.id, p.name as title, p.price, p.compare_price as original_price, 
                           p.image_url as image, p.slug, p.description, 
                           p.stock_quantity,
                           CASE 
                               WHEN p.compare_price IS NOT NULL AND p.compare_price > p.price 
                               THEN ROUND(((p.compare_price - p.price) / p.compare_price) * 100)
                               ELSE NULL
                           END as discount_percent
                    FROM products p 
                    WHERE p.status = 'active' AND p.stock_quantity > 0";
            
            if ($category_id) {
                $sql .= " AND p.category_id = :category_id";
            }
            
            $sql .= " ORDER BY p.created_at DESC LIMIT :limit";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            if ($category_id) {
                $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $products = $stmt->fetchAll();
            
            // Normalize product data for template use
            $normalized = [];
            foreach ($products as $product) {
                $normalized[] = [
                    'id' => (int)$product['id'],
                    'title' => (string)$product['title'],
                    'price' => '$' . number_format((float)$product['price'], 2),
                    'original_price' => $product['original_price'] ? '$' . number_format((float)$product['original_price'], 2) : null,
                    'discount_percent' => $product['discount_percent'] ? (int)$product['discount_percent'] : null,
                    'image' => $product['image'] ?: '/images/placeholder-product.jpg',
                    'url' => '/product/' . ($product['slug'] ?: $product['id']),
                    'store_name' => 'FezaMarket',
                    'seller_name' => 'FezaMarket',
                    'rating' => 4.5,
                    'reviews_count' => rand(10, 200),
                    'featured' => true
                ];
            }
            
            return $normalized;
            
        } catch (Exception $e) {
            error_log("Error fetching real products: " . $e->getMessage());
            // Fallback to sample products when database is unavailable
            return createSampleProducts($limit);
        }
    }
}

/* ---------- Banner Management Functions ---------- */
if (!function_exists('fetchBanners')) {
    function fetchBanners($position = 'hero'): array {
        try {
            $pdo = db();
            
            $sql = "SELECT id, title, subtitle, description, image_url, link_url, button_text,
                           background_color, text_color, sort_order
                    FROM homepage_banners 
                    WHERE status = 'active' 
                    AND position = :position
                    AND (start_date IS NULL OR start_date <= NOW())
                    AND (end_date IS NULL OR end_date >= NOW())
                    ORDER BY sort_order ASC";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':position', $position);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error fetching banners: " . $e->getMessage());
            return [];
        }
    }
}

$page_title = 'FezaMarket - Save Money. Live Better.';

// Fetch real products from database instead of mock data
try {
    $featured_products = fetchRealProducts(20);
} catch (Exception $e) {
    $featured_products = [];
}

try {
    $deals = fetchRealProducts(12); // Get deals products
} catch (Exception $e) {
    $deals = [];
}

try {
    $electronics = fetchRealProducts(12, 1); // Category ID 1 for electronics
} catch (Exception $e) {
    $electronics = [];
}

try {
    $fashion = fetchRealProducts(15, 2); // Category ID 2 for fashion
} catch (Exception $e) {
    $fashion = [];
}

try {
    $home_garden = fetchRealProducts(12, 3); // Category ID 3 for home & garden
} catch (Exception $e) {
    $home_garden = [];
}

try {
    $furniture = fetchRealProducts(10, 4); // Category ID 4 for furniture
} catch (Exception $e) {
    $furniture = [];
}

try {
    $trending_products = fetchRealProducts(10);
} catch (Exception $e) {
    $trending_products = [];
}

// Fetch banners from database
try {
    $hero_banners = fetchBanners('hero');
    $grid_banners = fetchBanners('top');
} catch (Exception $e) {
    $hero_banners = [];
    $grid_banners = [];
}

// For testing purposes, create a sample hero banner if none exist
if (empty($hero_banners)) {
    $hero_banners = [[
        'id' => 'hero-1',
        'title' => 'Welcome to FezaMarket',
        'subtitle' => 'Save Money. Live Better.',
        'description' => 'Discover amazing deals on everything you need. Free shipping on orders over $35.',
        'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200&h=400&fit=crop&crop=center',
        'link_url' => '/deals',
        'button_text' => 'Shop Now'
    ]];
}

includeHeader($page_title);
?>

<!-- Complete Walmart Homepage Layout -->
<div class="walmart-exact-layout">
    
    <!-- Hero Banner Section (top of homepage) -->
    <?php if (!empty($hero_banners)): ?>
    <section class="hero-banner-section">
        <div class="container-wide">
            <?php foreach ($hero_banners as $hero): ?>
            <div class="hero-banner <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                 data-banner-type="hero" data-banner-id="hero-<?php echo $hero['id']; ?>">
                <?php if ($is_admin_logged_in): ?>
                    <div class="admin-edit-overlay">
                        <button class="admin-edit-btn" onclick="editBanner('hero-<?php echo $hero['id']; ?>', 'hero')" title="Edit Hero Banner">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
                <div class="hero-content" style="background-image: url('<?php echo h($hero['image_url']); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">
                    <div class="hero-text">
                        <h1><?php echo h($hero['title']); ?></h1>
                        <?php if ($hero['subtitle']): ?>
                            <p class="hero-subtitle"><?php echo h($hero['subtitle']); ?></p>
                        <?php endif; ?>
                        <?php if ($hero['description']): ?>
                            <p class="hero-description"><?php echo h($hero['description']); ?></p>
                        <?php endif; ?>
                        <?php if ($hero['link_url'] && $hero['button_text']): ?>
                            <a href="<?php echo h($hero['link_url']); ?>" class="hero-cta-btn"><?php echo h($hero['button_text']); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Top Grid Section -->
    <section class="top-grid-section">
        <div class="container-wide">
            <div class="walmart-grid">
                
                <!-- Fall Shoe Edit - Large Left -->
                <div class="grid-card card-1-1 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 1 / 1 / 3 / 3;" 
                     data-banner-type="grid" data-banner-id="shoes-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('shoes-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="card-bg" style="background: linear-gradient(45deg, #8B4513 0%, #D2691E 100%); background-size: cover;">
                        <div class="card-content-wrapper">
                            <div class="text-content">
                                <span class="small-tag">The fall shoe edit</span>
                                <div class="card-image-small">
                                    <img src="https://picsum.photos/200/150?random=shoes1" alt="Fall Shoes" style="object-fit: cover;">
                                </div>
                                <a href="/category/shoes" class="shop-now-link">Shop now</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FezaMarket Cash Back - Medium Center -->
                <div class="grid-card card-1-2 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 1 / 3 / 3 / 5;"
                     data-banner-type="grid" data-banner-id="cashback-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('cashback-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="card-bg" style="background: linear-gradient(135deg, #004c91 0%, #0071ce 100%); background-size: cover;">
                        <div class="cashback-content">
                            <div class="cashback-text">
                                <span class="cashback-small">FezaMarket members earn</span>
                                <div class="cashback-big">
                                    <span class="percent">5%</span> <span class="cashback-desc">cash back at<br><strong>FezaMarket</strong></span>
                                </div>
                                <a href="/membership" class="learn-link">Learn how</a>
                            </div>
                            <div class="card-visual-right">
                                <div class="credit-card-visual">
                                    <div class="card-inner">
                                        <div class="card-chip"></div>
                                        <div class="card-brand">FezaPay</div>
                                        <div class="card-logo">★</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leaf Blowers - Small Right -->
                <div class="grid-card card-1-3 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 1 / 5 / 2 / 7;"
                     data-banner-type="grid" data-banner-id="leaf-blowers-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('leaf-blowers-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="card-bg" style="background: linear-gradient(45deg, #27ae60 0%, #2ecc71 100%); background-size: cover;">
                        <div class="small-promo-content">
                            <span class="promo-tag-small">Leaf blowers, mowers & more</span>
                            <div class="promo-image-small">
                                <img src="https://picsum.photos/120/80?random=garden1" alt="Garden Tools" style="object-fit: cover;">
                            </div>
                            <a href="/category/garden" class="shop-now-link">Shop now</a>
                        </div>
                    </div>
                </div>

                <!-- Dreamy Bedding - Medium Left -->
                <div class="grid-card card-2-1 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 3 / 1 / 4 / 3;"
                     data-banner-type="grid" data-banner-id="dreamy-bedding-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('dreamy-bedding-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="card-bg" style="background: #f8f9fa; background-size: cover;">
                        <div class="bedding-content">
                            <span class="promo-text">Save on dreamy bedding</span>
                            <div class="product-showcase-inline">
                                <?php 
                                $bedding_product = !empty($home_garden) ? safeNormalizeProduct($home_garden[0]) : safeNormalizeProduct(null);
                                ?>
                                <img src="<?php echo h($bedding_product['image']); ?>" alt="Bedding" class="bedding-img" style="object-fit: cover;">
                                <div class="price-tag">from $50</div>
                            </div>
                            <a href="/category/bedding" class="shop-now-link">Shop now</a>
                        </div>
                    </div>
                </div>

                <!-- Tech Savings - Small Right -->
                <div class="grid-card card-2-2 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 2 / 5 / 3 / 7;"
                     data-banner-type="grid" data-banner-id="tech-savings-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('tech-savings-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="card-bg" style="background: #fff3e0; background-size: cover;">
                        <div class="tech-savings-content">
                            <span class="savings-tag">Savings on tech—delivered fast</span>
                            <div class="tech-image-small">
                                <img src="https://picsum.photos/120/80?random=laptop1" alt="Tech" style="object-fit: cover;">
                            </div>
                            <a href="/category/electronics" class="shop-now-small">Shop now</a>
                        </div>
                    </div>
                </div>

                <!-- Resell FezaMarket - Medium Center -->
                <div class="grid-card card-2-3" style="grid-area: 3 / 3 / 4 / 5;">
                    <div class="card-bg" style="background: #e8f5e8;">
                        <div class="resell-content">
                            <span class="resell-title">Resell at FezaMarket: fave rewards & cash</span>
                            <div class="resell-product">
                                <div class="watch-container">
                                    <?php 
                                    $watch_product = !empty($electronics) ? safeNormalizeProduct($electronics[0]) : safeNormalizeProduct(null);
                                    ?>
                                    <img src="<?php echo h($watch_product['image']); ?>" alt="Smart Watch" class="watch-img">
                                    <div class="discount-badge-yellow">Up to 65% off</div>
                                </div>
                                <div class="flash-deal-badge">Flash Deal</div>
                            </div>
                            <a href="/resell" class="learn-more-link">Learn more</a>
                        </div>
                    </div>
                </div>

                <!-- Flash Deal - Small Right -->
                <div class="grid-card card-2-4" style="grid-area: 3 / 5 / 4 / 7;">
                    <div class="card-bg" style="background: #fff9c4;">
                        <div class="flash-item-content">
                            <?php 
                            $flash_product = !empty($electronics) && count($electronics) > 1 ? 
                                safeNormalizeProduct($electronics[1]) : safeNormalizeProduct(null);
                            ?>
                            <div class="flash-item-image">
                                <img src="<?php echo h($flash_product['image']); ?>" alt="Flash Deal">
                            </div>
                            <div class="flash-deal-text">
                                <div class="flash-badge">Flash Deal</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Miss Mouth's - Small Left -->
                <div class="grid-card card-3-1" style="grid-area: 4 / 1 / 5 / 2;">
                    <div class="card-bg" style="background: linear-gradient(45deg, #e3f2fd 0%, #bbdefb 100%);">
                        <div class="messy-eater-content">
                            <span class="product-tag-small">Miss Mouth's Messy Eater</span>
                            <div class="product-image-container">
                                <img src="https://picsum.photos/100/120?random=baby1" alt="Baby Product">
                            </div>
                            <a href="/category/baby" class="shop-now-tiny">Shop now</a>
                        </div>
                    </div>
                </div>

                <!-- New for Him & Her - Large Right -->
                <div class="grid-card card-3-2" style="grid-area: 4 / 5 / 5 / 7;">
                    <div class="card-bg" style="background: #fce4ec;">
                        <div class="him-her-content">
                            <h3 class="section-title-small">New for him & her</h3>
                            <div class="fashion-items-row">
                                <?php 
                                $fashion_items = array_slice($fashion, 0, 3);
                                if (empty($fashion_items)) {
                                    // Try to get real fashion products
                                    $fashion_items = fetchRealProducts(3, 2);
                                    if (empty($fashion_items)) {
                                        $fashion_items = fetchRealProducts(3); // Any products
                                    }
                                }
                                
                                // Only display if we have real items
                                if (!empty($fashion_items)):
                                    foreach($fashion_items as $fashion_item): 
                                        $item = safeNormalizeProduct($fashion_item); ?>
                                        <div class="fashion-item-small">
                                            <div class="fashion-image-container">
                                                <img src="<?php echo h($item['image']); ?>" alt="<?php echo h($item['title']); ?>" style="object-fit: cover;">
                                                <div class="heart-icon">♡</div>
                                            </div>
                                            <div class="item-price-small"><?php echo h($item['price']); ?></div>
                                        </div>
                                    <?php endforeach;
                                else: ?>
                                    <div class="no-fashion-items">
                                        <p>No fashion items available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="navigation-arrows">
                                <span class="arrow-left">‹</span>
                                <span class="arrow-right">›</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Burger King Partnership - Medium Center -->
                <div class="grid-card card-3-3" style="grid-area: 4 / 2 / 5 / 5;">
                    <div class="card-bg" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);">
                        <div class="burger-king-content">
                            <div class="bk-text">
                                <span class="bk-title">FezaMarket+ Members get 25% off Burger King®</span>
                                <a href="/partnership" class="learn-more-btn-orange">Learn more</a>
                            </div>
                            <div class="bk-food-image">
                                <img src="https://picsum.photos/180/120?random=burger1" alt="Food">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Mobile Category Cards Section -->
    <section class="mobile-categories-section">
        <div class="container">
            <div class="category-cards">
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=electronics" alt="Electronics">
                    <h3>Electronics</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=fashion" alt="Fashion">
                    <h3>Fashion</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=home" alt="Home">
                    <h3>Home</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=sports" alt="Sports">
                    <h3>Sports</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=auto" alt="Auto">
                    <h3>Auto</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=beauty" alt="Beauty">
                    <h3>Beauty</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=toys" alt="Toys">
                    <h3>Toys</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=books" alt="Books">
                    <h3>Books</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- Mobile Promo Cards Section -->
    <section class="mobile-promos-section">
        <div class="container">
            <div class="promo-cards">
                <div class="promo-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
                    <h3>Flash Sale</h3>
                    <p>Up to 50% off electronics</p>
                </div>
                <div class="promo-card" style="background: linear-gradient(135deg, #4834d4 0%, #686de0 100%);">
                    <h3>Free Shipping</h3>
                    <p>On orders over $35</p>
                </div>
                <div class="promo-card" style="background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);">
                    <h3>New Arrivals</h3>
                    <p>Latest fashion trends</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Free Assembly Banner -->
    <section class="assembly-full-banner">
        <div class="container-wide">
            <div class="assembly-banner-content" style="background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);">
                <div class="assembly-left">
                    <span class="assembly-tag">Only at FezaMarket</span>
                    <h2 class="assembly-title">Free Assembly</h2>
                    <span class="assembly-subtitle">fall prep</span>
                    <div class="assembly-fine-print">FREE ASSEMBLY</div>
                </div>
                <div class="assembly-right">
                    <img src="https://picsum.photos/500/250?random=furniture1" alt="Free Assembly" class="assembly-hero-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Styles for all your plans -->
    <section class="product-row-section">
        <div class="container">
            <div class="row-header">
                <h2>Styles for all your plans</h2>
                <a href="/fashion" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="styles-track">
                    <?php 
                    $style_products = !empty($fashion) ? array_slice($fashion, 0, 8) : [];
                    // If no real products, fallback but try to minimize sample data usage
                    if (empty($style_products)) {
                        // Try again with different approach
                        $style_products = fetchRealProducts(8, 2); // Try fashion category again
                        if (empty($style_products)) {
                            $style_products = fetchRealProducts(8); // Any products
                        }
                        // Do not create sample products - only display real products
                    }
                    
                    // Only display products if we have real ones
                    if (!empty($style_products)):
                        foreach($style_products as $product): 
                            $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>" style="object-fit: cover;">
                                <button class="wishlist-heart">♡</button>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="product-name"><?php echo h($product['title']); ?></p>
                                <div class="star-rating">
                                    <span class="stars">★★★★★</span>
                                    <span class="review-number"><?php echo $product['reviews_count']; ?></span>
                                </div>
                                <div class="shipping-text">
                                    <span class="free-shipping-text">Free shipping available</span>
                                </div>
                                <div class="action-buttons">
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">Add to Cart</button>
                                    <a href="<?php echo h($product['url']); ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; 
                    else: ?>
                        <div class="no-products-message">
                            <p>No fashion products available at the moment. Please check back later!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('styles-track', 'right')">›</button>
            </div>
        </div>
    </section>

    <!-- PrettyGarden Banner -->
    <section class="prettygarden-banner">
        <div class="container-wide">
            <div class="prettygarden-content" style="background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);">
                <div class="prettygarden-left">
                    <h2 class="pg-title">Dresses to sweaters</h2>
                    <h3 class="pg-subtitle">Just in from PrettyGarden</h3>
                    <div class="pg-brand">
                        <div class="pg-circle">PG</div>
                        <span class="pg-name">PrettyGarden</span>
                    </div>
                </div>
                <div class="prettygarden-right">
                    <?php 
                    $dress_product = !empty($fashion) ? safeNormalizeProduct($fashion[0]) : safeNormalizeProduct(null);
                    ?>
                    <img src="<?php echo h($dress_product['image']); ?>" alt="PrettyGarden Fashion" class="pg-model">
                </div>
            </div>
        </div>
    </section>

    <!-- Get it all right here -->
    <section class="categories-row-section">
        <div class="container">
            <div class="row-header">
                <h2>Get it all right here</h2>
                <a href="/categories" class="shop-all-link">Shop all</a>
            </div>
            <div class="categories-horizontal-container">
                <div class="categories-track">
                    <div class="category-circle-item">
                        <img src="https://picsum.photos/120/120?random=outdoor1" alt="Outdoor" class="category-circle-img">
                        <span class="category-name">Outdoor</span>
                    </div>
                    <div class="category-circle-item">
                        <img src="https://picsum.photos/120/120?random=gaming1" alt="Gaming" class="category-circle-img">
                        <span class="category-name">Gaming</span>
                    </div>
                    <div class="category-circle-item">
                        <img src="https://picsum.photos/120/120?random=auto1" alt="Auto" class="category-circle-img">
                        <span class="category-name">Auto</span>
                    </div>
                    <div class="category-circle-item">
                        <img src="https://picsum.photos/120/120?random=electronics1" alt="Electronics" class="category-circle-img">
                        <span class="category-name">Electronics</span>
                    </div>
                    <div class="category-circle-item">
                        <img src="https://picsum.photos/120/120?random=home1" alt="Home" class="category-circle-img">
                        <span class="category-name">Home</span>
                    </div>
                    <div class="category-circle-item">
                        <img src="https://picsum.photos/120/120?random=fashion1" alt="Fashion" class="category-circle-img">
                        <span class="category-name">Fashion</span>
                    </div>
                    <div class="category-circle-item">
                        <img src="https://picsum.photos/120/120?random=sports1" alt="Sports" class="category-circle-img">
                        <span class="category-name">Sports & outdoors</span>
                    </div>
                </div>
                <button class="scroll-right-btn" onclick="scrollCategories('right')">›</button>
            </div>
        </div>
    </section>

    <!-- Save on furniture -->
    <section class="product-row-section">
        <div class="container">
            <div class="row-header">
                <h2>Save on furniture</h2>
                <a href="/furniture" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="furniture-track">
                    <?php 
                    $furniture_products = !empty($furniture) ? $furniture : [];
                    // Try to get real furniture products instead of samples
                    if (empty($furniture_products)) {
                        $furniture_products = fetchRealProducts(6, 4); // Try furniture category
                        if (empty($furniture_products)) {
                            $furniture_products = fetchRealProducts(6); // Any products
                        }
                        // Do not create sample products - only display real products
                    }
                    
                    // Only display products if we have real ones
                    if (!empty($furniture_products)):
                        foreach($furniture_products as $index => $product): 
                            $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>" style="object-fit: cover;">
                                <button class="wishlist-heart">♡</button>
                                <?php if ($index < 2): ?>
                                    <div class="rollback-badge">Rollback</div>
                                <?php endif; ?>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="now-text">Now </span>
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="product-name"><?php echo h($product['title']); ?></p>
                                <div class="action-buttons">
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">Add to Cart</button>
                                    <a href="<?php echo h($product['url']); ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                    else: ?>
                        <div class="no-products-message">
                            <p>No furniture products available at the moment. Please check back later!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('furniture-track', 'right')">›</button>
            </div>
        </div>
    </section>

    <!-- Flash Deals -->
    <section class="product-row-section">
        <div class="container">
            <div class="row-header">
                <h2>Flash Deals</h2>
                <a href="/deals" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="deals-track">
                    <?php 
                    $deal_products = !empty($deals) ? $deals : [];
                    // Try to get real deal products
                    if (empty($deal_products)) {
                        $deal_products = fetchRealProducts(6); // Try to get any products as deals
                    }
                    
                    // Only display products if we have real ones
                    if (!empty($deal_products)):
                        foreach($deal_products as $product): 
                            $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>">
                                <button class="wishlist-heart">♡</button>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="now-text">Now </span>
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="product-name"><?php echo h($product['title']); ?></p>
                                <div class="star-rating">
                                    <span class="stars">★★★★★</span>
                                    <span class="review-number"><?php echo $product['reviews_count']; ?></span>
                                </div>
                                <div class="shipping-text">
                                    <span class="free-shipping-text">Free shipping, arrives in 3+ days</span>
                                </div>
                                <div class="action-buttons">
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">Add to Cart</button>
                                    <a href="<?php echo h($product['url']); ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                    else: ?>
                        <div class="no-products-message">
                            <p>No deals available at the moment. Please check back later!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('deals-track', 'right')">›</button>
            </div>
        </div>
    </section>

    <!-- Halloween Section - Three Cards -->
    <section class="halloween-section">
        <div class="container-wide">
            <div class="halloween-grid">
                <!-- Halloween Coziness -->
                <div class="halloween-card halloween-coziness">
                    <div class="halloween-content" style="background: linear-gradient(135deg, #ff6600 0%, #ff9900 100%);">
                        <div class="halloween-text">
                            <span class="halloween-tag">Family-friendly & beyond</span>
                            <h2 class="halloween-title">Halloween coziness</h2>
                            <a href="/halloween" class="shop-now-halloween">Shop now</a>
                        </div>
                        <div class="halloween-image">
                            <img src="https://picsum.photos/250/200?random=halloween-family" alt="Halloween Family">
                        </div>
                    </div>
                </div>

                <!-- Halloween Kitchen -->
                <div class="halloween-card halloween-kitchen">
                    <div class="halloween-content" style="background: #663399;">
                        <h2 class="halloween-kitchen-title">Halloween kitchen & dining</h2>
                        <div class="kitchen-items">
                            <div class="kitchen-item">
                                <img src="https://picsum.photos/80/80?random=candy1" alt="Halloween Candy">
                                <span class="kitchen-text">Halloween candy $10 & under</span>
                            </div>
                            <div class="kitchen-item">
                                <img src="https://picsum.photos/80/80?random=candy2" alt="Halloween Treats">
                                <span class="kitchen-text">Halloween bites & how from $9.98</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Halloween Fashion -->
                <div class="halloween-card halloween-fashion">
                    <div class="halloween-content" style="background: linear-gradient(135deg, #ff6b9d 0%, #ffa8cc 100%);">
                        <h2 class="halloween-fashion-title">Fierce & festive Halloween fashion</h2>
                        <div class="fashion-halloween-grid">
                            <img src="https://picsum.photos/60/80?random=costume1" alt="Costume 1">
                            <img src="https://picsum.photos/60/80?random=costume2" alt="Costume 2">
                            <img src="https://picsum.photos/60/80?random=costume3" alt="Costume 3">
                            <img src="https://picsum.photos/60/80?random=costume4" alt="Costume 4">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- All the Halloween feels -->
    <section class="product-row-section">
        <div class="container">
            <div class="row-header">
                <h2>All the Halloween feels</h2>
                <a href="/halloween" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="halloween-track">
                    <?php 
                    $halloween_products = !empty($trending_products) ? array_slice($trending_products, 0, 6) : [];
                    // Try to get real products for Halloween section
                    if (empty($halloween_products)) {
                        $halloween_products = fetchRealProducts(6); // Any products
                    }
                    
                    // Only display products if we have real ones
                    if (!empty($halloween_products)):
                        foreach($halloween_products as $product): 
                            $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>">
                                <button class="wishlist-heart">♡</button>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="product-name"><?php echo h($product['title']); ?></p>
                                <div class="action-buttons">
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">Add to Cart</button>
                                    <a href="<?php echo h($product['url']); ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                    else: ?>
                        <div class="no-products-message">
                            <p>No trending products available at the moment. Please check back later!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('halloween-track', 'right')">›</button>
            </div>
        </div>
    </section>

    <!-- Trending on social -->
    <section class="social-trending-section">
        <div class="container">
            <div class="row-header">
                <h2>Trending on social</h2>
            </div>
            <div class="social-images-grid">
                <div class="social-image-card">
                    <img src="https://picsum.photos/400/400?random=social1" alt="Trending 1">
                    <div class="social-overlay">
                        <span class="shop-the-look">Shop the look</span>
                    </div>
                </div>
                <div class="social-image-card">
                    <img src="https://picsum.photos/400/400?random=social2" alt="Trending 2">
                    <div class="social-overlay">
                        <span class="shop-the-look">Shop the look</span>
                    </div>
                </div>
                <div class="social-image-card">
                    <img src="https://picsum.photos/400/400?random=social3" alt="Trending 3">
                    <div class="social-overlay">
                        <span class="shop-the-look">Shop the look</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<!-- Complete Walmart Styling -->
<style>
/* Reset and Base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background-color: #f7f7f7;
    color: #333;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 16px;
}

.container-wide {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 16px;
}

/* Top Grid */
.top-grid-section {
    background: white;
    padding: 16px 0;
}

.walmart-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    grid-template-rows: repeat(4, 180px);
    gap: 12px;
    max-width: 1200px;
    margin: 0 auto;
}

.grid-card {
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.grid-card:hover {
    transform: translateY(-2px);
}

/* Admin Edit Functionality */
.admin-editable {
    position: relative;
}

.admin-edit-overlay {
    position: absolute;
    top: 8px;
    right: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 100;
}

.admin-editable:hover .admin-edit-overlay {
    opacity: 1;
}

.admin-edit-btn {
    background: rgba(0, 0, 0, 0.8);
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.admin-edit-btn:hover {
    background: #0071ce;
    transform: scale(1.1);
}

/* Enhanced Image Handling - Apply to ALL banners */
.card-bg,
.hero-content,
.assembly-banner-content,
.prettygarden-content,
.halloween-content {
    background-size: cover !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
}

.product-image-container img,
.card-image-small img,
.bedding-img,
.tech-image-small img,
.assembly-hero-image,
.pg-model {
    object-fit: cover !important;
    width: 100%;
    height: 100%;
}

/* Placeholder image handling */
.product-image-container img[src="/images/placeholder-product.jpg"],
.product-image-container img[src*="placeholder"] {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 14px;
    text-align: center;
}

.product-image-container img[src="/images/placeholder-product.jpg"]:before {
    content: "Product Image";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

/* Hero Banner Styles */
.hero-banner-section {
    margin: 20px 0 30px 0;
}

.hero-banner {
    position: relative;
    width: 100%;
    height: 400px;
    margin-bottom: 20px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.hero-content {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 40px;
    position: relative;
    background-size: cover !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
}

.hero-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 1;
}

.hero-text {
    position: relative;
    z-index: 2;
    color: white;
    max-width: 600px;
}

.hero-text h1 {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.hero-subtitle {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

.hero-description {
    font-size: 1.1rem;
    margin-bottom: 2rem;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

.hero-cta-btn {
    background: #0071ce;
    color: white;
    padding: 15px 30px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    transition: background-color 0.3s ease;
    text-shadow: none;
}

.hero-cta-btn:hover {
    background: #004c91;
    color: white;
    text-decoration: none;
}

/* Add to Cart Button Styling */
.add-to-cart-btn {
    background: #0071ce;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease;
    margin-right: 8px;
}

.add-to-cart-btn:hover {
    background: #004c91;
}

/* No Products Message Styling */
.no-products-message {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
}

.no-fashion-items {
    text-align: center;
    padding: 20px;
    color: #666;
    font-size: 0.9rem;
}

.card-bg {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    color: white;
}

/* Card Content Styles */
.card-content-wrapper {
    display: flex;
    flex-direction: column;
    height: 100%;
    justify-content: space-between;
}

.small-tag, .promo-tag-small, .product-tag-small {
    background: rgba(255,255,255,0.2);
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    align-self: flex-start;
    margin-bottom: 8px;
}

.card-image-small, .promo-image-small {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-image-small img, .promo-image-small img {
    max-width: 80%;
    height: auto;
    border-radius: 4px;
}

.shop-now-link, .shop-now-small, .shop-now-tiny {
    color: rgba(255,255,255,0.9);
    text-decoration: underline;
    font-size: 12px;
    font-weight: 600;
    margin-top: auto;
}

/* Cashback Card */
.cashback-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    height: 100%;
}

.cashback-text {
    flex: 1;
}

.cashback-small {
    font-size: 13px;
    font-weight: 400;
    display: block;
    margin-bottom: 8px;
}

.cashback-big {
    font-size: 18px;
    line-height: 1.2;
    margin-bottom: 12px;
}

.cashback-big .percent {
    font-size: 36px;
    font-weight: 700;
}

.learn-link {
    color: white;
    text-decoration: underline;
    font-size: 12px;
}

.card-visual-right {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
}

.credit-card-visual {
    width: 140px;
    height: 90px;
    background: linear-gradient(45deg, #1e3a8a, #3b82f6);
    border-radius: 8px;
    position: relative;
    padding: 12px;
}

.card-chip {
    width: 18px;
    height: 14px;
    background: #ffd700;
    border-radius: 2px;
    position: absolute;
    top: 12px;
    left: 12px;
}

.card-brand {
    position: absolute;
    bottom: 12px;
    left: 12px;
    font-size: 12px;
    font-weight: 700;
    color: white;
}

.card-logo {
    position: absolute;
    top: 12px;
    right: 12px;
    font-size: 16px;
    color: #ffd700;
}

/* Bedding Card */
.bedding-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    color: #333;
    justify-content: space-between;
}

.promo-text {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

.product-showcase-inline {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.bedding-img {
    width: 120px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 8px;
}

.price-tag {
    font-size: 16px;
    font-weight: 700;
    color: #000;
}

/* Tech Savings */
.tech-savings-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    color: #333;
    justify-content: space-between;
}

.savings-tag {
    font-size: 12px;
    font-weight: 600;
    color: #f57c00;
}

.tech-image-small {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tech-image-small img {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

/* Resell Card */
.resell-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    color: #333;
    justify-content: space-between;
}

.resell-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
    line-height: 1.3;
}

.resell-product {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.watch-container {
    position: relative;
    margin-bottom: 8px;
}

.watch-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.discount-badge-yellow {
    background: #ffeb3b;
    color: #333;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
}

.flash-deal-badge {
    background: #ff4444;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
}

.learn-more-link {
    font-size: 11px;
    color: #0071ce;
    text-decoration: underline;
    font-weight: 600;
}

/* Flash Item */
.flash-item-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    align-items: center;
    justify-content: space-between;
    color: #333;
}

.flash-item-image {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flash-item-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.flash-badge {
    background: #ff4444;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
}

/* Messy Eater */
.messy-eater-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    color: #333;
    justify-content: space-between;
}

.product-image-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image-container img {
    width: 60px;
    height: auto;
    border-radius: 4px;
}

/* Him & Her */
.him-her-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    color: #333;
}

.section-title-small {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

.fashion-items-row {
    display: flex;
    gap: 8px;
    flex: 1;
    align-items: center;
}

.fashion-item-small {
    text-align: center;
}

.fashion-image-container {
    position: relative;
    margin-bottom: 4px;
}

.fashion-image-container img {
    width: 50px;
    height: 70px;
    object-fit: cover;
    border-radius: 4px;
}

.heart-icon {
    position: absolute;
    top: 2px;
    right: 2px;
    background: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.item-price-small {
    font-size: 11px;
    font-weight: 600;
    color: #000;
}

.navigation-arrows {
    display: flex;
    justify-content: flex-end;
    gap: 4px;
    margin-top: 8px;
}

.arrow-left, .arrow-right {
    background: white;
    border: 1px solid #ccc;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    cursor: pointer;
}

/* Burger King */
.burger-king-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    height: 100%;
    color: white;
}

.bk-text {
    flex: 1;
    padding-right: 12px;
}

.bk-title {
    font-size: 16px;
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 12px;
}

.learn-more-btn-orange {
    background: white;
    color: #ff6b35;
    padding: 6px 12px;
    border-radius: 16px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

.bk-food-image img {
    width: 120px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

/* Assembly Banner */
.assembly-full-banner {
    margin: 16px 0;
}

.assembly-banner-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 32px;
    border-radius: 8px;
    color: white;
    min-height: 200px;
}

.assembly-left {
    flex: 1;
}

.assembly-tag {
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 12px;
}

.assembly-title {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 4px;
    line-height: 1;
}

.assembly-subtitle {
    font-size: 24px;
    font-weight: 300;
    display: block;
    margin-bottom: 12px;
}

.assembly-fine-print {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 2px;
}

.assembly-right {
    flex: 1;
    display: flex;
    justify-content: center;
}

.assembly-hero-image {
    max-width: 400px;
    height: 160px;
    object-fit: cover;
    border-radius: 8px;
}

/* Product Rows */
.product-row-section {
    background: white;
    padding: 24px 0;
    margin-bottom: 12px;
}

.row-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.row-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.shop-all-link {
    color: #0071ce;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.shop-all-link:hover {
    text-decoration: underline;
}

.products-horizontal-container {
    position: relative;
    overflow: hidden;
}

.products-track {
    display: flex;
    gap: 16px;
    transition: transform 0.3s ease;
    padding-bottom: 8px;
}

.walmart-product-card {
    background: white;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    min-width: 200px;
    flex-shrink: 0;
    overflow: hidden;
    transition: box-shadow 0.2s ease;
}

.walmart-product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.product-image-container {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.product-image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.wishlist-heart {
    position: absolute;
    top: 8px;
    right: 8px;
    background: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    color: #666;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.wishlist-heart:hover {
    color: #ff4444;
    background: #f9f9f9;
}

.rollback-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: #ff4444;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.product-details {
    padding: 16px;
}

.price-section {
    margin-bottom: 8px;
}

.now-text {
    font-size: 14px;
    color: #666;
    margin-right: 2px;
}

.current-price-large {
    font-size: 18px;
    font-weight: 700;
    color: #000;
}

.crossed-price {
    font-size: 14px;
    color: #666;
    text-decoration: line-through;
    margin-left: 6px;
}

.product-name {
    font-size: 14px;
    color: #333;
    line-height: 1.3;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 36px;
}

.star-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 8px;
}

.stars {
    font-size: 12px;
    color: #ffc107;
}

.review-number {
    font-size: 12px;
    color: #666;
}

.shipping-text {
    margin-bottom: 12px;
}

.free-shipping-text {
    font-size: 12px;
    color: #2e7d32;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.options-button, .add-button {
    flex: 1;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}

.options-button {
    background: transparent;
    border: 1px solid #0071ce;
    color: #0071ce;
}

.options-button:hover {
    background: #0071ce;
    color: white;
}

.add-button {
    background: #0071ce;
    border: 1px solid #0071ce;
    color: white;
}

.add-button:hover {
    background: #004c91;
}

.scroll-right-btn {
    position: absolute;
    right: -16px;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: 1px solid #ddd;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    color: #666;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 10;
}

.scroll-right-btn:hover {
    background: #f5f5f5;
    color: #333;
}

/* PrettyGarden Banner */
.prettygarden-banner {
    margin: 16px 0;
}

.prettygarden-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 32px;
    border-radius: 8px;
    color: white;
    min-height: 180px;
}

.prettygarden-left {
    flex: 1;
}

.pg-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
}

.pg-subtitle {
    font-size: 24px;
    font-weight: 400;
    margin-bottom: 16px;
}

.pg-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pg-circle {
    background: white;
    color: #ff69b4;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
}

.pg-name {
    font-size: 18px;
    font-weight: 400;
}

.prettygarden-right {
    flex: 1;
    display: flex;
    justify-content: center;
}

.pg-model {
    max-width: 200px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
}

/* Categories */
.categories-row-section {
    background: white;
    padding: 24px 0;
    margin-bottom: 12px;
}

.categories-horizontal-container {
    position: relative;
    overflow: hidden;
}

.categories-track {
    display: flex;
    gap: 24px;
    transition: transform 0.3s ease;
    padding-bottom: 8px;
}

.category-circle-item {
    text-align: center;
    min-width: 100px;
    flex-shrink: 0;
}

.category-circle-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 8px;
}

.category-name {
    font-size: 14px;
    color: #333;
    font-weight: 500;
    line-height: 1.2;
}

/* Halloween Section */
.halloween-section {
    background: white;
    padding: 32px 0;
    margin: 16px 0;
}

.halloween-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
    max-width: 1200px;
    margin: 0 auto;
}

.halloween-card {
    border-radius: 8px;
    overflow: hidden;
    min-height: 280px;
}

.halloween-content {
    padding: 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
    color: white;
}

.halloween-coziness .halloween-content {
    justify-content: space-between;
}

.halloween-tag {
    font-size: 12px;
    background: rgba(255,255,255,0.2);
    padding: 4px 8px;
    border-radius: 12px;
    display: inline-block;
    align-self: flex-start;
    margin-bottom: 12px;
}

.halloween-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 16px;
}

.shop-now-halloween {
    background: white;
    color: #ff6600;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    align-self: flex-start;
}

.halloween-image {
    text-align: center;
}

.halloween-image img {
    max-width: 200px;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
}

.halloween-kitchen-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    color: white;
}

.kitchen-items {
    flex: 1;
}

.kitchen-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.kitchen-item img {
    width: 50px;
    height: 50px;
    border-radius: 4px;
    object-fit: cover;
}

.kitchen-text {
    font-size: 13px;
    line-height: 1.3;
    color: white;
}

.halloween-fashion-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    color: white;
}

.fashion-halloween-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    flex: 1;
}

.fashion-halloween-grid img {
    width: 100%;
    height: 70px;
    object-fit: cover;
    border-radius: 4px;
}

/* Social Trending */
.social-trending-section {
    background: white;
    padding: 24px 0;
    margin-bottom: 12px;
}

.social-images-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    max-width: 1200px;
    margin: 0 auto;
}

.social-image-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    height: 400px;
}

.social-image-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.social-overlay {
    position: absolute;
    bottom: 16px;
    left: 16px;
    right: 16px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 12px;
    border-radius: 4px;
    text-align: center;
}

.shop-the-look {
    font-size: 14px;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
    /* Hero Banner Mobile Styles */
    .hero-content {
        min-height: 300px;
        padding: 20px;
    }
    
    .hero-text h1 {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1.2rem;
    }
    
    .hero-description {
        font-size: 1rem;
    }
    
    .hero-cta-btn {
        padding: 12px 24px;
        font-size: 1rem;
    }
    
    .walmart-grid {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: repeat(8, 160px);
    }
    
    .walmart-grid .grid-card {
        grid-column: span 1 !important;
        grid-row: span 1 !important;
    }
    
    .assembly-banner-content,
    .prettygarden-content {
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    
    .halloween-grid {
        grid-template-columns: 1fr;
    }
    
    .social-images-grid {
        grid-template-columns: 1fr;
    }
    
    .walmart-product-card {
        min-width: 160px;
    }
    
    .scroll-right-btn {
        display: none;
    }
}
</style>

<!-- JavaScript for Functionality -->
<script>
/* ---------- Admin Banner Editing Functions ---------- */
function editBanner(bannerId, bannerType) {
    // Create modal for editing banner
    const modal = document.createElement('div');
    modal.className = 'admin-edit-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeEditModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h3>Edit Banner</h3>
                    <button onclick="closeEditModal()" class="close-btn">&times;</button>
                </div>
                <form id="edit-banner-form" onsubmit="saveBanner(event, '${bannerId}', '${bannerType}')" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Title:</label>
                        <input type="text" name="title" id="banner-title" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="description" id="banner-description"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Background Image:</label>
                        <div class="image-upload-options">
                            <div class="upload-option">
                                <label for="banner-image-file">Upload Image File:</label>
                                <input type="file" name="banner_image" id="banner-image-file" accept="image/*">
                                <small>Max size: 5MB. Supports JPEG, PNG, GIF, WebP</small>
                            </div>
                            <div class="upload-divider">OR</div>
                            <div class="upload-option">
                                <label for="banner-image-url">Image URL:</label>
                                <input type="url" name="image_url" id="banner-image-url" placeholder="https://example.com/image.jpg">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Link URL:</label>
                        <input type="url" name="link_url" id="banner-link">
                    </div>
                    <div class="form-group">
                        <label>Button Text:</label>
                        <input type="text" name="button_text" id="banner-button">
                    </div>
                    <input type="hidden" name="banner_id" value="${bannerId}">
                    <input type="hidden" name="banner_type" value="${bannerType}">
                    <div class="modal-actions">
                        <button type="button" onclick="closeEditModal()">Cancel</button>
                        <button type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    // Add modal styles
    if (!document.getElementById('modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'modal-styles';
        styles.textContent = `
            .admin-edit-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; }
            .modal-overlay { background: rgba(0,0,0,0.8); width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
            .modal-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
            .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; }
            .form-group { margin-bottom: 15px; }
            .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
            .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            .image-upload-options { border: 1px solid #e0e0e0; padding: 15px; border-radius: 4px; background: #f9f9f9; }
            .upload-option { margin-bottom: 10px; }
            .upload-divider { text-align: center; margin: 15px 0; font-weight: bold; color: #666; }
            .upload-option small { color: #666; font-size: 12px; display: block; margin-top: 4px; }
            .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
            .modal-actions button { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
            .modal-actions button[type="submit"] { background: #0071ce; color: white; }
            .modal-actions button[type="button"] { background: #ccc; }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(modal);
}

function closeEditModal() {
    const modal = document.querySelector('.admin-edit-modal');
    if (modal) {
        modal.remove();
    }
}

function saveBanner(event, bannerId, bannerType) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Add banner ID and type if not already in form
    if (!formData.has('banner_id')) {
        formData.append('banner_id', bannerId);
    }
    if (!formData.has('banner_type')) {
        formData.append('banner_type', bannerType);
    }
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    // Send AJAX request to save banner
    fetch('/admin/save-banner.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Banner updated successfully!');
            closeEditModal();
            // Refresh the page to show changes
            location.reload();
        } else {
            alert('Error updating banner: ' + (result.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating banner. Please try again.');
    })
    .finally(() => {
        // Restore button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

/* ---------- Add to Cart Functionality ---------- */
function addToCart(productId) {
    if (!productId) {
        alert('Product ID is required');
        return;
    }
    
    // Send AJAX request to add product to cart
    fetch('/cart/ajax-add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Product added to cart!');
            // Update cart count if element exists
            const cartCount = document.querySelector('.cart-count');
            if (cartCount && result.cart_count) {
                cartCount.textContent = result.cart_count;
            }
        } else {
            // If user not logged in, redirect to login
            if (result.login_required) {
                window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.href);
            } else {
                alert('Error adding product to cart: ' + (result.message || 'Unknown error'));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding product to cart. Please try again.');
    });
}

/* ---------- Existing Functions ---------- */
function scrollProducts(trackId, direction) {
    const track = document.getElementById(trackId);
    const scrollAmount = 220;
    
    if (direction === 'right') {
        track.scrollLeft += scrollAmount;
        
        // Reset to start if at the end
        if (track.scrollLeft >= track.scrollWidth - track.offsetWidth) {
            setTimeout(() => {
                track.scrollLeft = 0;
            }, 100);
        }
    } else {
        track.scrollLeft -= scrollAmount;
    }
}

function scrollCategories(direction) {
    const track = document.querySelector('.categories-track');
    const scrollAmount = 200;
    
    if (direction === 'right') {
        track.scrollLeft += scrollAmount;
        
        // Reset to start if at the end
        if (track.scrollLeft >= track.scrollWidth - track.offsetWidth) {
            setTimeout(() => {
                track.scrollLeft = 0;
            }, 100);
        }
    } else {
        track.scrollLeft -= scrollAmount;
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Homepage with admin editing and add to cart functionality loaded');
});
</script>

<?php includeFooter(); ?>