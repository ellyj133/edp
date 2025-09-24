<?php
/**
 * UI Components Demo Page
 * Showcase new design system and components
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'UI Components Demo';
$meta_description = 'Modern UI components showcase for FezaMarket';

// Sample product data for demo
$sampleProduct = [
    'id' => 1,
    'name' => 'Premium Wireless Headphones',
    'price' => 199.99,
    'compare_price' => 249.99,
    'rating' => 4.5,
    'rating_count' => 128,
    'image_url' => '/assets/images/demo-product.jpg',
    'badge' => 'sale'
];

include __DIR__ . '/templates/header.php';
?>

<main class="container py-8">
    <!-- Page Header -->
    <div class="mb-12 text-center">
        <h1 class="text-4xl font-bold text-neutral-800 mb-4">UI Components Demo</h1>
        <p class="text-lg text-neutral-600 max-w-2xl mx-auto">
            Modern, accessible, and responsive components built with our new design system.
        </p>
    </div>

    <!-- Components Grid -->
    <div class="space-y-16">
        
        <!-- Toast Notifications -->
        <section class="component-section">
            <h2 class="text-2xl font-semibold text-neutral-800 mb-6">Toast Notifications</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <button class="btn btn-primary" onclick="UI.Toast.show('Success message!', 'success')">
                    Success Toast
                </button>
                <button class="btn btn-secondary" onclick="UI.Toast.show('Information message', 'info')">
                    Info Toast
                </button>
                <button class="btn btn-outline" onclick="UI.Toast.show('Warning message', 'warning')">
                    Warning Toast
                </button>
                <button class="btn btn-outline border-red-300 text-red-700 hover:bg-red-50" onclick="UI.Toast.show('Error occurred!', 'error')">
                    Error Toast
                </button>
            </div>
        </section>

        <!-- Product Cards -->
        <section class="component-section">
            <h2 class="text-2xl font-semibold text-neutral-800 mb-6">Product Cards</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                
                <!-- Default Product Card -->
                <?php echo ProductCard::render($sampleProduct); ?>
                
                <!-- Compact Product Card -->
                <?php echo ProductCard::render(array_merge($sampleProduct, [
                    'id' => 2,
                    'name' => 'Compact Product Example',
                    'badge' => 'new'
                ]), ['size' => 'compact']); ?>
                
                <!-- Large Product Card -->
                <?php echo ProductCard::render(array_merge($sampleProduct, [
                    'id' => 3,
                    'name' => 'Large Product Card with Long Name',
                    'badge' => 'featured'
                ]), ['size' => 'large']); ?>
                
            </div>
        </section>

        <!-- Price Badges -->
        <section class="component-section">
            <h2 class="text-2xl font-semibold text-neutral-800 mb-6">Price Components</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="card p-6">
                    <h3 class="font-semibold mb-4">Regular Price</h3>
                    <?php echo PriceBadge::render(29.99); ?>
                </div>
                <div class="card p-6">
                    <h3 class="font-semibold mb-4">Sale Price</h3>
                    <?php echo PriceBadge::render(19.99, 29.99); ?>
                </div>
                <div class="card p-6">
                    <h3 class="font-semibold mb-4">Large Price</h3>
                    <?php echo PriceBadge::large(199.99, 249.99); ?>
                </div>
            </div>
        </section>

        <!-- Rating Stars -->
        <section class="component-section">
            <h2 class="text-2xl font-semibold text-neutral-800 mb-6">Rating Components</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="card p-6">
                    <h3 class="font-semibold mb-4">Compact Rating</h3>
                    <?php echo RatingStars::compact(4.5, 128); ?>
                </div>
                <div class="card p-6">
                    <h3 class="font-semibold mb-4">Default Rating</h3>
                    <?php echo RatingStars::render(3.8, 45); ?>
                </div>
                <div class="card p-6">
                    <h3 class="font-semibold mb-4">Detailed Rating</h3>
                    <?php echo RatingStars::detailed(4.2, 256); ?>
                </div>
            </div>
        </section>

        <!-- Buttons -->
        <section class="component-section">
            <h2 class="text-2xl font-semibold text-neutral-800 mb-6">Button Components</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="card p-6">
                    <h3 class="font-semibold mb-4">Add to Cart Buttons</h3>
                    <div class="space-y-4">
                        <?php echo AddToCartButton::simple(1, 'Add to Cart'); ?>
                        <?php echo AddToCartButton::compact(2); ?>
                    </div>
                </div>
                <div class="card p-6">
                    <h3 class="font-semibold mb-4">Button Variants</h3>
                    <div class="space-y-4">
                        <button class="btn btn-primary">Primary Button</button>
                        <button class="btn btn-secondary">Secondary Button</button>
                        <button class="btn btn-outline">Outline Button</button>
                        <button class="btn btn-ghost">Ghost Button</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Form Elements -->
        <section class="component-section">
            <h2 class="text-2xl font-semibold text-neutral-800 mb-6">Form Components</h2>
            <div class="card p-6 max-w-2xl">
                <form data-validate class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-neutral-700 mb-2">
                            Email Address
                        </label>
                        <input type="email" id="email" name="email" class="input" 
                               placeholder="Enter your email" required>
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-neutral-700 mb-2">
                            Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone" class="input" 
                               placeholder="(555) 123-4567" data-mask="phone">
                    </div>
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-neutral-700 mb-2">
                            Message
                        </label>
                        <textarea id="message" name="message" rows="4" class="input" 
                                  placeholder="Enter your message" data-min-length="10" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        Submit Form
                    </button>
                </form>
            </div>
        </section>

        <!-- Loading States -->
        <section class="component-section">
            <h2 class="text-2xl font-semibold text-neutral-800 mb-6">Loading States</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="card p-6">
                    <div class="skeleton skeleton-title mb-4"></div>
                    <div class="skeleton skeleton-text mb-2"></div>
                    <div class="skeleton skeleton-text mb-2"></div>
                    <div class="skeleton skeleton-text w-3/4"></div>
                </div>
                <div class="card p-6">
                    <div class="skeleton skeleton-image mb-4"></div>
                    <div class="skeleton skeleton-title mb-2"></div>
                    <div class="skeleton skeleton-text"></div>
                </div>
                <div class="card p-6 flex items-center justify-center">
                    <svg class="animate-spin w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </section>

        <!-- Cart Drawer Demo -->
        <section class="component-section">
            <h2 class="text-2xl font-semibold text-neutral-800 mb-6">Interactive Components</h2>
            <div class="flex gap-4">
                <button class="btn btn-primary cart-toggle">
                    Open Cart Drawer
                </button>
                <button class="btn btn-outline" onclick="document.querySelector('.mobile-menu')?.classList.toggle('open')">
                    Toggle Mobile Menu
                </button>
            </div>
        </section>

    </div>
</main>

<!-- Server-rendered toast example -->
<?php if (isset($_GET['toast'])): ?>
    <?php echo Toast::success('Welcome to the UI Demo!'); ?>
<?php endif; ?>

<style>
/* Additional demo styles */
.component-section {
    padding: 2rem 0;
    border-bottom: 1px solid var(--color-neutral-200);
}

.component-section:last-child {
    border-bottom: none;
}

/* Mobile menu demo styles */
.mobile-menu {
    position: fixed;
    top: 0;
    left: -100%;
    width: 80%;
    max-width: 300px;
    height: 100vh;
    background: white;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    transition: left 0.3s ease;
    z-index: 1000;
    padding: 2rem;
}

.mobile-menu.open {
    left: 0;
}
</style>

<?php include __DIR__ . '/templates/footer.php'; ?>