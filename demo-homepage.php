<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FezaMarket - eBay-Style Homepage Demo</title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #ffffff;
        }

        .container {
            width: 100%;
            padding: 0 20px;
        }

        /* ========== Header ========== */
        .demo-header {
            background: #0654ba;
            color: white;
            padding: 16px 0;
            text-align: center;
            margin-bottom: 20px;
        }

        .demo-header h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .demo-header p {
            opacity: 0.9;
        }

        /* ========== eBay-Style Homepage Layout ========== */
        .homepage-container {
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
        }

        .homepage-container > section {
            margin-bottom: 48px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 32px;
            width: 100%;
            padding: 0 20px;
        }

        .section-title {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .section-subtitle {
            font-size: 16px;
            color: #6b7280;
            margin: 0;
        }

        /* Section 1: Hero Banner */
        .hero-section {
            margin-bottom: 48px;
        }

        .hero-main-banner {
            position: relative;
            height: 460px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 24px 20px;
            width: calc(100% - 40px);
        }

        .hero-content {
            position: absolute;
            top: 50%;
            left: 40px;
            transform: translateY(-50%);
            z-index: 10;
            max-width: 500px;
        }

        .hero-title {
            font-size: 42px;
            font-weight: 800;
            color: white;
            margin-bottom: 16px;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 18px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-hero {
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .btn-hero.primary {
            background: #fbbf24;
            color: #1f2937;
        }

        .btn-hero.primary:hover {
            background: #f59e0b;
            transform: translateY(-2px);
        }

        .btn-hero.secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
        }

        .badge-text {
            background: #dc2626;
            color: white;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Section 2: Categories */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            width: 100%;
            padding: 0 20px;
        }

        .category-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        }

        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }

        .category-content {
            text-align: center;
            padding: 20px;
        }

        .category-title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .category-subtitle {
            color: #6b7280;
            font-size: 14px;
        }

        /* Section 3: Deals */
        .deals-section {
            background: #f8fafc;
            padding: 48px 0;
            margin: 0 -20px 48px -20px;
        }

        .deals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            width: 100%;
            padding: 0 20px;
        }

        .deal-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            height: 280px;
        }

        .deal-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }

        .deal-timer {
            position: absolute;
            top: 12px;
            left: 12px;
            background: #dc2626;
            color: white;
            padding: 6px 10px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
        }

        .deal-image {
            height: 160px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }

        .deal-content {
            padding: 20px;
        }

        .deal-content h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .deal-price {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .deal-price .current {
            font-size: 20px;
            font-weight: 700;
            color: #dc2626;
        }

        .deal-price .original {
            font-size: 16px;
            color: #6b7280;
            text-decoration: line-through;
        }

        /* Product Shelf */
        .product-shelf-section {
            margin: 48px 0 32px;
        }

        .product-shelf-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            margin-bottom: 18px;
            width: 100%;
            padding: 0 20px;
        }

        .product-shelf-header h2 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            color: #1f2937;
        }

        .product-shelf-header a {
            font-size: 14px;
            color: #0654ba;
            text-decoration: none;
            font-weight: 500;
        }

        .product-shelf-scroll {
            display: flex;
            gap: 24px;
            padding: 6px 20px 10px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .product-shelf-scroll::-webkit-scrollbar {
            display: none;
        }

        .product-card-shelf {
            flex: 0 0 200px;
            width: 200px;
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .product-card-shelf .media-wrapper {
            height: 200px;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 24px;
        }

        .product-title-shelf {
            padding: 12px;
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
        }

        .price-row {
            padding: 0 12px 12px;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        /* Brands Section */
        .brands-section {
            background: #f8fafc;
            padding: 48px 0;
            margin: 0 -20px 48px -20px;
        }

        .brands-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 24px;
            width: 100%;
            padding: 0 20px;
        }

        .brand-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .brand-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border-radius: 50%;
            font-size: 24px;
            color: #64748b;
        }

        .brand-name {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        /* Recommendations */
        .recommendations-section {
            background: #f8fafc;
            padding: 48px 0;
            margin: 0 -20px 48px -20px;
        }

        .personalized-cta {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: calc(100% - 40px);
            margin: 0 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .cta-content {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .cta-icon {
            width: 80px;
            height: 80px;
            background: #0654ba;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            flex-shrink: 0;
        }

        .cta-text {
            flex: 1;
            min-width: 250px;
        }

        .cta-title {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .cta-subtitle {
            color: #6b7280;
            margin: 0;
        }

        .cta-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-cta {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-cta.primary {
            background: #0654ba;
            color: white;
        }

        .btn-cta.secondary {
            background: transparent;
            color: #0654ba;
            border: 2px solid #0654ba;
        }

        /* Demo Navigation */
        .demo-nav {
            background: #1f2937;
            color: white;
            padding: 16px 0;
            text-align: center;
            margin-top: 48px;
        }

        .demo-nav a {
            color: #fbbf24;
            text-decoration: none;
            margin: 0 16px;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-main-banner {
                height: 300px;
                margin: 16px 10px;
            }
            
            .hero-content {
                left: 20px;
                max-width: calc(100% - 40px);
            }
            
            .hero-title {
                font-size: 28px;
            }
            
            .categories-grid,
            .deals-grid,
            .brands-grid {
                grid-template-columns: 1fr;
                padding: 0 10px;
            }
            
            .section-header {
                padding: 0 10px;
            }
            
            .product-shelf-header {
                padding: 0 10px;
            }
            
            .cta-content {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="demo-header">
        <h1><i class="fas fa-home"></i> FezaMarket - eBay-Style Homepage Demo</h1>
        <p>Drag-and-drop CMS managed homepage with 8 configurable sections</p>
    </div>

    <div class="homepage-container">
        <!-- Section 1: Hero Banner -->
        <section class="hero-section">
            <div class="hero-main-banner">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1 class="hero-title">New & trending<br>editors' picks</h1>
                        <p class="hero-subtitle">Discover the latest curated collections from our fashion experts</p>
                        <div class="hero-actions">
                            <a href="#" class="btn-hero primary">
                                <i class="fas fa-star"></i>
                                Shop Now
                            </a>
                            <a href="#" class="btn-hero secondary">
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

        <!-- Section 2: Featured Categories -->
        <section class="featured-categories">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Shop by Category</h2>
                    <p class="section-subtitle">Discover what's trending in each category</p>
                </div>
                <div class="categories-grid">
                    <div class="category-card">
                        <div class="category-content">
                            <i class="fas fa-laptop" style="font-size: 48px; color: #0654ba; margin-bottom: 16px;"></i>
                            <h3 class="category-title">Electronics</h3>
                            <p class="category-subtitle">Latest gadgets & tech</p>
                        </div>
                    </div>
                    <div class="category-card">
                        <div class="category-content">
                            <i class="fas fa-tshirt" style="font-size: 48px; color: #dc2626; margin-bottom: 16px;"></i>
                            <h3 class="category-title">Fashion</h3>
                            <p class="category-subtitle">Trending styles</p>
                        </div>
                    </div>
                    <div class="category-card">
                        <div class="category-content">
                            <i class="fas fa-home" style="font-size: 48px; color: #16a34a; margin-bottom: 16px;"></i>
                            <h3 class="category-title">Home & Garden</h3>
                            <p class="category-subtitle">Decor & essentials</p>
                        </div>
                    </div>
                    <div class="category-card">
                        <div class="category-content">
                            <i class="fas fa-dumbbell" style="font-size: 48px; color: #f59e0b; margin-bottom: 16px;"></i>
                            <h3 class="category-title">Sports</h3>
                            <p class="category-subtitle">Fitness & outdoor</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

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
                    <div class="deal-card">
                        <div class="deal-timer">
                            <i class="fas fa-clock"></i>
                            12h 34m left
                        </div>
                        <div class="deal-image">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="deal-content">
                            <h3>Flash Sale Electronics</h3>
                            <div class="deal-price">
                                <span class="current">$89.99</span>
                                <span class="original">$199.99</span>
                            </div>
                        </div>
                    </div>
                    <div class="deal-card">
                        <div class="deal-timer">
                            <i class="fas fa-clock"></i>
                            6h 15m left
                        </div>
                        <div class="deal-image">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <div class="deal-content">
                            <h3>Fashion Clearance</h3>
                            <div class="deal-price">
                                <span class="current">$59.99</span>
                                <span class="original">$99.99</span>
                            </div>
                        </div>
                    </div>
                    <div class="deal-card">
                        <div class="deal-timer">
                            <i class="fas fa-clock"></i>
                            24h 00m left
                        </div>
                        <div class="deal-image">
                            <i class="fas fa-couch"></i>
                        </div>
                        <div class="deal-content">
                            <h3>Home Essentials</h3>
                            <div class="deal-price">
                                <span class="current">$39.99</span>
                                <span class="original">$59.99</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 4: Trending Products -->
        <section class="product-shelf-section">
            <div class="product-shelf-header">
                <h2>Trending Products</h2>
                <a href="#">View all</a>
            </div>
            <div class="product-shelf-scroll">
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-headphones"></i>
                    </div>
                    <h3 class="product-title-shelf">Premium Headphones</h3>
                    <div class="price-row">$129.99</div>
                </div>
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-watch"></i>
                    </div>
                    <h3 class="product-title-shelf">Smart Watch</h3>
                    <div class="price-row">$299.99</div>
                </div>
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-camera"></i>
                    </div>
                    <h3 class="product-title-shelf">Digital Camera</h3>
                    <div class="price-row">$599.99</div>
                </div>
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <h3 class="product-title-shelf">Gaming Controller</h3>
                    <div class="price-row">$79.99</div>
                </div>
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-keyboard"></i>
                    </div>
                    <h3 class="product-title-shelf">Mechanical Keyboard</h3>
                    <div class="price-row">$149.99</div>
                </div>
            </div>
        </section>

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
                    <div class="brand-card">
                        <div class="brand-logo">
                            <i class="fab fa-apple"></i>
                        </div>
                        <h3 class="brand-name">Apple</h3>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo">
                            <i class="fab fa-google"></i>
                        </div>
                        <h3 class="brand-name">Google</h3>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo">
                            <i class="fab fa-microsoft"></i>
                        </div>
                        <h3 class="brand-name">Microsoft</h3>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo">
                            <i class="fab fa-amazon"></i>
                        </div>
                        <h3 class="brand-name">Amazon</h3>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo">
                            <i class="fab fa-spotify"></i>
                        </div>
                        <h3 class="brand-name">Spotify</h3>
                    </div>
                    <div class="brand-card">
                        <div class="brand-logo">
                            <i class="fab fa-netflix"></i>
                        </div>
                        <h3 class="brand-name">Netflix</h3>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 6: Featured Products -->
        <section class="product-shelf-section">
            <div class="product-shelf-header">
                <h2><i class="fas fa-star"></i> Featured Products</h2>
                <a href="#">View all</a>
            </div>
            <div class="product-shelf-scroll">
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3 class="product-title-shelf">Gaming Laptop</h3>
                    <div class="price-row">$1,299.99</div>
                </div>
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-tablet-alt"></i>
                    </div>
                    <h3 class="product-title-shelf">Tablet Pro</h3>
                    <div class="price-row">$899.99</div>
                </div>
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-mouse"></i>
                    </div>
                    <h3 class="product-title-shelf">Wireless Mouse</h3>
                    <div class="price-row">$49.99</div>
                </div>
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <h3 class="product-title-shelf">USB Microphone</h3>
                    <div class="price-row">$199.99</div>
                </div>
            </div>
        </section>

        <!-- Section 7: New Arrivals -->
        <section class="product-shelf-section">
            <div class="product-shelf-header">
                <h2>New Arrivals</h2>
                <a href="#">View all</a>
            </div>
            <div class="product-shelf-scroll">
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-vr-cardboard"></i>
                    </div>
                    <h3 class="product-title-shelf">VR Headset</h3>
                    <div class="price-row">$399.99</div>
                </div>
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="product-title-shelf">Smart Robot</h3>
                    <div class="price-row">$799.99</div>
                </div>
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-drone"></i>
                    </div>
                    <h3 class="product-title-shelf">Drone Camera</h3>
                    <div class="price-row">$549.99</div>
                </div>
                <div class="product-card-shelf">
                    <div class="media-wrapper">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3 class="product-title-shelf">RC Car</h3>
                    <div class="price-row">$89.99</div>
                </div>
            </div>
        </section>

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
                            <a href="#" class="btn-cta primary">
                                <i class="fas fa-sign-in-alt"></i>
                                Sign In
                            </a>
                            <a href="#" class="btn-cta secondary">
                                <i class="fas fa-user-plus"></i>
                                Create Account
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="demo-nav">
        <p>
            <a href="/admin/cms/homepage-editor.php">
                <i class="fas fa-edit"></i> Open CMS Editor
            </a>
            <a href="/">
                <i class="fas fa-home"></i> View Live Homepage
            </a>
            <a href="/admin/cms/">
                <i class="fas fa-cog"></i> Admin Panel
            </a>
        </p>
    </div>
</body>
</html>