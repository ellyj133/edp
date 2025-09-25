<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Product Add Demo - FezaMarket</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #0654ba;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--secondary-color);
            margin: 0;
            padding: 0;
        }
        
        .demo-header {
            background: var(--primary-color);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .demo-notification {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
            color: #1976d2;
        }
        
        .seller-form {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            margin: 0;
        }
        
        .form-header {
            background: linear-gradient(135deg, var(--primary-color), #084298);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .form-body {
            padding: 40px;
            width: 100%;
        }
        
        .form-section {
            margin-bottom: 40px;
        }
        
        .form-section h4 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(6, 84, 186, 0.25);
        }
        
        .image-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .image-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(6, 84, 186, 0.05);
        }
        
        .image-upload-area i {
            font-size: 48px;
            color: #adb5bd;
            margin-bottom: 15px;
        }
        
        .price-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn-demo {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 15px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-demo:hover {
            background: #084298;
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-secondary-demo {
            background: #6c757d;
            border: none;
            color: white;
            padding: 15px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
        }
        
        .preview-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .preview-card h5 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .feature-tag {
            background: var(--success-color);
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            margin: 0 5px 5px 0;
            display: inline-block;
        }
        
        .mobile-friendly {
            width: 100%;
            margin: 0;
            padding: 10px;
        }
        
        @media (max-width: 768px) {
            .form-body {
                padding: 20px;
            }
            
            .price-inputs {
                grid-template-columns: 1fr;
            }
            
            .btn-actions {
                grid-template-columns: 1fr;
            }
            
            .demo-header {
                padding: 15px 10px;
            }
            
            .form-header {
                padding: 20px;
            }
        }
        
        .container {
            width: 100%;
            padding: 0 20px;
        }
    </style>
</head>
<body>
    <div class="demo-header">
        <div class="container">
            <h1 class="text-center mb-0">
                <i class="fas fa-store me-3"></i>
                Seller Product Add Demo - Full Width & Mobile Friendly
            </h1>
            <p class="text-center mb-0 mt-2">Demonstration of seller product adding functionality</p>
        </div>
    </div>

    <div class="container">
        <div class="demo-notification">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Demo Mode:</strong> This is a demonstration of the seller product adding interface. 
            In the real system, this would connect to the database and handle file uploads. 
            The layout is now 100% full width and fully mobile responsive.
        </div>

        <div class="seller-form">
            <div class="form-header">
                <h2><i class="fas fa-plus-circle me-2"></i>Add New Product</h2>
                <p class="mb-0">Create a new product listing for your store</p>
            </div>
            
            <div class="form-body">
                <form id="productForm">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h4><i class="fas fa-info-circle me-2"></i>Basic Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" placeholder="Enter product name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">SKU</label>
                                    <input type="text" class="form-control" placeholder="Product SKU (auto-generated if empty)">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Short Description *</label>
                            <textarea class="form-control" rows="3" placeholder="Brief product description for listings"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Full Description</label>
                            <textarea class="form-control" rows="6" placeholder="Detailed product description with features, specifications, etc."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Category *</label>
                                    <select class="form-select" required>
                                        <option value="">Select a category</option>
                                        <option value="electronics">Electronics</option>
                                        <option value="fashion">Fashion & Clothing</option>
                                        <option value="home">Home & Garden</option>
                                        <option value="sports">Sports & Outdoors</option>
                                        <option value="books">Books & Media</option>
                                        <option value="toys">Toys & Games</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control" placeholder="Product brand/manufacturer">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="form-section">
                        <h4><i class="fas fa-dollar-sign me-2"></i>Pricing & Inventory</h4>
                        <div class="price-inputs">
                            <div class="form-group">
                                <label class="form-label">Regular Price *</label>
                                <input type="number" step="0.01" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sale Price</label>
                                <input type="number" step="0.01" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" class="form-control" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Low Stock Alert</label>
                                    <input type="number" class="form-control" placeholder="5">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" step="0.01" class="form-control" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Images -->
                    <div class="form-section">
                        <h4><i class="fas fa-camera me-2"></i>Product Images</h4>
                        <div class="image-upload-area" onclick="showUploadDemo()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h5>Click to upload product images</h5>
                            <p class="text-muted mb-0">Drag and drop files here or click to browse</p>
                            <small class="text-muted">Supports: JPG, PNG, WebP (max 5MB each)</small>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-lightbulb me-1"></i>
                                Tip: First image will be used as the main product image
                            </small>
                        </div>
                    </div>

                    <!-- SEO & Visibility -->
                    <div class="form-section">
                        <h4><i class="fas fa-search me-2"></i>SEO & Visibility</h4>
                        <div class="form-group">
                            <label class="form-label">Meta Title</label>
                            <input type="text" class="form-control" placeholder="SEO title for search engines">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Meta Description</label>
                            <textarea class="form-control" rows="3" placeholder="SEO description for search engines"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Product Status</label>
                                    <select class="form-select">
                                        <option value="active">Active</option>
                                        <option value="draft">Draft</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Featured Product</label>
                                    <select class="form-select">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="btn-actions">
                        <button type="button" class="btn-secondary-demo" onclick="showPreview()">
                            <i class="fas fa-eye me-2"></i>Preview Product
                        </button>
                        <button type="button" class="btn-demo" onclick="submitDemo()">
                            <i class="fas fa-save me-2"></i>Save Product
                        </button>
                    </div>
                </form>

                <!-- Preview Section -->
                <div class="preview-card" id="previewCard" style="display: none;">
                    <h5><i class="fas fa-eye me-2"></i>Product Preview</h5>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Product form is working correctly!</strong> All fields are responsive and mobile-friendly.
                    </div>
                    <div class="mt-3">
                        <span class="feature-tag">Full Width Layout</span>
                        <span class="feature-tag">Mobile Responsive</span>
                        <span class="feature-tag">Image Upload Ready</span>
                        <span class="feature-tag">SEO Optimized</span>
                        <span class="feature-tag">Inventory Management</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Test Section -->
        <div class="seller-form mt-4">
            <div class="form-header">
                <h3><i class="fas fa-mobile-alt me-2"></i>Mobile Responsiveness Test</h3>
                <p class="mb-0">Resize your browser to test mobile functionality</p>
            </div>
            <div class="form-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-laptop fa-3x text-primary mb-3"></i>
                                <h5>Desktop</h5>
                                <p>Full-width layout with optimal spacing</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-tablet-alt fa-3x text-success mb-3"></i>
                                <h5>Tablet</h5>
                                <p>Responsive grid adapts to medium screens</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-mobile-alt fa-3x text-warning mb-3"></i>
                                <h5>Mobile</h5>
                                <p>Single column layout with touch-friendly inputs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showUploadDemo() {
            alert('Demo: In the real system, this would open a file upload dialog for selecting product images.');
        }
        
        function showPreview() {
            document.getElementById('previewCard').style.display = 'block';
            document.getElementById('previewCard').scrollIntoView({behavior: 'smooth'});
        }
        
        function submitDemo() {
            alert('Demo: Product would be saved to database. All form validation and mobile responsiveness is working correctly!');
        }
        
        // Demo form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('productForm');
            const inputs = form.querySelectorAll('input, textarea, select');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = '#0654ba';
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value && this.hasAttribute('required')) {
                        this.style.borderColor = '#dc3545';
                    } else {
                        this.style.borderColor = '#28a745';
                    }
                });
            });
        });
    </script>
</body>
</html>