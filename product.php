<?php
/**
 * Product Detail Page - Production Ready
 * Feza Marketplace - Working with existing models
 */

require_once __DIR__ . '/includes/init.php';

// Check if user is logged in for certain actions
$isLoggedIn = Session::isLoggedIn();
$userId = $isLoggedIn ? Session::getUserId() : null;

// Get product ID from URL parameters
$productId = null;
$productSlug = null;

// Handle route parameters
if (isset($_GET['route_params']) && !empty($_GET['route_params'][0])) {
    $param = $_GET['route_params'][0];
    if (is_numeric($param)) {
        $productId = (int)$param;
    } else {
        $productSlug = $param;
    }
}

// Fallback to direct GET parameters
if (!$productId && !$productSlug) {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $productId = (int)$_GET['id'];
    } elseif (isset($_GET['slug'])) {
        $productSlug = $_GET['slug'];
    }
}

// Redirect if no valid parameter
if (!$productId && !$productSlug) {
    header('Location: /');
    exit;
}

// Initialize models
try {
    $productModel = new Product();
    $cartModel = new Cart();
    $wishlistModel = new Wishlist();
    $reviewModel = new Review();
    $categoryModel = new Category();
} catch (Exception $e) {
    error_log("Model initialization failed: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo "Internal Server Error";
    exit;
}

// Find product
$product = null;
try {
    if ($productId) {
        $product = $productModel->findWithVendor($productId);
    } elseif ($productSlug) {
        $product = $productModel->findBySlug($productSlug);
        if ($product) {
            $productId = $product['id'];
        }
    }
} catch (Exception $e) {
    error_log("Product fetch failed: " . $e->getMessage());
}

if (!$product) {
    header('HTTP/1.1 404 Not Found');
    echo "Product not found";
    exit;
}

// Get additional product data
$images = [];
$reviews = [];
$avgRating = 0;
$reviewCount = 0;
$relatedProducts = [];
$isWishlisted = false;
$category = null;

try {
    // Product images
    $images = $productModel->getImages($productId);
    
    // Reviews and ratings
    $reviews = $reviewModel->getProductReviews($productId, 10);
    $ratingStats = $reviewModel->getProductRatingStats($productId);
    $avgRating = $ratingStats['average_rating'] ?? 0;
    $reviewCount = (int)($ratingStats['total_reviews'] ?? 0);
    
    // Related products
    if ($product['category_id']) {
        $relatedProducts = $productModel->findByCategory($product['category_id'], 8);
        // Remove current product from related products
        $relatedProducts = array_filter($relatedProducts, function($p) use ($productId) {
            return $p['id'] != $productId;
        });
    }
    
    // Check if in user's wishlist
    if ($userId) {
        $isWishlisted = $wishlistModel->isInWishlist($userId, $productId);
    }
    
    // Get category
    if ($product['category_id']) {
        $category = $categoryModel->find($product['category_id']);
    }
    
} catch (Exception $e) {
    error_log("Additional data fetch failed: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit;
    }
    
    switch ($_POST['action']) {
        case 'add_to_cart':
            try {
                $quantity = (int)($_POST['quantity'] ?? 1);
                if ($quantity < 1) $quantity = 1;
                
                // Check stock
                if (isset($product['stock_quantity']) && $product['stock_quantity'] < $quantity) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                    exit;
                }
                
                $result = $cartModel->addItem($userId, $productId, $quantity);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add item to cart']);
                }
            } catch (Exception $e) {
                error_log("Add to cart error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
            }
            exit;
            
        case 'add_to_wishlist':
            try {
                if ($isWishlisted) {
                    $result = $wishlistModel->removeFromWishlist($userId, $productId);
                    $action = 'removed';
                    $message = 'Item removed from wishlist';
                } else {
                    $result = $wishlistModel->addToWishlist($userId, $productId);
                    $action = 'added';
                    $message = 'Item added to wishlist';
                }
                
                if ($result !== false) {
                    echo json_encode(['success' => true, 'message' => $message, 'action' => $action]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update wishlist']);
                }
            } catch (Exception $e) {
                error_log("Wishlist error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error updating wishlist']);
            }
            exit;
            
        case 'buy_now':
            try {
                $quantity = (int)($_POST['quantity'] ?? 1);
                if ($quantity < 1) $quantity = 1;
                
                // Check stock
                if (isset($product['stock_quantity']) && $product['stock_quantity'] < $quantity) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                    exit;
                }
                
                // Add to cart and redirect to checkout
                $cartModel->addItem($userId, $productId, $quantity);
                echo json_encode(['success' => true, 'redirect' => '/checkout.php']);
            } catch (Exception $e) {
                error_log("Buy now error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error processing purchase']);
            }
            exit;
            
        case 'make_offer':
            try {
                $offerAmount = (float)($_POST['offer_amount'] ?? 0);
                if ($offerAmount <= 0 || $offerAmount >= $product['price']) {
                    echo json_encode(['success' => false, 'message' => 'Invalid offer amount']);
                    exit;
                }
                
                // Use the new Offer model to create the offer
                $offerModel = new Offer();
                $message = $_POST['offer_message'] ?? null;
                $expiresAt = null; // Could be set to 7 days from now: date('Y-m-d H:i:s', strtotime('+7 days'))
                
                $result = $offerModel->createOffer($productId, $userId, $offerAmount, $message, $expiresAt);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Offer submitted successfully! We will contact you soon.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to submit offer. Please try again.']);
                }
            } catch (Exception $e) {
                error_log("Offer error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error submitting offer']);
            }
            exit;
    }
}

// Prepare data for display
$primaryImage = !empty($images) ? $images[0]['image_url'] : '';
$price = (float)($product['price'] ?? 0);
$comparePrice = isset($product['compare_price']) ? (float)$product['compare_price'] : null;
$hasDiscount = $comparePrice && $comparePrice > $price;
$discountPercent = $hasDiscount ? round((($comparePrice - $price) / $comparePrice) * 100) : 0;

// Build breadcrumbs
$breadcrumbs = [
    ['label' => 'Home', 'url' => '/'],
    ['label' => 'Products', 'url' => '/products.php'],
];
if ($category) {
    $breadcrumbs[] = ['label' => $category['name'], 'url' => '/category.php?id=' . $category['id']];
}
$breadcrumbs[] = ['label' => $product['name'], 'url' => null];

// Page meta
$pageTitle = $product['name'] . ' - Feza Marketplace';
$metaDescription = $product['short_description'] ?? substr(strip_tags($product['description'] ?? ''), 0, 160);

// Helper functions
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Include header
if (file_exists(__DIR__ . '/templates/header.php')) {
    include __DIR__ . '/templates/header.php';
} elseif (file_exists(__DIR__ . '/includes/header.php')) {
    include __DIR__ . '/includes/header.php';
} else {
    echo "<!DOCTYPE html><html><head><title>" . h($pageTitle) . "</title></head><body>";
}
?>

<style>
/* Same CSS styles as before */
:root {
    --primary-color: #0654ba;
    --secondary-color: #3665f3;
    --success-color: #118a00;
    --warning-color: #f5af02;
    --danger-color: #e53238;
    --border-color: #e5e5e5;
    --text-color: #191919;
    --text-secondary: #707070;
    --bg-color: #ffffff;
}

.product-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 20px 24px;
    background: var(--bg-color);
}

.breadcrumbs {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 10px 0 20px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.breadcrumbs a {
    color: var(--primary-color);
    text-decoration: none;
}

.breadcrumbs a:hover {
    text-decoration: underline;
}

.product-layout {
    display: grid;
    grid-template-columns: 400px 1fr 320px;
    gap: 24px;
    align-items: start;
    margin-top: 20px;
}

@media (max-width: 1024px) {
    .product-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

.image-gallery {
    display: flex;
    flex-direction: column;
}

.main-image {
    position: relative;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 12px;
    text-align: center;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.main-image img {
    max-width: 100%;
    max-height: 360px;
    object-fit: contain;
}

.thumbnail-strip {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 4px 0;
}

.thumbnail {
    flex: 0 0 60px;
    width: 60px;
    height: 60px;
    border: 2px solid transparent;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.2s;
}

.thumbnail:hover,
.thumbnail.active {
    border-color: var(--primary-color);
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    padding: 0 12px;
}

.product-title {
    font-size: 24px;
    font-weight: 400;
    line-height: 1.3;
    color: var(--text-color);
    margin: 0 0 16px 0;
}

.seller-info {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    font-size: 14px;
}

.condition-section {
    margin-bottom: 20px;
}

.condition-label {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.condition-value {
    font-size: 16px;
    font-weight: 500;
    color: var(--text-color);
}

.price-section {
    margin-bottom: 24px;
    padding: 16px 0;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
}

.current-price {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 8px;
}

.purchase-panel {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
    background: #fff;
    position: sticky;
    top: 20px;
}

.quantity-selector {
    margin-bottom: 16px;
}

.quantity-label {
    font-size: 14px;
    color: var(--text-color);
    margin-bottom: 8px;
    display: block;
}

.quantity-input {
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 14px;
    width: 80px;
}

.btn {
    border: none;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 24px;
    width: 100%;
    cursor: pointer;
    margin-bottom: 12px;
    transition: all 0.2s;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: var(--secondary-color);
    color: white;
}

.btn-primary:hover {
    background: #2851e6;
}

.btn-secondary {
    background: #fff;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.btn-secondary:hover {
    background: var(--primary-color);
    color: white;
}

.btn-outline {
    background: #fff;
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-outline:hover {
    background: #f7f7f7;
}

.stock-status {
    font-size: 14px;
    color: var(--success-color);
    font-weight: 500;
    margin-bottom: 16px;
}

.description-section {
    margin: 32px 0;
    padding: 20px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: #fff;
}

.description-section h2 {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 16px;
    color: var(--text-color);
}

.reviews-section {
    margin: 32px 0;
    padding: 20px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: #fff;
}

.review-item {
    padding: 16px 0;
    border-bottom: 1px solid var(--border-color);
}

.review-item:last-child {
    border-bottom: none;
}

.review-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    font-size: 14px;
}

.review-stars {
    color: #ffc107;
}

.related-products {
    margin: 32px 0;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.product-card {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 16px;
    text-align: center;
    background: #fff;
    transition: box-shadow 0.2s;
    text-decoration: none;
    color: inherit;
}

.product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.product-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 12px;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.error-message {
    color: var(--danger-color);
    font-size: 14px;
    margin-top: 8px;
}

.success-message {
    color: var(--success-color);
    font-size: 14px;
    margin-top: 8px;
}
</style>

<div class="product-container">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <?php foreach ($breadcrumbs as $i => $bc): ?>
            <?php if ($bc['url']): ?>
                <a href="<?= $bc['url']; ?>"><?= h($bc['label']); ?></a>
            <?php else: ?>
                <span><?= h($bc['label']); ?></span>
            <?php endif; ?>
            <?php if ($i < count($breadcrumbs) - 1): ?>
                <span> / </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Main Product Layout -->
    <div class="product-layout">
        
        <!-- Left Column - Images -->
        <div class="image-gallery">
            <div class="main-image">
                <img id="mainImage" src="<?= getProductImageUrl($primaryImage); ?>" alt="<?= h($product['name']); ?>">
            </div>
            
            <?php if (count($images) > 1): ?>
            <div class="thumbnail-strip">
                <?php foreach ($images as $idx => $img): ?>
                <div class="thumbnail <?= $idx === 0 ? 'active' : ''; ?>" 
                     onclick="changeMainImage('<?= getProductImageUrl($img['image_url']); ?>', <?= $idx; ?>)">
                    <img src="<?= getProductImageUrl($img['image_url']); ?>" alt="<?= h($img['alt_text'] ?? $product['name']); ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Center Column - Product Info -->
        <div class="product-info">
            <h1 class="product-title"><?= h($product['name']); ?></h1>
            
            <div class="seller-info">
                <span>Sold by </span>
                <span><?= h($product['vendor_name'] ?? 'Feza Marketplace'); ?></span>
            </div>
            
            <div class="condition-section">
                <div class="condition-label">Condition:</div>
                <div class="condition-value">New</div>
            </div>
            
            <div class="price-section">
                <div class="current-price">$<?= formatPrice($price); ?></div>
                <?php if ($hasDiscount): ?>
                <div class="price-details">
                    <span class="original-price">$<?= formatPrice($comparePrice); ?></span>
                    <span class="discount">Save <?= $discountPercent; ?>%</span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($product['description'])): ?>
            <div class="description-section">
                <h2>About this item</h2>
                <div class="item-description">
                    <?= nl2br(h($product['description'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Purchase Options -->
        <div class="purchase-panel">
            <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] > 0): ?>
            <div class="quantity-selector">
                <label class="quantity-label" for="quantity">Quantity:</label>
                <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?= min(10, $product['stock_quantity']); ?>">
            </div>
            
            <button class="btn btn-primary" onclick="buyNow()">Buy It Now</button>
            <button class="btn btn-secondary" onclick="addToCart()">Add to cart</button>
            <button class="btn btn-outline" onclick="showOfferModal()">Make offer</button>
            <button class="btn btn-outline" onclick="toggleWishlist()">
                <?= $isWishlisted ? '? Remove from Watchlist' : '? Add to Watchlist'; ?>
            </button>
            
            <div class="stock-status">
                <?= $product['stock_quantity']; ?> available
            </div>
            <?php else: ?>
            <div class="stock-status" style="color: var(--danger-color);">
                Currently unavailable
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <?php if (!empty($reviews)): ?>
    <div class="reviews-section">
        <h2>Customer Reviews (<?= $reviewCount; ?>)</h2>
        <?php foreach ($reviews as $review): ?>
        <div class="review-item">
            <div class="review-meta">
                <strong><?= h($review['reviewer_name'] ?? 'Anonymous'); ?></strong>
                <span class="review-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?= $i <= ($review['rating'] ?? 0) ? '?' : '?'; ?>
                    <?php endfor; ?>
                </span>
                <time><?= date('M j, Y', strtotime($review['created_at'])); ?></time>
            </div>
            <?php if (!empty($review['title'])): ?>
            <div style="font-weight: 600; margin-bottom: 4px;"><?= h($review['title']); ?></div>
            <?php endif; ?>
            <div><?= nl2br(h($review['review_text'] ?? '')); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="related-products">
        <h2>Similar items</h2>
        <div class="products-grid">
            <?php foreach (array_slice($relatedProducts, 0, 6) as $related): ?>
            <a href="/product/<?= $related['id']; ?>" class="product-card">
                <img src="<?= getProductImageUrl($related['image_url'] ?? ''); ?>" alt="<?= h($related['name']); ?>">
                <div><?= h($related['name']); ?></div>
                <div>$<?= formatPrice($related['price'] ?? 0); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Offer Modal -->
<div id="offerModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeOfferModal()">&times;</span>
        <h2>Make an Offer</h2>
        <form id="offerForm">
            <div style="margin-bottom: 16px;">
                <label for="offerAmount">Your offer:</label>
                <input type="number" id="offerAmount" step="0.01" min="0.01" max="<?= $price * 0.9; ?>" style="width: 100%; padding: 8px; margin-top: 4px;">
            </div>
            <div style="margin-bottom: 16px;">
                <label for="offerMessage">Message (optional):</label>
                <textarea id="offerMessage" style="width: 100%; height: 80px; padding: 8px; margin-top: 4px;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Offer</button>
        </form>
        <div id="offerError" class="error-message" style="display: none;"></div>
        <div id="offerSuccess" class="success-message" style="display: none;"></div>
    </div>
</div>

<script>
// Global variables
const productId = <?= $productId; ?>;
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false'; ?>;
let isWishlisted = <?= $isWishlisted ? 'true' : 'false'; ?>;

// Image gallery functions
function changeMainImage(imageUrl, index) {
    document.getElementById('mainImage').src = imageUrl;
    document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
}

// Cart functionality
async function addToCart() {
    if (!isLoggedIn) {
        alert('Please login to add items to cart');
        window.location.href = '/login.php';
        return;
    }
    
    const quantity = document.getElementById('quantity').value;
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add_to_cart&quantity=${quantity}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Item added to cart successfully!');
        } else {
            alert(data.message || 'Failed to add item to cart');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error adding item to cart');
    }
}

// Buy now functionality
async function buyNow() {
    if (!isLoggedIn) {
        alert('Please login to purchase');
        window.location.href = '/login.php';
        return;
    }
    
    const quantity = document.getElementById('quantity').value;
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=buy_now&quantity=${quantity}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            alert(data.message || 'Failed to process purchase');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error processing purchase');
    }
}

// Wishlist functionality
async function toggleWishlist() {
    if (!isLoggedIn) {
        alert('Please login to use watchlist');
        window.location.href = '/login.php';
        return;
    }
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=add_to_wishlist'
        });
        
        const data = await response.json();
        
        if (data.success) {
            isWishlisted = data.action === 'added';
            const btn = event.target;
            btn.textContent = isWishlisted ? '? Remove from Watchlist' : '? Add to Watchlist';
            alert(data.message);
        } else {
            alert(data.message || 'Failed to update watchlist');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error updating watchlist');
    }
}

// Offer modal functions
function showOfferModal() {
    if (!isLoggedIn) {
        alert('Please login to make an offer');
        window.location.href = '/login.php';
        return;
    }
    document.getElementById('offerModal').style.display = 'block';
}

function closeOfferModal() {
    document.getElementById('offerModal').style.display = 'none';
    document.getElementById('offerForm').reset();
    document.getElementById('offerError').style.display = 'none';
    document.getElementById('offerSuccess').style.display = 'none';
}

// Handle offer form submission
document.getElementById('offerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const offerAmount = document.getElementById('offerAmount').value;
    const offerMessage = document.getElementById('offerMessage').value;
    
    if (!offerAmount || offerAmount <= 0) {
        document.getElementById('offerError').textContent = 'Please enter a valid offer amount';
        document.getElementById('offerError').style.display = 'block';
        return;
    }
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=make_offer&offer_amount=${offerAmount}&message=${encodeURIComponent(offerMessage)}`
        });
        
        const data = await response.json();
        
        document.getElementById('offerError').style.display = 'none';
        
        if (data.success) {
            document.getElementById('offerSuccess').textContent = data.message;
            document.getElementById('offerSuccess').style.display = 'block';
            setTimeout(() => {
                closeOfferModal();
            }, 2000);
        } else {
            document.getElementById('offerError').textContent = data.message;
            document.getElementById('offerError').style.display = 'block';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('offerError').textContent = 'Error submitting offer';
        document.getElementById('offerError').style.display = 'block';
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('offerModal');
    if (event.target == modal) {
        closeOfferModal();
    }
}
</script>

<?php
// Include footer
if (file_exists(__DIR__ . '/templates/footer.php')) {
    include __DIR__ . '/templates/footer.php';
} elseif (file_exists(__DIR__ . '/includes/footer.php')) {
    include __DIR__ . '/includes/footer.php';
} else {
    echo "</body></html>";
}
?>