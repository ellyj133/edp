<?php
/**
 * Seller Live Streaming Dashboard
 * Manage live selling streams and events
 */

require_once __DIR__ . '/../includes/init.php';

// Require vendor login
Session::requireLogin();

// Load models
$vendor = new Vendor();
$product = new Product();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo || $vendorInfo['status'] !== 'approved') {
    redirect('/seller-onboarding.php');
}

$vendorId = $vendorInfo['id'];

// Get vendor's products for live streaming
$vendorProducts = $product->getByVendorId($vendorId);
$activeProducts = array_filter($vendorProducts, function($p) { 
    return $p['status'] === 'active'; 
});

$page_title = 'Live Streaming - Seller Dashboard';
$meta_description = 'Manage your live selling streams and connect with customers in real-time.';

include __DIR__ . '/../templates/seller-header.php';
?>

<div class="container" style="padding: 20px;">
    <div class="live-streaming-dashboard">
        <!-- Header Section -->
        <div class="page-header" style="margin-bottom: 30px;">
            <h1 style="font-size: 28px; margin-bottom: 10px; color: #333;">
                🔴 Live Streaming Dashboard
            </h1>
            <p style="color: #666; font-size: 16px;">
                Host live selling events and engage with customers in real-time
            </p>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="action-card" style="background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);">
                <div style="font-size: 32px; margin-bottom: 12px;">🎥</div>
                <h3 style="font-size: 18px; margin-bottom: 8px; font-weight: 600;">Go Live Now</h3>
                <p style="font-size: 14px; opacity: 0.9; margin-bottom: 16px;">Start broadcasting to customers instantly</p>
                <button class="btn" style="background: white; color: #dc2626; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onclick="startLiveStream()">
                    Start Stream
                </button>
            </div>

            <div class="action-card" style="background: white; border: 2px solid #e5e7eb; padding: 24px; border-radius: 12px;">
                <div style="font-size: 32px; margin-bottom: 12px;">📅</div>
                <h3 style="font-size: 18px; margin-bottom: 8px; font-weight: 600; color: #333;">Schedule Event</h3>
                <p style="font-size: 14px; color: #666; margin-bottom: 16px;">Plan and promote upcoming live events</p>
                <button class="btn" style="background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onclick="scheduleEvent()">
                    Schedule
                </button>
            </div>

            <div class="action-card" style="background: white; border: 2px solid #e5e7eb; padding: 24px; border-radius: 12px;">
                <div style="font-size: 32px; margin-bottom: 12px;">📊</div>
                <h3 style="font-size: 18px; margin-bottom: 8px; font-weight: 600; color: #333;">View Analytics</h3>
                <p style="font-size: 14px; color: #666; margin-bottom: 16px;">Track views, engagement, and sales</p>
                <button class="btn" style="background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onclick="viewAnalytics()">
                    View Stats
                </button>
            </div>
        </div>

        <!-- Live Stream Setup Guide -->
        <div class="setup-guide" style="background: #f8f9fa; border-radius: 12px; padding: 30px; margin-bottom: 30px;">
            <h2 style="font-size: 22px; margin-bottom: 20px; color: #333;">
                🚀 How to Start Your Live Stream
            </h2>
            <div class="steps" style="display: grid; gap: 16px;">
                <div class="step" style="display: flex; gap: 16px; padding: 16px; background: white; border-radius: 8px;">
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">1</div>
                    <div>
                        <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 4px; color: #333;">Select Products</h4>
                        <p style="font-size: 14px; color: #666;">Choose which products you want to showcase during your live stream</p>
                    </div>
                </div>
                <div class="step" style="display: flex; gap: 16px; padding: 16px; background: white; border-radius: 8px;">
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">2</div>
                    <div>
                        <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 4px; color: #333;">Set Up Equipment</h4>
                        <p style="font-size: 14px; color: #666;">Ensure you have a camera, good lighting, and stable internet connection</p>
                    </div>
                </div>
                <div class="step" style="display: flex; gap: 16px; padding: 16px; background: white; border-radius: 8px;">
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">3</div>
                    <div>
                        <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 4px; color: #333;">Go Live</h4>
                        <p style="font-size: 14px; color: #666;">Click "Start Stream" and engage with your viewers in real-time</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Selection for Live Stream -->
        <div class="product-selection" style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 30px; margin-bottom: 30px;">
            <h2 style="font-size: 22px; margin-bottom: 20px; color: #333;">
                📦 Select Products for Live Stream
            </h2>
            <?php if (empty($activeProducts)): ?>
                <div class="empty-state" style="text-align: center; padding: 40px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 16px;">📦</div>
                    <h3 style="font-size: 18px; margin-bottom: 8px;">No Active Products</h3>
                    <p style="margin-bottom: 20px;">You need active products to start a live stream</p>
                    <a href="/seller/products/add.php" class="btn" style="background: #3b82f6; color: white; padding: 10px 24px; border-radius: 6px; text-decoration: none; display: inline-block;">
                        Add Products
                    </a>
                </div>
            <?php else: ?>
                <div class="products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
                    <?php foreach (array_slice($activeProducts, 0, 8) as $product): ?>
                        <div class="product-card" style="border: 2px solid #e5e7eb; border-radius: 8px; padding: 16px; cursor: pointer; transition: all 0.2s;" onclick="toggleProductSelection(<?php echo $product['id']; ?>, this)">
                            <div class="product-image" style="width: 100%; height: 150px; background: #f3f4f6; border-radius: 6px; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <span style="font-size: 48px;">📦</span>
                                <?php endif; ?>
                            </div>
                            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 4px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h4>
                            <p style="font-size: 16px; font-weight: bold; color: #3b82f6;">
                                $<?php echo number_format($product['price'], 2); ?>
                            </p>
                            <div class="selection-indicator" style="margin-top: 8px; padding: 4px 8px; border-radius: 4px; text-align: center; font-size: 12px; font-weight: 600; background: #f3f4f6; color: #6b7280;">
                                Click to Select
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Resources and Tips -->
        <div class="resources" style="background: linear-gradient(135deg, #f3f4f6, #e5e7eb); border-radius: 12px; padding: 30px;">
            <h2 style="font-size: 22px; margin-bottom: 20px; color: #333;">
                💡 Live Streaming Tips
            </h2>
            <div class="tips-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                <div class="tip" style="background: white; padding: 20px; border-radius: 8px;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 8px; color: #333;">🎯 Engage Your Audience</h4>
                    <p style="font-size: 14px; color: #666;">Ask questions, respond to comments, and create interactive content</p>
                </div>
                <div class="tip" style="background: white; padding: 20px; border-radius: 8px;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 8px; color: #333;">💰 Offer Exclusive Deals</h4>
                    <p style="font-size: 14px; color: #666;">Give live-only discounts to incentivize immediate purchases</p>
                </div>
                <div class="tip" style="background: white; padding: 20px; border-radius: 8px;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 8px; color: #333;">📱 Promote in Advance</h4>
                    <p style="font-size: 14px; color: #666;">Share your stream schedule on social media to build anticipation</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Selected products for live stream
let selectedProducts = [];

function toggleProductSelection(productId, element) {
    const indicator = element.querySelector('.selection-indicator');
    const index = selectedProducts.indexOf(productId);
    
    if (index > -1) {
        // Deselect
        selectedProducts.splice(index, 1);
        element.style.borderColor = '#e5e7eb';
        indicator.style.background = '#f3f4f6';
        indicator.style.color = '#6b7280';
        indicator.textContent = 'Click to Select';
    } else {
        // Select
        selectedProducts.push(productId);
        element.style.borderColor = '#3b82f6';
        indicator.style.background = '#3b82f6';
        indicator.style.color = 'white';
        indicator.textContent = '✓ Selected';
    }
}

function startLiveStream() {
    if (selectedProducts.length === 0) {
        alert('Please select at least one product to feature in your live stream!');
        return;
    }
    
    // In a real implementation, this would open the streaming interface
    alert('Live streaming feature is being configured!\n\nSelected products: ' + selectedProducts.length + '\n\nThis will open the streaming interface where you can:\n• Set up your camera\n• Preview your stream\n• Go live to customers');
}

function scheduleEvent() {
    // In a real implementation, this would open a scheduling modal
    alert('Schedule a live event!\n\nYou can set:\n• Event date and time\n• Featured products\n• Event title and description\n• Promotional banners');
}

function viewAnalytics() {
    // Redirect to analytics page
    window.location.href = '/seller/analytics.php?tab=live-streams';
}
</script>

<?php
include __DIR__ . '/../templates/footer.php';
?>
