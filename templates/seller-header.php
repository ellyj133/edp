<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title . ' - Seller Dashboard'); ?></title>
    
    <!-- Modern CSS Framework -->
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/css/styles.css">
    
    <!-- Bootstrap CSS for seller dashboard -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Preload critical assets -->
    <link rel="preload" href="/assets/js/ui.js" as="script">
    
    <!-- Favicon and meta -->
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description ?? 'Seller Dashboard - Manage Your Store on FezaMarket'); ?>">
    
    <!-- CSRF Meta Tag for AJAX -->
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
    
    <style>
        :root {
            --seller-primary: #0654ba;
            --seller-secondary: #4f46e5;
            --seller-success: #059669;
            --seller-danger: #dc2626;
            --seller-warning: #d97706;
            --seller-info: #0891b2;
        }
        
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .seller-header {
            background: linear-gradient(135deg, var(--seller-primary), var(--seller-secondary));
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .seller-navbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.75rem 0;
        }
        
        .seller-nav-link {
            color: #6b7280;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .seller-nav-link:hover,
        .seller-nav-link.active {
            color: var(--seller-primary);
            background-color: #eff6ff;
        }
        
        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-item {
            color: #6b7280;
        }
        
        .breadcrumb-item.active {
            color: #374151;
        }
        
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background-color: var(--seller-primary);
            border-color: var(--seller-primary);
        }
        
        .btn-primary:hover {
            background-color: #0c4a9a;
            border-color: #0c4a9a;
        }
        
        /* User dropdown styles */
        .account-dropdown {
            position: relative;
        }
        
        .user-menu-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .user-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            z-index: 1000;
            display: none;
        }
        
        .user-dropdown-menu.show {
            display: block;
        }
        
        .user-dropdown-item {
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .user-dropdown-item:hover {
            background-color: #f9fafb;
            color: var(--seller-primary);
        }
        
        .user-dropdown-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Seller Dashboard Header -->
    <div class="seller-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h4 class="mb-0 me-4">
                        <i class="fas fa-store me-2"></i>
                        Seller Dashboard
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/seller-center.php" class="text-white-50">Dashboard</a></li>
                            <?php if (isset($breadcrumb_items) && is_array($breadcrumb_items)): ?>
                                <?php foreach ($breadcrumb_items as $item): ?>
                                    <?php if (isset($item['url'])): ?>
                                        <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($item['url']); ?>" class="text-white-50"><?php echo htmlspecialchars($item['title']); ?></a></li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item active text-white"><?php echo htmlspecialchars($item['title']); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ol>
                    </nav>
                </div>
                
                <!-- User Menu -->
                <div class="account-dropdown">
                    <?php if (isset($current_user) && $current_user): ?>
                        <a href="#" class="user-menu-toggle" data-bs-toggle="dropdown">
                            <img src="<?php echo htmlspecialchars($current_user['avatar'] ?? '/images/default-avatar.png'); ?>" 
                                 alt="User Avatar" class="user-avatar">
                            <span><?php echo htmlspecialchars($current_user['username'] ?? $current_user['email'] ?? 'Seller'); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="user-dropdown-menu">
                            <a href="/seller/profile.php" class="user-dropdown-item">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                            <a href="/seller-center.php" class="user-dropdown-item">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a href="/seller/products/" class="user-dropdown-item">
                                <i class="fas fa-box me-2"></i>Products
                            </a>
                            <a href="/seller/orders.php" class="user-dropdown-item">
                                <i class="fas fa-shopping-cart me-2"></i>Orders
                            </a>
                            <a href="/account.php" class="user-dropdown-item">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                            <a href="/logout.php" class="user-dropdown-item">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="/login.php" class="btn btn-outline-light btn-sm">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Seller Navigation -->
    <div class="seller-navbar">
        <div class="container">
            <nav class="d-flex gap-3">
                <a href="/seller-center.php" class="seller-nav-link <?php echo (basename($_SERVER['REQUEST_URI']) == 'seller-center.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a href="/seller/products/" class="seller-nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/seller/products/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-box me-1"></i>Products
                </a>
                <a href="/seller/orders.php" class="seller-nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/seller/orders') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart me-1"></i>Orders
                </a>
                <a href="/seller/analytics.php" class="seller-nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/seller/analytics') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line me-1"></i>Analytics
                </a>
                <a href="/seller/profile.php" class="seller-nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/seller/profile') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-store me-1"></i>Store Profile
                </a>
            </nav>
        </div>
    </div>
    
    <!-- Main Content Container -->
    <div class="container mt-4">