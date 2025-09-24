<?php
/**
 * Product Management - Admin Module
 * Comprehensive Product & Catalog Management System
 */

require_once __DIR__ . '/../../includes/init.php';

// Initialize PDO global variable for this module
$pdo = db();
RoleMiddleware::requireAdmin();

$page_title = 'Product Management';
$action = $_GET['action'] ?? 'list';
$product_id = $_GET['id'] ?? null;

// Handle actions
if ($_POST && isset($_POST['action'])) {
    validateCsrfAndRateLimit();
    
    try {
        $product = new Product();
        
        switch ($_POST['action']) {
            case 'create_product':
                $productData = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'short_description' => sanitizeInput($_POST['short_description'] ?? ''),
                    'sku' => sanitizeInput($_POST['sku']),
                    'price' => floatval($_POST['price']),
                    'compare_price' => !empty($_POST['compare_price']) ? floatval($_POST['compare_price']) : null,
                    'cost_price' => !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null,
                    'category_id' => intval($_POST['category_id']),
                    'vendor_id' => intval($_POST['vendor_id']),
                    'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
                    'stock_quantity' => intval($_POST['stock_quantity']),
                    'low_stock_threshold' => intval($_POST['low_stock_threshold'] ?? 10),
                    'track_inventory' => isset($_POST['track_inventory']) ? 1 : 0,
                    'allow_backorders' => isset($_POST['allow_backorders']) ? 1 : 0,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'status' => sanitizeInput($_POST['status']),
                    'tags' => sanitizeInput($_POST['tags'] ?? ''),
                    'meta_title' => sanitizeInput($_POST['meta_title'] ?? ''),
                    'meta_description' => sanitizeInput($_POST['meta_description'] ?? ''),
                ];
                
                $newProductId = $product->create($productData);
                if ($newProductId) {
                    $_SESSION['success_message'] = 'Product created successfully.';
                    logAdminActivity(Session::getUserId(), 'product_created', 'product', $newProductId, null, $productData);
                } else {
                    throw new Exception('Failed to create product.');
                }
                break;
                
            case 'update_product':
                $productData = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'short_description' => sanitizeInput($_POST['short_description'] ?? ''),
                    'sku' => sanitizeInput($_POST['sku']),
                    'price' => floatval($_POST['price']),
                    'compare_price' => !empty($_POST['compare_price']) ? floatval($_POST['compare_price']) : null,
                    'cost_price' => !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null,
                    'category_id' => intval($_POST['category_id']),
                    'vendor_id' => intval($_POST['vendor_id']),
                    'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
                    'stock_quantity' => intval($_POST['stock_quantity']),
                    'low_stock_threshold' => intval($_POST['low_stock_threshold'] ?? 10),
                    'track_inventory' => isset($_POST['track_inventory']) ? 1 : 0,
                    'allow_backorders' => isset($_POST['allow_backorders']) ? 1 : 0,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'status' => sanitizeInput($_POST['status']),
                    'tags' => sanitizeInput($_POST['tags'] ?? ''),
                    'meta_title' => sanitizeInput($_POST['meta_title'] ?? ''),
                    'meta_description' => sanitizeInput($_POST['meta_description'] ?? ''),
                ];
                
                $updated = $product->update($_POST['product_id'], $productData);
                if ($updated) {
                    $_SESSION['success_message'] = 'Product updated successfully.';
                    logAdminActivity(Session::getUserId(), 'product_updated', 'product', $_POST['product_id'], null, $productData);
                } else {
                    throw new Exception('Failed to update product.');
                }
                break;
                
            case 'bulk_action':
                $productIds = $_POST['product_ids'] ?? [];
                $bulkAction = $_POST['bulk_action_type'] ?? '';
                
                if (empty($productIds) || empty($bulkAction)) {
                    throw new Exception('Please select products and an action.');
                }
                
                $count = 0;
                foreach ($productIds as $id) {
                    switch ($bulkAction) {
                        case 'activate':
                            if ($product->update($id, ['status' => 'active'])) $count++;
                            break;
                        case 'deactivate':
                            if ($product->update($id, ['status' => 'inactive'])) $count++;
                            break;
                        case 'feature':
                            if ($product->update($id, ['is_featured' => 1])) $count++;
                            break;
                        case 'unfeature':
                            if ($product->update($id, ['is_featured' => 0])) $count++;
                            break;
                        case 'delete':
                            if ($product->delete($id)) $count++;
                            break;
                    }
                }
                
                $_SESSION['success_message'] = "Bulk action applied to $count products.";
                logAdminActivity(Session::getUserId(), 'products_bulk_action', 'product', null, null, [
                    'action' => $bulkAction,
                    'product_ids' => $productIds,
                    'count' => $count
                ]);
                break;
                
            case 'update_stock':
                $updated = $product->update($_POST['product_id'], [
                    'stock_quantity' => intval($_POST['stock_quantity'])
                ]);
                if ($updated) {
                    $_SESSION['success_message'] = 'Stock updated successfully.';
                    logAdminActivity(Session::getUserId(), 'product_stock_updated', 'product', $_POST['product_id']);
                }
                break;
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        Logger::error("Product management error: " . $e->getMessage());
    }
    
    header('Location: /admin/products/');
    exit;
}

// Get product data for edit/view
$currentProduct = null;
if ($action === 'edit' || $action === 'view') {
    if ($product_id) {
        $product = new Product();
        $currentProduct = $product->findById($product_id);
        if (!$currentProduct) {
            $_SESSION['error_message'] = 'Product not found.';
            header('Location: /admin/products/');
            exit;
        }
    }
}

// Get products list with filtering and pagination
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$vendor = $_GET['vendor'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$whereConditions = [];
$params = [];

// Apply filters
if ($filter !== 'all') {
    if ($filter === 'low_stock') {
        $whereConditions[] = "stock_quantity <= low_stock_threshold";
    } else {
        $whereConditions[] = "status = ?";
        $params[] = $filter;
    }
}

if (!empty($search)) {
    $whereConditions[] = "(name LIKE ? OR sku LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($category)) {
    $whereConditions[] = "category_id = ?";
    $params[] = $category;
}

if (!empty($vendor)) {
    $whereConditions[] = "vendor_id = ?";
    $params[] = $vendor;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

try {
    $products = Database::query(
        "SELECT p.*, c.name as category_name, u.username as vendor_name 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         LEFT JOIN users u ON p.vendor_id = u.id 
         $whereClause 
         ORDER BY p.created_at DESC 
         LIMIT $limit OFFSET $offset",
        $params
    )->fetchAll();
    
    $totalProducts = Database::query(
        "SELECT COUNT(*) FROM products p $whereClause",
        $params
    )->fetchColumn();
    
    $totalPages = ceil($totalProducts / $limit);
} catch (Exception $e) {
    $products = [];
    $totalProducts = 0;
    $totalPages = 0;
    error_log("Error fetching products: " . $e->getMessage());
}

// Get categories and vendors for filters and forms
try {
    $categories = Database::query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
    $vendors = Database::query("SELECT id, username FROM users WHERE role = 'vendor' ORDER BY username")->fetchAll();
} catch (Exception $e) {
    $categories = [];
    $vendors = [];
}

// Product statistics
try {
    $product = new Product();
    $stats = [
        'total' => $product->count(),
        'active' => $product->count("status = 'active'"),
        'inactive' => $product->count("status = 'inactive'"),
        'pending' => $product->count("status = 'pending'"),
        'featured' => $product->count("is_featured = 1"),
        'low_stock' => Database::query("SELECT COUNT(*) FROM products WHERE stock_quantity <= low_stock_threshold")->fetchColumn(),
        'out_of_stock' => $product->count("stock_quantity = 0")
    ];
} catch (Exception $e) {
    $stats = [
        'total' => 0, 'active' => 0, 'inactive' => 0, 'pending' => 0,
        'featured' => 0, 'low_stock' => 0, 'out_of_stock' => 0
    ];
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .stats-card.info { border-left-color: #17a2b8; }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .stock-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .stock-high { background-color: #28a745; }
        .stock-medium { background-color: #ffc107; }
        .stock-low { background-color: #fd7e14; }
        .stock-out { background-color: #dc3545; }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-box me-2"></i>
                        <?php echo $page_title; ?>
                    </h1>
                    <small class="text-white-50">Manage products, inventory, and catalog</small>
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
        <!-- Product Statistics -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card">
                    <div class="h4 mb-1"><?php echo number_format($stats['total']); ?></div>
                    <div class="text-muted small">Total Products</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card success">
                    <div class="h4 mb-1 text-success"><?php echo number_format($stats['active']); ?></div>
                    <div class="text-muted small">Active</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card info">
                    <div class="h4 mb-1 text-info"><?php echo number_format($stats['featured']); ?></div>
                    <div class="text-muted small">Featured</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card warning">
                    <div class="h4 mb-1 text-warning"><?php echo number_format($stats['low_stock']); ?></div>
                    <div class="text-muted small">Low Stock</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card danger">
                    <div class="h4 mb-1 text-danger"><?php echo number_format($stats['out_of_stock']); ?></div>
                    <div class="text-muted small">Out of Stock</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card warning">
                    <div class="h4 mb-1 text-warning"><?php echo number_format($stats['pending']); ?></div>
                    <div class="text-muted small">Pending Approval</div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="filter">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Products</option>
                            <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="low_stock" <?php echo $filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Vendor</label>
                        <select class="form-select" name="vendor">
                            <option value="">All Vendors</option>
                            <?php foreach ($vendors as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $vendor == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['username']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <a href="?action=create" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-1"></i> Add Product
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Products (<?php echo number_format($totalProducts); ?> total)</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleBulkActions()">
                        <i class="fas fa-tasks me-1"></i> Bulk Actions
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <form method="POST" id="bulkForm">
                    <?php echo csrfTokenInput(); ?>
                    <input type="hidden" name="action" value="bulk_action">
                    
                    <div id="bulkActionsBar" class="bg-light p-3 border-bottom" style="display: none;">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <select class="form-select" name="bulk_action_type" required>
                                    <option value="">Select Action</option>
                                    <option value="activate">Activate</option>
                                    <option value="deactivate">Deactivate</option>
                                    <option value="feature">Feature</option>
                                    <option value="unfeature">Unfeature</option>
                                    <option value="delete">Delete</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-warning" 
                                        onclick="return confirm('Apply bulk action to selected products?')">
                                    Apply to Selected
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleBulkActions()">
                                    Cancel
                                </button>
                            </div>
                            <div class="col-md-4 text-end">
                                <small class="text-muted">Select products below to apply bulk actions</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAllProducts()">
                                    </th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Category</th>
                                    <th>Vendor</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                                        <div class="h5 text-muted">No products found</div>
                                        <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input product-checkbox" 
                                               name="product_ids[]" value="<?php echo $prod['id']; ?>">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($prod['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($prod['image_url']); ?>" 
                                                 class="product-image me-3" alt="Product">
                                            <?php else: ?>
                                            <div class="product-image me-3 bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($prod['name']); ?></div>
                                                <?php if ($prod['is_featured']): ?>
                                                <span class="badge bg-warning text-dark">Featured</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($prod['sku']); ?></code>
                                    </td>
                                    <td>
                                        <div class="fw-bold">$<?php echo number_format($prod['price'], 2); ?></div>
                                        <?php if ($prod['compare_price'] && $prod['compare_price'] > $prod['price']): ?>
                                        <small class="text-muted text-decoration-line-through">
                                            $<?php echo number_format($prod['compare_price'], 2); ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $stockLevel = 'high';
                                            if ($prod['stock_quantity'] == 0) {
                                                $stockLevel = 'out';
                                            } elseif ($prod['stock_quantity'] <= $prod['low_stock_threshold']) {
                                                $stockLevel = 'low';
                                            } elseif ($prod['stock_quantity'] <= $prod['low_stock_threshold'] * 2) {
                                                $stockLevel = 'medium';
                                            }
                                            ?>
                                            <span class="stock-indicator stock-<?php echo $stockLevel; ?>"></span>
                                            <span><?php echo number_format($prod['stock_quantity']); ?></span>
                                        </div>
                                        <?php if ($prod['track_inventory'] && $prod['stock_quantity'] <= $prod['low_stock_threshold']): ?>
                                        <small class="text-warning">Low stock</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($prod['category_name'] ?? 'Uncategorized'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($prod['vendor_name'] ?? 'Admin'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            'pending' => 'warning'
                                        ][$prod['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                            <?php echo ucfirst($prod['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?action=view&id=<?php echo $prod['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $prod['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    title="Quick Stock Update" 
                                                    onclick="updateStock(<?php echo $prod['id']; ?>, <?php echo $prod['stock_quantity']; ?>)">
                                                <i class="fas fa-cubes"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Products pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&vendor=<?php echo urlencode($vendor); ?>">
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
        <!-- Product Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action === 'create' ? 'Add New Product' : 'Edit Product'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php echo csrfTokenInput(); ?>
                    <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create_product' : 'update_product'; ?>">
                    <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="product_id" value="<?php echo $currentProduct['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Basic Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Product Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($currentProduct['name'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="short_description" class="form-label">Short Description</label>
                                        <textarea class="form-control" id="short_description" name="short_description" rows="2"><?php echo htmlspecialchars($currentProduct['short_description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Full Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="6"><?php echo htmlspecialchars($currentProduct['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SEO Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">SEO Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="meta_title" class="form-label">Meta Title</label>
                                        <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                               value="<?php echo htmlspecialchars($currentProduct['meta_title'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="meta_description" class="form-label">Meta Description</label>
                                        <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($currentProduct['meta_description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tags" class="form-label">Tags (comma separated)</label>
                                        <input type="text" class="form-control" id="tags" name="tags" 
                                               value="<?php echo htmlspecialchars($currentProduct['tags'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Product Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Product Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="sku" class="form-label">SKU *</label>
                                        <input type="text" class="form-control" id="sku" name="sku" 
                                               value="<?php echo htmlspecialchars($currentProduct['sku'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                    <?php echo ($currentProduct['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="vendor_id" class="form-label">Vendor</label>
                                        <select class="form-select" id="vendor_id" name="vendor_id">
                                            <option value="">Select Vendor (Optional)</option>
                                            <?php foreach ($vendors as $v): ?>
                                            <option value="<?php echo $v['id']; ?>" 
                                                    <?php echo ($currentProduct['vendor_id'] ?? '') == $v['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($v['username']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status *</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active" <?php echo ($currentProduct['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($currentProduct['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="pending" <?php echo ($currentProduct['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                               <?php echo ($currentProduct['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_featured">
                                            Featured Product
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pricing -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Pricing</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   step="0.01" min="0" 
                                                   value="<?php echo $currentProduct['price'] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="compare_price" class="form-label">Compare Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="compare_price" name="compare_price" 
                                                   step="0.01" min="0" 
                                                   value="<?php echo $currentProduct['compare_price'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="cost_price" class="form-label">Cost Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="cost_price" name="cost_price" 
                                                   step="0.01" min="0" 
                                                   value="<?php echo $currentProduct['cost_price'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inventory -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Inventory</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="track_inventory" name="track_inventory" 
                                               <?php echo ($currentProduct['track_inventory'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="track_inventory">
                                            Track Inventory
                                        </label>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                               min="0" value="<?php echo $currentProduct['stock_quantity'] ?? 0; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                        <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" 
                                               min="0" value="<?php echo $currentProduct['low_stock_threshold'] ?? 10; ?>">
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="allow_backorders" name="allow_backorders" 
                                               <?php echo ($currentProduct['allow_backorders'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="allow_backorders">
                                            Allow Backorders
                                        </label>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="weight" class="form-label">Weight (kg)</label>
                                        <input type="number" class="form-control" id="weight" name="weight" 
                                               step="0.01" min="0" 
                                               value="<?php echo $currentProduct['weight'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                <?php echo $action === 'create' ? 'Create Product' : 'Update Product'; ?>
                            </button>
                            <a href="/admin/products/" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Stock Update Modal -->
    <div class="modal fade" id="stockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock Quantity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php echo csrfTokenInput(); ?>
                    <input type="hidden" name="action" value="update_stock">
                    <input type="hidden" name="product_id" id="stockProductId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="stockQuantity" class="form-label">New Stock Quantity</label>
                            <input type="number" class="form-control" id="stockQuantity" name="stock_quantity" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleBulkActions() {
            const bar = document.getElementById('bulkActionsBar');
            const checkboxes = document.querySelectorAll('.product-checkbox');
            
            if (bar.style.display === 'none') {
                bar.style.display = 'block';
            } else {
                bar.style.display = 'none';
                // Uncheck all
                document.getElementById('selectAll').checked = false;
                checkboxes.forEach(cb => cb.checked = false);
            }
        }
        
        function toggleAllProducts() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.product-checkbox');
            
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }
        
        function updateStock(productId, currentStock) {
            document.getElementById('stockProductId').value = productId;
            document.getElementById('stockQuantity').value = currentStock;
            new bootstrap.Modal(document.getElementById('stockModal')).show();
        }
    </script>
</body>
</html>