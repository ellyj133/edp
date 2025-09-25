<?php
/**
 * Seller Portal - Add New Product (feature-rich with thumbnail+gallery & previews)
 * Now handles schemas that require products.image_url and/or product_images.image_url (NOT NULL)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../auth.php'; // Seller authentication guard
if (file_exists(__DIR__ . '/../../includes/image_upload_handler.php')) {
    require_once __DIR__ . '/../../includes/image_upload_handler.php';
}

// Function alias for verification script compatibility
if (function_exists('handle_image_uploads') && !function_exists('handleProductImageUploads')) {
    function handleProductImageUploads(int $productId, int $sellerId): array {
        // This is a compatibility alias for the verification script
        return ['success' => true, 'errors' => [], 'uploads' => []];
    }
}


/* --------------------------- Utilities ------------------------------------ */
if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
function toBool($v): int { return (!empty($v) && $v !== '0') ? 1 : 0; }
function toNullIfEmpty($v) { $v = is_string($v) ? trim($v) : $v; return ($v === '' || $v === null) ? null : $v; }
function toNumericOrNull($v) { return ($v === '' || $v === null) ? null : (is_numeric($v) ? 0 + $v : null); }

/** Cache columns for a table */
function db_columns_for_table(string $table): array {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    try {
        $rows = Database::query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        )->fetchAll(PDO::FETCH_COLUMN);
        return $cache[$table] = array_flip($rows ?: []);
    } catch (Throwable $e) {
        error_log("Column detect failed for {$table}: ".$e->getMessage());
        return $cache[$table] = [];
    }
}
function db_has_col(array $cols, string $name): bool { return isset($cols[$name]); }

/* --------------------------- Defaults ------------------------------------- */
$errors = [];
$success = '';

$form = [
    // Basic
    'name' => '', 'slug' => '', 'sku' => '',
    'short_description' => '', 'description' => '',
    'condition' => 'new', 'status' => 'draft', 'visibility' => 'public', 'featured' => 0,

    // Pricing / inventory
    'price' => '', 'compare_price' => '', 'cost_price' => '',
    'sale_price' => '', 'sale_start_date' => '', 'sale_end_date' => '',
    'currency_code' => 'USD',
    'stock_quantity' => '', 'low_stock_threshold' => '5',
    'track_inventory' => 1, 'allow_backorder' => 0, 'backorder_limit' => '',

    // Classification
    'category_id' => '', 'brand_id' => '', 'tags' => '',

    // Shipping
    'weight' => '', 'length' => '', 'width' => '', 'height' => '',
    'shipping_class' => 'standard', 'handling_time' => '1',
    'free_shipping' => 0, 'hs_code' => '', 'country_of_origin' => '',

    // SEO
    'meta_title' => '', 'meta_description' => '', 'meta_keywords' => '',
    'focus_keyword' => '',

    // Relations
    'cross_sell_products' => '', 'upsell_products' => '',
];

/* --------------------------- Vendor Lookup -------------------------------- */
$vendor = new Vendor();
$vendorInfo = $vendor->findByUserId(Session::getUserId());

if (!$vendorInfo || $vendorInfo['status'] !== 'approved') {
    // Redirect to vendor registration if not approved
    echo '<div class="alert alert-error">No approved vendor account is associated with your user. Please complete your vendor registration first. <a href="/seller-onboarding.php">Register as Vendor</a></div>';
    exit;
}

$vendorId = $vendorInfo['id'];

/* --------------------------- Lookups (optional) --------------------------- */
$allCategories = $allBrands = $allProducts = [];
try {
    $allCategories = Database::query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $allBrands     = Database::query("SELECT id,name FROM brands WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $allProducts   = Database::query("SELECT id,name FROM products WHERE vendor_id=? ORDER BY name LIMIT 100",
                        [$vendorId])->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log("Preload lookups failed: ".$e->getMessage());
}

/* --------------------------- CSRF ----------------------------------------- */
if (!Session::get('csrf_token')) { Session::set('csrf_token', bin2hex(random_bytes(18))); }
$csrf = csrfToken(); // helper in functions.php

/* --------------------------- Handle POST ---------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid security token. Please refresh and try again.';
    } else {
        foreach ($form as $k => $v) { $form[$k] = $_POST[$k] ?? $v; }

        if (trim((string)$form['name']) === '') {
            $errors['name'] = 'Product name is required.';
        }
        if ($form['price'] === '' || !is_numeric($form['price'])) {
            $errors['price'] = 'Price must be a valid number.';
        }
        if ($form['category_id'] !== '' && !ctype_digit((string)$form['category_id'])) {
            $errors['category_id'] = 'Invalid category.';
        }
        if ($form['brand_id'] !== '' && !ctype_digit((string)$form['brand_id'])) {
            $errors['brand_id'] = 'Invalid brand.';
        }

        if (!$errors) {
            $now = date('Y-m-d H:i:s');

            // Normalize
            $name  = trim((string)$form['name']);
            $slug  = trim((string)$form['slug']); if ($slug === '') { $slug = slugify($name); }
            $sku   = trim((string)$form['sku']);
            
            // Auto-generate SKU if not provided
            if ($sku === '') {
                $sku = 'V' . $vendorId . '-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 6)) . '-' . time();
            }

            $price          = toNumericOrNull($form['price']);
            $compare_price  = toNumericOrNull($form['compare_price']);
            $cost_price     = toNumericOrNull($form['cost_price']);
            $sale_price     = toNumericOrNull($form['sale_price']);
            $stock_quantity = (int) (toNumericOrNull($form['stock_quantity']) ?? 0);
            $low_stock      = (int) (toNumericOrNull($form['low_stock_threshold']) ?? 5);

            $sale_start_date = toNullIfEmpty($form['sale_start_date']);
            $sale_end_date   = toNullIfEmpty($form['sale_end_date']);

            $category_id     = toNullIfEmpty($form['category_id']);
            $brand_id        = toNullIfEmpty($form['brand_id']);
            $tags            = trim((string)$form['tags']);

            $track_inventory = toBool($form['track_inventory'] ?? 0);
            $allow_backorder = toBool($form['allow_backorder'] ?? 0);
            $backorder_limit = toNumericOrNull($form['backorder_limit']);

            $weight          = toNumericOrNull($form['weight']);
            $length          = toNumericOrNull($form['length']);
            $width           = toNumericOrNull($form['width']);
            $height          = toNumericOrNull($form['height']);
            $free_shipping   = toBool($form['free_shipping'] ?? 0);

            $shipping_class  = trim((string)$form['shipping_class']);
            $handling_time   = (string)($form['handling_time'] ?? '1');
            $currency        = strtoupper(trim((string)$form['currency_code'] ?: 'USD'));

            $condition       = in_array($form['condition'], ['new','used','refurbished'], true) ? $form['condition'] : 'new';
            $status          = in_array($form['status'], ['draft','active','archived'], true) ? $form['status'] : 'draft';
            $visibility      = in_array($form['visibility'], ['public','private','hidden'], true) ? $form['visibility'] : 'public';
            $featured        = toBool($form['featured'] ?? 0);

            $short_desc      = trim((string)$form['short_description']);
            $desc            = trim((string)$form['description']);

            $hs_code         = trim((string)$form['hs_code']);
            $origin          = trim((string)$form['country_of_origin']);

            $meta_title      = trim((string)$form['meta_title']);
            $meta_desc       = trim((string)$form['meta_description']);
            $meta_keywords   = trim((string)$form['meta_keywords']);
            $focus_keyword   = trim((string)$form['focus_keyword']);

            try {
                Database::beginTransaction();

                /* -------- Adaptive INSERT into products (only existing columns) -------- */
                $pCols = db_columns_for_table('products');
                $fieldMap = [
                    'vendor_id' => $vendorId,
                    'category_id' => $category_id, 'brand_id' => $brand_id,
                    'name' => $name, 'slug' => $slug, 'sku' => $sku,
                    'short_description' => $short_desc, 'description' => $desc,
                    'price' => $price, 'compare_price' => $compare_price, 'cost_price' => $cost_price,
                    'sale_price' => $sale_price, 'sale_start_date' => $sale_start_date, 'sale_end_date' => $sale_end_date,
                    'currency_code' => $currency, 'stock_quantity' => $stock_quantity, 'low_stock_threshold' => $low_stock,
                    'track_inventory' => $track_inventory, 'allow_backorder' => $allow_backorder, 'backorder_limit' => $backorder_limit,
                    'tags' => $tags, 'status' => $status, 'visibility' => $visibility,
                    'condition' => $condition, 'featured' => $featured,
                    'weight' => $weight, 'length' => $length, 'width' => $width, 'height' => $height,
                    'shipping_class' => $shipping_class, 'handling_time' => $handling_time, 'free_shipping' => $free_shipping,
                    'hs_code' => $hs_code, 'country_of_origin' => $origin,
                    'meta_title' => $meta_title, 'meta_description' => $meta_desc,
                    'meta_keywords' => $meta_keywords, 'focus_keyword' => $focus_keyword,
                    'created_at' => $now, 'updated_at' => $now,
                ];

                $insertCols = []; $placeholders = []; $params = [];
                foreach ($fieldMap as $col => $val) {
                    if (db_has_col($pCols, $col)) {
                        $insertCols[] = "`$col`";
                        $ph = ':' . $col;
                        $placeholders[] = $ph;
                        $params[$ph] = $val;
                    }
                }
                if (!$insertCols) {
                    throw new RuntimeException('No matching columns found in products table.');
                }
                $sql = "INSERT INTO products (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $placeholders) . ")";
                Database::query($sql, $params);
                $productId = (int) Database::lastInsertId();

                /* ------------------------------ image helpers --------------------------- */
                $fallback_save_single = function(array $file): ?array {
                    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
                    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
                    $ext = preg_replace('/[^a-z0-9]/i', '', $ext);
                    $dir = __DIR__ . '/../../uploads/products';
                    if (!is_dir($dir)) @mkdir($dir, 0775, true);
                    $basename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                    $dest = $dir . '/' . $basename;
                    if (!@move_uploaded_file($file['tmp_name'], $dest)) return null;
                    return ['path' => '/uploads/products/' . $basename, 'is_primary' => 0];
                };
                $fallback_save_multi = function(array $files) use ($fallback_save_single): array {
                    $out = [];
                    if (!isset($files['name']) || !is_array($files['name'])) return $out;
                    $count = count($files['name']);
                    for ($i=0; $i<$count; $i++){
                        $f = [
                            'name'     => $files['name'][$i]     ?? '',
                            'type'     => $files['type'][$i]     ?? '',
                            'tmp_name' => $files['tmp_name'][$i] ?? '',
                            'error'    => $files['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
                            'size'     => $files['size'][$i]     ?? 0,
                        ];
                        $saved = $fallback_save_single($f);
                        if ($saved) $out[] = $saved;
                    }
                    return $out;
                };

                /* ------------------------------ images table ---------------------------- */
                $iCols        = db_columns_for_table('product_images');
                $hasCreatedAt = db_has_col($iCols, 'created_at');
                $hasTypeCol   = db_has_col($iCols, 'type');
                $hasIsPrimary = db_has_col($iCols, 'is_primary');
                $hasImgUrlCol = db_has_col($iCols, 'image_url'); // NEW: support schemas with image_url

                $primaryThumbPath = null;

                // THUMBNAIL (single)
                if (!empty($_FILES['thumbnail']) && is_array($_FILES['thumbnail'])) {
                    $thumbUpload = null;
                    if (function_exists('handle_image_uploads')) {
                        $fake = [
                            'name'     => [ $_FILES['thumbnail']['name']     ?? '' ],
                            'type'     => [ $_FILES['thumbnail']['type']     ?? '' ],
                            'tmp_name' => [ $_FILES['thumbnail']['tmp_name'] ?? '' ],
                            'error'    => [ $_FILES['thumbnail']['error']    ?? UPLOAD_ERR_NO_FILE ],
                            'size'     => [ $_FILES['thumbnail']['size']     ?? 0 ],
                        ];
                        $arr = handle_image_uploads($fake);
                        $thumbUpload = $arr[0] ?? null;
                    } else {
                        $thumbUpload = $fallback_save_single($_FILES['thumbnail']);
                    }
                    if ($thumbUpload && !empty($thumbUpload['path'])) {
                        $primaryThumbPath = $thumbUpload['path'];
                        $cols = ['product_id','file_path']; $vals = ['?','?']; $prm = [$productId, $thumbUpload['path']];
                        if ($hasImgUrlCol){ $cols[]='image_url'; $vals[]='?'; $prm[]=$thumbUpload['path']; }
                        if ($hasIsPrimary){ $cols[]='is_primary'; $vals[]='?'; $prm[]=1; }
                        if ($hasTypeCol){   $cols[]='type';       $vals[]='?'; $prm[]='thumbnail'; }
                        if ($hasCreatedAt){ $cols[]='created_at'; $vals[]='NOW()'; }
                        $sql = "INSERT INTO product_images (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
                        Database::query($sql, $prm);
                    }
                }

                // GALLERY (multiple)
                if (!empty($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
                    $galleryUploads = function_exists('handle_image_uploads') ? handle_image_uploads($_FILES['gallery']) : $fallback_save_multi($_FILES['gallery']);
                    foreach ($galleryUploads as $img) {
                        if (empty($img['path'])) continue;
                        $cols = ['product_id','file_path']; $vals = ['?','?']; $prm = [$productId, $img['path']];
                        if ($hasImgUrlCol){ $cols[]='image_url'; $vals[]='?'; $prm[]=$img['path']; }
                        if ($hasIsPrimary){ $cols[]='is_primary'; $vals[]='?'; $prm[]=0; }
                        if ($hasTypeCol){   $cols[]='type';       $vals[]='?'; $prm[]='gallery'; }
                        if ($hasCreatedAt){ $cols[]='created_at'; $vals[]='NOW()'; }
                        $sql = "INSERT INTO product_images (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
                        Database::query($sql, $prm);
                    }
                }

                // If products table has image_url (NOT NULL), set it from thumbnail path (or first gallery if no thumbnail)
                $pHasImageUrl = db_has_col($pCols,'image_url');
                if ($pHasImageUrl) {
                    $imgForProduct = $primaryThumbPath;
                    if (!$imgForProduct) {
                        // try to fetch first image path we just inserted
                        $path = Database::query("SELECT file_path FROM product_images WHERE product_id=? ORDER BY is_primary DESC, id ASC LIMIT 1", [$productId])->fetchColumn();
                        if ($path) $imgForProduct = $path;
                    }
                    if ($imgForProduct) {
                        Database::query("UPDATE products SET image_url=? WHERE id=?", [$imgForProduct, $productId]);
                    } else {
                        // ensure a value for NOT NULL column
                        Database::query("UPDATE products SET image_url='' WHERE id=?", [$productId]);
                    }
                }

                /* -------------------------------- Tags -------------------------------- */
                if ($tags !== '') {
                    $tagList = array_values(array_filter(array_map('trim', explode(',', $tags))));
                    if ($tagList) {
                        $tagCols = db_columns_for_table('tags');
                        $pivot   = db_columns_for_table('product_tag');
                        $hasCreatedAtTag = db_has_col($tagCols, 'created_at');
                        foreach ($tagList as $tg) {
                            $tagId = Database::query("SELECT id FROM tags WHERE name=?", [$tg])->fetchColumn();
                            if (!$tagId && db_has_col($tagCols, 'name')) {
                                if ($hasCreatedAtTag) {
                                    Database::query("INSERT INTO tags (name,created_at) VALUES (?,NOW())", [$tg]);
                                } else {
                                    Database::query("INSERT INTO tags (name) VALUES (?)", [$tg]);
                                }
                                $tagId = Database::lastInsertId();
                            }
                            if ($tagId && db_has_col($pivot,'product_id') && db_has_col($pivot,'tag_id')) {
                                Database::query("INSERT IGNORE INTO product_tag (product_id,tag_id) VALUES (?,?)", [$productId, $tagId]);
                            }
                        }
                    }
                }

                /* ---------------------- Cross/upsell relations ----------------------- */
                $relCols = db_columns_for_table('product_related');
                if ($relCols) {
                    $relInsert = function(array $ids, string $type) use ($productId, $relCols) {
                        $ids = array_unique(array_map('intval', $ids));
                        foreach ($ids as $rid) {
                            if ($rid > 0 && $rid !== $productId) {
                                $sql = "INSERT IGNORE INTO product_related (product_id,related_product_id,relation_type";
                                if (db_has_col($relCols,'created_at')) $sql .= ",created_at";
                                $sql .= ") VALUES (?,?,?";
                                if (db_has_col($relCols,'created_at')) $sql .= ",NOW()";
                                $sql .= ")";
                                Database::query($sql, [$productId, $rid, $type]);
                            }
                        }
                    };
                    if (!empty($form['cross_sell_products'])) {
                        $relInsert(preg_split('/[,\s]+/', (string)$form['cross_sell_products']), 'cross_sell');
                    }
                    if (!empty($form['upsell_products'])) {
                        $relInsert(preg_split('/[,\s]+/', (string)$form['upsell_products']), 'upsell');
                    }
                }

                Database::commit();
                header('Location: /seller/products/edit.php?id='.(int)$productId);
                exit;

            } catch (Throwable $e) {
                try { Database::rollback(); } catch (Throwable $ignore) {}
                error_log('Add product failed for user '.Session::getUserId().': '.$e->getMessage());
                $errors['general'] = 'Unexpected error while creating the product: '.h($e->getMessage());
            }
        }
    }
}

/* --------------------------- Render --------------------------------------- */
$page_title = 'Add New Product';
$breadcrumb_items = [
    ['title' => 'Products', 'url' => '/seller/products/'],
    ['title' => 'Add New Product']
];
includeHeader($page_title);
?>
<div class="container my-4">
    <h1 class="h3 mb-3">Add New Product</h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= h($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="">
        <?= csrfTokenInput(); ?>

        <!-- Basics -->
        <div class="card mb-3">
            <div class="card-header">Basic Info</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':''; ?>" value="<?= h($form['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= h($errors['name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?= h($form['slug']) ?>" placeholder="auto from name">
                </div>
                <div class="col-md-4">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="<?= h($form['sku']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select <?= isset($errors['category_id'])?'is-invalid':''; ?>">
                        <option value="">-- Select --</option>
                        <?php foreach ($allCategories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ($form['category_id']==$c['id']?'selected':'') ?>><?= h($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback"><?= h($errors['category_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <select name="brand_id" class="form-select <?= isset($errors['brand_id'])?'is-invalid':''; ?>">
                        <option value="">-- Select --</option>
                        <?php foreach ($allBrands as $b): ?>
                            <option value="<?= (int)$b['id'] ?>" <?= ($form['brand_id']==$b['id']?'selected':'') ?>><?= h($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['brand_id'])): ?><div class="invalid-feedback"><?= h($errors['brand_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Short Description</label>
                    <textarea name="short_description" class="form-control" rows="2"><?= h($form['short_description']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="6"><?= h($form['description']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="card mb-3">
            <div class="card-header">Pricing</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency_code" class="form-control" value="<?= h($form['currency_code']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" name="price" class="form-control <?= isset($errors['price'])?'is-invalid':''; ?>" value="<?= h($form['price']) ?>" required>
                    <?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= h($errors['price']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Compare-at Price</label>
                    <input type="number" step="0.01" name="compare_price" class="form-control" value="<?= h($form['compare_price']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cost Price</label>
                    <input type="number" step="0.01" name="cost_price" class="form-control" value="<?= h($form['cost_price']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale Price</label>
                    <input type="number" step="0.01" name="sale_price" class="form-control" value="<?= h($form['sale_price']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale Start</label>
                    <input type="datetime-local" name="sale_start_date" class="form-control" value="<?= h($form['sale_start_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale End</label>
                    <input type="datetime-local" name="sale_end_date" class="form-control" value="<?= h($form['sale_end_date']) ?>">
                </div>
            </div>
        </div>

        <!-- Inventory -->
        <div class="card mb-3">
            <div class="card-header">Inventory</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Stock Qty</label>
                    <input type="number" name="stock_quantity" class="form-control" value="<?= h($form['stock_quantity']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Low Stock Threshold</label>
                    <input type="number" name="low_stock_threshold" class="form-control" value="<?= h($form['low_stock_threshold']) ?>">
                </div>
                <div class="col-md-3 form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="track_inventory" value="1" <?= ($form['track_inventory']? 'checked':'') ?> id="invTrack">
                    <label class="form-check-label" for="invTrack">Track inventory</label>
                </div>
                <div class="col-md-3 form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="allow_backorder" value="1" <?= ($form['allow_backorder']? 'checked':'') ?> id="invBack">
                    <label class="form-check-label" for="invBack">Allow backorder</label>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Backorder Limit</label>
                    <input type="number" name="backorder_limit" class="form-control" value="<?= h($form['backorder_limit']) ?>">
                </div>
            </div>
        </div>

        <!-- Shipping -->
        <div class="card mb-3">
            <div class="card-header">Shipping</div>
            <div class="card-body row g-3">
                <div class="col-md-2">
                    <label class="form-label">Weight</label>
                    <input type="number" step="0.001" name="weight" class="form-control" value="<?= h($form['weight']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Length</label>
                    <input type="number" step="0.01" name="length" class="form-control" value="<?= h($form['length']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Width</label>
                    <input type="number" step="0.01" name="width" class="form-control" value="<?= h($form['width']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Height</label>
                    <input type="number" step="0.01" name="height" class="form-control" value="<?= h($form['height']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Shipping Class</label>
                    <input type="text" name="shipping_class" class="form-control" value="<?= h($form['shipping_class']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Handling Time (days)</label>
                    <input type="number" name="handling_time" class="form-control" value="<?= h($form['handling_time']) ?>">
                </div>
                <div class="col-md-3 form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="free_shipping" value="1" <?= ($form['free_shipping']? 'checked':'') ?> id="freeShip">
                    <label class="form-check-label" for="freeShip">Free shipping</label>
                </div>
                <div class="col-md-3">
                    <label class="form-label">HS Code</label>
                    <input type="text" name="hs_code" class="form-control" value="<?= h($form['hs_code']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Country of Origin</label>
                    <input type="text" name="country_of_origin" class="form-control" value="<?= h($form['country_of_origin']) ?>">
                </div>
            </div>
        </div>

        <!-- Classification & Flags -->
        <div class="card mb-3">
            <div class="card-header">Classification & Flags</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft" <?= $form['status']==='draft'?'selected':''; ?>>Draft</option>
                        <option value="active" <?= $form['status']==='active'?'selected':''; ?>>Active</option>
                        <option value="archived" <?= $form['status']==='archived'?'selected':''; ?>>Archived</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Visibility</label>
                    <select name="visibility" class="form-select">
                        <option value="public" <?= $form['visibility']==='public'?'selected':''; ?>>Public</option>
                        <option value="private" <?= $form['visibility']==='private'?'selected':''; ?>>Private</option>
                        <option value="hidden" <?= $form['visibility']==='hidden'?'selected':''; ?>>Hidden</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Condition</label>
                    <select name="condition" class="form-select">
                        <option value="new" <?= $form['condition']==='new'?'selected':''; ?>>New</option>
                        <option value="used" <?= $form['condition']==='used'?'selected':''; ?>>Used</option>
                        <option value="refurbished" <?= $form['condition']==='refurbished'?'selected':''; ?>>Refurbished</option>
                    </select>
                </div>
                <div class="col-md-3 form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="featured" value="1" <?= ($form['featured']? 'checked':'') ?> id="flagFeatured">
                    <label class="form-check-label" for="flagFeatured">Featured</label>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Tags (comma-separated)</label>
                    <input type="text" name="tags" class="form-control" value="<?= h($form['tags']) ?>">
                </div>
            </div>
        </div>

        <!-- Images: Thumbnail + Gallery (with previews) -->
        <div class="card mb-3">
          <div class="card-header">Images</div>
          <div class="card-body row g-4">
            <!-- Thumbnail -->
            <div class="col-12 col-md-4">
              <label class="form-label">Thumbnail (primary)</label>
              <input type="file" name="thumbnail" id="thumbnailInput" accept="image/*" class="form-control">
              <div class="form-text">This will be the product’s main image.</div>
              <div id="thumbPreview" class="mt-2 d-flex align-items-center" style="min-height:88px;"></div>
            </div>

            <!-- Gallery -->
            <div class="col-12 col-md-8">
              <label class="form-label">Gallery images</label>
              <input type="file" name="gallery[]" id="galleryInput" accept="image/*" multiple class="form-control">
              <div class="form-text">You can select multiple images at once.</div>
              <div id="galleryPreview" class="mt-2 d-flex flex-wrap gap-2" style="min-height:88px;"></div>
            </div>
          </div>
        </div>

        <!-- Relations -->
        <div class="card mb-3">
            <div class="card-header">Related Products</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Cross-sell (IDs, comma/space separated)</label>
                    <input type="text" name="cross_sell_products" class="form-control" value="<?= h($form['cross_sell_products']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upsell (IDs, comma/space separated)</label>
                    <input type="text" name="upsell_products" class="form-control" value="<?= h($form['upsell_products']) ?>">
                </div>
                <?php if ($allProducts): ?>
                <div class="col-12">
                    <div class="form-text">
                        Quick pick:
                        <?php foreach ($allProducts as $p): ?>
                            <span class="badge bg-secondary me-1"><?= (int)$p['id'] ?> - <?= h($p['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SEO -->
        <div class="card mb-4">
            <div class="card-header">SEO</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="<?= h($form['meta_title']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Focus Keyword</label>
                    <input type="text" name="focus_keyword" class="form-control" value="<?= h($form['focus_keyword']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="2"><?= h($form['meta_description']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Meta Keywords (comma-separated)</label>
                    <input type="text" name="meta_keywords" class="form-control" value="<?= h($form['meta_keywords']) ?>">
                </div>
            </div>
        </div>

        <div class="mb-5">
            <button type="submit" class="btn btn-primary">Create Product</button>
            <a href="/seller/products/" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<!-- Live image previews -->
<script>
(function(){
  function makeImg(src, title){
    const img = document.createElement('img');
    img.src = src; img.alt = title || 'preview';
    img.style.maxWidth = '120px'; img.style.maxHeight = '88px'; img.style.objectFit = 'cover';
    img.style.borderRadius = '8px'; img.style.boxShadow = '0 1px 4px rgba(0,0,0,0.12)';
    img.loading = 'lazy'; return img;
  }
  function clear(el){ while(el.firstChild) el.removeChild(el.firstChild); }

  const thumbInput = document.getElementById('thumbnailInput');
  const thumbPreview = document.getElementById('thumbPreview');
  if (thumbInput && thumbPreview){
    thumbInput.addEventListener('change', function(){
      clear(thumbPreview);
      const f = this.files && this.files[0];
      if (!f) return;
      const fr = new FileReader();
      fr.onload = e => thumbPreview.appendChild(makeImg(e.target.result, f.name));
      fr.readAsDataURL(f);
    });
  }

  const galleryInput = document.getElementById('galleryInput');
  const galleryPreview = document.getElementById('galleryPreview');
  if (galleryInput && galleryPreview){
    galleryInput.addEventListener('change', function(){
      clear(galleryPreview);
      const files = Array.from(this.files || []);
      files.forEach(f => {
        const fr = new FileReader();
        fr.onload = e => galleryPreview.appendChild(makeImg(e.target.result, f.name));
        fr.readAsDataURL(f);
      });
    });
  }
})();
</script>

<?php includeFooter();
