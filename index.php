<?php
/**
 * Homepage - FezaMarket E-Commerce Platform
 * Hardened + Professional Shelf Layout
 */

require_once __DIR__ . '/includes/init.php';

/* ---------- Helpers (idempotent) ---------- */
if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('hp')) {
    function hp($amount, $currency = 'USD'): string {
        if (!is_numeric($amount)) $amount = 0;
        if (function_exists('formatPrice')) {
            return formatPrice((float)$amount, (string)$currency);
        }
        $symbol = '$';
        if ($currency === 'EUR') $symbol = '€';
        elseif ($currency === 'GBP') $symbol = '£';
        return $symbol . number_format((float)$amount, 2);
    }
}
if (!function_exists('normalizeProduct')) {
    function normalizeProduct(array $p): array {
        return [
            'id'           => isset($p['id']) ? (int)$p['id'] : 0,
            'name'         => (string)($p['name'] ?? ''),
            'slug'         => (string)($p['slug'] ?? ''),
            'image_url'    => (string)($p['image_url'] ?? ($p['image'] ?? '')),
            'price'        => isset($p['price']) && is_numeric($p['price']) ? (float)$p['price'] : 0.0,
            'currency'     => (string)($p['currency'] ?? 'USD'),
            'vendor_name'  => (string)($p['vendor_name'] ?? ''),
            'sponsored'    => !empty($p['sponsored']),
            'unit_price'   => isset($p['unit_price']) ? (float)$p['unit_price'] : null,   // optional
            'unit_label'   => (string)($p['unit_label'] ?? ''), // e.g. 'oz', 'c/oz'
        ];
    }
}
if (!function_exists('normalizeProductList')) {
    function normalizeProductList(?array $list): array {
        if (empty($list) || !is_array($list)) return [];
        return array_map('normalizeProduct', array_filter($list, 'is_array'));
    }
}
if (!function_exists('safeProductImg')) {
    function safeProductImg(array $p, string $fallback = '/images/placeholder-product.png'): string {
        $url = $p['image_url'] ?? '';
        if ($url === '') return $fallback;
        return function_exists('getProductImageUrl') ? getProductImageUrl($url) : $url;
    }
}

/* ---------- Domain objects ---------- */
$product        = class_exists('Product')        ? new Product()        : null;
$category       = class_exists('Category')       ? new Category()       : null;
$recommendation = class_exists('Recommendation') ? new Recommendation() : null;

/* ---------- Data Fetch (temporary mock data for UI development) ---------- */
$featuredProducts  = [];
$categories        = [];
$trendingProducts  = [];
$newArrivals       = [];
$bannerProductsRaw = [];
$bannerProducts    = [];

// Fetch banners from database for CMS management
$banners = [];
try {
    $db = db();
    $stmt = $db->query("
        SELECT * FROM banners 
        WHERE status = 'active' 
        ORDER BY position, sort_order
    ");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Failed to fetch banners: ' . $e->getMessage());
    // Fallback to empty array
    $banners = [];
}

// Organize banners by position for easy access
$bannersByPosition = [];
foreach ($banners as $banner) {
    $position = $banner['position'] ?? 'header';
    if (!isset($bannersByPosition[$position])) {
        $bannersByPosition[$position] = [];
    }
    $bannersByPosition[$position][] = $banner;
}

while (count($bannerProducts) < 4) $bannerProducts[] = normalizeProduct([]);

/* ---------- Activity log ---------- */
if ($recommendation && class_exists('Session') && Session::isLoggedIn()) {
    try { $recommendation->logActivity(Session::getUserId(), null, 'view_product'); }
    catch (Throwable $e) { error_log('Activity log failed: '.$e->getMessage()); }
}

/* ---------- Current user ---------- */
$current_user = $current_user ?? [];
if (empty($current_user) && class_exists('Session') && Session::isLoggedIn() && function_exists('getUserById')) {
    try { $current_user = getUserById(Session::getUserId()) ?: []; } catch (Throwable $e) { $current_user = []; }
}

/* ---------- Page meta & header ---------- */
$page_title = 'FezaMarket - Buy & Sell Everything';
if (function_exists('includeHeader')) {
    includeHeader($page_title);
} else {
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>".h($page_title)."</title></head><body>";
}

/* ---------- Professional Shelf Styles ---------- */
?>
<style>
/* ========== Shelf Layout ========== */
.product-shelf-section { margin: 48px 0 32px; }
.product-shelf-header { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; margin-bottom:18px; }
.product-shelf-header h2 { font-size:20px; font-weight:600; margin:0; color:#1f2937; }
.product-shelf-header a { font-size:14px; color:#0654ba; text-decoration:none; font-weight:500; }
.product-shelf-container { position:relative; }
.product-shelf-scroll {
    display:flex;
    gap:48px; /* Space between cards similar to screenshot */
    padding:6px 6px 10px;
    overflow-x:auto;
    scroll-snap-type:x mandatory;
    scrollbar-width:none;
}
.product-shelf-scroll::-webkit-scrollbar { display:none; }
.product-card-shelf {
    flex:0 0 200px;
    width:200px;
    scroll-snap-align:start;
    display:flex;
    flex-direction:column;
    position:relative;
    font-size:14px;
    color:#1e1e1e;
    background:transparent;
}
.product-card-shelf .media-wrapper {
    position:relative;
    width:100%;
    height:200px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fff;
    border-radius:4px;
    border:1px solid #e5e7eb;
    overflow:hidden;
}
.product-card-shelf img {
    width:100%;
    height:100%;
    object-fit:cover;
    transition:transform .35s ease;
}
.product-card-shelf:hover img { transform:scale(1.04); }
.wishlist-btn {
    position:absolute; top:8px; right:8px;
    width:38px; height:38px;
    background:#fff;
    border:1px solid #cfcfcf;
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:18px;
    cursor:pointer;
    transition:background .25s;
}
.wishlist-btn:hover { background:#f2f2f2; }
.sponsored-label {
    font-size:12px; color:#555; margin:10px 0 2px;
}
.price-row { display:flex; flex-wrap:wrap; align-items:baseline; gap:8px; margin-top:6px; }
.price-row .main-price { font-size:16px; font-weight:600; }
.price-row .unit-price { font-size:12px; color:#555; letter-spacing:.3px; }
.product-title-shelf {
    margin:10px 0 12px;
    font-size:14px;
    line-height:1.3;
    font-weight:500;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    min-height:37px; /* maintain uniform height for 2 lines */
}
.pill-btn-row {
    margin-top:auto;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.pill-btn {
    border:1px solid #1e1e1e;
    background:#fff;
    color:#111;
    font-size:14px;
    padding:10px 28px;
    border-radius:1000px;
    font-weight:500;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    gap:6px;
    line-height:1;
    transition:background .2s, color .2s;
}
.pill-btn:hover { background:#111; color:#fff; }
.pill-btn.secondary {
    border:1px solid #1e1e1e;
    background:#fff;
}
.add-icon { font-size:18px; line-height:1; position:relative; top:1px; }

.shelf-nav-btn {
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    right:6px;
    width:56px;
    height:56px;
    background:#fff;
    border:1px solid #d4d4d4;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    font-size:28px;
    box-shadow:0 4px 12px rgba(0,0,0,.12);
    transition:background .25s;
    z-index:5;
}
.shelf-nav-btn:hover { background:#f6f6f6; }
.shelf-nav-btn.hidden { display:none !important; }

@media (max-width: 860px) {
    .product-shelf-scroll { gap:28px; }
    .product-card-shelf { flex:0 0 170px; width:170px; }
    .product-card-shelf .media-wrapper { height:180px; }
}

@media (max-width: 560px) {
    .product-card-shelf { flex:0 0 150px; width:150px; }
    .product-card-shelf .media-wrapper { height:160px; }
    .pill-btn { padding:8px 18px; font-size:12px; }
    .price-row .main-price { font-size:14px; }
}

</style>

<div class="homepage-container">

    <!-- Enhanced Hero Banner Section -->
    <section class="hero-section">
        <div class="hero-main-banner">
            <div class="hero-image">
                <?php 
                $heroBanner = $bannersByPosition['hero'][0] ?? null;
                $heroImage = $heroBanner ? $heroBanner['image_url'] : '/images/banners/trending-banner.jpg';
                $heroLink = $heroBanner ? $heroBanner['link_url'] : '/deals.php';
                $heroTitle = $heroBanner ? $heroBanner['title'] : 'New & trending editors\' picks';
                ?>
                <img src="<?= h($heroImage) ?>" alt="<?= h($heroTitle) ?>" loading="lazy">
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">New & trending<br><span class="highlight">editors' picks</span></h1>
                    <p class="hero-subtitle">Discover the latest curated collections from our fashion experts</p>
                    <div class="hero-actions">
                        <a href="<?= h($heroLink) ?>" class="btn-hero primary">
                            <i class="fas fa-star"></i>
                            Shop Now
                        </a>
                        <a href="/collections.php" class="btn-hero secondary">
                            <i class="fas fa-eye"></i>
                            View Collection
                        </a>
                    </div>
                    <div class="hero-badge">
                        <span class="badge-text">
                            <i class="fas fa-fire"></i>
                            Trending Now
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Categories Grid -->
    <section class="featured-categories">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Shop by Category</h2>
                <p class="section-subtitle">Discover what's trending in each category</p>
            </div>
            <div class="categories-grid">
                <?php
                $categoryBanners = $bannersByPosition['category'] ?? [];
                $categoryData = [
                    ['name' => 'electronics', 'title' => 'Electronics', 'subtitle' => 'Latest gadgets & tech', 'badge' => 'Hot'],
                    ['name' => 'fashion', 'title' => 'Fashion', 'subtitle' => 'Trending styles', 'badge' => ''],
                    ['name' => 'home-garden', 'title' => 'Home & Garden', 'subtitle' => 'Decor & essentials', 'badge' => '']
                ];
                
                foreach ($categoryData as $index => $categoryInfo):
                    $banner = isset($categoryBanners[$index]) ? $categoryBanners[$index] : null;
                    $image = $banner ? $banner['image_url'] : "/images/banners/{$categoryInfo['name']}-banner.jpg";
                    $link = $banner ? $banner['link_url'] : "/category.php?name={$categoryInfo['name']}";
                    $featured = $index === 0 ? ' featured' : '';
                ?>
                <div class="category-card<?= $featured ?>" onclick="window.location.href='<?= h($link) ?>'">
                    <div class="category-image">
                        <img src="<?= h($image) ?>" alt="<?= h($categoryInfo['title']) ?>" loading="lazy">
                        <div class="category-overlay"></div>
                    </div>
                    <div class="category-content">
                        <h3 class="category-title"><?= h($categoryInfo['title']) ?></h3>
                        <p class="category-subtitle"><?= h($categoryInfo['subtitle']) ?></p>
                        <?php if ($categoryInfo['badge']): ?>
                        <div class="category-badge"><?= h($categoryInfo['badge']) ?></div>
                        <?php endif; ?>
                        <a href="<?= h($link) ?>" class="category-btn">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="category-card" onclick="window.location.href='/deals.php'">
                    <div class="category-image special-offer">
                        <div class="offer-content">
                            <i class="fas fa-bolt"></i>
                            <span class="offer-text">Flash Deals</span>
                        </div>
                        <div class="category-overlay"></div>
                    </div>
                    <div class="category-content">
                        <h3 class="category-title">Special Offers</h3>
                        <p class="category-subtitle">Up to 55% off</p>
                        <div class="category-badge sale">Sale</div>
                        <a href="/deals.php" class="category-btn">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Promotional Banner Section -->
    <section class="promotional-banners">
        <div class="container">
            <div class="promo-grid">
                <div class="promo-card primary" onclick="window.location.href='/category.php?name=electronics'">
                    <div class="promo-content">
                        <div class="promo-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3 class="promo-title">Get top tech in as fast as an hour*</h3>
                        <p class="promo-subtitle">Latest gadgets delivered quickly</p>
                        <a href="/category.php?name=electronics" class="promo-btn">
                            <span>Shop Electronics</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="promo-graphic">
                        <i class="fas fa-laptop"></i>
                    </div>
                </div>
                
                <div class="promo-card membership" onclick="window.location.href='/membership.php'">
                    <div class="promo-content">
                        <div class="promo-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h3 class="promo-title">Members enjoy free delivery</h3>
                        <p class="promo-subtitle">Join our premium membership program</p>
                        <a href="/membership.php" class="promo-btn">
                            <span>Start Free Trial</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <div class="promo-note">$35 min. T&C apply. One free trial per member.</div>
                    </div>
                    <div class="promo-graphic">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Personalized CTA Section -->
    <section class="personalized-cta">
        <div class="container">
            <div class="cta-content">
                <div class="cta-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="cta-text">
                    <h3 class="cta-title">Get personalized recommendations</h3>
                    <p class="cta-subtitle">Sign in to discover products tailored just for you</p>
                </div>
                <div class="cta-actions">
                    <?php if (!class_exists('Session') || !Session::isLoggedIn()): ?>
                        <a href="/login.php" class="btn-cta primary">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In
                        </a>
                        <a href="/register.php" class="btn-cta secondary">
                            <i class="fas fa-user-plus"></i>
                            Create Account
                        </a>
                    <?php else: ?>
                        <div class="welcome-message">
                            <i class="fas fa-check-circle"></i>
                            <span>Welcome back, <?= h($current_user['first_name'] ?? 'User'); ?>!</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="products-section featured">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-star"></i>
                    Featured Products
                </h2>
                <div class="section-actions">
                    <p class="section-subtitle">Hand-picked favorites just for you</p>
                    <a href="/products.php" class="view-all-btn">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="products-carousel">
                <div class="products-grid" id="featured-products">
                    <!-- Mock featured products since we have empty data -->
                    <div class="product-card modern">
                        <div class="product-image">
                            <img src="/images/products/sample-1.jpg" alt="Premium Headphones" loading="lazy">
                            <div class="product-badges">
                                <span class="badge trending">Trending</span>
                            </div>
                            <div class="product-actions">
                                <button class="action-btn wishlist" aria-label="Add to wishlist">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="action-btn quick-view" aria-label="Quick view">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">Premium Wireless Headphones</h3>
                            <div class="product-rating">
                                <div class="stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                                <span class="rating-count">(247)</span>
                            </div>
                            <div class="product-price">
                                <span class="current-price">$89.99</span>
                                <span class="original-price">$129.99</span>
                                <span class="discount">-31%</span>
                            </div>
                            <button class="add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                    
                    <div class="product-card modern">
                        <div class="product-image">
                            <img src="/images/products/sample-2.jpg" alt="Smart Watch" loading="lazy">
                            <div class="product-badges">
                                <span class="badge new">New</span>
                            </div>
                            <div class="product-actions">
                                <button class="action-btn wishlist" aria-label="Add to wishlist">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="action-btn quick-view" aria-label="Quick view">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">Smart Fitness Watch</h3>
                            <div class="product-rating">
                                <div class="stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <span class="rating-count">(1.2k)</span>
                            </div>
                            <div class="product-price">
                                <span class="current-price">$199.99</span>
                            </div>
                            <button class="add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                    
                    <div class="product-card modern">
                        <div class="product-image">
                            <img src="/images/products/sample-3.jpg" alt="Designer Backpack" loading="lazy">
                            <div class="product-badges">
                                <span class="badge sale">Sale</span>
                            </div>
                            <div class="product-actions">
                                <button class="action-btn wishlist" aria-label="Add to wishlist">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="action-btn quick-view" aria-label="Quick view">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">Designer Travel Backpack</h3>
                            <div class="product-rating">
                                <div class="stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                <span class="rating-count">(89)</span>
                            </div>
                            <div class="product-price">
                                <span class="current-price">$59.99</span>
                                <span class="original-price">$89.99</span>
                                <span class="discount">-33%</span>
                            </div>
                            <button class="add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                    
                    <div class="product-card modern">
                        <div class="product-image">
                            <img src="/images/products/sample-4.jpg" alt="Wireless Speaker" loading="lazy">
                            <div class="product-actions">
                                <button class="action-btn wishlist" aria-label="Add to wishlist">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="action-btn quick-view" aria-label="Quick view">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">Portable Bluetooth Speaker</h3>
                            <div class="product-rating">
                                <div class="stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                                <span class="rating-count">(156)</span>
                            </div>
                            <div class="product-price">
                                <span class="current-price">$39.99</span>
                            </div>
                            <button class="add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="carousel-controls">
                    <button class="carousel-btn prev" aria-label="Previous products">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-btn next" aria-label="Next products">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    
    <!-- Brand Spotlight Section -->
            </div>
            <button class="shelf-nav-btn" data-shelf-arrow="featured" aria-label="Next products">›</button>
        </div>
    </section>

    <!-- ========== TRENDING / STYLES SHELF ========== -->
    <section class="product-shelf-section" aria-label="Styles for all your plans">
        <div class="product-shelf-header">
            <h2>Styles for all your plans</h2>
            <a href="/category.php?name=fashion">View all</a>
        </div>
        <div class="product-shelf-container">
            <div class="product-shelf-scroll" data-shelf="styles">
                <?php foreach ($trendingProducts as $prod): ?>
                    <article class="product-card-shelf">
                        <div class="media-wrapper">
                            <img src="<?= h(safeProductImg($prod)) ?>" alt="<?= h($prod['name']) ?>">
                            <button type="button" class="wishlist-btn" aria-label="Add to wishlist" data-id="<?= h($prod['id']) ?>">♡</button>
                        </div>
                        <div class="price-row">
                            <span class="main-price"><?= h(hp($prod['price'], $prod['currency'])) ?></span>
                        </div>
                        <h3 class="product-title-shelf">
                            <a href="/product.php?id=<?= h($prod['id']) ?>" style="text-decoration:none;color:#111;">
                                <?= h($prod['name']) ?>
                            </a>
                        </h3>
                        <div class="pill-btn-row">
                            <button class="pill-btn secondary" type="button">Options</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <button class="shelf-nav-btn" data-shelf-arrow="styles" aria-label="Next products">›</button>
        </div>
    </section>

    <!-- Pretty Garden Banner -->
    <?php $b3 = $bannerProducts[3] ?? []; ?>
    <section class="garden-banner"
             style="background:linear-gradient(135deg,#fef3c7,#fcd34d);border-radius:12px;padding:30px;margin:30px 0;display:grid;grid-template-columns:1fr 1fr;gap:30px;align-items:center;">
        <div>
            <p style="color:#0654ba;font-size:14px;margin-bottom:5px;">Dresses to sweaters</p>
            <h2 style="color:#1f2937;font-size:32px;font-weight:700;margin-bottom:15px;">Just in from<br>PrettyGarden</h2>
            <a href="/brands/prettygarden.php"
               style="background:#fff;color:#1f2937;padding:12px 24px;border-radius:6px;font-weight:700;text-decoration:none;display:inline-block;">Shop now</a>
        </div>
        <div style="text-align:center;">
            <?php if (!empty($b3['image_url'])): ?>
                <img src="<?= h(safeProductImg($b3,'/images/banners/fashion-banner.jpg')) ?>"
                     alt="PrettyGarden Fashion" style="max-width:200px;border-radius:8px;">
            <?php endif; ?>
        </div>
    </section>

    <!-- ========== NEW ARRIVALS SHELF ========== -->
    <?php if (!empty($newArrivals)): ?>
        <section class="product-shelf-section" aria-label="New Arrivals">
            <div class="product-shelf-header">
                <h2>New Arrivals</h2>
                <a href="/products.php?filter=new">View all</a>
            </div>
            <div class="product-shelf-container">
                <div class="product-shelf-scroll" data-shelf="arrivals">
                    <?php foreach ($newArrivals as $prod): ?>
                        <article class="product-card-shelf">
                            <div class="media-wrapper">
                                <img src="<?= h(safeProductImg($prod)) ?>" alt="<?= h($prod['name']) ?>">
                                <button type="button" class="wishlist-btn" aria-label="Add to wishlist" data-id="<?= h($prod['id']) ?>">♡</button>
                                <div style="position:absolute;top:10px;left:10px;background:#16a34a;color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600;">New</div>
                            </div>
                            <div class="price-row">
                                <span class="main-price"><?= h(hp($prod['price'], $prod['currency'])) ?></span>
                            </div>
                            <h3 class="product-title-shelf">
                                <a href="/product.php?id=<?= h($prod['id']) ?>" style="text-decoration:none;color:#111;">
                                    <?= h($prod['name']) ?>
                                </a>
                            </h3>
                            <div class="pill-btn-row">
                                <button class="pill-btn" type="button" data-add="<?= h($prod['id']) ?>"><span class="add-icon">＋</span>Add</button>
                                <button class="pill-btn secondary" type="button">Options</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <button class="shelf-nav-btn" data-shelf-arrow="arrivals" aria-label="Next products">›</button>
            </div>
        </section>
    <?php endif; ?>

</div>

<script>
/* Horizontal shelf nav */
document.querySelectorAll('[data-shelf-arrow]').forEach(btn => {
    const key = btn.getAttribute('data-shelf-arrow');
    const scroller = document.querySelector('[data-shelf="'+key+'"]');
    if (!scroller) {
        btn.classList.add('hidden');
        return;
    }
    const checkOverflow = () => {
        // Hide arrow if no horizontal overflow
        if (scroller.scrollWidth <= scroller.clientWidth + 4) {
            btn.classList.add('hidden');
        } else {
            btn.classList.remove('hidden');
        }
    };
    checkOverflow();
    window.addEventListener('resize', checkOverflow);
    btn.addEventListener('click', () => {
        scroller.scrollBy({ left: scroller.clientWidth * 0.9, behavior: 'smooth' });
        setTimeout(checkOverflow, 500);
    });
});

/* Optional: Wishlist toggle visual only */
document.addEventListener('click', e => {
    const w = e.target.closest('.wishlist-btn');
    if (!w) return;
    w.textContent = w.textContent.trim() === '♡' ? '❤' : '♡';
});

/* Optional: Add button (demo) */
document.addEventListener('click', e => {
    const addBtn = e.target.closest('[data-add]');
    if (!addBtn) return;
    const id = addBtn.getAttribute('data-add');
    addBtn.disabled = true;
    addBtn.textContent = 'Added';
    // TODO: AJAX call to cart
    setTimeout(()=>{ addBtn.disabled = false; addBtn.textContent = '＋ Add'; }, 2000);
});
</script>

<?php
if (function_exists('includeFooter')) includeFooter(); else echo "</body></html>";
?>