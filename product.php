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
/* Page-scoped styles only (no global header/footer overrides) */
:root {
    --c-border:#e5e7eb;
    --c-border-strong:#d1d5db;
    --c-primary:#1d4ed8;
    --c-primary-hover:#1e40af;
    --c-text:#111827;
    --c-text-muted:#6b7280;
    --c-danger:#dc2626;
    --c-success:#059669;
    --c-surface:#f9fafb;
    --radius:10px;
    --focus-ring:0 0 0 3px rgba(29,78,216,.35);
}
.product-container { max-width:1420px; margin:0 auto; padding:0 28px 70px; }
.breadcrumbs { font-size:13px; color:var(--c-text-muted); margin:22px 0 14px; }
.breadcrumbs a { color:var(--c-text-muted); text-decoration:none; }
.breadcrumbs a:hover { color:var(--c-primary); text-decoration:underline; }

.product-grid {
    display:grid;
    grid-template-columns:380px 1fr 380px;
    gap:34px;
    align-items:start;
}
@media (max-width:1240px){ .product-grid { grid-template-columns:340px 1fr 360px; } }
@media (max-width:1040px){ .product-grid { grid-template-columns:300px 1fr 340px; } }
@media (max-width:960px){ .product-grid { grid-template-columns:1fr; } }

.media-column { display:grid; grid-template-columns:88px 1fr; gap:18px; }
@media (max-width:640px){ .media-column { grid-template-columns:1fr; } .thumb-rail { order:2; display:flex;flex-wrap:wrap; } }
.thumb-rail { display:flex; flex-direction:column; gap:10px; }
.thumb {
    width:88px;height:88px; border:1px solid var(--c-border); border-radius:8px;
    overflow:hidden; background:#fff; cursor:pointer; display:grid; place-items:center;
    transition:.2s;
}
.thumb img { width:100%; height:100%; object-fit:cover; }
.thumb:hover, .thumb.is-active { border-color:var(--c-primary); box-shadow:0 0 0 2px rgba(29,78,216,.2); }
.main-media {
    background:#fff; border:1px solid var(--c-border); border-radius:16px;
    padding:24px; min-height:520px; display:flex; align-items:center; justify-content:center;
    cursor: zoom-in;
}
.main-media img { max-width:100%; max-height:460px; object-fit:contain; }
.media-tool-row { margin-top:14px; display:flex; gap:10px; }
.pill-btn { font-size:13px; padding:8px 14px; border:1px solid var(--c-border); background:#fff; border-radius:999px; cursor:pointer; }
.pill-btn:hover { border-color:var(--c-border-strong); }

.info-column h1 { font-size:26px; line-height:1.25; margin:0 0 12px; font-weight:600; }
.badge-row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
.badge { font-size:11px; font-weight:600; text-transform:uppercase; padding:4px 8px; border-radius:4px; }
.badge-featured { background:#1d4ed8; color:#fff; }
.badge-bestseller { background:#f59e0b; color:#111; }
.badge-out { background:#dc2626; color:#fff; }

.rating-row { display:flex; align-items:center; gap:8px; font-size:14px; margin:6px 0 18px; }
.stars { color:#fbbf24; letter-spacing:1px; }

.variant-section { margin:22px 0 28px; }
.variant-group { margin-bottom:18px; }
.variant-label { font-size:13px;font-weight:600;color:var(--c-text-muted); margin-bottom:8px; }
.swatch-row, .option-row { display:flex; gap:10px; flex-wrap:wrap; }
.swatch {
    min-width:62px; padding:10px 14px; background:#fff; border:1px solid var(--c-border);
    border-radius:8px; cursor:pointer; font-size:13px; text-align:center; transition:.2s;
}
.swatch:hover { border-color:var(--c-border-strong); }
.swatch.active { border-color:var(--c-primary); box-shadow:0 0 0 2px rgba(29,78,216,.25); }

.content-card {
    background:#fff; border:1px solid var(--c-border); border-radius:12px;
    padding:22px 26px; margin-bottom:28px;
}
.content-card h3 { margin:0 0 16px; font-size:16px; font-weight:600; }

.about-bullets { list-style:none; padding:0; margin:0 0 8px; }
.about-bullets li { position:relative; padding-left:20px; margin:8px 0; font-size:14px; line-height:1.5; }
.about-bullets li:before { content:''; position:absolute; left:0; color:var(--c-primary); font-weight:700; }
.show-more { background:none; border:none; color:var(--c-primary); font-size:13px; cursor:pointer; padding:0; }

.spec-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; }
.spec-item { background:#f9fafb; border:1px solid var(--c-border); border-radius:10px; padding:12px 14px; font-size:13px; line-height:1.25; }
.spec-item .label { font-weight:600; display:block; margin-bottom:4px; color:#374151; }
.spec-item .value { color:#111; font-weight:500; }

.purchase-col { position:relative; }
.purchase-card {
    position:sticky; top:90px; background:#fff; border:1px solid var(--c-border);
    border-radius:14px; padding:24px 24px 28px;
}
.price-main { font-size:30px; font-weight:700; color:var(--c-danger); }
.compare-price { font-size:14px; color:#6b7280; text-decoration:line-through; margin-left:10px; }
.save-chip { display:inline-block; background:#dc2626;color:#fff;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600;margin-left:8px; }
.you-save { font-size:13px; color:#059669; margin-top:4px; font-weight:600; }
.stock-msg { font-size:13px;color:#6b7280;margin:12px 0; }
.stock-msg .oos { color:#dc2626; font-weight:600; }

.add-cart .qty-row { display:flex; gap:10px; margin:0 0 14px; }
.add-cart select { padding:8px 10px; border:1px solid var(--c-border); border-radius:8px; font-size:14px; background:#fff; }
.btn-primary {
    background:#1d4ed8;color:#fff;border:none;width:100%;padding:14px 18px;
    font-size:15px;font-weight:600;border-radius:10px;cursor:pointer;transition:.2s;
}
.btn-primary:hover { background:#1e40af; }
.btn-primary:disabled { opacity:.55; cursor:not-allowed; }
.btn-secondary {
    background:#fff;border:1px solid var(--c-border);color:#111827;width:100%;
    padding:12px 16px;font-size:14px;font-weight:500;border-radius:10px;cursor:pointer;
}
.btn-secondary:hover { background:#f9fafb; }

.action-links { display:flex; gap:10px; margin-top:10px; flex-wrap:wrap; }
.mini-link { background:#f9fafb; border:1px solid var(--c-border); padding:6px 10px; font-size:12px; border-radius:8px; cursor:pointer; }
.mini-link:hover { background:#eef2ff; }

.shipping-methods { margin:18px 0 24px; border-top:1px solid var(--c-border); padding-top:18px; }
.ship-options { display:flex; gap:10px; flex-wrap:wrap; }
.ship-option {
    flex:1; min-width:95px; border:1px solid var(--c-border); background:#f9fafb; border-radius:10px;
    padding:10px 8px; font-size:12px; text-align:center; line-height:1.3;
}
.ship-option strong { display:block;font-size:12px;margin-bottom:4px; }
.ship-option.disabled { opacity:.45; }

.trust-row { margin-top:20px; border-top:1px solid var(--c-border); padding-top:16px; display:flex; flex-direction:column; gap:10px; font-size:12px; color:#6b7280; }
.trust-item { display:flex; gap:8px; align-items:flex-start; }

.section-block { margin:60px 0 20px; }
.section-block h2 { font-size:20px; margin:0 0 18px; font-weight:600; }
.carousel-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:18px; }
.card-mini { background:#fff; border:1px solid var(--c-border); border-radius:12px; padding:14px; text-align:center; display:flex; flex-direction:column; gap:10px; cursor:pointer; transition:.2s;}
.card-mini:hover { border-color:var(--c-border-strong); box-shadow:0 1px 4px rgba(0,0,0,.06); }
.card-mini img { width:100%; height:140px; object-fit:cover; border-radius:8px; background:#f3f4f6; }
.card-mini .name { font-size:13px; font-weight:500; line-height:1.3; height:34px; overflow:hidden; }
.card-mini .price { font-size:14px; font-weight:600; color:#dc2626; }

.reviews-block { background:#fff; border:1px solid var(--c-border); border-radius:12px; padding:26px 30px; margin:60px 0 10px; }
.reviews-block h2 { margin:0 0 18px;font-size:20px;font-weight:600; }
.review-item { border-top:1px solid var(--c-border); padding:16px 0; font-size:14px; line-height:1.5; }
.review-item:first-of-type { border-top:none; }
.review-meta { display:flex; gap:8px; align-items:center; font-size:13px; color:#6b7280; margin-bottom:4px; }
.review-stars { color:#fbbf24; font-size:13px; }

.badge-row .badge,
.swatch,
.thumb,
.button,
.btn-primary,
.btn-secondary,
.mini-link { outline:none; }
.badge-row .badge:focus,
.swatch:focus,
.thumb:focus,
.btn-primary:focus,
.btn-secondary:focus,
.mini-link:focus { box-shadow:var(--focus-ring); }

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