<?php
/**
 * Main Site Header - eBay Style Layout
 * Used across entire FezaMarket site for consistency
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/init.php';

$isLoggedIn = Session::isLoggedIn();
$currentUser = null;
if ($isLoggedIn) {
    $user = new User();
    $currentUser = $user->find(Session::getUserId());
}

$userName = $currentUser ? ($currentUser['first_name'] ?? $currentUser['username'] ?? $currentUser['email']) : null;
$userRole = getCurrentUserRole();
$cart_count = 0; // Implement your cart count logic here

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
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Arial:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js for admin pages -->
    <?php if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    
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
        
        .logo .f { color: #e53238; }
        .logo .e { color: #0064d2; }
        .logo .z { color: #f5af02; }
        .logo .a { color: #86b817; }
        
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

        /* Admin/Seller Specific Styles */
        <?php if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false): ?>
        .admin-content-wrapper {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .admin-sidebar {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 0;
            margin-bottom: 2rem;
        }
        
        .admin-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .admin-nav-item {
            border-bottom: 1px solid #eee;
        }
        
        .admin-nav-link {
            display: block;
            padding: 1rem 1.5rem;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .admin-nav-link:hover {
            background-color: #f8f9fa;
            border-left-color: #0654ba;
            color: #0654ba;
        }
        
        .admin-nav-link.active {
            background-color: #0654ba;
            color: white;
            border-left-color: #0654ba;
        }
        
        .admin-nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid #0654ba;
            margin-bottom: 1rem;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card.success { border-left-color: #28a745; }
        .stats-card.warning { border-left-color: #ffc107; }
        .stats-card.danger { border-left-color: #dc3545; }
        
        .stats-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        <?php endif; ?>
        
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
        }
        
        @media (max-width: 768px) {
            .top-header {
                font-size: 12px;
            }
            
            .nav-links {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .logo {
                font-size: 28px;
            }
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
                            <a href="/saved.php">Watchlist</a>
                            <a href="/account.php?section=purchase-history">Purchase History</a>
                            <?php if ($userRole === 'seller' || $userRole === 'admin'): ?>
                                <a href="/seller/">Selling</a>
                            <?php endif; ?>
                            <?php if ($userRole === 'admin'): ?>
                                <a href="/admin/">Admin Panel</a>
                            <?php endif; ?>
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
                <span class="f">f</span><span class="e">e</span><span class="z">z</span><span class="a">a</span>
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
                        <option value="electronics">Electronics</option>
                        <option value="motors">Motors</option>
                        <option value="fashion">Fashion</option>
                        <option value="collectibles">Collectibles</option>
                        <option value="sports">Sports</option>
                        <option value="health">Health & Beauty</option>
                        <option value="industrial">Industrial</option>
                        <option value="home">Home & Garden</option>
                    </select>
                    <button type="submit" class="search-btn">Search</button>
                </form>
                <a href="/search.php?advanced=1" class="advanced-link">Advanced</a>
            </div>
            
            <div class="header-icons">
                <a href="/notifications.php" class="header-icon" title="Notifications">
                    <i class="far fa-bell"></i>
                </a>
                <a href="/cart.php" class="header-icon" title="Shopping Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="notification-badge"><?php echo min($cart_count, 99); ?></span>
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

    <!-- Main Content Container Start -->
    <div id="main-content">
        <!-- Page content will be inserted here by individual pages -->