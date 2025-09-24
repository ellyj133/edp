<?php
/**
 * Admin Header Include - Required in all admin pages
 * Standardized admin page header with navigation
 */

// Ensure required includes are loaded
if (!function_exists('hasAdminPermission')) {
    require_once __DIR__ . '/rbac.php';
}
if (!function_exists('csrfMeta')) {
    require_once __DIR__ . '/csrf.php';
}

$page_title = $page_title ?? 'Admin Dashboard';
$admin_nav = getAdminNavigation();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - E-Commerce Admin</title>
    
    <!-- CSRF Meta Tag for AJAX -->
    <?php echo csrfMeta(); ?>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom Admin Styles -->
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-success: #27ae60;
            --admin-danger: #e74c3c;
            --admin-warning: #f39c12;
            --admin-info: #3498db;
        }
        
        body {
            background-color: #f8f9fa;
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
            border-right: 1px solid #dee2e6;
            min-height: calc(100vh - 80px);
            padding: 0;
        }
        
        .admin-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .admin-nav-item {
            border-bottom: 1px solid #f8f9fa;
        }
        
        .admin-nav-link {
            display: block;
            padding: 1rem 1.5rem;
            color: #495057;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .admin-nav-link:hover {
            background-color: #f8f9fa;
            color: var(--admin-primary);
            border-left-color: var(--admin-info);
        }
        
        .admin-nav-link.active {
            background-color: var(--admin-primary);
            color: white;
            border-left-color: var(--admin-info);
        }
        
        .admin-nav-link i {
            width: 20px;
            margin-right: 0.5rem;
        }
        
        .admin-content {
            padding: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--admin-info);
            margin-bottom: 1rem;
        }
        
        .stats-card.success { border-left-color: var(--admin-success); }
        .stats-card.warning { border-left-color: var(--admin-warning); }
        .stats-card.danger { border-left-color: var(--admin-danger); }
        
        .stats-card h3 {
            color: var(--admin-primary);
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-card p {
            color: #6c757d;
            margin: 0;
        }
        
        .page-header {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            color: var(--admin-primary);
            margin: 0;
            font-size: 1.75rem;
        }
        
        .btn-admin-primary {
            background-color: var(--admin-primary);
            border-color: var(--admin-primary);
            color: white;
        }
        
        .btn-admin-primary:hover {
            background-color: var(--admin-secondary);
            border-color: var(--admin-secondary);
            color: white;
        }
        
        .table-actions {
            white-space: nowrap;
        }
        
        .table-actions .btn {
            margin: 0 0.2rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-active { background-color: var(--admin-success); color: white; }
        .status-pending { background-color: var(--admin-warning); color: white; }
        .status-suspended { background-color: var(--admin-danger); color: white; }
        .status-cancelled { background-color: #6c757d; color: white; }
        
        .alert-dismissible {
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                min-height: auto;
            }
            
            .admin-nav-link {
                padding: 0.75rem 1rem;
            }
            
            .admin-content {
                padding: 1rem;
            }
        }
    </style>
    
    <!-- Additional page-specific styles -->
    <?php if (isset($additional_styles)): ?>
        <?php echo $additional_styles; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <small class="text-white-50">
                        <?php echo $page_subtitle ?? 'Administrative Control Panel'; ?>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <!-- User Info -->
                        <div class="dropdown me-3">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars(Session::get('username', 'Admin')); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/account.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="/admin/settings/"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                        
                        <!-- Back to Site -->
                        <a href="/" class="btn btn-outline-light me-2">
                            <i class="fas fa-external-link-alt me-1"></i>
                            View Site
                        </a>
                        
                        <!-- Back to Dashboard -->
                        <?php if ($_SERVER['REQUEST_URI'] !== '/admin/'): ?>
                        <a href="/admin/" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-1"></i>
                            Dashboard
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar">
                <ul class="admin-nav">
                    <?php foreach ($admin_nav as $nav_item): ?>
                    <li class="admin-nav-item">
                        <a href="<?php echo htmlspecialchars($nav_item['url']); ?>" 
                           class="admin-nav-link <?php echo $nav_item['active'] ? 'active' : ''; ?>">
                            <i class="<?php echo htmlspecialchars($nav_item['icon']); ?>"></i>
                            <?php echo htmlspecialchars($nav_item['title']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 admin-content">
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

                <?php if (isset($_SESSION['warning_message'])): ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['warning_message']); unset($_SESSION['warning_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['info_message'])): ?>
                <div class="alert alert-info alert-dismissible fade show">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['info_message']); unset($_SESSION['info_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>