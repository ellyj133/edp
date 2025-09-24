<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title . ' - FezaMarket'); ?></title>
    
    <!-- Modern CSS Framework -->
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/css/styles.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Preload critical assets -->
    <link rel="preload" href="/assets/js/ui.js" as="script">
    
    <!-- Favicon and meta -->
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description ?? 'FezaMarket - Buy & Sell Everything Online'); ?>">
    
    <!-- CSRF Meta Tag for AJAX -->
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
    
    <style>
        /* Enhanced User dropdown styles */
        .account-dropdown {
            position: relative;
        }
        
        .user-menu-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 20px;
            transition: all 0.2s ease;
        }
        
        .user-menu-toggle:hover {
            background-color: rgba(6, 84, 186, 0.1);
        }
        
        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .dropdown-arrow {
            font-size: 12px;
            color: #6c757d;
            transition: transform 0.2s ease;
        }
        
        .user-menu-toggle:hover .dropdown-arrow {
            transform: rotate(180deg);
        }
        
        .user-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            z-index: 1000;
            margin-top: 0.5rem;
            overflow: hidden;
            animation: dropdownFadeIn 0.2s ease-out;
        }
        
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            color: #374151;
            text-decoration: none;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #0654ba;
        }
        
        .dropdown-item i {
            width: 16px;
            color: #6c757d;
        }
        
        .dropdown-item:hover i {
            color: #0654ba;
        }
        
        .dropdown-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 4px 0;
        }
    </style>
    
    <!-- Modern JavaScript -->
    <script src="/assets/js/ui.js" defer></script>
    <script src="/js/fezamarket.js" defer></script>
</head>
<body>
    <?php
    // Defensive alias: ensure we always work with an array when reading user fields
    $cu = is_array($current_user ?? null) ? $current_user : [];
    ?>
    <header class="fezamarket-header">
        <!-- Top Navigation Bar -->
        <div class="top-nav">
            <div class="container">
                <div class="top-nav-content">
                    <div class="top-nav-left">
                        <span class="greeting">
                            <?php if (Session::isLoggedIn()): ?>
                                Hi, <?php echo htmlspecialchars(($cu['first_name'] ?? 'User')); ?>!
                            <?php else: ?>
                                <a href="/login.php" class="auth-link">Sign in</a> or <a href="/register.php" class="auth-link">register</a>
                            <?php endif; ?>
                        </span>
                        <a href="/deals.php" class="top-nav-link">Daily Deals</a>
                        <a href="/help.php" class="top-nav-link">Help & Contact</a>
                    </div>
                    <div class="top-nav-right">
                        <a href="/sell.php" class="top-nav-link sell-link">
                            <i class="fas fa-store"></i> Sell
                        </a>
                        <?php if (Session::isLoggedIn()): ?>
                            <div class="account-dropdown">
                                <a href="#" class="top-nav-link user-menu-toggle">
                                    <img src="<?php echo getUserAvatar($cu, 24); ?>" alt="Avatar" class="user-avatar">
                                    <?php echo htmlspecialchars(($cu['first_name'] ?? 'User')); ?> <span class="dropdown-arrow">▾</span>
                                </a>
                                <div class="user-dropdown-menu" style="display: none;">
                                    <a href="/account.php" class="dropdown-item">
                                        <i class="fas fa-user"></i> My Account
                                    </a>
                                    <a href="/account.php?tab=orders" class="dropdown-item">
                                        <i class="fas fa-box"></i> Orders
                                    </a>
                                    <a href="/wishlist.php" class="dropdown-item">
                                        <i class="fas fa-heart"></i> Watchlist
                                    </a>
                                    <?php if (hasRole('vendor')): ?>
                                        <a href="/seller-center.php" class="dropdown-item">
                                            <i class="fas fa-store"></i> Seller Center
                                        </a>
                                    <?php endif; ?>
                                    <?php if (hasRole('admin')): ?>
                                        <a href="/admin/index.php" class="dropdown-item">
                                            <i class="fas fa-cog"></i> Admin Panel
                                        </a>
                                    <?php endif; ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="/logout.php" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt"></i> Sign Out
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="/wishlist.php" class="top-nav-link">
                                <i class="fas fa-heart"></i> Watchlist
                            </a>
                        <?php endif; ?>
                        <a href="/notifications.php" class="notification-icon">
                            <i class="fas fa-bell"></i>
                        </a>
                        <a href="/cart.php" class="cart-icon-top">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if (isset($cart_count) && $cart_count > 0): ?>
                                <span class="cart-count"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="main-header">
            <div class="container">
                <div class="main-header-content">
                    <!-- Logo -->
                    <div class="logo-section">
                        <!-- Mobile menu toggle -->
                        <button class="mobile-menu-toggle" id="mobileMenuToggle">
                            <span class="hamburger-line"></span>
                            <span class="hamburger-line"></span>
                            <span class="hamburger-line"></span>
                        </button>
                        
                        <a href="/" class="fezamarket-logo">
                            <div class="logo-container">
                                <span class="logo-f">f</span><span class="logo-e">e</span><span class="logo-z">z</span><span class="logo-a">a</span><span class="logo-market">Market</span>
                            </div>
                        </a>
                    </div>

                    <!-- Search Section -->
                    <div class="search-section">
                        <div class="search-form-container">
                            <form class="search-form" action="/search.php" method="GET">
                                <div class="search-input-group">
                                    <select class="category-select" name="category" id="category-select">
                                        <option value="">All Categories</option>
                                        <?php
                                        $category = new Category();
                                        $categories = $category->getParents();
                                        foreach ($categories as $cat):
                                        ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" 
                                           name="q" 
                                           id="search-input" 
                                           class="search-input" 
                                           placeholder="Search for anything" 
                                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
                                           autocomplete="off">
                                    <button type="submit" class="search-button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div class="search-suggestions" id="search-suggestions" style="display: none;"></div>
                            </form>
                            <a href="/search/advanced.php" class="advanced-search">Advanced</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Navigation -->
        <div class="category-nav">
            <div class="container">
                <nav class="category-nav-content">
                    <a href="/live.php" class="category-nav-item">FezaMarket Live</a>
                    <a href="/saved.php" class="category-nav-item">Saved</a>
                    <a href="/category.php?name=electronics" class="category-nav-item">Electronics</a>
                    <a href="/category.php?name=motors" class="category-nav-item">Motors</a>
                    <a href="/category.php?name=fashion" class="category-nav-item">Fashion</a>
                    <a href="/category.php?name=collectibles" class="category-nav-item">Collectibles and Art</a>
                    <a href="/category.php?name=sports" class="category-nav-item">Sports</a>
                    <a href="/category.php?name=health-beauty" class="category-nav-item">Health & Beauty</a>
                    <a href="/category.php?name=industrial" class="category-nav-item">Industrial equipment</a>
                    <a href="/category.php?name=home-garden" class="category-nav-item">Home & Garden</a>
                    <a href="/deals.php" class="category-nav-item">Deals</a>
                    <a href="/sell.php" class="category-nav-item">Sell</a>
                </nav>
            </div>
        </div>
        
        <!-- Mobile Navigation Overlay -->
        <div class="mobile-nav-overlay" id="mobileNavOverlay">
            <div class="mobile-nav-content">
                <div class="mobile-nav-header">
                    <div class="mobile-nav-title">Menu</div>
                    <button class="mobile-nav-close" id="mobileNavClose">&times;</button>
                </div>
                
                <div class="mobile-nav-search">
                    <form action="/search.php" method="GET" class="mobile-search-form">
                        <input type="text" name="q" placeholder="Search for anything" class="mobile-search-input">
                        <button type="submit" class="mobile-search-btn">?</button>
                    </form>
                </div>
                
                <div class="mobile-nav-sections">
                    <!-- Account Section -->
                    <?php if (Session::isLoggedIn()): ?>
                        <div class="mobile-nav-section">
                            <div class="mobile-user-info">
                                <img src="<?php echo getUserAvatar($cu, 40); ?>" alt="Avatar" class="mobile-user-avatar">
                                <div class="mobile-user-details">
                                    <?php
                                        $fullName = trim(($cu['first_name'] ?? '') . ' ' . ($cu['last_name'] ?? ''));
                                    ?>
                                    <div class="mobile-user-name"><?php echo htmlspecialchars($fullName ?: 'User'); ?></div>
                                    <div class="mobile-user-email"><?php echo htmlspecialchars($cu['email'] ?? ''); ?></div>
                                </div>
                            </div>
                            <div class="mobile-nav-divider"></div>
                            <a href="/account.php" class="mobile-nav-link">Dashboard</a>
                            <a href="/account.php?tab=orders" class="mobile-nav-link">My Orders</a>
                            <a href="/wishlist.php" class="mobile-nav-link">Watchlist</a>
                            <a href="/cart.php" class="mobile-nav-link">
                                Cart
                                <?php if (isset($cart_count) && $cart_count > 0): ?>
                                    <span class="mobile-cart-count"><?php echo $cart_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <?php if (hasRole('vendor')): ?>
                                <a href="/seller-center.php" class="mobile-nav-link">Seller Center</a>
                            <?php else: ?>
                                <a href="<?php echo sellerUrl('register'); ?>" class="mobile-nav-link">Start Selling</a>
                            <?php endif; ?>
                            <?php if (hasRole('admin')): ?>
                                <a href="/admin/index.php" class="mobile-nav-link">Admin Panel</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="mobile-nav-section">
                            <a href="/login.php" class="mobile-nav-link primary">Sign In</a>
                            <a href="/register.php" class="mobile-nav-link">Register</a>
                            <div class="mobile-nav-divider"></div>
                            <a href="/cart.php" class="mobile-nav-link">Cart</a>
                            <a href="/wishlist.php" class="mobile-nav-link">Watchlist</a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Categories Section -->
                    <div class="mobile-nav-section">
                        <div class="mobile-nav-section-title">Shop by Category</div>
                        <a href="/category.php?name=electronics" class="mobile-nav-link">Electronics</a>
                        <a href="/category.php?name=fashion" class="mobile-nav-link">Fashion</a>
                        <a href="/category.php?name=home-garden" class="mobile-nav-link">Home & Garden</a>
                        <a href="/category.php?name=sports" class="mobile-nav-link">Sports</a>
                        <a href="/category.php?name=health-beauty" class="mobile-nav-link">Health & Beauty</a>
                        <a href="/category.php?name=motors" class="mobile-nav-link">Motors</a>
                    </div>
                    
                    <!-- Quick Links Section -->
                    <div class="mobile-nav-section">
                        <div class="mobile-nav-section-title">Quick Links</div>
                        <a href="/deals.php" class="mobile-nav-link">Daily Deals</a>
                        <a href="/live.php" class="mobile-nav-link">FezaMarket Live</a>
                        <a href="/brands.php" class="mobile-nav-link">Brand Outlet</a>
                        <a href="/gift-cards.php" class="mobile-nav-link">Gift Cards</a>
                        <a href="/help.php" class="mobile-nav-link">Help & Contact</a>
                    </div>
                    
                    <!-- Bottom Section -->
                    <div class="mobile-nav-section">
                        <?php if (Session::isLoggedIn()): ?>
                            <div class="mobile-nav-divider"></div>
                            <a href="/account.php?tab=security" class="mobile-nav-link">Account Settings</a>
                            <a href="/logout.php" class="mobile-nav-link">Sign Out</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mobileNavOverlay = document.getElementById('mobileNavOverlay');
            const mobileNavClose = document.getElementById('mobileNavClose');
            
            // Open mobile menu
            mobileMenuToggle.addEventListener('click', function() {
                mobileNavOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
            
            // Close mobile menu
            function closeMobileMenu() {
                mobileNavOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            mobileNavClose.addEventListener('click', closeMobileMenu);
            
            // Close on overlay click
            mobileNavOverlay.addEventListener('click', function(e) {
                if (e.target === mobileNavOverlay) {
                    closeMobileMenu();
                }
            });
            
            // Close on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && mobileNavOverlay.classList.contains('active')) {
                    closeMobileMenu();
                }
            });
            
            // Existing user menu functionality
            const userMenuToggle = document.querySelector('.user-menu-toggle');
            const userDropdownMenu = document.querySelector('.user-dropdown-menu');
            
            if (userMenuToggle && userDropdownMenu) {
                userMenuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isVisible = userDropdownMenu.style.display === 'block';
                    userDropdownMenu.style.display = isVisible ? 'none' : 'block';
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userMenuToggle.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                        userDropdownMenu.style.display = 'none';
                    }
                });
            }
        });
    </script>
    
    <style>
        /* Mobile Menu Styles */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            margin-right: 12px;
        }
        
        .hamburger-line {
            width: 24px;
            height: 3px;
            background: #333;
            margin: 2px 0;
            transition: 0.3s;
            border-radius: 2px;
        }
        
        .mobile-nav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .mobile-nav-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .mobile-nav-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 320px;
            height: 100%;
            background: white;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .mobile-nav-overlay.active .mobile-nav-content {
            transform: translateX(0);
        }
        
        .mobile-nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .mobile-nav-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .mobile-nav-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
            padding: 4px;
        }
        
        .mobile-nav-search {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .mobile-search-form {
            display: flex;
            gap: 8px;
        }
        
        .mobile-search-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .mobile-search-btn {
            padding: 12px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .mobile-nav-sections {
            flex: 1;
            padding: 16px 0;
        }
        
        .mobile-nav-section {
            padding: 0 20px 20px;
        }
        
        .mobile-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 0;
        }
        
        .mobile-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        .mobile-user-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .mobile-user-email {
            font-size: 14px;
            color: #6b7280;
        }
        
        .mobile-nav-section-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .mobile-nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            color: #374151;
            text-decoration: none;
            border-bottom: 1px solid #f3f4f6;
            transition: color 0.2s ease;
        }
        
        .mobile-nav-link:hover {
            color: #3b82f6;
        }
        
        .mobile-nav-link.primary {
            color: #3b82f6;
            font-weight: 600;
        }
        
        .mobile-nav-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 12px 0;
        }
        
        .mobile-cart-count {
            background: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: 600;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .top-nav {
                display: none;
            }
            
            .category-nav {
                display: none;
            }
            
            .mobile-menu-toggle {
                display: flex;
            }
            
            .search-section {
                flex: 1;
                margin: 0 16px;
            }
            
            .search-form-container {
                width: 100%;
            }
            
            .category-select {
                display: none;
            }
            
            .search-input-group {
                display: flex;
            }
            
            .search-input {
                flex: 1;
                border-radius: 8px 0 0 8px;
            }
            
            .search-button {
                border-radius: 0 8px 8px 0;
                padding: 0 16px;
            }
            
            .advanced-search {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .main-header-content {
                padding: 12px 0;
            }
            
            .fezamarket-logo {
                font-size: 20px;
            }
            
            .search-input {
                font-size: 16px; /* Prevent zoom on iOS */
            }
        }
        
        @media (max-width: 480px) {
            .mobile-nav-content {
                width: 280px;
            }
            
            .mobile-nav-sections {
                padding: 12px 0;
            }
            
            .mobile-nav-section {
                padding: 0 16px 16px;
            }
        }
    </style>
    
    <main class="main-content"><?php