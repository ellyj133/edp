<?php
/**
 * Test Product Page - eBay Style Layout
 * Isolated test without database dependencies
 */

// Mock data for testing
$productData = [
    'id' => 1,
    'name' => 'Sun Oracle X5-2 Server 2 x E5-2650v3 36-core / 128Gb / 2 x 600GB',
    'description' => 'This is a quality server product with excellent build and design for daily use. Optimized performance with reliable parts and user-friendly controls.',
    'price' => 399.00,
    'compare_price' => 599.00,
    'stock_quantity' => 3,
    'featured' => true
];

$productId = 1;
$price = $productData['price'];
$comparePrice = $productData['compare_price'];
$hasDiscount = $comparePrice && $comparePrice > $price;
$youSave = $hasDiscount ? ($comparePrice - $price) : 0;
$savePercent = ($hasDiscount && $comparePrice > 0) ? round(($youSave / $comparePrice) * 100) : 0;

$images = [
    ['image_url' => 'server.jpg', 'alt_text' => 'Server front view', 'is_primary' => 1],
    ['image_url' => 'server-2.jpg', 'alt_text' => 'Server side view', 'is_primary' => 0],  
    ['image_url' => 'server-3.jpg', 'alt_text' => 'Server back view', 'is_primary' => 0],
];

$primaryImage = 'server.jpg';

$breadcrumbs = [
    ['label' => 'Home', 'url' => '/'],
    ['label' => 'Products', 'url' => '/products.php'],
    ['label' => $productData['name'], 'url' => null],
];

// Helper functions
function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function formatPrice($v) { return number_format((float)$v, 2); }
function getProductImageUrl($imagePath, $size = 'medium') {
    if (empty($imagePath)) {
        return '/images/placeholder-product.png';
    }
    // For testing, just return the path as-is
    return '/images/products/' . $imagePath;
}

$pageTitle = $productData['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle); ?> - FezaMarket</title>
    <style>
        @import url('https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css');
        @import url('https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lg-zoom.min.css');
        @import url('https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lg-thumbnail.min.css');
        
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--ebay-text);
            background: var(--ebay-bg);
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
            flex-wrap: wrap;
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

        .positive-feedback {
            color: var(--ebay-success);
            font-weight: 500;
        }

        .seller-items-link, .contact-seller {
            color: var(--ebay-primary);
            text-decoration: none;
            font-size: 13px;
        }

        .seller-items-link:hover, .contact-seller:hover {
            text-decoration: underline;
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

        .condition-details {
            font-size: 14px;
            color: var(--ebay-text-secondary);
            margin-top: 4px;
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

        .price-details {
            margin-bottom: 12px;
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

        .best-offer {
            font-size: 14px;
            color: var(--ebay-text-secondary);
            margin-top: 4px;
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

        .delivery-info, .returns-info {
            font-size: 14px;
            margin-top: 16px;
        }

        .delivery-info strong, .returns-info strong {
            display: block;
            margin-bottom: 4px;
        }

        .delivery-info div, .returns-info div {
            color: var(--ebay-text-secondary);
            line-height: 1.5;
        }

        .delivery-info a, .returns-info a {
            color: var(--ebay-primary);
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
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .ebay-watchlist-btn:hover {
            background: #f7f7f7;
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

        .shipping-section {
            margin: 16px 0;
            padding: 16px 0;
            border-top: 1px solid var(--ebay-border);
            font-size: 14px;
        }

        .international-note {
            font-size: 12px;
            color: var(--ebay-text-secondary);
            margin-top: 8px;
            font-style: italic;
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

        /* Similar Items Sections */
        .similar-items-section, .warranty-items-section {
            margin: 24px 0;
            padding: 16px 0;
            border-top: 1px solid var(--ebay-border);
        }

        .similar-items-section h3, .warranty-items-section h3 {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 12px;
            color: var(--ebay-text);
        }

        .similar-items-grid, .warranty-items-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 768px) {
            .similar-items-grid, .warranty-items-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .similar-item, .warranty-item {
            border: 1px solid var(--ebay-border);
            border-radius: 4px;
            padding: 8px;
            text-align: center;
            background: #fff;
            transition: box-shadow 0.2s;
        }

        .similar-item:hover, .warranty-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .similar-item img, .warranty-item img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .similar-item-price, .warranty-item-price {
            font-size: 14px;
            font-weight: 600;
            color: var(--ebay-text);
            margin-bottom: 4px;
        }

        .similar-item-shipping, .warranty-item-shipping {
            font-size: 12px;
            color: var(--ebay-text-secondary);
            margin-bottom: 4px;
        }

        .similar-item-seller, .warranty-item-seller {
            font-size: 11px;
            color: var(--ebay-text-secondary);
        }

        /* About Section */
        .about-section {
            margin: 32px 0;
            padding: 20px;
            border: 1px solid var(--ebay-border);
            border-radius: 8px;
            background: #fff;
        }

        .about-section h2 {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 16px;
            color: var(--ebay-text);
        }

        .item-description {
            font-size: 14px;
            line-height: 1.6;
            color: var(--ebay-text);
        }

        /* Hide the main media container used by lightGallery */
        #lightGallery-container { display: none; }
    </style>
</head>
<body>
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

        <!-- eBay-Style Product Layout -->
        <div class="ebay-product-layout">
            
            <!-- Left Column - Image Gallery -->
            <div class="ebay-image-gallery">
                <div class="ebay-main-image">
                    <img id="mainProductImage" 
                         src="<?= getProductImageUrl($primaryImage); ?>" 
                         alt="<?= h($productData['name']); ?>"
                         onclick="openGallery(0)">
                </div>
                
                <div class="ebay-thumbnail-strip">
                    <?php foreach ($images as $idx => $img): 
                        $imgUrl = getProductImageUrl($img['image_url'] ?? '');
                    ?>
                    <div class="ebay-thumbnail <?= $idx === 0 ? 'active' : ''; ?>" 
                         onclick="changeMainImage('<?= $imgUrl; ?>', <?= $idx; ?>)">
                        <img src="<?= $imgUrl; ?>" alt="<?= h($img['alt_text'] ?? $productData['name']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Center Column - Product Info -->
            <div class="ebay-product-info">
                <h1 class="ebay-product-title"><?= h($productData['name']); ?></h1>
                
                <div class="ebay-seller-info">
                    <span>Sold by </span>
                    <a href="#" class="ebay-seller-link">canapaktechnology</a>
                    <span class="ebay-seller-rating">(852)</span>
                    <span class="positive-feedback">98.3% positive</span>
                    <a href="#" class="seller-items-link">Seller's other items</a>
                    <a href="#" class="contact-seller">Contact seller</a>
                </div>
                
                <div class="ebay-condition">
                    <div class="ebay-condition-label">Condition:</div>
                    <div class="ebay-condition-value">
                        Used <span class="condition-info">ℹ️</span>
                    </div>
                    <div class="condition-details">
                        <em>"<?= h($productData['name']); ?>"</em>
                    </div>
                </div>
                
                <div class="ebay-price-section">
                    <div class="ebay-current-price">
                        C $<?= formatPrice($price); ?>
                    </div>
                    <div class="price-details">
                        <?php if ($hasDiscount): ?>
                            <span class="ebay-original-price">US $<?= formatPrice($comparePrice); ?></span>
                            <span class="ebay-discount">Save <?= $savePercent; ?>%</span>
                        <?php else: ?>
                            <span class="price-conversion">Approximately US $<?= formatPrice($price * 0.75); ?></span>
                        <?php endif; ?>
                        <div class="best-offer">or Best Offer</div>
                    </div>
                    
                    <div class="ebay-shipping-info">
                        <span class="highlight">Free shipping</span> to Canada
                    </div>
                    
                    <div class="ebay-location-info">
                        Located in: Mississauga, Canada
                    </div>
                    
                    <div class="delivery-info">
                        <strong>Delivery:</strong>
                        <div>Estimated between Mon, Dec 15 and Mon, Mar 9 to 🏠</div>
                    </div>
                    
                    <div class="returns-info">
                        <strong>Returns:</strong>
                        <div>30 days returns. Buyer pays for return shipping. If you use an eBay shipping label, it will be deducted from your refund amount. 
                        <a href="#">See details</a></div>
                    </div>
                </div>
                
                <!-- Similar Items Section -->
                <div class="similar-items-section">
                    <h3>Similar Items</h3>
                    <div class="similar-items-grid">
                        <?php 
                        // Show some similar items (mock data for now)
                        for ($i = 1; $i <= 4; $i++): 
                            $similarPrice = $price + rand(-50, 100);
                        ?>
                        <div class="similar-item">
                            <img src="<?= getProductImageUrl('product-' . $i . '.jpg'); ?>" alt="Similar Item <?= $i; ?>">
                            <div class="similar-item-price">$<?= formatPrice($similarPrice); ?></div>
                            <div class="similar-item-shipping">+ shipping</div>
                            <div class="similar-item-seller">Seller with 100% positive feedback</div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Similar Items with Free Warranty -->
                <div class="warranty-items-section">
                    <h3>Similar Items with Free Warranty</h3>
                    <div class="warranty-items-grid">
                        <?php for ($i = 1; $i <= 5; $i++): 
                            $warrantyPrice = $price + rand(50, 200);
                        ?>
                        <div class="warranty-item">
                            <img src="<?= getProductImageUrl('product-' . $i . '.jpg'); ?>" alt="Warranty Item <?= $i; ?>">
                            <div class="warranty-item-price">$<?= formatPrice($warrantyPrice); ?></div>
                            <div class="warranty-item-shipping">+ shipping</div>
                            <div class="warranty-item-seller">Seller with 100% positive feedback</div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Purchase Options -->
            <div class="ebay-purchase-panel">
                <div class="ebay-buy-box">
                    <div class="ebay-quantity-selector">
                        <label class="ebay-quantity-label" for="quantity">Quantity:</label>
                        <input type="number" id="quantity" class="ebay-quantity-input" value="1" min="1" max="10">
                    </div>
                    
                    <button class="ebay-buy-now-btn" onclick="buyNow()">
                        Buy It Now
                    </button>
                    
                    <button class="ebay-add-cart-btn" onclick="addToCart()">
                        Add to cart
                    </button>
                    
                    <button class="ebay-watchlist-btn" onclick="makeOffer()">
                        Make offer
                    </button>
                    
                    <button class="ebay-watchlist-btn" onclick="addToWatchlist()">
                        ♡ Add to Watchlist
                    </button>
                </div>
                
                <div class="ebay-availability">
                    <div class="ebay-stock-status">
                        <?php if (!empty($productData['stock_quantity'])): ?>
                            3 have added this to their watchlist
                        <?php else: ?>
                            Currently unavailable
                        <?php endif; ?>
                    </div>
                    
                    <div class="ebay-delivery-info">
                        <strong>Breathe easy.</strong> Returns accepted.
                    </div>
                </div>
                
                <div class="shipping-section">
                    <div><strong>Shipping:</strong></div>
                    <div>C $199.00 (approx US $142.75) Canada Post International Parcel (Non-US) - Surface. 
                    <a href="#">See details</a></div>
                    <div class="international-note">
                        International shipment of items may be subject to customs processing and additional charges. 📋
                    </div>
                </div>
                
                <div class="ebay-return-policy">
                    <strong>Returns:</strong> 30 days returns. Buyer pays for return shipping. If you use an eBay shipping label, it will be deducted from your refund amount. <a href="#">See details</a>
                </div>
                
                <div class="ebay-payments">
                    <div><strong>Payments:</strong></div>
                    <div class="ebay-payment-methods">
                        <div class="ebay-payment-icon">PP</div>
                        <div class="ebay-payment-icon">GP</div>
                        <div class="ebay-payment-icon">VS</div>
                        <div class="ebay-payment-icon">MC</div>
                        <div class="ebay-payment-icon">DI</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- About this item section -->
        <div class="about-section">
            <h2>About this item</h2>
            <div class="item-description">
                <?php if (!empty($productData['description'])): ?>
                    <?= nl2br(h($productData['description'])); ?>
                <?php else: ?>
                    <p>This is a quality product with excellent build and design for daily use. Optimized performance with reliable parts and user-friendly controls.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Light Gallery Container (hidden) -->
        <div id="lightGallery-container" style="display: none;">
            <?php foreach ($images as $img): ?>
                <a href="<?= getProductImageUrl($img['image_url'] ?? ''); ?>" data-sub-html="<?= h($img['alt_text'] ?? $productData['name']); ?>">
                    <img src="<?= getProductImageUrl($img['image_url'] ?? ''); ?>" />
                </a>
            <?php endforeach; ?>
        </div>

    </div>

    <script>
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
            alert(`Buy Now: ${quantity} item(s)`);
        }

        function addToCart() {
            const quantity = document.getElementById('quantity').value;
            alert(`Added ${quantity} item(s) to cart!`);
        }

        function makeOffer() {
            const currentPrice = <?= $price; ?>;
            const offer = prompt(`Make an offer (Current price: $${currentPrice.toFixed(2)}):`);
            if (offer && !isNaN(offer) && parseFloat(offer) > 0) {
                alert(`Offer of $${parseFloat(offer).toFixed(2)} submitted!`);
            }
        }

        function addToWatchlist() {
            alert('Added to watchlist!');
        }

        function openGallery(index) {
            alert('Gallery would open here - lightGallery integration needed');
        }
    </script>
</body>
</html>