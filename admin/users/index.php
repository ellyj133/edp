<?php
/**
 * User Management - Admin Module
 * Comprehensive User & Role Management System
 */

// Global admin page requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';

// Initialize with graceful fallback
require_once __DIR__ . '/../../includes/init.php';

// Database graceful fallback
$database_available = false;
$pdo = null;
try {
    $pdo = db();
    $pdo->query('SELECT 1');
    $database_available = true;
} catch (Exception $e) {
    $database_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

requireAdminAuth();
checkPermission('users.view');

$page_title = 'User Management';
$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? null;

// Initialize variables
$users = [];
$total_users = 0;
$error_message = '';
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'pending_users' => 0,
    'seller_users' => 0,
    'customer_users' => 0
];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
    } else {
        try {
            switch ($_POST['action']) {
                case 'create_user':
                    // Handle user creation
                    $message = 'User created successfully (demo mode)';
                    break;
                    
                case 'update_user':
                    // Handle user update
                    $message = 'User updated successfully (demo mode)';
                    break;
                    
                case 'suspend_user':
                    // Handle user suspension
                    $message = 'User suspended successfully (demo mode)';
                    break;
                    
                case 'activate_user':
                    // Handle user activation
                    $message = 'User activated successfully (demo mode)';
                    break;
                    
                case 'delete_user':
                    // Handle user deletion
                    $message = 'User deleted successfully (demo mode)';
                    break;
            }
            $_SESSION['success_message'] = $message ?? 'Action completed successfully';
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Get data from database or use fallback
if ($database_available) {
    try {
        // Get users with proper error handling
        $usersQuery = "
            SELECT u.*, 
                   COALESCE(u.role, 'customer') as role,
                   COALESCE(u.status, 'active') as status,
                   COALESCE(u.created_at, NOW()) as created_at,
                   COALESCE(u.last_login_at, u.created_at) as last_login_at
            FROM users u 
            ORDER BY u.created_at DESC 
            LIMIT 100
        ";
        
        $usersStmt = $pdo->prepare($usersQuery);
        $usersStmt->execute();
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
        $total_users = count($users);
        
        // Calculate stats
        foreach ($users as $user) {
            $stats['total_users']++;
            if (($user['status'] ?? 'active') === 'active') {
                $stats['active_users']++;
            } elseif (($user['status'] ?? 'active') === 'pending') {
                $stats['pending_users']++;
            }
            
            if (($user['role'] ?? 'customer') === 'seller') {
                $stats['seller_users']++;
            } else {
                $stats['customer_users']++;
            }
        }
    } catch (Exception $e) {
        $database_available = false;
        $error_message = "Database query failed. Showing demo data.";
        error_log("User management query error: " . $e->getMessage());
    }
}

// Fallback demo data if database is not available
if (!$database_available) {
    $error_message = "Database connection issue. Showing demo data for interface testing.";
    
    // Enhanced fallback demo users with realistic data
    $users = [
        [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@fezamarket.com',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'role' => 'admin',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-6 months')),
            'last_login_at' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
            'email_verified_at' => date('Y-m-d H:i:s', strtotime('-6 months'))
        ],
        [
            'id' => 2,
            'username' => 'seller_demo',
            'email' => 'seller@fezamarket.com',
            'first_name' => 'Demo',
            'last_name' => 'Seller',
            'role' => 'seller',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 months')),
            'last_login_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'email_verified_at' => date('Y-m-d H:i:s', strtotime('-3 months'))
        ],
        [
            'id' => 3,
            'username' => 'customer_demo',
            'email' => 'customer@fezamarket.com',
            'first_name' => 'Demo',
            'last_name' => 'Customer',
            'role' => 'customer',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 month')),
            'last_login_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
            'email_verified_at' => date('Y-m-d H:i:s', strtotime('-1 month'))
        ],
        [
            'id' => 4,
            'username' => 'pending_user',
            'email' => 'pending@fezamarket.com',
            'first_name' => 'Pending',
            'last_name' => 'User',
            'role' => 'customer',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'last_login_at' => null,
            'email_verified_at' => null
        ],
        [
            'id' => 5,
            'username' => 'inactive_seller',
            'email' => 'inactive@fezamarket.com',
            'first_name' => 'Inactive',
            'last_name' => 'Seller',
            'role' => 'seller',
            'status' => 'inactive',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 months')),
            'last_login_at' => date('Y-m-d H:i:s', strtotime('-1 month')),
            'email_verified_at' => date('Y-m-d H:i:s', strtotime('-2 months'))
        ]
    ];
    
    $total_users = count($users);
    
    // Calculate demo stats
    foreach ($users as $user) {
        $stats['total_users']++;
        if ($user['status'] === 'active') {
            $stats['active_users']++;
        } elseif ($user['status'] === 'pending') {
            $stats['pending_users']++;
        }
        
        if ($user['role'] === 'seller') {
            $stats['seller_users']++;
        } else {
            $stats['customer_users']++;
        }
    }
}

// Get current user for edit/view
$currentUser = null;
if (($action === 'edit' || $action === 'view') && $user_id) {
    foreach ($users as $user) {
        if ($user['id'] == $user_id) {
            $currentUser = $user;
            break;
        }
    }
    if (!$currentUser) {
        $_SESSION['error_message'] = 'User not found.';
        header('Location: /admin/users/');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
        }
        
        body { 
            background-color: #f8f9fa; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .page-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .user-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .role-admin { background-color: #e7f3ff; color: #0066cc; }
        .role-seller { background-color: #fff0e6; color: #cc6600; }
        .role-customer { background-color: #f0f0f0; color: #666666; }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .stats-icon {
            font-size: 2.5rem;
            margin-right: 1rem;
            opacity: 0.8;
        }
        
        .stats-content h3 {
            font-size: 2rem;
            margin: 0;
            font-weight: 700;
        }
        
        .stats-content p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .user-info {
            font-size: 0.85rem;
        }
        
        .avatar-placeholder {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent)) !important;
        }
        
        .table-actions {
            white-space: nowrap;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-1">
                        <i class="fas fa-users me-2"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <p class="mb-0 opacity-75">Manage users, roles, and permissions across the platform</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="../index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-info border-0 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <h6 class="mb-1">Demo Mode Active</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
        <!-- Enhanced Stats Dashboard -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card bg-primary text-white">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-success text-white">
                    <div class="stats-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format($stats['active_users']); ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-warning text-white">
                    <div class="stats-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format($stats['pending_users']); ?></h3>
                        <p>Pending Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-info text-white">
                    <div class="stats-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format($stats['seller_users']); ?></h3>
                        <p>Sellers</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Actions -->
        <div class="page-actions">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">User Management</h4>
                    <small class="text-muted">Manage all platform users and their permissions</small>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="btn-group">
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Add User
                        </a>
                        <button class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-download me-2"></i>Export Users</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-upload me-2"></i>Import Users</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-shield-alt me-2"></i>Bulk Actions</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Users Grid -->
        <div class="row">
            <?php if (empty($users)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <h5>No Users Found</h5>
                            <p>Get started by adding your first user to the system.</p>
                            <a href="?action=create" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Add First User
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="user-card">
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div class="d-flex">
                                    <div class="avatar-placeholder bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 50px; height: 50px; font-size: 1.2rem; font-weight: 600;">
                                        <?php echo strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars(($user['first_name'] ?? 'Unknown') . ' ' . ($user['last_name'] ?? 'User')); ?>
                                        </h6>
                                        <p class="text-muted mb-0 small">@<?php echo htmlspecialchars($user['username'] ?? 'unknown'); ?></p>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link btn-sm" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="?action=edit&id=<?php echo $user['id']; ?>">
                                            <i class="fas fa-edit me-2"></i>Edit
                                        </a></li>
                                        <li><a class="dropdown-item" href="?action=view&id=<?php echo $user['id']; ?>">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                            <li><a class="dropdown-item text-warning" href="#" onclick="suspendUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-pause me-2"></i>Suspend
                                            </a></li>
                                        <?php else: ?>
                                            <li><a class="dropdown-item text-success" href="#" onclick="activateUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-play me-2"></i>Activate
                                            </a></li>
                                        <?php endif; ?>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-trash me-2"></i>Delete
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="user-info">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Email:</span>
                                    <span class="small"><?php echo htmlspecialchars($user['email'] ?? 'No email'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Role:</span>
                                    <span class="role-badge role-<?php echo $user['role'] ?? 'customer'; ?>">
                                        <?php echo ucfirst($user['role'] ?? 'customer'); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Status:</span>
                                    <span class="status-badge status-<?php echo $user['status'] ?? 'active'; ?>">
                                        <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Joined:</span>
                                    <span class="small"><?php echo date('M j, Y', strtotime($user['created_at'] ?? 'now')); ?></span>
                                </div>
                                <?php if ($user['last_login_at'] ?? null): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Last Login:</span>
                                        <span class="small"><?php echo date('M j, Y g:i A', strtotime($user['last_login_at'])); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Last Login:</span>
                                        <span class="small text-muted">Never</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (($user['status'] ?? 'active') === 'pending'): ?>
                                <div class="mt-3 pt-3 border-top">
                                    <button class="btn btn-success btn-sm me-2" onclick="approveUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-check me-1"></i>Approve
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <!-- User Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action === 'create' ? 'Add New User' : 'Edit User'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create_user' : 'update_user'; ?>">
                    <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="user_id" value="<?php echo $currentUser['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($currentUser['first_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($currentUser['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="customer" <?php echo ($currentUser['role'] ?? '') === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="seller" <?php echo ($currentUser['role'] ?? '') === 'seller' ? 'selected' : ''; ?>>Seller</option>
                                    <option value="support" <?php echo ($currentUser['role'] ?? '') === 'support' ? 'selected' : ''; ?>>Support</option>
                                    <option value="admin" <?php echo ($currentUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="active" <?php echo ($currentUser['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo ($currentUser['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="suspended" <?php echo ($currentUser['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="inactive" <?php echo ($currentUser['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password <?php echo $action === 'create' ? '*' : '(leave blank to keep current)'; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       <?php echo $action === 'create' ? 'required' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                <?php echo $action === 'create' ? 'Create User' : 'Update User'; ?>
                            </button>
                            <a href="/admin/users/" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php elseif ($action === 'view' && $currentUser): ?>
        <!-- User Details View -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">User Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Username:</strong></td>
                                        <td><?php echo htmlspecialchars($currentUser['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($currentUser['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Full Name:</strong></td>
                                        <td><?php echo htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Role:</strong></td>
                                        <td><span class="badge bg-secondary"><?php echo ucfirst($currentUser['role']); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'active' => 'success',
                                                'pending' => 'warning',
                                                'suspended' => 'danger',
                                                'inactive' => 'secondary'
                                            ][$currentUser['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo ucfirst($currentUser['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Joined:</strong></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($currentUser['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="?action=edit&id=<?php echo $currentUser['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Edit User
                            </a>
                            <a href="/admin/users/" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-info" onclick="alert('Demo: KYC feature would open here')">
                                <i class="fas fa-id-card me-1"></i> View KYC
                            </button>
                            <button class="btn btn-outline-success" onclick="alert('Demo: Orders feature would open here')">
                                <i class="fas fa-shopping-cart me-1"></i> View Orders
                            </button>
                            <button class="btn btn-outline-warning" onclick="alert('Demo: Support tickets would open here')">
                                <i class="fas fa-headset me-1"></i> Support Tickets
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Enhanced User Management JavaScript -->
    <script>
    // User management functions
    function editUser(userId) {
        window.location.href = `?action=edit&id=${userId}`;
    }
    
    function viewUser(userId) {
        window.location.href = `?action=view&id=${userId}`;
    }
    
    function suspendUser(userId) {
        if (confirm('Are you sure you want to suspend this user?')) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="suspend_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function activateUser(userId) {
        if (confirm('Are you sure you want to activate this user?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="activate_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function approveUser(userId) {
        if (confirm('Approve this user account?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="activate_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function rejectUser(userId) {
        if (confirm('Reject this user account? They will need to re-register.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Add search functionality on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('User Management loaded with <?php echo count($users); ?> users');
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.querySelector('.btn-close')) {
                    alert.querySelector('.btn-close').click();
                }
            });
        }, 5000);
    });
    </script>
</body>
</html>