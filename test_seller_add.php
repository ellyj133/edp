<?php
/**
 * Test Seller Add Product Page (No Auth Required)
 * This is a simplified version to demonstrate the UI functionality
 */

// Mock session for testing
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'seller';

// Load test database configuration  
require_once 'test_db_config.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Load categories
$categories = [];
try {
    $categories = Database::query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Handle form submission
$form = [
    'name' => '',
    'price' => '',
    'description' => '',
    'short_description' => '',
    'category_id' => '',
    'brand' => '',
    'status' => 'draft'
];
$errors = [];
$success = '';

if ($_POST) {
    foreach ($form as $k => $v) {
        $form[$k] = $_POST[$k] ?? $v;
    }
    
    // Basic validation
    if (empty($form['name'])) $errors['name'] = 'Product name is required';
    if (empty($form['price']) || !is_numeric($form['price'])) $errors['price'] = 'Valid price is required';
    
    if (empty($errors)) {
        try {
            Database::query("
                INSERT INTO products (seller_id, name, price, description, short_description, category_id, brand, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))",
                [1, $form['name'], $form['price'], $form['description'], $form['short_description'], 
                 $form['category_id'] ?: null, $form['brand'], $form['status']]
            );
            $success = "Product '{$form['name']}' added successfully!";
            // Clear form
            $form = ['name' => '', 'price' => '', 'description' => '', 'short_description' => '', 'category_id' => '', 'brand' => '', 'status' => 'draft'];
        } catch (Exception $e) {
            $errors['general'] = 'Error saving product: ' . $e->getMessage();
        }
    }
}

// Get existing products for testing
$products = [];
try {
    $products = Database::query("SELECT * FROM products ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Seller - Add Product</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .main-container { max-width: 800px; margin: 20px auto; }
        .form-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success-alert { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error-alert { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error-field { border-color: #dc3545 !important; }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Add New Product</h1>
            <div class="text-muted">Seller Dashboard</div>
        </div>

        <?php if ($success): ?>
            <div class="success-alert">✅ <?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="error-alert">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'error-field' : '' ?>" 
                               value="<?= h($form['name']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Price *</label>
                        <input type="number" step="0.01" name="price" class="form-control <?= isset($errors['price']) ? 'error-field' : '' ?>" 
                               value="<?= h($form['price']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= h($cat['id']) ?>" <?= $form['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= h($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control" value="<?= h($form['brand']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Short Description</label>
                        <textarea name="short_description" rows="2" class="form-control"><?= h($form['short_description']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Full Description</label>
                        <textarea name="description" rows="4" class="form-control"><?= h($form['description']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?= $form['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Add Product</button>
                    <a href="#products" class="btn btn-outline-secondary btn-lg ms-2">View Products</a>
                </div>
            </form>
        </div>

        <!-- Existing Products List -->
        <?php if ($products): ?>
        <div class="form-card mt-4" id="products">
            <h4>Recent Products</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= h($product['id']) ?></td>
                            <td><?= h($product['name']) ?></td>
                            <td>$<?= h($product['price']) ?></td>
                            <td>
                                <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= h($product['status']) ?>
                                </span>
                            </td>
                            <td><?= h($product['created_at']) ?></td>
                            <td>
                                <a href="test_seller_edit.php?id=<?= h($product['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>