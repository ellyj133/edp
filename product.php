<?php
/**
 * Product Detail Page (Consistent Header/Footer Version)
 * FezaMarket E?Commerce
 *
 * This version intentionally omits any custom header/footer markup.
 * It depends on existing global layout includes to keep branding consistent.
 */

require_once __DIR__ . '/includes/init.php';

// Handle both ID and slug parameters from router
$productId = null;
$productSlug = null;

// Check for route parameters first (from router)
if (isset($_GET['route_params']) && !empty($_GET['route_params'][0])) {
    $param = $_GET['route_params'][0];
    // If parameter is numeric, treat as ID; otherwise as slug
    if (is_numeric($param)) {
        $productId = (int)$param;
    } else {
        $productSlug = $param;
    }
}

// Fallback to direct GET parameters for backwards compatibility
if (!$productId && !$productSlug) {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $productId = (int)$_GET['id'];
    } elseif (isset($_GET['slug'])) {
        $productSlug = $_GET['slug'];
    }
}

// If no valid parameter, redirect
if (!$productId && !$productSlug) {
    header('Location: /products.php');
    exit;
}

$productModel        = new Product();
$recommendationModel = new Recommendation();

// Find product by ID or slug
$productData = null;
if ($productId) {
    $productData = $productModel->findWithVendor($productId);
} elseif ($productSlug) {
    $productData = $productModel->findBySlug($productSlug);
    if ($productData) {
        $productId = $productData['id']; // Set productId for subsequent calls
    }
}

if (!$productData) {
    header('Location: /products.php');
    exit;
}

// Images
$images = $productModel->getImages($productId);
$primaryImage = null;
foreach ($images as $img) {
    if (!empty($img['is_primary'])) {
        $primaryImage = $img['image_url'] ?? null;
        break;
    }
}
if (!$primaryImage && !empty($images[0]['image_url'])) {
    $primaryImage = $images[0]['image_url'];
}
// Use safe image URL function for better fallback handling
$primaryImage = getSafeProductImageUrl($productData, $primaryImage);

// Reviews & rating
$reviews = $productModel->getReviews($productId, 8);
$rating  = $productModel->getAverageRating($productId);
$avgRating   = isset($rating['avg_rating']) ? round($rating['avg_rating'], 1) : 0;
$reviewCount = (int)($rating['review_count'] ?? 0);

// Related & viewed together
$relatedProducts = [];
if (!empty($productData['category_id'])) {
    $relatedProducts = $productModel->findByCategory($productData['category_id'], 8);
}
$viewedTogether = [];
try {
    $viewedTogether = $recommendationModel->getViewedTogether($productId, 8);
} catch (Throwable $e) {
    $viewedTogether = [];
}

// Badges (example logic only)
$badges = [];
if (!empty($productData['featured']))   $badges[] = ['label' => 'Featured', 'class' => 'badge-featured'];
if (!empty($productData['bestseller'])) $badges[] = ['label' => 'Bestseller', 'class' => 'badge-bestseller'];
if (!empty($productData['status']) && $productData['status'] === 'active' && empty($productData['stock_quantity'])) {
    $badges[] = ['label' => 'Out of stock', 'class' => 'badge-out'];
}

// Pricing
$price        = (float)($productData['price'] ?? 0);
$comparePrice = isset($productData['compare_price']) ? (float)$productData['compare_price'] : null;
$hasDiscount  = $comparePrice && $comparePrice > $price;
$youSave      = $hasDiscount ? ($comparePrice - $price) : 0;
$savePercent  = ($hasDiscount && $comparePrice > 0) ? round(($youSave / $comparePrice) * 100) : 0;

// Specifications parsing
$specItems = [];
$rawSpecs  = $productData['specifications'] ?? '';
if ($rawSpecs) {
    $decoded = json_decode($rawSpecs, true);
    if (is_array($decoded)) {
        foreach ($decoded as $k => $v) {
            $specItems[] = [
                'label' => (string)$k,
                'value' => is_scalar($v) ? (string)$v : json_encode($v)
            ];
        }
    } else {
        $lines = preg_split('/\r?\n/', $rawSpecs);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;
            if (strpos($line, ':') !== false) {
                [$l, $val] = explode(':', $line, 2);
                $specItems[] = ['label' => trim($l), 'value' => trim($val)];
            } else {
                $specItems[] = ['label' => $line, 'value' => ''];
            }
        }
    }
}

// About bullets placeholder
$aboutBullets = [
    'Quality build & design for daily use.',
    'Optimized performance with reliable parts.',
    'User?friendly controls & intuitive layout.',
    'Designed for durability and consistent output.'
];

// Breadcrumbs
$breadcrumbs = [
    ['label' => 'Home', 'url' => '/'],
    ['label' => 'Products', 'url' => '/products.php'],
    ['label' => $productData['name'] ?? 'Product', 'url' => null],
];

// Helpers
if (!function_exists('formatPrice')) {
    function formatPrice($v) { return number_format((float)$v, 2); }
}
function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$pageTitle       = $productData['name'] ?? 'Product';
$metaDescription = $productData['short_description'] ?? (mb_substr(strip_tags($productData['description'] ?? ''), 0, 155));

// HEADER INCLUDE (no custom header markup is injected here)
if (function_exists('includeHeader')) {
    includeHeader($pageTitle);
} else {
    // If header helper does not exist, at least set a minimal <title> (no custom layout)
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>" . h($pageTitle) . "</title></head><body>";
}

?>
<style>
    @import url('https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css');
    @import url('https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lg-zoom.min.css');
    @import url('https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lg-thumbnail.min.css');
</style>
<style>
/* eBay-style Product Page Layout */
:root {
    --ebay-primary: #0654ba;
    --ebay-secondary: #3665f3;
    --ebay-text: #191919;
    --ebay-text-secondary: #707070;
    --ebay-border: #e5e5e5;
    --ebay-bg: #ffffff;
    --ebay-success: #118a00;
    --ebay-warning: #f5af02;
    --ebay-danger: #e53238;
}

.product-container { 
    max-width: 1200px; 
    margin: 0 auto; 
    padding: 20px; 
    background: var(--ebay-bg);
}

.breadcrumbs { 
    font-size: 13px; 
    color: var(--ebay-text-secondary); 
    margin: 10px 0 20px; 
    display: flex;
    align-items: center;
    gap: 5px;
}
.breadcrumbs a { 
    color: var(--ebay-primary); 
    text-decoration: none; 
}
.breadcrumbs a:hover { 
    text-decoration: underline; 
}

/* Main Product Layout - eBay Style */
.ebay-product-layout {
    display: grid;
    grid-template-columns: 400px 1fr 320px;
    gap: 24px;
    align-items: start;
    margin-top: 20px;
}

@media (max-width: 1024px) {
    .ebay-product-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

/* Left Column - Image Gallery */
.ebay-image-gallery {
    display: flex;
    flex-direction: column;
}

.ebay-main-image {
    position: relative;
    background: #fff;
    border: 1px solid var(--ebay-border);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 12px;
    text-align: center;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ebay-main-image img {
    max-width: 100%;
    max-height: 360px;
    object-fit: contain;
    cursor: zoom-in;
}

.ebay-thumbnail-strip {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 4px 0;
}

.ebay-thumbnail {
    flex: 0 0 60px;
    width: 60px;
    height: 60px;
    border: 2px solid transparent;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.2s;
}

.ebay-thumbnail:hover,
.ebay-thumbnail.active {
    border-color: var(--ebay-primary);
}

.ebay-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Center Column - Product Info */
.ebay-product-info {
    padding: 0 12px;
}

.ebay-product-title {
    font-size: 24px;
    font-weight: 400;
    line-height: 1.3;
    color: var(--ebay-text);
    margin: 0 0 16px 0;
}

.ebay-seller-info {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    font-size: 14px;
}

.ebay-seller-link {
    color: var(--ebay-primary);
    text-decoration: none;
    font-weight: 500;
}

.ebay-seller-link:hover {
    text-decoration: underline;
}

.ebay-seller-rating {
    color: var(--ebay-text-secondary);
}

.ebay-condition {
    margin-bottom: 20px;
}

.ebay-condition-label {
    font-size: 14px;
    color: var(--ebay-text-secondary);
    margin-bottom: 4px;
}

.ebay-condition-value {
    font-size: 16px;
    font-weight: 500;
    color: var(--ebay-text);
}

.ebay-price-section {
    margin-bottom: 24px;
    padding: 16px 0;
    border-top: 1px solid var(--ebay-border);
    border-bottom: 1px solid var(--ebay-border);
}

.ebay-current-price {
    font-size: 28px;
    font-weight: 700;
    color: var(--ebay-text);
    margin-bottom: 8px;
}

.ebay-original-price {
    font-size: 14px;
    color: var(--ebay-text-secondary);
    text-decoration: line-through;
    margin-right: 8px;
}

.ebay-discount {
    font-size: 14px;
    color: var(--ebay-success);
    font-weight: 500;
}

.ebay-shipping-info {
    font-size: 14px;
    color: var(--ebay-text-secondary);
    margin-top: 8px;
}

.ebay-shipping-info .highlight {
    color: var(--ebay-success);
    font-weight: 500;
}

.ebay-location-info {
    font-size: 14px;
    color: var(--ebay-text-secondary);
    margin-top: 8px;
}

/* Right Column - Purchase Options */
.ebay-purchase-panel {
    border: 1px solid var(--ebay-border);
    border-radius: 8px;
    padding: 20px;
    background: #fff;
    position: sticky;
    top: 20px;
}

.ebay-buy-box {
    margin-bottom: 20px;
}

.ebay-buy-now-btn {
    background: var(--ebay-secondary);
    color: white;
    border: none;
    padding: 14px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 24px;
    width: 100%;
    cursor: pointer;
    margin-bottom: 12px;
    transition: background-color 0.2s;
}

.ebay-buy-now-btn:hover {
    background: #2851e6;
}

.ebay-add-cart-btn {
    background: #fff;
    color: var(--ebay-primary);
    border: 1px solid var(--ebay-primary);
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 500;
    border-radius: 24px;
    width: 100%;
    cursor: pointer;
    margin-bottom: 12px;
    transition: all 0.2s;
}

.ebay-add-cart-btn:hover {
    background: var(--ebay-primary);
    color: white;
}

.ebay-watchlist-btn {
    background: #fff;
    color: var(--ebay-text);
    border: 1px solid var(--ebay-border);
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 24px;
    width: 100%;
    cursor: pointer;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}

.ebay-watchlist-btn:hover {
    background: #f7f7f7;
}

.ebay-quantity-selector {
    margin-bottom: 16px;
}

.ebay-quantity-label {
    font-size: 14px;
    color: var(--ebay-text);
    margin-bottom: 8px;
    display: block;
}

.ebay-quantity-input {
    border: 1px solid var(--ebay-border);
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 14px;
    width: 80px;
}

.ebay-availability {
    margin-bottom: 20px;
    padding: 16px 0;
    border-top: 1px solid var(--ebay-border);
}

.ebay-stock-status {
    font-size: 14px;
    color: var(--ebay-success);
    font-weight: 500;
    margin-bottom: 8px;
}

.ebay-delivery-info {
    font-size: 14px;
    color: var(--ebay-text-secondary);
}

.ebay-return-policy {
    font-size: 14px;
    color: var(--ebay-text-secondary);
    border-top: 1px solid var(--ebay-border);
    padding-top: 16px;
    margin-top: 16px;
}

.ebay-payments {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--ebay-border);
}

.ebay-payment-methods {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.ebay-payment-icon {
    width: 32px;
    height: 20px;
    background: #f0f0f0;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #666;
}
/* Hide the main media container used by lightGallery */
#lightGallery-container { display: none; }
</style>

<div class="product-container" id="productContent">

    <div class="breadcrumbs" aria-label="Breadcrumb">
        <?php foreach ($breadcrumbs as $i => $bc): ?>
            <?php if ($bc['url']): ?>
                <a href="<?= $bc['url']; ?>"><?= h($bc['label']); ?></a><?= $i < count($breadcrumbs)-1 ? ' / ' : ''; ?>
            <?php else: ?>
                <span><?= h($bc['label']); ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="product-grid">
        <!-- Media -->
        <aside class="media-column" aria-label="Product media gallery">
            <div class="thumb-rail" role="list">
                <?php foreach ($images as $idx => $img):
                    $imgUrl  = getProductImageUrl($img['image_url'] ?? '');
                ?>
                <button class="thumb"
                        role="listitem"
                        aria-label="Thumbnail <?= $idx+1; ?>"
                        onclick="openGallery(<?= $idx; ?>)">
                    <img src="<?= $imgUrl; ?>" alt="<?= h($img['alt_text'] ?? $productData['name'] ?? ''); ?>">
                </button>
                <?php endforeach; ?>
                 <?php if (empty($images)): ?>
                    <div class="thumb is-active">
                        <img src="<?= getProductImageUrl($primaryImage); ?>" alt="<?= h($productData['name']); ?>">
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <div class="main-media" id="mainMedia" aria-live="polite" onclick="openGallery(0)">
                    <img id="mainProductImage"
                         src="<?= getProductImageUrl($primaryImage); ?>"
                         alt="<?= h($productData['name']); ?>">
                </div>
                <div class="media-tool-row">
                    <button class="pill-btn" type="button" onclick="openGallery(0)">? Expand</button>
                </div>
            </div>
        </aside>
        
        <div id="lightGallery-container">
            <?php foreach ($images as $img): ?>
                <a href="<?= getProductImageUrl($img['image_url'] ?? ''); ?>" data-sub-html="<?= h($img['alt_text'] ?? $productData['name']); ?>">
                    <img src="<?= getProductImageUrl($img['image_url'] ?? ''); ?>" />
                </a>
            <?php endforeach; ?>
        </div>


        <!-- Info -->
        <section class="info-column" aria-label="Product information">
            <div class="badge-row" aria-label="Highlights">
                <?php foreach ($badges as $b): ?>
                    <span class="badge <?= h($b['class']); ?>"><?= h($b['label']); ?></span>
                <?php endforeach; ?>
            </div>

            <h1><?= h($productData['name']); ?></h1>

            <div class="rating-row">
                <div class="stars" aria-label="Average rating <?= $avgRating; ?> out of 5">
                    <?php $starInt = (int)round($avgRating);
                    for ($i=1;$i<=5;$i++) echo $i <= $starInt ? '&#9733;':'&#9734;'; ?>
                </div>
                <div><?= $avgRating; ?> (<?= $reviewCount; ?>)</div>
                <?php if ($reviewCount > 0): ?>
                    <a href="#reviews" aria-label="Jump to reviews">See reviews</a>
                <?php endif; ?>
            </div>

            <!-- Variants (placeholders) -->
            <div class="variant-section" aria-label="Product variants">
                <div class="variant-group" data-variant="color">
                    <div class="variant-label">Color</div>
                    <div class="swatch-row" id="colorOptions">
                        <button type="button" class="swatch active" data-value="Default">Default</button>
                        <button type="button" class="swatch" data-value="Option 2">Option 2</button>
                        <button type="button" class="swatch" data-value="Option 3">Option 3</button>
                    </div>
                </div>
                <div class="variant-group" data-variant="size">
                    <div class="variant-label">Size</div>
                    <div class="option-row" id="sizeOptions">
                        <button type="button" class="swatch active" data-value="Standard">Standard</button>
                        <button type="button" class="swatch" data-value="Large">Large</button>
                    </div>
                </div>
            </div>

            <!-- About -->
            <article class="content-card" aria-label="About this item">
                <h3>About this item</h3>
                <ul class="about-bullets">
                    <?php foreach ($aboutBullets as $bullet): ?>
                        <li><?= h($bullet); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button class="show-more" type="button" onclick="toggleLongDescription()">View full item details</button>
            </article>

            <!-- Specs -->
            <?php if (!empty($specItems)): ?>
            <section class="content-card" aria-label="Specifications at a glance">
                <h3>Specifications at a glance</h3>
                <div class="spec-grid">
                    <?php foreach ($specItems as $spec): ?>
                        <div class="spec-item">
                            <span class="label"><?= h($spec['label']); ?></span>
                            <span class="value"><?= h($spec['value']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Long description (collapsed) -->
            <section class="content-card" id="fullDescription" aria-label="Full description" hidden>
                <h3>Full description</h3>
                <div style="font-size:14px;line-height:1.55;">
                    <?= nl2br(h($productData['description'] ?? 'No additional description.')); ?>
                </div>
            </section>
        </section>

        <!-- Purchase -->
        <aside class="purchase-col" aria-label="Purchase options">
            <div class="purchase-card" role="complementary">
                <div>
                    <span class="price-main">$<?= formatPrice($price); ?></span>
                    <?php if ($hasDiscount): ?>
                        <span class="compare-price">$<?= formatPrice($comparePrice); ?></span>
                        <span class="save-chip">-<?= $savePercent; ?>%</span>
                        <div class="you-save">You save $<?= formatPrice($youSave); ?></div>
                    <?php endif; ?>
                </div>

                <div class="stock-msg">
                    <?php if (!empty($productData['stock_quantity'])): ?>
                        In stock  Ships soon
                    <?php else: ?>
                        <span class="oos">Currently unavailable</span>
                    <?php endif; ?>
                </div>

                <form class="add-cart" action="/cart.php" method="post">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$productId; ?>">
                    <?= function_exists('csrfTokenInput') ? csrfTokenInput() : ''; ?>
                    <div class="qty-row">
                        <label class="sr-only" for="qtySelect">Quantity</label>
                        <select id="qtySelect" name="quantity" aria-label="Quantity">
                            <?php for ($q=1;$q<=10;$q++): ?>
                                <option value="<?= $q; ?>"><?= $q; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit"
                            class="btn-primary"
                            <?= empty($productData['stock_quantity']) ? 'disabled' : ''; ?>>
                        <?= empty($productData['stock_quantity']) ? 'Out of Stock' : 'Add to Cart'; ?>
                    </button>
                </form>

                <form method="post" action="/wishlist/toggle.php" style="margin:0 0 14px;">
                    <input type="hidden" name="product_id" value="<?= (int)$productId; ?>">
                    <button type="submit" class="btn-secondary">Add to Wishlist</button>
                </form>

                <div class="action-links">
                    <button class="mini-link" type="button">? Share</button>
                    <button class="mini-link" type="button">? Ask</button>
                    <button class="mini-link" type="button">? Report</button>
                </div>

                <div class="shipping-methods">
                    <div style="font-weight:600;font-size:13px;margin-bottom:10px;">How you'll get this item:</div>
                    <div class="ship-options">
                        <div class="ship-option<?= empty($productData['stock_quantity']) ? ' disabled':''; ?>">
                            <strong>Shipping</strong>
                            <?= empty($productData['stock_quantity']) ? 'Not available' : 'Standard'; ?>
                        </div>
                        <div class="ship-option disabled">
                            <strong>Pickup</strong> Not available
                        </div>
                        <div class="ship-option disabled">
                            <strong>Delivery</strong> Not available
                        </div>
                    </div>
                </div>

                <div class="trust-row">
                    <div class="trust-item">? Secure transaction</div>
                    <div class="trust-item">? 30?day returns policy</div>
                    <div class="trust-item">? Fast order processing</div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Viewed Together -->
    <?php if (!empty($viewedTogether)): ?>
    <div class="section-block" id="viewedTogether">
        <h2>Customers also viewed</h2>
        <div class="carousel-grid">
            <?php foreach ($viewedTogether as $rec):
                $recImg = getProductImageUrl($rec['image_url'] ?? '');
            ?>
            <a class="card-mini" href="/product.php?id=<?= (int)$rec['id']; ?>" aria-label="View <?= h($rec['name']); ?>">
                <img src="<?= $recImg; ?>" alt="<?= h($rec['name']); ?>">
                <div class="name"><?= h($rec['name']); ?></div>
                <div class="price">$<?= formatPrice($rec['price'] ?? 0); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Related -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="section-block">
        <h2>Similar sponsored items</h2>
        <div class="carousel-grid">
            <?php foreach ($relatedProducts as $rel):
                $relImg = getProductImageUrl($rel['image_url'] ?? '');
            ?>
            <a class="card-mini" href="/product.php?id=<?= (int)$rel['id']; ?>" aria-label="View <?= h($rel['name']); ?>">
                <img src="<?= $relImg; ?>" alt="<?= h($rel['name']); ?>">
                <div class="name"><?= h($rel['name']); ?></div>
                <div class="price">$<?= formatPrice($rel['price'] ?? 0); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reviews -->
    <div class="reviews-block" id="reviews" aria-label="Customer reviews">
        <h2>Customer Reviews (<?= $reviewCount; ?>)</h2>
        <?php if (empty($reviews)): ?>
            <p style="font-size:14px;color:#6b7280;">No reviews yet.</p>
        <?php else: ?>
            <?php foreach ($reviews as $rev):
                $revName  = trim(($rev['first_name'] ?? '') . ' ' . ($rev['last_name'] ?? ''));
                $revStars = (int)($rev['rating'] ?? 0);
            ?>
            <div class="review-item">
                <div class="review-meta">
                    <strong><?= h($revName ?: 'User'); ?></strong>
                    <span class="review-stars" aria-label="Rating <?= $revStars; ?> out of 5">
                        <?php for ($i=1;$i<=5;$i++) echo $i <= $revStars ? '&#9733;':'&#9734;'; ?>
                    </span>
                    <time datetime="<?= h($rev['created_at']); ?>"><?= h(date('M j, Y', strtotime($rev['created_at']))); ?></time>
                </div>
                <?php if (!empty($rev['title'])): ?>
                <div style="font-weight:600; margin-bottom:4px;"><?= h($rev['title']); ?></div>
                <?php endif; ?>
                <div><?= nl2br(h($rev['comment'] ?? '')); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/lightgallery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/zoom/lg-zoom.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/plugins/thumbnail/lg-thumbnail.min.js"></script>

<script>
let galleryInstance = null;
const galleryContainer = document.getElementById('lightGallery-container');

if (galleryContainer) {
    galleryInstance = lightGallery(galleryContainer, {
        plugins: [lgZoom, lgThumbnail],
        speed: 500,
        download: false,
    });
}

function openGallery(index) {
    if (galleryInstance) {
        galleryInstance.openGallery(index);
    }
}

function swapMainImage(url, btn) {
    const main = document.getElementById('mainProductImage');
    if (main) main.src = url;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('is-active'));
    if (btn) btn.classList.add('is-active');
}
function toggleLongDescription() {
    const desc = document.getElementById('fullDescription');
    if (!desc) return;
    if (desc.hasAttribute('hidden')) desc.removeAttribute('hidden'); else desc.setAttribute('hidden','');
}

// eBay-style product page functions
function changeMainImage(imageUrl, index) {
    const mainImg = document.getElementById('mainProductImage');
    if (mainImg) {
        mainImg.src = imageUrl;
    }
    
    // Update active thumbnail
    document.querySelectorAll('.ebay-thumbnail').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
}

function buyNow() {
    const quantity = document.getElementById('quantity').value;
    // Add buy now functionality
    alert(`Buy Now: ${quantity} item(s)`);
    // TODO: Implement actual buy now functionality
}

function addToCart() {
    const quantity = document.getElementById('quantity').value;
    const productId = <?= (int)$productId; ?>;
    
    fetch('/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Item added to cart!');
        } else {
            alert('Error adding item to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding item to cart');
    });
}

function makeOffer() {
    // Add make offer functionality
    const currentPrice = <?= $price; ?>;
    const offer = prompt(`Make an offer (Current price: $${currentPrice.toFixed(2)}):`);
    if (offer && !isNaN(offer) && parseFloat(offer) > 0) {
        alert(`Offer of $${parseFloat(offer).toFixed(2)} submitted!`);
        // TODO: Implement actual offer functionality
    }
}

function addToWatchlist() {
    const productId = <?= (int)$productId; ?>;
    
    fetch('/wishlist/toggle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Added to watchlist!');
        } else {
            alert('Error adding to watchlist');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding to watchlist');
    });
}

document.querySelectorAll('.swatch-row .swatch, .option-row .swatch').forEach(swatch => {
    swatch.addEventListener('click', () => {
        const group = swatch.parentElement;
        group.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
        swatch.classList.add('active');
    });
});
</script>

<?php
// FOOTER INCLUDE (no custom footer markup)
if (function_exists('includeFooter')) {
    includeFooter();
} else {
    echo "</body></html>";
}