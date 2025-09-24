<?php
/**
 * Selling Help Page
 * E-Commerce Platform - Comprehensive Seller Guide
 */
require_once __DIR__ . '/../includes/init.php';
$page_title = 'Selling Help & Guide';
includeHeader($page_title);
?>

<div class="help-page">
    <div class="container">
        <div class="help-header">
            <h1>Selling Help & Guide</h1>
            <p class="lead">Everything you need to know about selling on our e-commerce platform</p>
        </div>

        <div class="help-content">
            <div class="row">
                <div class="col-md-3">
                    <div class="help-nav">
                        <h3>Quick Navigation</h3>
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="#getting-started">Getting Started</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#seller-registration">Seller Registration</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#product-listing">Product Listing</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#order-management">Order Management</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#payments">Payments & Fees</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#marketing">Marketing Tools</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#analytics">Analytics</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#policies">Policies & Guidelines</a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="help-sections">
                        
                        <!-- Getting Started Section -->
                        <section id="getting-started" class="help-section">
                            <h2>Getting Started</h2>
                            <div class="alert alert-info">
                                <strong>Welcome to our seller community!</strong> Follow these steps to start selling on our platform.
                            </div>
                            
                            <h3>Steps to Start Selling</h3>
                            <ol>
                                <li><strong>Create Account:</strong> Sign up for a seller account with valid business information</li>
                                <li><strong>Complete Verification:</strong> Submit required documents for KYC verification</li>
                                <li><strong>Set Up Store:</strong> Create your seller profile and store information</li>
                                <li><strong>Add Products:</strong> List your first products with detailed descriptions</li>
                                <li><strong>Configure Shipping:</strong> Set up shipping methods and pricing</li>
                                <li><strong>Start Selling:</strong> Your store is now live and ready for customers</li>
                            </ol>
                        </section>

                        <!-- Seller Registration Section -->
                        <section id="seller-registration" class="help-section">
                            <h2>Seller Registration</h2>
                            
                            <h3>Required Information</h3>
                            <ul>
                                <li>Valid email address and phone number</li>
                                <li>Business name and registration details</li>
                                <li>Tax identification number</li>
                                <li>Bank account information for payments</li>
                                <li>Business address and contact information</li>
                            </ul>

                            <h3>Verification Process</h3>
                            <p>All sellers must complete our KYC (Know Your Customer) verification process:</p>
                            <ul>
                                <li>Identity verification with government-issued ID</li>
                                <li>Business registration documents</li>
                                <li>Address verification</li>
                                <li>Bank account verification</li>
                            </ul>

                            <div class="alert alert-warning">
                                <strong>Note:</strong> Verification typically takes 2-5 business days. You'll receive email updates about your status.
                            </div>
                        </section>

                        <!-- Product Listing Section -->
                        <section id="product-listing" class="help-section">
                            <h2>Product Listing</h2>
                            
                            <h3>Creating Quality Listings</h3>
                            <ul>
                                <li><strong>Clear Title:</strong> Use descriptive, searchable product names</li>
                                <li><strong>High-Quality Images:</strong> Upload multiple photos from different angles</li>
                                <li><strong>Detailed Description:</strong> Include specifications, materials, and features</li>
                                <li><strong>Accurate Categories:</strong> Select the most relevant product category</li>
                                <li><strong>Competitive Pricing:</strong> Research market prices for similar products</li>
                                <li><strong>Stock Management:</strong> Keep inventory levels updated</li>
                            </ul>

                            <h3>Image Requirements</h3>
                            <ul>
                                <li>Minimum resolution: 800x800 pixels</li>
                                <li>Maximum file size: 5MB per image</li>
                                <li>Supported formats: JPG, PNG, GIF</li>
                                <li>White or neutral background preferred</li>
                                <li>Maximum 10 images per product</li>
                            </ul>

                            <h3>Product Variants</h3>
                            <p>Use variants for products with different sizes, colors, or configurations:</p>
                            <ul>
                                <li>Set unique SKUs for each variant</li>
                                <li>Manage individual stock levels</li>
                                <li>Set different prices if needed</li>
                                <li>Add variant-specific images</li>
                            </ul>
                        </section>

                        <!-- Order Management Section -->
                        <section id="order-management" class="help-section">
                            <h2>Order Management</h2>
                            
                            <h3>Order Processing Workflow</h3>
                            <ol>
                                <li><strong>New Order:</strong> Receive notification and review order details</li>
                                <li><strong>Confirm Order:</strong> Accept the order within 24 hours</li>
                                <li><strong>Prepare Item:</strong> Package item securely for shipping</li>
                                <li><strong>Ship Order:</strong> Generate shipping label and dispatch</li>
                                <li><strong>Update Status:</strong> Mark as shipped with tracking information</li>
                                <li><strong>Delivery Confirmation:</strong> Order marked as delivered</li>
                            </ol>

                            <h3>Order Status Types</h3>
                            <ul>
                                <li><strong>Pending:</strong> New order awaiting confirmation</li>
                                <li><strong>Processing:</strong> Order confirmed and being prepared</li>
                                <li><strong>Shipped:</strong> Order dispatched with tracking</li>
                                <li><strong>Delivered:</strong> Order received by customer</li>
                                <li><strong>Cancelled:</strong> Order cancelled before shipping</li>
                                <li><strong>Refunded:</strong> Order refunded after delivery</li>
                            </ul>

                            <div class="alert alert-success">
                                <strong>Pro Tip:</strong> Fast order processing improves your seller rating and customer satisfaction.
                            </div>
                        </section>

                        <!-- Payments Section -->
                        <section id="payments" class="help-section">
                            <h2>Payments & Fees</h2>
                            
                            <h3>Payment Schedule</h3>
                            <ul>
                                <li>Payments are processed weekly on Fridays</li>
                                <li>7-day holding period for new sellers</li>
                                <li>Funds transferred to your registered bank account</li>
                                <li>Email notifications sent for all transactions</li>
                            </ul>

                            <h3>Fee Structure</h3>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Fee Type</th>
                                        <th>Rate</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Commission Fee</td>
                                        <td>5% - 15%</td>
                                        <td>Varies by category</td>
                                    </tr>
                                    <tr>
                                        <td>Payment Processing</td>
                                        <td>2.9% + $0.30</td>
                                        <td>Per transaction</td>
                                    </tr>
                                    <tr>
                                        <td>Listing Fee</td>
                                        <td>Free</td>
                                        <td>No charge for basic listings</td>
                                    </tr>
                                </tbody>
                            </table>

                            <h3>Tax Responsibilities</h3>
                            <p>Sellers are responsible for:</p>
                            <ul>
                                <li>Calculating and collecting applicable sales tax</li>
                                <li>Filing tax returns in their jurisdiction</li>
                                <li>Maintaining records of all transactions</li>
                                <li>Complying with local tax laws</li>
                            </ul>
                        </section>

                        <!-- Marketing Section -->
                        <section id="marketing" class="help-section">
                            <h2>Marketing Tools</h2>
                            
                            <h3>Promotional Features</h3>
                            <ul>
                                <li><strong>Flash Sales:</strong> Create time-limited discount offers</li>
                                <li><strong>Coupons:</strong> Generate discount codes for customers</li>
                                <li><strong>Bulk Discounts:</strong> Set quantity-based pricing</li>
                                <li><strong>Seasonal Campaigns:</strong> Join platform-wide promotional events</li>
                                <li><strong>Featured Listings:</strong> Boost product visibility (paid)</li>
                            </ul>

                            <h3>SEO Best Practices</h3>
                            <ul>
                                <li>Use relevant keywords in titles and descriptions</li>
                                <li>Optimize product images with alt text</li>
                                <li>Encourage customer reviews and ratings</li>
                                <li>Maintain competitive pricing</li>
                                <li>Keep inventory levels updated</li>
                            </ul>

                            <h3>Social Media Integration</h3>
                            <p>Connect your social media accounts to:</p>
                            <ul>
                                <li>Auto-share new product listings</li>
                                <li>Cross-promote sales and offers</li>
                                <li>Build brand awareness</li>
                                <li>Drive traffic to your store</li>
                            </ul>
                        </section>

                        <!-- Analytics Section -->
                        <section id="analytics" class="help-section">
                            <h2>Analytics & Reporting</h2>
                            
                            <h3>Key Metrics to Track</h3>
                            <ul>
                                <li><strong>Sales Performance:</strong> Revenue, order count, average order value</li>
                                <li><strong>Product Performance:</strong> Views, conversion rates, inventory turnover</li>
                                <li><strong>Customer Insights:</strong> Demographics, repeat purchases, feedback</li>
                                <li><strong>Traffic Sources:</strong> Search, direct, social media, campaigns</li>
                            </ul>

                            <h3>Available Reports</h3>
                            <ul>
                                <li>Daily/Weekly/Monthly sales reports</li>
                                <li>Product performance analysis</li>
                                <li>Customer behavior insights</li>
                                <li>Marketing campaign effectiveness</li>
                                <li>Financial summaries and tax reports</li>
                            </ul>

                            <h3>Using Data to Improve</h3>
                            <ul>
                                <li>Identify top-performing products</li>
                                <li>Optimize pricing strategies</li>
                                <li>Improve product descriptions</li>
                                <li>Plan inventory restocking</li>
                                <li>Target marketing efforts</li>
                            </ul>
                        </section>

                        <!-- Policies Section -->
                        <section id="policies" class="help-section">
                            <h2>Policies & Guidelines</h2>
                            
                            <h3>Prohibited Items</h3>
                            <p>The following items are not allowed on our platform:</p>
                            <ul>
                                <li>Illegal or regulated substances</li>
                                <li>Counterfeit or replica goods</li>
                                <li>Weapons and dangerous items</li>
                                <li>Adult content and services</li>
                                <li>Stolen or fraudulent items</li>
                                <li>Items violating intellectual property rights</li>
                            </ul>

                            <h3>Quality Standards</h3>
                            <ul>
                                <li>Accurate product descriptions</li>
                                <li>Authentic product images</li>
                                <li>Competitive and fair pricing</li>
                                <li>Prompt order processing</li>
                                <li>Professional customer service</li>
                                <li>Compliance with platform policies</li>
                            </ul>

                            <h3>Seller Performance Metrics</h3>
                            <p>Your seller rating is based on:</p>
                            <ul>
                                <li>Order defect rate (target: <1%)</li>
                                <li>Customer feedback score (target: >4.5/5)</li>
                                <li>Order processing time (target: <24 hours)</li>
                                <li>Response time to customer messages (target: <12 hours)</li>
                                <li>Return and refund rate (target: <5%)</li>
                            </ul>

                            <div class="alert alert-danger">
                                <strong>Warning:</strong> Violating platform policies may result in account suspension or permanent ban.
                            </div>
                        </section>

                        <!-- Support Section -->
                        <section id="support" class="help-section">
                            <h2>Need More Help?</h2>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5>Contact Support</h5>
                                            <p>Get help from our seller support team:</p>
                                            <ul>
                                                <li>Email: seller-support@platform.com</li>
                                                <li>Phone: 1-800-SELLER (24/7)</li>
                                                <li>Live Chat: Available 9 AM - 6 PM EST</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5>Additional Resources</h5>
                                            <ul>
                                                <li><a href="/seller-center.php">Seller Dashboard</a></li>
                                                <li><a href="/seller/products.php">Manage Products</a></li>
                                                <li><a href="/seller/orders.php">Process Orders</a></li>
                                                <li><a href="/seller/analytics.php">View Analytics</a></li>
                                                <li><a href="/seller/marketing.php">Marketing Tools</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.help-page {
    padding: 2rem 0;
}

.help-header {
    text-align: center;
    margin-bottom: 3rem;
}

.help-header h1 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.help-nav {
    position: sticky;
    top: 2rem;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.help-nav h3 {
    color: #495057;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.help-nav .nav-link {
    color: #6c757d;
    padding: 0.5rem 0;
    border: none;
    background: none;
    font-size: 0.9rem;
}

.help-nav .nav-link:hover {
    color: #007bff;
    background: none;
}

.help-section {
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #dee2e6;
}

.help-section:last-child {
    border-bottom: none;
}

.help-section h2 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #007bff;
}

.help-section h3 {
    color: #495057;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.help-section ul, .help-section ol {
    margin-bottom: 1.5rem;
}

.help-section li {
    margin-bottom: 0.5rem;
}

.table {
    margin-top: 1rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

@media (max-width: 768px) {
    .help-nav {
        position: static;
    }
    
    .help-section h2 {
        font-size: 1.5rem;
    }
    
    .help-section h3 {
        font-size: 1.25rem;
    }
}
</style>

<script>
// Smooth scrolling for navigation links
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.help-nav .nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Update active link
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Highlight current section on scroll
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('.help-section');
        const scrollPos = window.scrollY + 100;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionBottom = sectionTop + section.offsetHeight;
            const sectionId = section.getAttribute('id');
            const correspondingLink = document.querySelector(`.help-nav .nav-link[href="#${sectionId}"]`);
            
            if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                navLinks.forEach(l => l.classList.remove('active'));
                if (correspondingLink) {
                    correspondingLink.classList.add('active');
                }
            }
        });
    });
});
</script>

<?php includeFooter(); ?>