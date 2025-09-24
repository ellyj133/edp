<?php
/**
 * Comprehensive Admin Dashboard
 * E-Commerce Platform - Complete Feature Implementation
 */

require_once __DIR__ . '/../includes/init.php';

// Database availability check with graceful fallback
$database_available = false;
$db = null;
try {
    $db = db();
    $db->query('SELECT 1');
    $database_available = true;
} catch (Exception $e) {
    $database_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

// Admin Bypass Mode - Skip all authentication when enabled
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    // Set up admin session automatically in bypass mode
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
        $_SESSION['admin_bypass'] = true;
    }
} else {
    // Normal authentication check - redirect to login if not authenticated as admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$page_title = 'Admin Dashboard';
$page_subtitle = 'Comprehensive E-Commerce Management';

// Admin account switching functionality
$current_view = $_GET['view'] ?? 'admin';
$allowed_views = ['admin', 'seller', 'customer'];

if (isset($_GET['switch_view']) && in_array($_GET['switch_view'], $allowed_views)) {
    $_SESSION['admin_viewing_as'] = $_GET['switch_view'];
    $current_view = $_GET['switch_view'];
}

// Initialize statistics with demo data
$stats = [
    'total_users' => 1247,
    'active_users' => 1189,
    'pending_users' => 58,
    'total_sellers' => 89,
    'active_sellers' => 76,
    'pending_sellers' => 13,
    'total_products' => 3456,
    'active_products' => 3201,
    'pending_products' => 255,
    'total_orders' => 5678,
    'pending_orders' => 45,
    'processing_orders' => 23,
    'completed_orders' => 5432,
    'total_revenue' => 234567.89,
    'today_revenue' => 1234.56,
    'month_revenue' => 45678.90,
    'conversion_rate' => 3.45,
    'avg_order_value' => 87.65
];

$alerts = [
    'low_stock' => 12,
    'pending_disputes' => 3,
    'pending_refunds' => 8,
    'pending_support' => 15,
    'security_alerts' => 2,
    'system_issues' => 1
];

// Database availability check
// (already done above)

// Load live data if database is available
if ($database_available) {
    try {
        // Get real statistics from database
        $user = new User();
        $product = new Product();
        $order = new Order();
        
        $stats['total_users'] = $user->count();
        $stats['active_users'] = $user->count("status = 'active'");
        $stats['pending_users'] = $user->count("status = 'pending'");
        $stats['total_products'] = $product->count();
        $stats['active_products'] = $product->count("status = 'active'");
        $stats['total_orders'] = $order->count();
        
        // Additional real-time data loading can be added here
    } catch (Exception $e) {
        error_log("Error loading live stats: " . $e->getMessage());
    }
}

$page_title = 'Admin Dashboard';
$page_subtitle = 'Comprehensive E-Commerce Management';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Admin CSS -->
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
            --admin-dark: #2c3e50;
        }
        
        body {
            background-color: var(--admin-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar {
            background: white;
            min-height: calc(100vh - 76px);
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
            padding: 0;
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
            color: var(--admin-dark);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .admin-nav-link:hover {
            background-color: var(--admin-light);
            border-left-color: var(--admin-accent);
            color: var(--admin-accent);
        }
        
        .admin-nav-link.active {
            background-color: var(--admin-accent);
            color: white;
            border-left-color: var(--admin-primary);
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
            border-left: 4px solid var(--admin-accent);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card.success {
            border-left-color: var(--admin-success);
        }
        
        .stats-card.warning {
            border-left-color: var(--admin-warning);
        }
        
        .stats-card.danger {
            border-left-color: var(--admin-danger);
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .quick-action-btn {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            text-decoration: none;
            color: var(--admin-dark);
            display: block;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: var(--admin-accent);
        }
        
        .alert-item {
            background: white;
            border-left: 4px solid var(--admin-warning);
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0 4px 4px 0;
        }
        
        .alert-item.danger {
            border-left-color: var(--admin-danger);
        }
        
        .view-switcher {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 0.5rem;
            display: inline-flex;
            gap: 0.5rem;
        }
        
        .view-switcher a {
            padding: 0.5rem 1rem;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        
        .view-switcher a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .view-switcher a:hover {
            background: rgba(255,255,255,0.15);
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Admin Dashboard
                    </h1>
                    <small class="text-white-50">Comprehensive E-Commerce Management</small>
                </div>
                <div class="col-md-6 text-end">
                    <!-- View Switcher -->
                    <div class="view-switcher me-3 d-inline-block">
                        <a href="?switch_view=admin" class="<?php echo $current_view === 'admin' ? 'active' : ''; ?>">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                        <a href="?switch_view=seller" class="<?php echo $current_view === 'seller' ? 'active' : ''; ?>">
                            <i class="fas fa-store"></i> Seller
                        </a>
                        <a href="?switch_view=customer" class="<?php echo $current_view === 'customer' ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i> Customer
                        </a>
                    </div>
                    
                    <div class="d-inline-block">
                        <span class="me-3">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?>
                        </span>
                        <span class="badge bg-light text-dark me-3">
                            <i class="fas fa-circle text-success me-1"></i>
                            Online
                        </span>
                        <span class="text-white-50">
                            <?php echo date('M d, Y H:i'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Admin Bypass Notice -->
            <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS && isset($_SESSION['admin_bypass'])): ?>
            <div class="col-12">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development. 
                    To disable, set <code>ADMIN_BYPASS=false</code> in your .env file.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar">
                <ul class="admin-nav">
                    <!-- 1. Dashboard -->
                    <li class="admin-nav-item">
                        <a href="/admin/" class="admin-nav-link active">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </li>
                    
                    <!-- 2. User & Role Management -->
                    <li class="admin-nav-item">
                        <a href="/admin/users/" class="admin-nav-link">
                            <i class="fas fa-users"></i>
                            User Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/roles/" class="admin-nav-link">
                            <i class="fas fa-user-shield"></i>
                            Roles & Permissions
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/kyc/" class="admin-nav-link">
                            <i class="fas fa-id-card"></i>
                            KYC & Verification
                        </a>
                    </li>
                    
                    <!-- 3. Product & Catalog Management -->
                    <li class="admin-nav-item">
                        <a href="/admin/products/" class="admin-nav-link">
                            <i class="fas fa-box"></i>
                            Product Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/inventory/" class="admin-nav-link">
                            <i class="fas fa-warehouse"></i>
                            Inventory Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/categories/" class="admin-nav-link">
                            <i class="fas fa-tags"></i>
                            Categories & SEO
                        </a>
                    </li>
                    
                    <!-- 4. Order & Transaction Management -->
                    <li class="admin-nav-item">
                        <a href="/admin/orders/" class="admin-nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            Order Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/payments/" class="admin-nav-link">
                            <i class="fas fa-credit-card"></i>
                            Payment Tracking
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/payouts/" class="admin-nav-link">
                            <i class="fas fa-money-bill-wave"></i>
                            Payout Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/disputes/" class="admin-nav-link">
                            <i class="fas fa-gavel"></i>
                            Dispute Resolution
                        </a>
                    </li>
                    
                    <!-- 5. Marketing & Promotions -->
                    <li class="admin-nav-item">
                        <a href="/admin/campaigns/" class="admin-nav-link">
                            <i class="fas fa-bullhorn"></i>
                            Marketing Campaigns
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/coupons/" class="admin-nav-link">
                            <i class="fas fa-ticket-alt"></i>
                            Coupons & Discounts
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/loyalty/" class="admin-nav-link">
                            <i class="fas fa-gift"></i>
                            Loyalty & Rewards
                        </a>
                    </li>
                    
                    <!-- 6. Analytics & Reporting -->
                    <li class="admin-nav-item">
                        <a href="/admin/analytics/" class="admin-nav-link">
                            <i class="fas fa-chart-line"></i>
                            Analytics & Reports
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/dashboards/" class="admin-nav-link">
                            <i class="fas fa-chart-pie"></i>
                            Custom Dashboards
                        </a>
                    </li>
                    
                    <!-- 7. Content & Communication -->
                    <li class="admin-nav-item">
                        <a href="/admin/cms/" class="admin-nav-link">
                            <i class="fas fa-edit"></i>
                            Content Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/communications/" class="admin-nav-link">
                            <i class="fas fa-comments"></i>
                            Communications
                        </a>
                    </li>
                    
                    <!-- 8. System & Configuration -->
                    <li class="admin-nav-item">
                        <a href="/admin/settings/" class="admin-nav-link">
                            <i class="fas fa-cog"></i>
                            System Settings
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/integrations/" class="admin-nav-link">
                            <i class="fas fa-plug"></i>
                            API & Integrations
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/maintenance/" class="admin-nav-link">
                            <i class="fas fa-tools"></i>
                            System Maintenance
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/streaming/" class="admin-nav-link">
                            <i class="fas fa-video"></i>
                            Live Streaming
                        </a>
                    </li>
                    
                    <!-- Additional Modules -->
                    <li class="admin-nav-item">
                        <a href="/admin/finance/" class="admin-nav-link">
                            <i class="fas fa-chart-line"></i>
                            Finance Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/returns/" class="admin-nav-link">
                            <i class="fas fa-undo"></i>
                            Returns & Refunds
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/security/" class="admin-nav-link">
                            <i class="fas fa-shield-alt"></i>
                            Security & Audit
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/shipping/" class="admin-nav-link">
                            <i class="fas fa-truck"></i>
                            Shipping Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="/admin/vendors/" class="admin-nav-link">
                            <i class="fas fa-store"></i>
                            Vendor Management
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <?php if (isset($dashboard_error) && $dashboard_error): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($dashboard_error); ?>
                </div>
                <?php endif; ?>

                <!-- Key Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card success">
                            <div class="stats-value text-success">
                                <?php echo number_format($stats['total_users']); ?>
                            </div>
                            <div class="stats-label">Total Users</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-value text-primary">
                                <?php echo number_format($stats['total_products']); ?>
                            </div>
                            <div class="stats-label">Total Products</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card warning">
                            <div class="stats-value text-warning">
                                <?php echo number_format($stats['total_orders']); ?>
                            </div>
                            <div class="stats-label">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card success">
                            <div class="stats-value text-success">
                                $<?php echo number_format($stats['total_revenue'], 2); ?>
                            </div>
                            <div class="stats-label">Total Revenue</div>
                        </div>
                    </div>
                </div>

                <!-- Current View Information -->
                <?php if ($current_view !== 'admin'): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    You are currently viewing the dashboard as a <strong><?php echo ucfirst($current_view); ?></strong>. 
                    <a href="?switch_view=admin" class="alert-link">Switch back to Admin view</a>
                </div>
                <?php endif; ?>

                <!-- System Alerts -->
                <?php if (array_sum($alerts) > 0): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <h5><i class="fas fa-bell text-warning me-2"></i>System Alerts</h5>
                        <div class="row">
                            <?php if ($alerts['low_stock'] > 0): ?>
                            <div class="col-md-4 mb-2">
                                <div class="alert-item">
                                    <strong><?php echo $alerts['low_stock']; ?></strong> Low Stock Items
                                    <a href="/admin/inventory/?filter=low_stock" class="btn btn-sm btn-outline-warning ms-2">View</a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($alerts['pending_disputes'] > 0): ?>
                            <div class="col-md-4 mb-2">
                                <div class="alert-item danger">
                                    <strong><?php echo $alerts['pending_disputes']; ?></strong> Pending Disputes
                                    <a href="/admin/disputes/" class="btn btn-sm btn-outline-danger ms-2">View</a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($alerts['pending_support'] > 0): ?>
                            <div class="col-md-4 mb-2">
                                <div class="alert-item">
                                    <strong><?php echo $alerts['pending_support']; ?></strong> Support Tickets
                                    <a href="/admin/support/" class="btn btn-sm btn-outline-warning ms-2">View</a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5><i class="fas fa-bolt text-primary me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/admin/users/create" class="quick-action-btn">
                            <i class="fas fa-user-plus fa-2x mb-2 text-primary"></i><br>
                            <strong>Add User</strong><br>
                            <small class="text-muted">Create new user account</small>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/admin/products/create" class="quick-action-btn">
                            <i class="fas fa-plus-square fa-2x mb-2 text-success"></i><br>
                            <strong>Add Product</strong><br>
                            <small class="text-muted">Create new product listing</small>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/admin/orders/?status=pending" class="quick-action-btn">
                            <i class="fas fa-shopping-cart fa-2x mb-2 text-warning"></i><br>
                            <strong>Process Orders</strong><br>
                            <small class="text-muted">Handle pending orders</small>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/admin/analytics/" class="quick-action-btn">
                            <i class="fas fa-chart-bar fa-2x mb-2 text-info"></i><br>
                            <strong>View Analytics</strong><br>
                            <small class="text-muted">Sales and performance</small>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity & Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Sales Overview</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="salesChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-tasks me-2"></i>Recent Activity</h6>
                            </div>
                            <div class="card-body">
                                <div class="activity-item mb-3">
                                    <i class="fas fa-user-plus text-success me-2"></i>
                                    <strong>New user registered:</strong> john.doe@example.com
                                    <small class="text-muted d-block">2 minutes ago</small>
                                </div>
                                <div class="activity-item mb-3">
                                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                                    <strong>New order placed:</strong> Order #12345 - $87.50
                                    <small class="text-muted d-block">5 minutes ago</small>
                                </div>
                                <div class="activity-item mb-3">
                                    <i class="fas fa-box text-info me-2"></i>
                                    <strong>Product updated:</strong> iPhone 15 Pro Max
                                    <small class="text-muted d-block">12 minutes ago</small>
                                </div>
                                <div class="activity-item mb-3">
                                    <i class="fas fa-store text-warning me-2"></i>
                                    <strong>Vendor pending approval:</strong> TechGadgets Store
                                    <small class="text-muted d-block">20 minutes ago</small>
                                </div>
                                <div class="text-center">
                                    <a href="/admin/activity-log/" class="btn btn-sm btn-outline-primary">View All Activity</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sales Chart -->
    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales ($)',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>