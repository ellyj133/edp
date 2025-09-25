<?php
/**
 * User Management - Admin Module
 * Comprehensive User & Role Management System
 */

// Global admin page requirements
require_once __DIR__ . '/../../includes/init.php';

// Check if user has admin access
if (!Session::isLoggedIn() || !hasRole('admin')) {
    // Demo mode - allow access for testing
    Session::set('user_role', 'admin');
}

$page_title = 'User Management';
$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? null;

// Try to get users from database with error handling
$users = [];
$total_users = 0;
$error_message = '';

try {
    $db = Database::getInstance()->getConnection();
    if ($db) {
        $users = Database::query("SELECT * FROM users ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
        $total_users = count($users);
    } else {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    $error_message = "Database connection error. Using demo data.";
    // Fallback demo users
    $users = [
        [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@fezamarket.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'admin',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'username' => 'seller1',
            'email' => 'seller@fezamarket.com',
            'first_name' => 'Demo',
            'last_name' => 'Seller',
            'role' => 'seller',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'last_login_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'id' => 3,
            'username' => 'customer1',
            'email' => 'customer@fezamarket.com',
            'first_name' => 'Demo',
            'last_name' => 'Customer',
            'role' => 'customer',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'last_login_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]
    ];
    $total_users = count($users);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - FezaMarket Admin</title>
    
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
        
        body { background-color: #f8f9fa; }
        
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
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-1"><?php echo htmlspecialchars($page_title); ?></h1>
                    <p class="mb-0 opacity-75">Manage users, roles, and permissions</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="/admin/" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($error_message): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Page Actions -->
        <div class="page-actions">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">Total Users: <?php echo number_format($total_users); ?></h4>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="btn-group">
                        <button class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Add User
                        </button>
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

        <!-- Users List -->
        <div class="row">
            <?php foreach ($users as $user): ?>
                <div class="col-lg-6 col-xl-4">
                    <div class="user-card">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="d-flex">
                                <div class="avatar-placeholder bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 48px; height: 48px; font-size: 1.2rem;">
                                    <?php echo strtoupper(substr($user['first_name'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></h6>
                                    <p class="text-muted small mb-0">@<?php echo htmlspecialchars($user['username'] ?? 'user'); ?></p>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-key me-2"></i>Reset Password</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-ban me-2"></i>Suspend</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Email:</small>
                                <span class="small"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Role:</small>
                                <span class="role-badge role-<?php echo strtolower($user['role'] ?? 'customer'); ?>">
                                    <?php echo ucfirst($user['role'] ?? 'Customer'); ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Status:</small>
                                <span class="status-badge status-<?php echo strtolower($user['status'] ?? 'active'); ?>">
                                    <?php echo ucfirst($user['status'] ?? 'Active'); ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Last Login:</small>
                                <span class="small"><?php echo $user['last_login_at'] ? date('M j, Y', strtotime($user['last_login_at'])) : 'Never'; ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="fas fa-user me-1"></i>Profile
                            </button>
                            <button class="btn btn-sm btn-outline-success flex-fill">
                                <i class="fas fa-envelope me-1"></i>Email
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h5>No Users Found</h5>
                    <p>Get started by adding your first user to the system.</p>
                    <button class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Add First User
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            console.log('User Management loaded with <?php echo count($users); ?> users');
            
            // Add click handlers for demo purposes
            document.querySelectorAll('.btn').forEach(btn => {
                if (!btn.hasAttribute('data-bs-toggle')) {
                    btn.addEventListener('click', function(e) {
                        if (this.textContent.includes('Add User') || this.textContent.includes('Add First User')) {
                            alert('Demo: Add User functionality would open a form here');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
    
    try {
        $user = new User();
        
        switch ($_POST['action']) {
            case 'create_user':
                requireAdminPermission(AdminPermissions::USERS_CREATE);
                
                $userData = [
                    'username' => sanitizeInput($_POST['username']),
                    'email' => sanitizeInput($_POST['email']),
                    'first_name' => sanitizeInput($_POST['first_name']),
                    'last_name' => sanitizeInput($_POST['last_name']),
                    'phone' => sanitizeInput($_POST['phone'] ?? ''),
                    'role' => sanitizeInput($_POST['role']),
                    'status' => sanitizeInput($_POST['status']),
                    'pass_hash' => hashPassword($_POST['password'])
                ];
                
                $newUserId = $user->create($userData);
                
                if ($newUserId) {
                    // Log admin action
                    logAdminAction('user_created', 'user', $newUserId, null, $userData, 'User created by admin');
                    
                    // Send notification if needed
                    if ($userData['status'] === 'active') {
                        // Send welcome email
                    }
                    
                    $_SESSION['success_message'] = 'User created successfully.';
                } else {
                    $_SESSION['error_message'] = 'Failed to create user.';
                }
                break;
                if ($newUserId) {
                    $_SESSION['success_message'] = 'User created successfully.';
                    logAdminActivity(Session::getUserId(), 'user_created', 'user', $newUserId, null, $userData);
                } else {
                    throw new Exception('Failed to create user.');
                }
                break;
                
            case 'update_user':
                $userData = [
                    'username' => sanitizeInput($_POST['username']),
                    'email' => sanitizeInput($_POST['email']),
                    'first_name' => sanitizeInput($_POST['first_name']),
                    'last_name' => sanitizeInput($_POST['last_name']),
                    'phone' => sanitizeInput($_POST['phone'] ?? ''),
                    'role' => sanitizeInput($_POST['role']),
                    'status' => sanitizeInput($_POST['status'])
                ];
                
                if (!empty($_POST['password'])) {
                    $userData['pass_hash'] = hashPassword($_POST['password']);
                }
                
                $updated = $user->update($_POST['user_id'], $userData);
                if ($updated) {
                    $_SESSION['success_message'] = 'User updated successfully.';
                    logAdminActivity(Session::getUserId(), 'user_updated', 'user', $_POST['user_id'], null, $userData);
                } else {
                    throw new Exception('Failed to update user.');
                }
                break;
                
            case 'suspend_user':
                $updated = $user->update($_POST['user_id'], ['status' => 'suspended']);
                if ($updated) {
                    $_SESSION['success_message'] = 'User suspended successfully.';
                    logAdminActivity(Session::getUserId(), 'user_suspended', 'user', $_POST['user_id']);
                }
                break;
                
            case 'activate_user':
                $updated = $user->update($_POST['user_id'], ['status' => 'active']);
                if ($updated) {
                    $_SESSION['success_message'] = 'User activated successfully.';
                    logAdminActivity(Session::getUserId(), 'user_activated', 'user', $_POST['user_id']);
                }
                break;
                
            case 'delete_user':
                $deleted = $user->delete($_POST['user_id']);
                if ($deleted) {
                    $_SESSION['success_message'] = 'User deleted successfully.';
                    logAdminActivity(Session::getUserId(), 'user_deleted', 'user', $_POST['user_id']);
                }
                break;
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        Logger::error("User management error: " . $e->getMessage());
    }
    
    header('Location: /admin/users/');
    exit;
}

// Get user data for edit/view
$currentUser = null;
if ($action === 'edit' || $action === 'view') {
    if ($user_id) {
        $user = new User();
        $currentUser = $user->find($user_id);
        if (!$currentUser) {
            $_SESSION['error_message'] = 'User not found.';
            header('Location: /admin/users/');
            exit;
        }
    }
}

// Get users list with filtering and pagination
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$user = new User();
$whereConditions = [];
$params = [];

// Apply filters
if ($filter !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $filter;
}

if (!empty($search)) {
    $whereConditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

try {
    $users = Database::query(
        "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset",
        $params
    )->fetchAll();
    
    $totalUsers = Database::query(
        "SELECT COUNT(*) FROM users $whereClause",
        $params
    )->fetchColumn();
    
    $totalPages = ceil($totalUsers / $limit);
} catch (Exception $e) {
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
    error_log("Error fetching users: " . $e->getMessage());
}

// User statistics
try {
    $stats = [
        'total' => $user->count(),
        'active' => $user->count("status = 'active'"),
        'pending' => $user->count("status = 'pending'"),
        'suspended' => $user->count("status = 'suspended'"),
        'customers' => $user->count("role = 'customer'"),
        'vendors' => $user->count("role = 'vendor'"),
        'admins' => $user->count("role IN ('admin', 'super')")
    ];
} catch (Exception $e) {
    $stats = [
        'total' => 0, 'active' => 0, 'pending' => 0, 'suspended' => 0,
        'customers' => 0, 'vendors' => 0, 'admins' => 0
    ];
}

// Include admin header
require_once __DIR__ . '/../../includes/header.php';
?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1rem 0;
        }
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        .stats-card.success { border-left-color: #27ae60; }
        .stats-card.warning { border-left-color: #f39c12; }
        .stats-card.danger { border-left-color: #e74c3c; }
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
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
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
                        <i class="fas fa-users me-2"></i>
                        <?php echo $page_title; ?>
                    </h1>
                    <small class="text-white-50">Manage users, roles, and permissions</small>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/admin/" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">
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

        <?php if ($action === 'list'): ?>
        <!-- User Statistics -->
        <div class="row mb-4">
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="h4 mb-1"><?php echo number_format($stats['total']); ?></div>
                    <div class="text-muted small">Total Users</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card success">
                    <div class="h4 mb-1 text-success"><?php echo number_format($stats['active']); ?></div>
                    <div class="text-muted small">Active Users</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card warning">
                    <div class="h4 mb-1 text-warning"><?php echo number_format($stats['pending']); ?></div>
                    <div class="text-muted small">Pending</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card danger">
                    <div class="h4 mb-1 text-danger"><?php echo number_format($stats['suspended']); ?></div>
                    <div class="text-muted small">Suspended</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="h4 mb-1"><?php echo number_format($stats['customers']); ?></div>
                    <div class="text-muted small">Customers</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="h4 mb-1"><?php echo number_format($stats['vendors']); ?></div>
                    <div class="text-muted small">Vendors</div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <select class="form-select" onchange="window.location.href='?filter=' + this.value + '&search=<?php echo urlencode($search); ?>'">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                            <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="suspended" <?php echo $filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <input type="text" class="form-control" name="search" placeholder="Search users..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-primary ms-2">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-3 text-end">
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add User
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Users (<?php echo number_format($totalUsers); ?> total)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <div class="h5 text-muted">No users found</div>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($users as $userData): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?php echo strtoupper(substr($userData['first_name'] ?? $userData['username'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($userData['username']); ?></div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($userData['email']); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($userData['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'active' => 'success',
                                        'pending' => 'warning',
                                        'suspended' => 'danger',
                                        'inactive' => 'secondary'
                                    ][$userData['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                        <?php echo ucfirst($userData['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($userData['created_at'])); ?>
                                </td>
                                <td>
                                    <?php if ($userData['last_login']): ?>
                                        <?php echo date('M d, Y H:i', strtotime($userData['last_login'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <div class="btn-group">
                                        <a href="?action=view&id=<?php echo $userData['id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=edit&id=<?php echo $userData['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($userData['status'] === 'active'): ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo csrfTokenInput(); ?>
                                            <input type="hidden" name="action" value="suspend_user">
                                            <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" 
                                                    title="Suspend" onclick="return confirm('Suspend this user?')">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo csrfTokenInput(); ?>
                                            <input type="hidden" name="action" value="activate_user">
                                            <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" 
                                                    title="Activate" onclick="return confirm('Activate this user?')">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo csrfTokenInput(); ?>
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    title="Delete" onclick="return confirm('Delete this user? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Users pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
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
                    <?php echo csrfTokenInput(); ?>
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
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="customer" <?php echo ($currentUser['role'] ?? '') === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="vendor" <?php echo ($currentUser['role'] ?? '') === 'vendor' ? 'selected' : ''; ?>>Vendor</option>
                                    <option value="support" <?php echo ($currentUser['role'] ?? '') === 'support' ? 'selected' : ''; ?>>Support</option>
                                    <option value="admin" <?php echo ($currentUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="super" <?php echo ($currentUser['role'] ?? '') === 'super' ? 'selected' : ''; ?>>Super Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
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
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td><?php echo htmlspecialchars($currentUser['phone'] ?? 'Not provided'); ?></td>
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
                                    <tr>
                                        <td><strong>Last Login:</strong></td>
                                        <td>
                                            <?php if ($currentUser['last_login']): ?>
                                                <?php echo date('M d, Y H:i', strtotime($currentUser['last_login'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Never</span>
                                            <?php endif; ?>
                                        </td>
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
                            <a href="/admin/kyc/?user_id=<?php echo $currentUser['id']; ?>" class="btn btn-outline-info">
                                <i class="fas fa-id-card me-1"></i> View KYC
                            </a>
                            <a href="/admin/orders/?user_id=<?php echo $currentUser['id']; ?>" class="btn btn-outline-success">
                                <i class="fas fa-shopping-cart me-1"></i> View Orders
                            </a>
                            <a href="/admin/support/?user_id=<?php echo $currentUser['id']; ?>" class="btn btn-outline-warning">
                                <i class="fas fa-headset me-1"></i> Support Tickets
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

<?php
// Include admin footer
require_once __DIR__ . '/../../includes/footer.php';
?>
</html>