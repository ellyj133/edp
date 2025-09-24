<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage CMS Editor Demo - FezaMarket</title>
    
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
            justify-content: space-between;
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
        
        .banner-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .banner-item {
            position: relative;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
            background: white;
            height: 100px;
        }
        
        .banner-placeholder {
            width: 100%;
            height: 60px;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 12px;
        }
        
        .banner-info {
            padding: 8px;
            font-size: 11px;
        }
        
        .banner-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: flex;
            gap: 2px;
        }
        
        .demo-notification {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            color: #1976d2;
        }
        
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
                    Homepage Editor Demo
                </h5>
                <small class="text-muted">Drag sections to reorder</small>
            </div>
            
            <div class="demo-notification">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Demo Mode:</strong> This is a demonstration of the CMS interface. In the real system, changes would be saved to the database.
            </div>
            
            <!-- Section List -->
            <div class="p-3">
                <h6 class="text-uppercase text-muted mb-3">
                    <i class="fas fa-layer-group me-1"></i>
                    Layout Sections
                </h6>
                
                <div id="sections-list">
                    <div class="section-item" data-section-id="hero" data-section-type="hero">
                        <div class="section-header">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-2"></i>
                                <h6 class="section-title">Hero Banner</h6>
                                <span class="section-type-badge badge-hero ms-2">hero</span>
                            </div>
                            <div class="section-controls">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="toggleSection('hero')" title="Disable Section">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-icon" onclick="editSection('hero')" title="Edit Section">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <div class="section-meta">
                            <small class="text-muted">Order: 1 | Status: Enabled</small>
                        </div>
                    </div>
                    
                    <div class="section-item" data-section-id="categories" data-section-type="categories">
                        <div class="section-header">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-2"></i>
                                <h6 class="section-title">Featured Categories</h6>
                                <span class="section-type-badge badge-categories ms-2">categories</span>
                            </div>
                            <div class="section-controls">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="toggleSection('categories')" title="Disable Section">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-icon" onclick="editSection('categories')" title="Edit Section">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <div class="section-meta">
                            <small class="text-muted">Order: 2 | Status: Enabled</small>
                        </div>
                    </div>
                    
                    <div class="section-item" data-section-id="deals" data-section-type="deals">
                        <div class="section-header">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-2"></i>
                                <h6 class="section-title">Daily Deals</h6>
                                <span class="section-type-badge badge-deals ms-2">deals</span>
                            </div>
                            <div class="section-controls">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="toggleSection('deals')" title="Disable Section">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-icon" onclick="editSection('deals')" title="Edit Section">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <div class="section-meta">
                            <small class="text-muted">Order: 3 | Status: Enabled</small>
                        </div>
                    </div>
                    
                    <div class="section-item" data-section-id="trending" data-section-type="products">
                        <div class="section-header">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-2"></i>
                                <h6 class="section-title">Trending Products</h6>
                                <span class="section-type-badge badge-products ms-2">products</span>
                            </div>
                            <div class="section-controls">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="toggleSection('trending')" title="Disable Section">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-icon" onclick="editSection('trending')" title="Edit Section">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <div class="section-meta">
                            <small class="text-muted">Order: 4 | Status: Enabled</small>
                        </div>
                    </div>
                    
                    <div class="section-item" data-section-id="brands" data-section-type="brands">
                        <div class="section-header">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-2"></i>
                                <h6 class="section-title">Top Brands</h6>
                                <span class="section-type-badge badge-brands ms-2">brands</span>
                            </div>
                            <div class="section-controls">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="toggleSection('brands')" title="Disable Section">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-icon" onclick="editSection('brands')" title="Edit Section">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <div class="section-meta">
                            <small class="text-muted">Order: 5 | Status: Enabled</small>
                        </div>
                    </div>
                    
                    <div class="section-item" data-section-id="featured" data-section-type="products">
                        <div class="section-header">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-2"></i>
                                <h6 class="section-title">Featured Products</h6>
                                <span class="section-type-badge badge-products ms-2">products</span>
                            </div>
                            <div class="section-controls">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="toggleSection('featured')" title="Disable Section">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-icon" onclick="editSection('featured')" title="Edit Section">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <div class="section-meta">
                            <small class="text-muted">Order: 6 | Status: Enabled</small>
                        </div>
                    </div>
                    
                    <div class="section-item" data-section-id="new-arrivals" data-section-type="products">
                        <div class="section-header">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-2"></i>
                                <h6 class="section-title">New Arrivals</h6>
                                <span class="section-type-badge badge-products ms-2">products</span>
                            </div>
                            <div class="section-controls">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="toggleSection('new-arrivals')" title="Disable Section">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-icon" onclick="editSection('new-arrivals')" title="Edit Section">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <div class="section-meta">
                            <small class="text-muted">Order: 7 | Status: Enabled</small>
                        </div>
                    </div>
                    
                    <div class="section-item" data-section-id="recommendations" data-section-type="products">
                        <div class="section-header">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-2"></i>
                                <h6 class="section-title">Recommended for You</h6>
                                <span class="section-type-badge badge-products ms-2">products</span>
                            </div>
                            <div class="section-controls">
                                <button class="btn btn-sm btn-outline-primary btn-icon" onclick="toggleSection('recommendations')" title="Disable Section">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-icon" onclick="editSection('recommendations')" title="Edit Section">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <div class="section-meta">
                            <small class="text-muted">Order: 8 | Status: Enabled</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Banner Management -->
            <div class="p-3 border-top">
                <h6 class="text-uppercase text-muted mb-3">
                    <i class="fas fa-images me-1"></i>
                    Banner Management
                </h6>
                
                <div class="upload-area" onclick="showUploadDemo()">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-0">Click to upload banner</p>
                    <small class="text-muted">JPG, PNG, GIF, WebP (max 5MB)</small>
                </div>
                
                <!-- Banner list by position -->
                <div class="banner-sections">
                    <div class="mb-3">
                        <h6 class="text-capitalize mb-2">Hero Banners</h6>
                        <div class="banner-grid">
                            <div class="banner-item">
                                <div class="banner-placeholder">Hero Banner 1</div>
                                <div class="banner-actions">
                                    <button class="btn btn-sm btn-danger btn-icon" onclick="deleteDemo()" title="Delete Banner">
                                        <i class="fas fa-trash" style="font-size: 10px;"></i>
                                    </button>
                                </div>
                                <div class="banner-info">
                                    <div class="fw-bold">Main Hero</div>
                                    <small class="text-muted">Clicks: 2,543</small>
                                </div>
                            </div>
                            <div class="banner-item">
                                <div class="banner-placeholder">Hero Banner 2</div>
                                <div class="banner-actions">
                                    <button class="btn btn-sm btn-danger btn-icon" onclick="deleteDemo()" title="Delete Banner">
                                        <i class="fas fa-trash" style="font-size: 10px;"></i>
                                    </button>
                                </div>
                                <div class="banner-info">
                                    <div class="fw-bold">Secondary</div>
                                    <small class="text-muted">Clicks: 1,234</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-capitalize mb-2">Category Banners</h6>
                        <div class="banner-grid">
                            <div class="banner-item">
                                <div class="banner-placeholder">Electronics</div>
                                <div class="banner-actions">
                                    <button class="btn btn-sm btn-danger btn-icon" onclick="deleteDemo()" title="Delete Banner">
                                        <i class="fas fa-trash" style="font-size: 10px;"></i>
                                    </button>
                                </div>
                                <div class="banner-info">
                                    <div class="fw-bold">Tech Sale</div>
                                    <small class="text-muted">Clicks: 892</small>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <a href="/demo-homepage.php" target="_blank" class="btn btn-outline-primary me-2">
                        <i class="fas fa-external-link-alt me-1"></i>
                        Preview Live
                    </a>
                    <button class="btn btn-primary" onclick="saveDemo()">
                        <i class="fas fa-save me-1"></i>
                        Save Layout
                    </button>
                </div>
            </div>
            
            <!-- Preview Area -->
            <div id="preview-area" class="bg-white rounded border">
                <div class="section-preview has-content" data-section="hero">
                    <div class="p-4">
                        <h5 class="mb-2">
                            <i class="fas fa-star me-2"></i>
                            Hero Banner
                        </h5>
                        <p class="text-muted mb-0">Main banner and call-to-action area</p>
                        <small class="text-primary">Section 1 - hero</small>
                    </div>
                </div>
                
                <div class="section-preview has-content" data-section="categories">
                    <div class="p-4">
                        <h5 class="mb-2">
                            <i class="fas fa-th-large me-2"></i>
                            Featured Categories
                        </h5>
                        <p class="text-muted mb-0">Featured product categories grid</p>
                        <small class="text-primary">Section 2 - categories</small>
                    </div>
                </div>
                
                <div class="section-preview has-content" data-section="deals">
                    <div class="p-4">
                        <h5 class="mb-2">
                            <i class="fas fa-tags me-2"></i>
                            Daily Deals
                        </h5>
                        <p class="text-muted mb-0">Special offers and discounted items</p>
                        <small class="text-primary">Section 3 - deals</small>
                    </div>
                </div>
                
                <div class="section-preview has-content" data-section="trending">
                    <div class="p-4">
                        <h5 class="mb-2">
                            <i class="fas fa-box me-2"></i>
                            Trending Products
                        </h5>
                        <p class="text-muted mb-0">Product showcase with images and prices</p>
                        <small class="text-primary">Section 4 - products</small>
                    </div>
                </div>
                
                <div class="section-preview has-content" data-section="brands">
                    <div class="p-4">
                        <h5 class="mb-2">
                            <i class="fas fa-crown me-2"></i>
                            Top Brands
                        </h5>
                        <p class="text-muted mb-0">Featured brand logos and links</p>
                        <small class="text-primary">Section 5 - brands</small>
                    </div>
                </div>
                
                <div class="section-preview has-content" data-section="featured">
                    <div class="p-4">
                        <h5 class="mb-2">
                            <i class="fas fa-box me-2"></i>
                            Featured Products
                        </h5>
                        <p class="text-muted mb-0">Product showcase with images and prices</p>
                        <small class="text-primary">Section 6 - products</small>
                    </div>
                </div>
                
                <div class="section-preview has-content" data-section="new-arrivals">
                    <div class="p-4">
                        <h5 class="mb-2">
                            <i class="fas fa-box me-2"></i>
                            New Arrivals
                        </h5>
                        <p class="text-muted mb-0">Product showcase with images and prices</p>
                        <small class="text-primary">Section 7 - products</small>
                    </div>
                </div>
                
                <div class="section-preview has-content" data-section="recommendations">
                    <div class="p-4">
                        <h5 class="mb-2">
                            <i class="fas fa-box me-2"></i>
                            Recommended for You
                        </h5>
                        <p class="text-muted mb-0">Product showcase with images and prices</p>
                        <small class="text-primary">Section 8 - products</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Save Bar -->
        <div class="save-bar">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Last saved: <span id="last-saved">Demo Mode</span></small>
                </div>
                <div>
                    <button class="btn btn-outline-secondary me-2" onclick="resetDemo()">
                        <i class="fas fa-undo me-1"></i>
                        Reset
                    </button>
                    <button class="btn btn-success" onclick="saveDemo()">
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
        // Initialize sortable sections
        document.addEventListener('DOMContentLoaded', function() {
            const sectionsList = document.getElementById('sections-list');
            
            Sortable.create(sectionsList, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    console.log('Section reordered from', evt.oldIndex, 'to', evt.newIndex);
                    showToast('Sections reordered! Click Save to apply changes.', 'info');
                    updateSectionOrder();
                }
            });
        });
        
        // Toggle section enabled/disabled
        function toggleSection(sectionId) {
            const sectionElement = document.querySelector(`[data-section-id="${sectionId}"]`);
            const icon = sectionElement.querySelector('.section-controls .fa-eye, .section-controls .fa-eye-slash');
            const previewElement = document.querySelector(`[data-section="${sectionId}"]`);
            
            if (sectionElement.classList.contains('disabled')) {
                sectionElement.classList.remove('disabled');
                icon.className = 'fas fa-eye';
                if (previewElement) previewElement.style.display = 'flex';
                showToast(`${sectionId} section enabled!`, 'success');
            } else {
                sectionElement.classList.add('disabled');
                icon.className = 'fas fa-eye-slash';
                if (previewElement) previewElement.style.display = 'none';
                showToast(`${sectionId} section disabled!`, 'warning');
            }
            
            updateSectionOrder();
        }
        
        // Edit section (demo)
        function editSection(sectionId) {
            showToast(`Opening editor for ${sectionId} section...`, 'info');
        }
        
        // Demo functions
        function showUploadDemo() {
            showToast('Banner upload dialog would open here in the real system.', 'info');
        }
        
        function deleteDemo() {
            if (confirm('Delete this banner?')) {
                showToast('Banner deleted successfully!', 'success');
            }
        }
        
        function saveDemo() {
            showToast('Layout saved successfully! (Demo mode)', 'success');
            document.getElementById('last-saved').textContent = new Date().toLocaleTimeString();
        }
        
        function resetDemo() {
            if (confirm('Reset layout to default?')) {
                location.reload();
            }
        }
        
        // Update section order display
        function updateSectionOrder() {
            const sections = document.querySelectorAll('.section-item');
            sections.forEach((section, index) => {
                const meta = section.querySelector('.section-meta small');
                const isEnabled = !section.classList.contains('disabled');
                meta.textContent = `Order: ${index + 1} | Status: ${isEnabled ? 'Enabled' : 'Disabled'}`;
            });
        }
        
        // Toast notification system
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'info' ? 'primary' : type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 5000);
        }
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltips = document.querySelectorAll('[title]');
            tooltips.forEach(el => {
                new bootstrap.Tooltip(el);
            });
        });
    </script>
</body>
</html>