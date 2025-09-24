<?php
/**
 * Homepage CMS Editor - eBay-style Layout Management
 * Drag-and-drop editor for managing homepage sections
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';

// Initialize database and auth
$pdo = db();
requireAdminAuth();
checkPermission('cms.manage');

// Set reasonable limits for file uploads
ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '6M');
ini_set('max_execution_time', '30');

$message = '';
$error = '';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'save_layout':
                if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception('Invalid CSRF token');
                }
                
                $sections = json_decode($_POST['sections'] ?? '[]', true);
                if (!is_array($sections)) {
                    throw new Exception('Invalid sections data - must be an array');
                }
                
                // Validate section structure
                $validSectionTypes = ['hero', 'categories', 'deals', 'products', 'brands'];
                foreach ($sections as $section) {
                    if (!is_array($section) || !isset($section['id']) || !isset($section['type'])) {
                        throw new Exception('Invalid section structure - missing required fields');
                    }
                    if (!in_array($section['type'], $validSectionTypes)) {
                        throw new Exception('Invalid section type: ' . htmlspecialchars($section['type']));
                    }
                    if (strlen($section['id']) > 50) {
                        throw new Exception('Section ID too long');
                    }
                }
                
                // Save layout configuration - use INSERT ... ON DUPLICATE KEY UPDATE
                $stmt = $pdo->prepare("
                    INSERT INTO homepage_sections (section_key, section_data, created_at, updated_at) 
                    VALUES ('layout_config', ?, NOW(), NOW()) 
                    ON DUPLICATE KEY UPDATE section_data = VALUES(section_data), updated_at = NOW()
                ");
                $stmt->execute([json_encode($sections)]);
                
                echo json_encode(['success' => true, 'message' => 'Layout saved successfully']);
                exit;
                
            case 'upload_banner':
                if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception('Invalid CSRF token');
                }
                
                // Validate input parameters
                $position = $_POST['position'] ?? 'hero';
                $title = trim($_POST['title'] ?? '');
                
                $validPositions = ['hero', 'top', 'middle', 'bottom', 'sidebar'];
                if (!in_array($position, $validPositions)) {
                    throw new Exception('Invalid banner position');
                }
                
                if (strlen($title) > 255) {
                    throw new Exception('Title too long (max 255 characters)');
                }
                
                // Handle file upload
                if (!isset($_FILES['banner_image']) || $_FILES['banner_image']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No file uploaded or upload error');
                }
                
                $file = $_FILES['banner_image'];
                $uploadDir = __DIR__ . '/../../uploads/banners/';
                
                // Additional security checks
                $maxFileSize = 5 * 1024 * 1024; // 5MB
                if ($file['size'] > $maxFileSize) {
                    throw new Exception('File too large. Maximum size is 5MB.');
                }
                
                // Validate MIME type
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileMimeType = mime_content_type($file['tmp_name']);
                if (!in_array($fileMimeType, $allowedMimeTypes)) {
                    throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.');
                }
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception('Invalid file extension. Only JPG, PNG, GIF, and WebP are allowed.');
                }
                
                $fileName = uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    throw new Exception('Failed to upload file');
                }
                
                $imageUrl = '/uploads/banners/' . $fileName;
                
                // Save banner to database
                $stmt = $pdo->prepare("
                    INSERT INTO homepage_banners (title, image_url, position, status, created_by) 
                    VALUES (?, ?, ?, 'active', ?)
                ");
                $stmt->execute([$title, $imageUrl, $position, getCurrentUserId()]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Banner uploaded successfully',
                    'banner_id' => $pdo->lastInsertId(),
                    'image_url' => $imageUrl
                ]);
                exit;
                
            case 'delete_banner':
                if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                    throw new Exception('Invalid CSRF token');
                }
                
                $bannerId = (int)($_POST['banner_id'] ?? 0);
                if ($bannerId <= 0) {
                    throw new Exception('Invalid banner ID');
                }
                
                // Get banner info for file deletion
                $stmt = $pdo->prepare("SELECT image_url FROM homepage_banners WHERE id = ?");
                $stmt->execute([$bannerId]);
                $banner = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($banner) {
                    // Delete file
                    $filePath = __DIR__ . '/../../' . ltrim($banner['image_url'], '/');
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    
                    // Delete from database
                    $stmt = $pdo->prepare("DELETE FROM homepage_banners WHERE id = ?");
                    $stmt->execute([$bannerId]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Banner deleted successfully']);
                exit;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Get current layout sections
$layoutSections = [];
try {
    $stmt = $pdo->prepare("SELECT section_data FROM homepage_sections WHERE section_key = 'layout_config'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['section_data']) {
        $layoutSections = json_decode($result['section_data'], true) ?: [];
    }
} catch (Exception $e) {
    // Create table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS homepage_sections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_key VARCHAR(100) UNIQUE NOT NULL,
            section_data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
}

// Default sections if none exist
if (empty($layoutSections)) {
    $layoutSections = [
        ['id' => 'hero', 'type' => 'hero', 'title' => 'Hero Banner', 'enabled' => true],
        ['id' => 'categories', 'type' => 'categories', 'title' => 'Featured Categories', 'enabled' => true],
        ['id' => 'deals', 'type' => 'deals', 'title' => 'Daily Deals', 'enabled' => true],
        ['id' => 'trending', 'type' => 'products', 'title' => 'Trending Products', 'enabled' => true],
        ['id' => 'brands', 'type' => 'brands', 'title' => 'Top Brands', 'enabled' => true],
        ['id' => 'featured', 'type' => 'products', 'title' => 'Featured Products', 'enabled' => true],
        ['id' => 'new-arrivals', 'type' => 'products', 'title' => 'New Arrivals', 'enabled' => true],
        ['id' => 'recommendations', 'type' => 'products', 'title' => 'Recommended for You', 'enabled' => true]
    ];
}

// Get all banners
$banners = [];
try {
    $stmt = $pdo->query("SELECT * FROM homepage_banners ORDER BY position, sort_order");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $banners = [];
}

// Group banners by position
$bannersByPosition = [];
foreach ($banners as $banner) {
    $position = $banner['position'] ?? 'hero';
    if (!isset($bannersByPosition[$position])) {
        $bannersByPosition[$position] = [];
    }
    $bannersByPosition[$position][] = $banner;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage Editor - FezaMarket CMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Sortable.js for drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <style>
        :root {
            --primary-color: #0654ba;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --dark-color: #343a40;
        }
        
        .editor-container {
            min-height: 100vh;
            background: var(--secondary-color);
        }
        
        .editor-sidebar {
            background: white;
            border-right: 1px solid #dee2e6;
            min-height: 100vh;
            position: fixed;
            width: 320px;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .editor-main {
            margin-left: 320px;
            padding: 20px;
        }
        
        .section-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 15px;
            cursor: move;
            transition: all 0.3s ease;
        }
        
        .section-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(6, 84, 186, 0.1);
        }
        
        .section-item.disabled {
            opacity: 0.6;
            background: #f8f9fa;
        }
        
        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .section-title {
            font-weight: 600;
            margin: 0;
            flex: 1;
        }
        
        .section-controls {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .section-preview {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            position: relative;
        }
        
        .section-preview.has-content {
            border-style: solid;
            border-color: var(--primary-color);
        }
        
        .preview-placeholder {
            text-align: center;
            color: #6c757d;
        }
        
        .banner-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .banner-item {
            position: relative;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
            background: white;
        }
        
        .banner-item img {
            width: 100%;
            height: 80px;
            object-fit: cover;
        }
        
        .banner-item .banner-info {
            padding: 8px;
            font-size: 12px;
        }
        
        .banner-item .banner-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: flex;
            gap: 2px;
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(6, 84, 186, 0.05);
        }
        
        .upload-area.dragover {
            border-color: var(--success-color);
            background: rgba(40, 167, 69, 0.05);
        }
        
        .save-bar {
            position: fixed;
            bottom: 0;
            left: 320px;
            right: 0;
            background: white;
            border-top: 1px solid #dee2e6;
            padding: 15px 20px;
            z-index: 999;
        }
        
        .section-type-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .badge-hero { background: #ff6b6b; color: white; }
        .badge-categories { background: #4ecdc4; color: white; }
        .badge-products { background: #45b7d1; color: white; }
        .badge-deals { background: #f9ca24; color: #333; }
        .badge-brands { background: #f0932b; color: white; }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .editor-sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }
            
            .editor-main {
                margin-left: 0;
            }
            
            .save-bar {
                left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="editor-container">
        <!-- Sidebar -->
        <div class="editor-sidebar">
            <div class="p-3 border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-home me-2"></i>
                    Homepage Editor
                </h5>
                <small class="text-muted">Drag sections to reorder</small>
            </div>
            
            <!-- Section List -->
            <div class="p-3">
                <h6 class="text-uppercase text-muted mb-3">
                    <i class="fas fa-layer-group me-1"></i>
                    Layout Sections
                </h6>
                
                <div id="sections-list">
                    <?php foreach ($layoutSections as $index => $section): ?>
                    <div class="section-item <?= !$section['enabled'] ? 'disabled' : '' ?>" 
                         data-section-id="<?= htmlspecialchars($section['id']) ?>"
                         data-section-type="<?= htmlspecialchars($section['type']) ?>">
                        
                        <div class="section-header">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-2"></i>
                                <h6 class="section-title"><?= htmlspecialchars($section['title']) ?></h6>
                                <span class="section-type-badge badge-<?= $section['type'] ?> ms-2">
                                    <?= $section['type'] ?>
                                </span>
                            </div>
                            
                            <div class="section-controls">
                                <button class="btn btn-sm btn-outline-primary btn-icon" 
                                        onclick="toggleSection('<?= $section['id'] ?>')"
                                        title="<?= $section['enabled'] ? 'Disable' : 'Enable' ?> Section">
                                    <i class="fas fa-<?= $section['enabled'] ? 'eye' : 'eye-slash' ?>"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-icon" 
                                        onclick="editSection('<?= $section['id'] ?>')"
                                        title="Edit Section">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="section-meta">
                            <small class="text-muted">
                                Order: <?= $index + 1 ?> | 
                                Status: <?= $section['enabled'] ? 'Enabled' : 'Disabled' ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Banner Management -->
            <div class="p-3 border-top">
                <h6 class="text-uppercase text-muted mb-3">
                    <i class="fas fa-images me-1"></i>
                    Banner Management
                </h6>
                
                <div class="upload-area" onclick="document.getElementById('banner-upload').click()">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-0">Click to upload banner</p>
                    <small class="text-muted">JPG, PNG, GIF, WebP (max 5MB)</small>
                </div>
                
                <input type="file" id="banner-upload" style="display: none;" 
                       accept="image/*" onchange="handleBannerUpload(this)">
                
                <!-- Banner list by position -->
                <div class="banner-sections">
                    <?php foreach (['hero', 'top', 'middle', 'bottom'] as $position): ?>
                    <div class="mb-3">
                        <h6 class="text-capitalize mb-2"><?= $position ?> Banners</h6>
                        <div class="banner-grid">
                            <?php 
                            $positionBanners = $bannersByPosition[$position] ?? [];
                            foreach ($positionBanners as $banner): 
                            ?>
                            <div class="banner-item" data-banner-id="<?= $banner['id'] ?>">
                                <img src="<?= htmlspecialchars($banner['image_url']) ?>" 
                                     alt="<?= htmlspecialchars($banner['title']) ?>">
                                <div class="banner-actions">
                                    <button class="btn btn-sm btn-danger btn-icon" 
                                            onclick="deleteBanner(<?= $banner['id'] ?>)"
                                            title="Delete Banner">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="banner-info">
                                    <div class="fw-bold"><?= htmlspecialchars($banner['title']) ?></div>
                                    <small class="text-muted">Clicks: <?= $banner['click_count'] ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Main Editor Area -->
        <div class="editor-main">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Homepage Layout Preview</h4>
                    <p class="text-muted mb-0">Preview how your homepage will look</p>
                </div>
                <div>
                    <a href="/" target="_blank" class="btn btn-outline-primary me-2">
                        <i class="fas fa-external-link-alt me-1"></i>
                        Preview Live
                    </a>
                    <button class="btn btn-primary" onclick="saveLayout()">
                        <i class="fas fa-save me-1"></i>
                        Save Layout
                    </button>
                </div>
            </div>
            
            <!-- Preview Area -->
            <div id="preview-area" class="bg-white rounded border">
                <!-- Sections will be dynamically rendered here -->
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-eye fa-3x mb-3"></i>
                    <h5>Homepage Preview</h5>
                    <p>Your homepage sections will appear here as you configure them</p>
                </div>
            </div>
        </div>
        
        <!-- Save Bar -->
        <div class="save-bar">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Last saved: <span id="last-saved">Never</span></small>
                </div>
                <div>
                    <button class="btn btn-outline-secondary me-2" onclick="resetLayout()">
                        <i class="fas fa-undo me-1"></i>
                        Reset
                    </button>
                    <button class="btn btn-success" onclick="saveLayout()">
                        <i class="fas fa-save me-1"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global variables
        let sectionsData = <?= json_encode($layoutSections) ?>;
        let bannersData = <?= json_encode($bannersByPosition) ?>;
        let hasUnsavedChanges = false;
        
        // Initialize sortable sections
        document.addEventListener('DOMContentLoaded', function() {
            const sectionsList = document.getElementById('sections-list');
            
            Sortable.create(sectionsList, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    // Reorder sections data
                    const item = sectionsData.splice(evt.oldIndex, 1)[0];
                    sectionsData.splice(evt.newIndex, 0, item);
                    hasUnsavedChanges = true;
                    updatePreview();
                }
            });
            
            updatePreview();
        });
        
        // Toggle section enabled/disabled
        function toggleSection(sectionId) {
            const section = sectionsData.find(s => s.id === sectionId);
            if (section) {
                section.enabled = !section.enabled;
                hasUnsavedChanges = true;
                
                // Update UI
                const sectionElement = document.querySelector(`[data-section-id="${sectionId}"]`);
                if (section.enabled) {
                    sectionElement.classList.remove('disabled');
                    sectionElement.querySelector('.fa-eye-slash').className = 'fas fa-eye';
                } else {
                    sectionElement.classList.add('disabled');
                    sectionElement.querySelector('.fa-eye').className = 'fas fa-eye-slash';
                }
                
                updatePreview();
            }
        }
        
        // Edit section (placeholder for future enhancement)
        function editSection(sectionId) {
            alert('Section editing will be implemented in future version');
        }
        
        // Handle banner upload
        function handleBannerUpload(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (file.size > maxSize) {
                    alert('File too large. Maximum size is 5MB.');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'upload_banner');
                formData.append('banner_image', file);
                formData.append('title', prompt('Enter banner title:') || 'Untitled Banner');
                formData.append('position', 'hero'); // Default position
                formData.append('csrf_token', '<?= csrfToken() ?>');
                
                fetch('homepage-editor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Banner uploaded successfully!');
                        location.reload(); // Refresh to show new banner
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while uploading the banner');
                });
            }
        }
        
        // Delete banner
        function deleteBanner(bannerId) {
            if (!confirm('Are you sure you want to delete this banner?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_banner');
            formData.append('banner_id', bannerId);
            formData.append('csrf_token', '<?= csrfToken() ?>');
            
            fetch('homepage-editor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove banner from UI
                    document.querySelector(`[data-banner-id="${bannerId}"]`).remove();
                    alert('Banner deleted successfully!');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the banner');
            });
        }
        
        // Update preview area
        function updatePreview() {
            const previewArea = document.getElementById('preview-area');
            let html = '';
            
            sectionsData.forEach((section, index) => {
                if (section.enabled) {
                    html += `
                        <div class="section-preview has-content" data-section="${section.id}">
                            <div class="p-4">
                                <h5 class="mb-2">
                                    <i class="fas fa-${getSectionIcon(section.type)} me-2"></i>
                                    ${section.title}
                                </h5>
                                <p class="text-muted mb-0">
                                    ${getSectionDescription(section.type)}
                                </p>
                                <small class="text-primary">Section ${index + 1} - ${section.type}</small>
                            </div>
                        </div>
                    `;
                }
            });
            
            if (html === '') {
                html = `
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-eye-slash fa-3x mb-3"></i>
                        <h5>No sections enabled</h5>
                        <p>Enable some sections from the sidebar</p>
                    </div>
                `;
            }
            
            previewArea.innerHTML = html;
        }
        
        // Get section icon
        function getSectionIcon(type) {
            const icons = {
                hero: 'star',
                categories: 'th-large',
                products: 'box',
                deals: 'tags',
                brands: 'crown'
            };
            return icons[type] || 'square';
        }
        
        // Get section description
        function getSectionDescription(type) {
            const descriptions = {
                hero: 'Main banner and call-to-action area',
                categories: 'Featured product categories grid',
                products: 'Product showcase with images and prices',
                deals: 'Special offers and discounted items',
                brands: 'Featured brand logos and links'
            };
            return descriptions[type] || 'Content section';
        }
        
        // Save layout
        function saveLayout() {
            const formData = new FormData();
            formData.append('action', 'save_layout');
            formData.append('sections', JSON.stringify(sectionsData));
            formData.append('csrf_token', '<?= csrfToken() ?>');
            
            fetch('homepage-editor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hasUnsavedChanges = false;
                    document.getElementById('last-saved').textContent = new Date().toLocaleTimeString();
                    alert('Layout saved successfully!');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the layout');
            });
        }
        
        // Reset layout
        function resetLayout() {
            if (confirm('Are you sure you want to reset the layout? This will restore the default sections.')) {
                location.reload();
            }
        }
        
        // Warn about unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Drag and drop for banner upload
        const uploadArea = document.querySelector('.upload-area');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('banner-upload').files = files;
                handleBannerUpload(document.getElementById('banner-upload'));
            }
        });
    </script>
</body>
</html>