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

// Fetch homepage sections configuration from CMS
$layoutSections = [];
$homepageSectionsEnabled = [];
try {
    $db = db();
    $stmt = $db->prepare("SELECT section_data FROM homepage_sections WHERE section_key = 'layout_config'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['section_data']) {
        $layoutSections = json_decode($result['section_data'], true) ?: [];
        // Create enabled sections lookup
        foreach ($layoutSections as $section) {
            $homepageSectionsEnabled[$section['id']] = $section['enabled'] ?? true;
        }
    }
} catch (Throwable $e) {
    error_log('Failed to fetch homepage sections: ' . $e->getMessage());
}

// Default sections if none exist in CMS
if (empty($layoutSections)) {
    $layoutSections = [
        ['id' => 'hero', 'type' => 'hero', 'title' => 'Hero Banner', 'enabled' => true],
        ['id' => 'categories', 'type' => 'categories', 'title' => 'Featured Categories', 'enabled' => true],
        ['id' => 'deals', 'type' => 'deals', 'title' => 'Daily Deals', 'enabled' => true],
        ['id' => 'trending', 'type' => 'products', 'title' => 'Trending Products', 'enabled' => true],
        ['id' => 'brands', 'type' => 'brands', 'title' => 'Top Brands', 'enabled' => true],
        ['id' => 'featured', 'type' => 'products', 'title' => 'Featured Products', 'enabled' => true],
        ['id' => 'new-arrivals', 'type' => 'products', 'title' => 'New Arrivals', 'enabled' => true],
        ['id' => 'recommendations', 'type' => 'products', 'title' => 'Recommended for You', 'enabled' => true]
    ];
    foreach ($layoutSections as $section) {
        $homepageSectionsEnabled[$section['id']] = true;
    }
}

// Fetch banners from database for CMS management
$banners = [];
try {
    $stmt = $db->query("
        SELECT * FROM homepage_banners 
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
    $position = $banner['position'] ?? 'hero';
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
    <?php
    // Render sections based on CMS configuration
    foreach ($layoutSections as $section) {
        if (!($homepageSectionsEnabled[$section['id']] ?? true)) {
            continue; // Skip disabled sections
        }
        
        switch ($section['id']) {
            case 'hero':
                renderHeroSection($bannersByPosition);
                break;
            case 'categories':
                renderCategoriesSection($bannersByPosition);
                break;
            case 'deals':
                renderDealsSection($bannersByPosition);
                break;
            case 'trending':
                renderTrendingSection($trendingProducts);
                break;
            case 'brands':
                renderBrandsSection($bannersByPosition);
                break;
            case 'featured':
                renderFeaturedSection($featuredProducts);
                break;
            case 'new-arrivals':
                renderNewArrivalsSection($newArrivals);
                break;
            case 'recommendations':
                renderRecommendationsSection($bannersByPosition);
                break;
        }
    }
    ?>
</div>

<?php
/* ========== SECTION RENDERING FUNCTIONS ========== */

function renderHeroSection($bannersByPosition) {
    $heroBanner = $bannersByPosition['hero'][0] ?? null;
    $heroImage = $heroBanner ? $heroBanner['image_url'] : '/images/banners/trending-banner.jpg';
    $heroLink = $heroBanner ? $heroBanner['link_url'] : '/deals.php';
    $heroTitle = $heroBanner ? $heroBanner['title'] : 'New & trending editors\' picks';
    $heroSubtitle = $heroBanner ? $heroBanner['subtitle'] : 'Discover the latest curated collections from our fashion experts';
    ?>
    <!-- Section 1: Hero Banner -->
    <section class="hero-section">
        <div class="hero-main-banner">
            <div class="hero-image">
                <img src="<?= h($heroImage) ?>" alt="<?= h($heroTitle) ?>" loading="lazy">
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title"><?= h($heroTitle) ?></h1>
                    <p class="hero-subtitle"><?= h($heroSubtitle) ?></p>
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
    <?php
}

function renderCategoriesSection($bannersByPosition) {
    ?>
    <!-- Section 2: Featured Categories Grid -->
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
                    ['name' => 'home-garden', 'title' => 'Home & Garden', 'subtitle' => 'Decor & essentials', 'badge' => ''],
                    ['name' => 'sports', 'title' => 'Sports', 'subtitle' => 'Fitness & outdoor', 'badge' => 'New']
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
            </div>
        </div>
    </section>
    <?php
}

function renderDealsSection($bannersByPosition) {
    ?>
    <!-- Section 3: Daily Deals -->
    <section class="deals-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-bolt"></i>
                    Daily Deals
                </h2>
                <p class="section-subtitle">Limited time offers - grab them while they last!</p>
            </div>
            <div class="deals-grid">
                <div class="deal-card featured">
                    <div class="deal-timer">
                        <i class="fas fa-clock"></i>
                        <span>12h 34m left</span>
                    </div>
                    <div class="deal-image">
                        <img src="/images/products/deal-1.jpg" alt="Flash Deal" loading="lazy">
                        <div class="deal-badge">-55%</div>
                    </div>
                    <div class="deal-content">
                        <h3>Flash Sale Electronics</h3>
                        <div class="deal-price">
                            <span class="current">$89.99</span>
                            <span class="original">$199.99</span>
                        </div>
                        <a href="/deals.php?category=electronics" class="deal-btn">Shop Now</a>
                    </div>
                </div>
                
                <div class="deal-card">
                    <div class="deal-timer">
                        <i class="fas fa-clock"></i>
                        <span>6h 15m left</span>
                    </div>
                    <div class="deal-image">
                        <img src="/images/products/deal-2.jpg" alt="Fashion Deal" loading="lazy">
                        <div class="deal-badge">-40%</div>
                    </div>
                    <div class="deal-content">
                        <h3>Fashion Clearance</h3>
                        <div class="deal-price">
                            <span class="current">$59.99</span>
                            <span class="original">$99.99</span>
                        </div>
                        <a href="/deals.php?category=fashion" class="deal-btn">Shop Now</a>
                    </div>
                </div>
                
                <div class="deal-card">
                    <div class="deal-timer">
                        <i class="fas fa-clock"></i>
                        <span>24h 00m left</span>
                    </div>
                    <div class="deal-image">
                        <img src="/images/products/deal-3.jpg" alt="Home Deal" loading="lazy">
                        <div class="deal-badge">-30%</div>
                    </div>
                    <div class="deal-content">
                        <h3>Home Essentials</h3>
                        <div class="deal-price">
                            <span class="current">$39.99</span>
                            <span class="original">$59.99</span>
                        </div>
                        <a href="/deals.php?category=home" class="deal-btn">Shop Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function renderTrendingSection($trendingProducts) {
    ?>
    <!-- Section 4: Trending Products -->
    <section class="product-shelf-section" aria-label="Trending Products">
        <div class="container">
            <div class="product-shelf-header">
                <h2>Trending Products</h2>
                <a href="/products.php?filter=trending">View all</a>
            </div>
            <div class="product-shelf-container">
                <div class="product-shelf-scroll" data-shelf="trending">
                    <?php if (empty($trendingProducts)): ?>
                        <!-- Mock trending products -->
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <article class="product-card-shelf">
                            <div class="media-wrapper">
                                <img src="/images/products/trending-<?= $i ?>.jpg" alt="Trending Product <?= $i ?>">
                                <button type="button" class="wishlist-btn" aria-label="Add to wishlist">♡</button>
                                <div class="trending-badge">🔥</div>
                            </div>
                            <div class="price-row">
                                <span class="main-price">$<?= 19.99 + ($i * 10) ?></span>
                            </div>
                            <h3 class="product-title-shelf">Trending Item <?= $i ?></h3>
                            <div class="pill-btn-row">
                                <button class="pill-btn" type="button"><span class="add-icon">＋</span>Add</button>
                                <button class="pill-btn secondary" type="button">Options</button>
                            </div>
                        </article>
                        <?php endfor; ?>
                    <?php else: ?>
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
                    <?php endif; ?>
                </div>
                <button class="shelf-nav-btn" data-shelf-arrow="trending" aria-label="Next products">›</button>
            </div>
        </div>
    </section>
    <?php
}

function renderBrandsSection($bannersByPosition) {
    ?>
    <!-- Section 5: Top Brands -->
    <section class="brands-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-crown"></i>
                    Top Brands
                </h2>
                <p class="section-subtitle">Shop from your favorite brands</p>
            </div>
            <div class="brands-grid">
                <?php
                $brandData = [
                    ['name' => 'Apple', 'logo' => '/images/brands/apple.png', 'link' => '/brands/apple.php'],
                    ['name' => 'Samsung', 'logo' => '/images/brands/samsung.png', 'link' => '/brands/samsung.php'],
                    ['name' => 'Nike', 'logo' => '/images/brands/nike.png', 'link' => '/brands/nike.php'],
                    ['name' => 'Adidas', 'logo' => '/images/brands/adidas.png', 'link' => '/brands/adidas.php'],
                    ['name' => 'Sony', 'logo' => '/images/brands/sony.png', 'link' => '/brands/sony.php'],
                    ['name' => 'Microsoft', 'logo' => '/images/brands/microsoft.png', 'link' => '/brands/microsoft.php']
                ];
                
                foreach ($brandData as $brand):
                ?>
                <div class="brand-card" onclick="window.location.href='<?= h($brand['link']) ?>'">
                    <div class="brand-logo">
                        <img src="<?= h($brand['logo']) ?>" alt="<?= h($brand['name']) ?>" loading="lazy">
                    </div>
                    <h3 class="brand-name"><?= h($brand['name']) ?></h3>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

function renderFeaturedSection($featuredProducts) {
    ?>
    <!-- Section 6: Featured Products -->
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
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="product-card modern">
                        <div class="product-image">
                            <img src="/images/products/featured-<?= $i ?>.jpg" alt="Featured Product <?= $i ?>" loading="lazy">
                            <div class="product-badges">
                                <span class="badge <?= $i % 2 ? 'trending' : 'new' ?>"><?= $i % 2 ? 'Trending' : 'New' ?></span>
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
                            <h3 class="product-title">Featured Product <?= $i ?></h3>
                            <div class="product-rating">
                                <div class="stars">
                                    <?php for ($j = 0; $j < 5; $j++): ?>
                                    <i class="fas fa-star<?= $j >= 4 ? '-half-alt' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-count">(<?= rand(50, 500) ?>)</span>
                            </div>
                            <div class="product-price">
                                <span class="current-price">$<?= number_format(rand(20, 200), 2) ?></span>
                                <?php if ($i % 2): ?>
                                <span class="original-price">$<?= number_format(rand(250, 300), 2) ?></span>
                                <span class="discount">-<?= rand(20, 40) ?>%</span>
                                <?php endif; ?>
                            </div>
                            <button class="add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function renderNewArrivalsSection($newArrivals) {
    ?>
    <!-- Section 7: New Arrivals -->
    <section class="product-shelf-section" aria-label="New Arrivals">
        <div class="container">
            <div class="product-shelf-header">
                <h2>New Arrivals</h2>
                <a href="/products.php?filter=new">View all</a>
            </div>
            <div class="product-shelf-container">
                <div class="product-shelf-scroll" data-shelf="arrivals">
                    <?php if (empty($newArrivals)): ?>
                        <!-- Mock new arrivals -->
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <article class="product-card-shelf">
                            <div class="media-wrapper">
                                <img src="/images/products/new-<?= $i ?>.jpg" alt="New Arrival <?= $i ?>">
                                <button type="button" class="wishlist-btn" aria-label="Add to wishlist">♡</button>
                                <div class="new-badge">New</div>
                            </div>
                            <div class="price-row">
                                <span class="main-price">$<?= number_format(rand(15, 150), 2) ?></span>
                            </div>
                            <h3 class="product-title-shelf">New Arrival <?= $i ?></h3>
                            <div class="pill-btn-row">
                                <button class="pill-btn" type="button"><span class="add-icon">＋</span>Add</button>
                                <button class="pill-btn secondary" type="button">Options</button>
                            </div>
                        </article>
                        <?php endfor; ?>
                    <?php else: ?>
                        <?php foreach ($newArrivals as $prod): ?>
                        <article class="product-card-shelf">
                            <div class="media-wrapper">
                                <img src="<?= h(safeProductImg($prod)) ?>" alt="<?= h($prod['name']) ?>">
                                <button type="button" class="wishlist-btn" aria-label="Add to wishlist" data-id="<?= h($prod['id']) ?>">♡</button>
                                <div class="new-badge">New</div>
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
                    <?php endif; ?>
                </div>
                <button class="shelf-nav-btn" data-shelf-arrow="arrivals" aria-label="Next products">›</button>
            </div>
        </div>
    </section>
    <?php
}

function renderRecommendationsSection($bannersByPosition) {
    ?>
    <!-- Section 8: Personalized Recommendations -->
    <section class="recommendations-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-magic"></i>
                    Recommended for You
                </h2>
                <p class="section-subtitle">Based on your browsing history and preferences</p>
            </div>
            
            <!-- Personalized CTA or Product Grid -->
            <?php if (!class_exists('Session') || !Session::isLoggedIn()): ?>
            <div class="personalized-cta">
                <div class="cta-content">
                    <div class="cta-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="cta-text">
                        <h3 class="cta-title">Get personalized recommendations</h3>
                        <p class="cta-subtitle">Sign in to discover products tailored just for you</p>
                    </div>
                    <div class="cta-actions">
                        <a href="/login.php" class="btn-cta primary">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In
                        </a>
                        <a href="/register.php" class="btn-cta secondary">
                            <i class="fas fa-user-plus"></i>
                            Create Account
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="recommendations-grid">
                <!-- Mock personalized recommendations for logged-in users -->
                <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="recommendation-card">
                    <div class="rec-image">
                        <img src="/images/products/rec-<?= $i ?>.jpg" alt="Recommended Product <?= $i ?>" loading="lazy">
                        <div class="rec-badge">For You</div>
                    </div>
                    <div class="rec-content">
                        <h3>Recommended Item <?= $i ?></h3>
                        <p class="rec-reason">Based on your recent purchases</p>
                        <div class="rec-price">$<?= number_format(rand(25, 125), 2) ?></div>
                        <button class="rec-btn">Add to Cart</button>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}
?>

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