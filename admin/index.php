<?php
/**
 * Comprehensive Admin Dashboard - Now uses unified header/footer
 */

require_once __DIR__ . '/../includes/init.php';

// Database availability check
$database_available = false;
try {
    $db = db();
    $db->query('SELECT 1');
    $database_available = true;
} catch (Exception $e) {
    $database_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

// Admin authentication
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
        $_SESSION['admin_bypass'] = true;
    }
} else {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$page_title = 'Admin Dashboard - FezaMarket';
$page_subtitle = 'Comprehensive E-Commerce Management';

// Admin account switching functionality
$current_view = $_GET['view'] ?? 'admin';
$allowed_views = ['admin', 'seller', 'customer'];

if (isset($_GET['switch_view']) && in_array($_GET['switch_view'], $allowed_views)) {
    $_SESSION['admin_viewing_as'] = $_GET['switch_view'];
    $current_view = $_GET['switch_view'];
}

// Initialize statistics
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

// Load live data if database is available
if ($database_available) {
    try {
        $user = new User();
        $product = new Product();
        $order = new Order();
        
        $stats['total_users'] = $user->count();
        $stats['active_users'] = $user->count("status = 'active'");
        $stats['pending_users'] = $user->count("status = 'pending'");
        $stats['total_products'] = $product->count();
        $stats['active_products'] = $product->count("status = 'active'");
        $stats['total_orders'] = $order->count();
    } catch (Exception $e) {
        error_log("Error loading live stats: " . $e->getMessage());
    }
}

// Include unified header
include __DIR__ . '/../templates/header.php';
?>

<!-- Admin Content Wrapper -->
<div class="admin-content-wrapper">
    <div class="container-fluid">
        
        <!-- Admin Bypass Notice -->
        <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS && isset($_SESSION['admin_bypass'])): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development. 
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="admin-sidebar">
                    <ul class="admin-nav">
                        <!-- Dashboard -->
                        <li class="admin-nav-item">
                            <a href="/admin/" class="admin-nav-link active">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        
                        <!-- User Management -->
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
                        
                        <!-- Product Management -->
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
                        
                        <!-- Order Management -->
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
                            <a href="/admin/disputes/" class="admin-nav-link">
                                <i class="fas fa-gavel"></i>
                                Dispute Resolution
                            </a>
                        </li>
                        
                        <!-- Marketing -->
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
                        
                        <!-- Analytics -->
                        <li class="admin-nav-item">
                            <a href="/admin/analytics/" class="admin-nav-link">
                                <i class="fas fa-chart-line"></i>
                                Analytics & Reports
                            </a>
                        </li>
                        
                        <!-- System Settings -->
                        <li class="admin-nav-item">
                            <a href="/admin/settings/" class="admin-nav-link">
                                <i class="fas fa-cog"></i>
                                System Settings
                            </a>
                        </li>
                        <li class="admin-nav-item">
                            <a href="/admin/security/" class="admin-nav-link">
                                <i class="fas fa-shield-alt"></i>
                                Security & Audit
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Admin Content -->
            <div class="col-md-9 col-lg-10">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Admin Dashboard
                        </h1>
                        <p class="text-muted">Comprehensive E-Commerce Management</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <!-- View Switcher -->
                        <div class="btn-group" role="group">
                            <a href="?switch_view=admin" class="btn btn-sm <?php echo $current_view === 'admin' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-user-shield"></i> Admin
                            </a>
                            <a href="?switch_view=seller" class="btn btn-sm <?php echo $current_view === 'seller' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-store"></i> Seller
                            </a>
                            <a href="?switch_view=customer" class="btn btn-sm <?php echo $current_view === 'customer' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-user"></i> Customer
                            </a>
                        </div>
                        
                        <span class="badge bg-success">
                            <i class="fas fa-circle me-1"></i>
                            Online
                        </span>
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

                <!-- System Alerts -->
                <?php if (array_sum($alerts) > 0): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <h5><i class="fas fa-bell text-warning me-2"></i>System Alerts</h5>
                        <div class="row">
                            <?php if ($alerts['low_stock'] > 0): ?>
                            <div class="col-md-4 mb-2">
                                <div class="alert alert-warning">
                                    <strong><?php echo $alerts['low_stock']; ?></strong> Low Stock Items
                                    <a href="/admin/inventory/?filter=low_stock" class="btn btn-sm btn-outline-warning ms-2">View</a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($alerts['pending_disputes'] > 0): ?>
                            <div class="col-md-4 mb-2">
                                <div class="alert alert-danger">
                                    <strong><?php echo $alerts['pending_disputes']; ?></strong> Pending Disputes
                                    <a href="/admin/disputes/" class="btn btn-sm btn-outline-danger ms-2">View</a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($alerts['pending_support'] > 0): ?>
                            <div class="col-md-4 mb-2">
                                <div class="alert alert-info">
                                    <strong><?php echo $alerts['pending_support']; ?></strong> Support Tickets
                                    <a href="/admin/support/" class="btn btn-sm btn-outline-info ms-2">View</a>
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
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-user-plus fa-2x mb-2 text-primary"></i>
                                <h6 class="card-title">Add User</h6>
                                <p class="card-text small">Create new user account</p>
                                <a href="/admin/users/create" class="btn btn-primary btn-sm">Create</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-plus-square fa-2x mb-2 text-success"></i>
                                <h6 class="card-title">Add Product</h6>
                                <p class="card-text small">Create new product listing</p>
                                <a href="/admin/products/create" class="btn btn-success btn-sm">Create</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-shopping-cart fa-2x mb-2 text-warning"></i>
                                <h6 class="card-title">Process Orders</h6>
                                <p class="card-text small">Handle pending orders</p>
                                <a href="/admin/orders/?status=pending" class="btn btn-warning btn-sm">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-chart-bar fa-2x mb-2 text-info"></i>
                                <h6 class="card-title">Analytics</h6>
                                <p class="card-text small">Sales and performance</p>
                                <a href="/admin/analytics/" class="btn btn-info btn-sm">View</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Activity -->
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
                                <div class="d-flex align-items-start mb-3">
                                    <i class="fas fa-user-plus text-success me-2 mt-1"></i>
                                    <div>
                                        <strong>New user registered:</strong> john.doe@example.com
                                        <small class="text-muted d-block">2 minutes ago</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start mb-3">
                                    <i class="fas fa-shopping-cart text-primary me-2 mt-1"></i>
                                    <div>
                                        <strong>New order placed:</strong> Order #12345 - $87.50
                                        <small class="text-muted d-block">5 minutes ago</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start mb-3">
                                    <i class="fas fa-box text-info me-2 mt-1"></i>
                                    <div>
                                        <strong>Product updated:</strong> iPhone 15 Pro Max
                                        <small class="text-muted d-block">12 minutes ago</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start mb-3">
                                    <i class="fas fa-store text-warning me-2 mt-1"></i>
                                    <div>
                                        <strong>Vendor pending approval:</strong> TechGadgets Store
                                        <small class="text-muted d-block">20 minutes ago</small>
                                    </div>
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
</div>

<!-- Sales Chart -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Sales ($)',
                data: [12000, 19000, 15000, 25000, 22000, 30000],
                borderColor: '#0654ba',
                backgroundColor: 'rgba(6, 84, 186, 0.1)',
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
});
</script>

<?php
// Include unified footer
include __DIR__ . '/../templates/footer.php';
?>