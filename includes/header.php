<?php
/**
 * Main Site Header - eBay Style Layout
 * Complete header implementation matching eBay design
 */

// Ensure user session and functions are available
if (!function_exists('Session')) {
    require_once __DIR__ . '/functions.php';
}
if (!function_exists('getCurrentUserRole')) {
    require_once __DIR__ . '/functions.php';
}

$isLoggedIn = class_exists('Session') ? Session::isLoggedIn() : false;
$userRole = getCurrentUserRole();
$userName = $isLoggedIn ? (Session::get('user_name') ?? Session::get('email')) : null;

$page_title = $page_title ?? 'FezaMarket - Online Marketplace';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($meta_description ?? 'Buy and sell electronics, cars, fashion apparel, collectibles, sporting goods, digital cameras, baby items, coupons, and everything else on FezaMarket.'); ?>">
    <meta name="keywords" content="buy, sell, auction, online marketplace, electronics, fashion, home, garden">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/css/styles.css">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Arial:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;
            background-color: #ffffff;
        }
        
        /* Top Header Bar */
        .top-header {
            background-color: #f7f7f7;
            border-bottom: 1px solid #e5e5e5;
            padding: 8px 0;
            font-size: 13px;
        }
        
        .top-header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .top-left-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .top-right-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .top-header a {
            color: #0654ba;
            text-decoration: none;
            font-weight: normal;
        }
        
        .top-header a:hover {
            text-decoration: underline;
        }
        
        .greeting {
            color: #333;
        }
        
        /* Dropdown Styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border: 1px solid #ccc;
            border-radius: 4px;
            top: 100%;
            right: 0;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-content a {
            color: #333;
            padding: 8px 12px;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        
        .dropdown-content a:hover {
            background-color: #f0f0f0;
        }
        
        /* Main Header */
        .main-header {
            background-color: white;
            padding: 12px 0;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .main-header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 20px;
        }
        
        /* Logo */
        .logo {
            font-size: 32px;
            font-weight: bold;
            text-decoration: none;
            letter-spacing: -1px;
            font-family: Arial, sans-serif;
        }
        
        .logo .e1 { color: #e53238; }
        .logo .b { color: #0064d2; }
        .logo .a { color: #f5af02; }
        .logo .y { color: #86b817; }
        
        /* Category Dropdown */
        .category-dropdown {
            position: relative;
            background-color: white;
            border: 2px solid #767676;
            border-radius: 4px 0 0 4px;
            padding: 11px 35px 11px 12px;
            cursor: pointer;
            font-size: 14px;
            color: #333;
            white-space: nowrap;
            min-width: 140px;
        }
        
        .category-dropdown:after {
            content: '▼';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
            color: #767676;
        }
        
        /* Search Container */
        .search-container {
            flex: 1;
            display: flex;
            max-width: 800px;
            position: relative;
        }
        
        .search-input {
            flex: 1;
            border: 2px solid #767676;
            border-left: none;
            border-right: none;
            padding: 11px 12px;
            font-size: 16px;
            outline: none;
        }
        
        .search-input:focus {
            border-color: #0064d2;
        }
        
        .search-category-dropdown {
            background-color: #f7f7f7;
            border: 2px solid #767676;
            border-left: none;
            border-right: none;
            padding: 11px 35px 11px 12px;
            cursor: pointer;
            font-size: 14px;
            color: #333;
            position: relative;
            white-space: nowrap;
            min-width: 140px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }
        
        .search-category-dropdown:after {
            content: '▼';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
            color: #767676;
            pointer-events: none;
        }
        
        .search-btn {
            background: linear-gradient(135deg, #4285f4, #1a73e8);
            border: 2px solid #1a73e8;
            border-radius: 0 4px 4px 0;
            color: white;
            padding: 11px 24px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .search-btn:hover {
            background: linear-gradient(135deg, #3367d6, #1557b0);
        }
        
        .advanced-link {
            color: #0654ba;
            text-decoration: none;
            font-size: 13px;
            margin-left: 12px;
        }
        
        .advanced-link:hover {
            text-decoration: underline;
        }
        
        /* Header Icons */
        .header-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-icon {
            color: #333;
            font-size: 20px;
            text-decoration: none;
            position: relative;
        }
        
        .header-icon:hover {
            color: #0654ba;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e53238;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Navigation Bar */
        .nav-bar {
            background-color: white;
            border-bottom: 1px solid #e5e5e5;
            padding: 0;
        }
        
        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 0;
        }
        
        .nav-links li {
            position: relative;
        }
        
        .nav-links a {
            display: block;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .nav-links a:hover,
        .nav-links a.active {
            color: #0654ba;
            border-bottom-color: #0654ba;
        }
        
        /* Category dropdown content */
        .category-dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 220px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border: 1px solid #ccc;
            border-radius: 4px;
            top: 100%;
            left: 0;
        }
        
        .category-dropdown:hover .category-dropdown-content {
            display: block;
        }
        
        .category-dropdown-content a {
            color: #333;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .category-dropdown-content a:hover {
            background-color: #f0f0f0;
            color: #0654ba;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .main-header-content {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .search-container {
                order: 3;
                width: 100%;
                max-width: none;
            }
            
            .category-dropdown {
                display: none;
            }
            
            .top-left-links,
            .top-right-links {
                gap: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .top-header {
                font-size: 12px;
            }
            
            .top-left-links,
            .top-right-links {
                gap: 10px;
            }
            
            .top-left-links > *:nth-child(n+3),
            .top-right-links > *:nth-child(n+3) {
                display: none;
            }
            
            .nav-links {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .nav-links a {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            .header-icons {
                gap: 15px;
            }
            
            .logo {
                font-size: 28px;
            }
        }
        
        /* Search suggestions dropdown */
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ccc;
            border-top: none;
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        
        .search-suggestion-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .search-suggestion-item:hover {
            background-color: #f7f7f7;
        }
    </style>
</head>
<body>
    <!-- Top Header Bar -->
    <div class="top-header">
        <div class="top-header-content">
            <div class="top-left-links">
                <?php if ($isLoggedIn): ?>
                    <span class="greeting">Hi! <strong><?php echo htmlspecialchars($userName ?? 'User'); ?></strong></span>
                <?php else: ?>
                    <span class="greeting">Hi! <a href="/login.php">Sign in</a> or <a href="/register.php">register</a></span>
                <?php endif; ?>
                <a href="/deals.php">Daily Deals</a>
                <a href="/brands.php">Brand Outlet</a>
                <a href="/gift-cards.php">Gift Cards</a>
                <a href="/help.php">Help & Contact</a>
            </div>
            <div class="top-right-links">
                <a href="/shipping.php">Ship to</a>
                <?php if ($userRole === 'seller' || $userRole === 'admin'): ?>
                    <a href="/seller/">Sell</a>
                <?php else: ?>
                    <a href="/sell.php">Sell</a>
                <?php endif; ?>
                
                <?php if ($isLoggedIn): ?>
                    <div class="dropdown">
                        <a href="/saved.php">Watchlist ▼</a>
                        <div class="dropdown-content">
                            <a href="/saved.php?tab=watching">Watch list</a>
                            <a href="/saved.php?tab=recently-viewed">Recently viewed</a>
                            <a href="/saved.php?tab=saved-searches">Saved searches</a>
                            <a href="/saved.php?tab=saved-sellers">Saved sellers</a>
                        </div>
                    </div>
                    <div class="dropdown">
                        <a href="/account.php">My FezaMarket ▼</a>
                        <div class="dropdown-content">
                            <a href="/account.php">Summary</a>
                            <a href="/account.php?section=recently-viewed">Recently Viewed</a>
                            <a href="/account.php?section=bids">Bids/Offers</a>
                            <a href="/saved.php">Watchlist</a>
                            <a href="/account.php?section=purchase-history">Purchase History</a>
                            <a href="/account.php?section=buy-again">Buy Again</a>
                            <?php if ($userRole === 'seller' || $userRole === 'admin'): ?>
                                <a href="/seller/">Selling</a>
                            <?php endif; ?>
                            <a href="/saved.php?tab=saved-searches">Saved Searches</a>
                            <a href="/saved.php?tab=saved-sellers">Saved Sellers</a>
                            <a href="/messages.php">Messages</a>
                            <a href="/collection.php">Collection beta</a>
                            <hr style="margin: 4px 0; border: none; border-top: 1px solid #e0e0e0;">
                            <a href="/account.php?section=settings">Account settings</a>
                            <a href="/logout.php">Sign out</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/saved.php">Watchlist</a>
                    <a href="/login.php">My FezaMarket</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <div class="main-header">
        <div class="main-header-content">
            <a href="/" class="logo">
                <span class="e1">f</span><span class="b">e</span><span class="a">z</span><span class="y">a</span>
            </a>
            
            <div class="category-dropdown">
                Shop by category
                <div class="category-dropdown-content">
                    <a href="/category.php?cat=electronics">Electronics</a>
                    <a href="/category.php?cat=motors">Motors</a>
                    <a href="/category.php?cat=fashion">Fashion</a>
                    <a href="/category.php?cat=collectibles">Collectibles & Art</a>
                    <a href="/category.php?cat=sports">Sports</a>
                    <a href="/category.php?cat=health">Health & Beauty</a>
                    <a href="/category.php?cat=industrial">Industrial equipment</a>
                    <a href="/category.php?cat=home">Home & Garden</a>
                    <a href="/deals.php">Deals & Savings</a>
                </div>
            </div>
            
            <div class="search-container">
                <form action="/search.php" method="GET" id="searchForm" style="display: flex; width: 100%; position: relative;">
                    <input 
                        type="text" 
                        name="q" 
                        class="search-input" 
                        placeholder="Search for anything" 
                        value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                        autocomplete="off"
                        id="searchInput"
                    >
                    <select name="category" class="search-category-dropdown">
                        <option value="">All Categories</option>
                        <option value="electronics" <?php echo ($_GET['category'] ?? '') === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                        <option value="motors" <?php echo ($_GET['category'] ?? '') === 'motors' ? 'selected' : ''; ?>>Motors</option>
                        <option value="fashion" <?php echo ($_GET['category'] ?? '') === 'fashion' ? 'selected' : ''; ?>>Fashion</option>
                        <option value="collectibles" <?php echo ($_GET['category'] ?? '') === 'collectibles' ? 'selected' : ''; ?>>Collectibles</option>
                        <option value="sports" <?php echo ($_GET['category'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="health" <?php echo ($_GET['category'] ?? '') === 'health' ? 'selected' : ''; ?>>Health & Beauty</option>
                        <option value="industrial" <?php echo ($_GET['category'] ?? '') === 'industrial' ? 'selected' : ''; ?>>Industrial</option>
                        <option value="home" <?php echo ($_GET['category'] ?? '') === 'home' ? 'selected' : ''; ?>>Home & Garden</option>
                    </select>
                    <button type="submit" class="search-btn">Search</button>
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </form>
                <a href="/search.php?advanced=1" class="advanced-link">Advanced</a>
            </div>
            
            <div class="header-icons">
                <a href="/notifications.php" class="header-icon" title="Notifications">
                    <i class="far fa-bell"></i>
                    <?php 
                    // Check for unread notifications (implement this based on your notification system)
                    $unreadCount = 0; // Replace with actual count from database
                    if ($unreadCount > 0): 
                    ?>
                        <span class="notification-badge"><?php echo min($unreadCount, 99); ?></span>
                    <?php endif; ?>
                </a>
                <a href="/cart.php" class="header-icon" title="Shopping Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <?php 
                    // Check for cart items (implement this based on your cart system)
                    $cartCount = 0; // Replace with actual count from database/session
                    if ($cartCount > 0): 
                    ?>
                        <span class="notification-badge"><?php echo min($cartCount, 99); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="nav-bar">
        <div class="nav-content">
            <ul class="nav-links">
                <li><a href="/live.php">FezaMarket Live</a></li>
                <li><a href="/saved.php">Saved</a></li>
                <li><a href="/category.php?cat=electronics">Electronics</a></li>
                <li><a href="/category.php?cat=motors">Motors</a></li>
                <li><a href="/category.php?cat=fashion">Fashion</a></li>
                <li><a href="/category.php?cat=collectibles">Collectibles and Art</a></li>
                <li><a href="/category.php?cat=sports">Sports</a></li>
                <li><a href="/category.php?cat=health">Health & Beauty</a></li>
                <li><a href="/category.php?cat=industrial">Industrial equipment</a></li>
                <li><a href="/category.php?cat=home">Home & Garden</a></li>
                <li><a href="/deals.php">Deals</a></li>
                <?php if ($userRole === 'seller' || $userRole === 'admin'): ?>
                    <li><a href="/seller/">Sell</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- JavaScript for Enhanced Functionality -->
    <script>
        // Search suggestions functionality
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        
        if (searchInput && searchSuggestions) {
            let timeout;
            
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                clearTimeout(timeout);
                
                if (query.length < 2) {
                    searchSuggestions.style.display = 'none';
                    return;
                }
                
                timeout = setTimeout(() => {
                    // Simple suggestions - you can replace with actual API call
                    const suggestions = [
                        query + ' electronics',
                        query + ' deals',
                        query + ' new',
                        query + ' used',
                        query + ' accessories'
                    ];
                    
                    searchSuggestions.innerHTML = suggestions.map(suggestion => 
                        `<div class="search-suggestion-item" onclick="selectSuggestion('${suggestion.replace(/'/g, "\\'")}')">${suggestion}</div>`
                    ).join('');
                    
                    searchSuggestions.style.display = 'block';
                }, 300);
            });
            
            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                    searchSuggestions.style.display = 'none';
                }
            });
        }
        
        function selectSuggestion(suggestion) {
            if (searchInput) {
                searchInput.value = suggestion;
                searchSuggestions.style.display = 'none';
                document.getElementById('searchForm').submit();
            }
        }
        
        // Add active class to current page nav link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-links a');
            
            navLinks.forEach(link => {
                const linkPath = new URL(link.href).pathname;
                if (linkPath === currentPath || (currentPath.startsWith('/category') && link.href.includes('cat='))) {
                    link.classList.add('active');
                }
            });
        });
        
        // Mobile menu toggle (if needed)
        function toggleMobileMenu() {
            const navLinks = document.querySelector('.nav-links');
            if (navLinks) {
                navLinks.classList.toggle('mobile-active');
            }
        }
    </script>

    <!-- Main Content Container Start -->
    <div id="main-content">
        <!-- Page content will be inserted here by individual pages -->