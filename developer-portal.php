<?php
/**
 * Developer Portal - API Key Management
 * E-Commerce Platform
 * 
 * Features:
 * - Generate API keys for sandbox and live environments
 * - Manage existing API keys
 * - View API documentation
 * - Monitor API usage
 */

require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = Session::get('user_id');
$db = db();
$page_title = 'Developer Portal';

// Handle API key actions
$message = '';
$messageType = '';
$newApiSecret = ''; // Store temporarily to show to user once

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create_api_key':
                $name = sanitizeInput($_POST['name'] ?? '');
                $environment = in_array($_POST['environment'] ?? '', ['sandbox', 'live']) ? $_POST['environment'] : 'sandbox';
                
                if (empty($name)) {
                    $message = 'API key name is required';
                    $messageType = 'error';
                } else {
                    // Generate API key and secret
                    $apiKey = 'feza_' . $environment . '_' . bin2hex(random_bytes(24));
                    $apiSecret = bin2hex(random_bytes(32));
                    
                    try {
                        $stmt = $db->prepare("
                            INSERT INTO api_keys (user_id, name, environment, api_key, api_secret, is_active, created_at) 
                            VALUES (?, ?, ?, ?, ?, 1, NOW())
                        ");
                        $stmt->execute([$userId, $name, $environment, $apiKey, hash('sha256', $apiSecret)]);
                        
                        $message = 'API key created successfully!';
                        $messageType = 'success';
                        $newApiSecret = $apiSecret; // Store to display once
                    } catch (Exception $e) {
                        $message = 'Error creating API key: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'toggle_status':
                $keyId = intval($_POST['key_id'] ?? 0);
                
                try {
                    // Verify ownership
                    $stmt = $db->prepare("SELECT id, is_active FROM api_keys WHERE id = ? AND user_id = ?");
                    $stmt->execute([$keyId, $userId]);
                    $key = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($key) {
                        $newStatus = $key['is_active'] ? 0 : 1;
                        $stmt = $db->prepare("UPDATE api_keys SET is_active = ? WHERE id = ?");
                        $stmt->execute([$newStatus, $keyId]);
                        
                        $message = 'API key status updated successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'API key not found';
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error updating API key: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'delete_key':
                $keyId = intval($_POST['key_id'] ?? 0);
                
                try {
                    $stmt = $db->prepare("DELETE FROM api_keys WHERE id = ? AND user_id = ?");
                    $stmt->execute([$keyId, $userId]);
                    
                    if ($stmt->rowCount() > 0) {
                        $message = 'API key deleted successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'API key not found';
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error deleting API key: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Generate CSRF token
if (!Session::get('csrf_token')) {
    Session::set('csrf_token', bin2hex(random_bytes(32)));
}
$csrfToken = Session::get('csrf_token');

// Get user's API keys
try {
    $stmt = $db->prepare("
        SELECT id, name, environment, api_key, is_active, rate_limit, last_used_at, created_at 
        FROM api_keys 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $apiKeys = [];
    error_log("Error fetching API keys: " . $e->getMessage());
}

includeHeader($page_title);
?>

<div class="container dev-portal-container">
    <div class="dev-portal-header">
        <h1>Developer Portal</h1>
        <p>Manage your API keys and access documentation</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
            <?php if ($newApiSecret): ?>
                <div class="api-secret-display">
                    <strong>⚠️ Important: Save your API Secret</strong><br>
                    <code><?php echo htmlspecialchars($newApiSecret); ?></code><br>
                    <small>This secret will only be shown once. Store it securely.</small>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="dev-portal-nav">
        <button class="tab-btn active" data-tab="keys">API Keys</button>
        <button class="tab-btn" data-tab="docs">Documentation</button>
        <button class="tab-btn" data-tab="usage">Usage</button>
    </div>
    
    <!-- API Keys Tab -->
    <div class="tab-content active" id="keys-tab">
        <div class="section-header">
            <h2>Your API Keys</h2>
            <button class="btn btn-primary" onclick="showCreateKeyModal()">
                <i class="fas fa-plus"></i> Create API Key
            </button>
        </div>
        
        <?php if (empty($apiKeys)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔑</div>
                <h3>No API Keys Yet</h3>
                <p>Create your first API key to start using the FezaMarket API</p>
                <button class="btn btn-primary" onclick="showCreateKeyModal()">Create API Key</button>
            </div>
        <?php else: ?>
            <div class="api-keys-grid">
                <?php foreach ($apiKeys as $key): ?>
                    <div class="api-key-card">
                        <div class="key-header">
                            <div class="key-info">
                                <h3><?php echo htmlspecialchars($key['name']); ?></h3>
                                <span class="env-badge env-<?php echo $key['environment']; ?>">
                                    <?php echo ucfirst($key['environment']); ?>
                                </span>
                            </div>
                            <div class="key-status">
                                <span class="status-indicator <?php echo $key['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $key['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="key-details">
                            <div class="detail-row">
                                <span class="label">API Key:</span>
                                <code class="api-key-display"><?php echo htmlspecialchars($key['api_key']); ?></code>
                                <button class="btn-copy" onclick="copyToClipboard('<?php echo htmlspecialchars($key['api_key']); ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="detail-row">
                                <span class="label">Rate Limit:</span>
                                <span><?php echo number_format($key['rate_limit']); ?> requests/hour</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Last Used:</span>
                                <span><?php echo $key['last_used_at'] ? date('M j, Y g:i A', strtotime($key['last_used_at'])) : 'Never'; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Created:</span>
                                <span><?php echo date('M j, Y', strtotime($key['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="key-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline">
                                    <?php echo $key['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this API key?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="delete_key">
                                <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Documentation Tab -->
    <div class="tab-content" id="docs-tab">
        <div class="docs-container">
            <h2>API Documentation</h2>
            
            <section class="doc-section">
                <h3>Getting Started</h3>
                <p>Welcome to the FezaMarket API! This guide will help you get started with integrating our platform into your applications.</p>
                
                <h4>Authentication</h4>
                <p>All API requests require authentication using your API key. Include your key in the request headers:</p>
                <pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>
                
                <h4>Environments</h4>
                <ul>
                    <li><strong>Sandbox:</strong> Use for testing and development. Data is isolated and safe to experiment with.</li>
                    <li><strong>Live:</strong> Production environment with real data and transactions.</li>
                </ul>
            </section>
            
            <section class="doc-section">
                <h3>Base URLs</h3>
                <div class="code-block">
                    <p><strong>Sandbox:</strong> <code>https://api-sandbox.fezamarket.com</code></p>
                    <p><strong>Live:</strong> <code>https://api.fezamarket.com</code></p>
                </div>
            </section>
            
            <section class="doc-section">
                <h3>Products API</h3>
                
                <h4>List Products</h4>
                <div class="endpoint">
                    <span class="method">GET</span>
                    <code>/v1/products</code>
                </div>
                <p>Retrieve a list of products with optional filtering and pagination.</p>
                
                <h5>Query Parameters:</h5>
                <table class="params-table">
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                    <tr>
                        <td><code>page</code></td>
                        <td>integer</td>
                        <td>Page number (default: 1)</td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td>integer</td>
                        <td>Items per page (default: 20, max: 100)</td>
                    </tr>
                    <tr>
                        <td><code>category</code></td>
                        <td>string</td>
                        <td>Filter by category slug</td>
                    </tr>
                    <tr>
                        <td><code>search</code></td>
                        <td>string</td>
                        <td>Search products by name or description</td>
                    </tr>
                </table>
                
                <h5>Example Request:</h5>
                <pre><code>curl -X GET "https://api.fezamarket.com/v1/products?page=1&limit=20" \
  -H "Authorization: Bearer YOUR_API_KEY"</code></pre>
                
                <h5>Example Response:</h5>
                <pre><code>{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "price": 29.99,
      "currency": "USD",
      "image_url": "https://example.com/image.jpg",
      "category": "electronics"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 100
  }
}</code></pre>
            </section>
            
            <section class="doc-section">
                <h3>Rate Limiting</h3>
                <p>API requests are rate-limited based on your account tier:</p>
                <ul>
                    <li><strong>Free Tier:</strong> 100 requests per hour</li>
                    <li><strong>Pro Tier:</strong> 1,000 requests per hour</li>
                    <li><strong>Enterprise:</strong> Custom limits</li>
                </ul>
                
                <p>Rate limit headers are included in all responses:</p>
                <pre><code>X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200</code></pre>
            </section>
            
            <section class="doc-section">
                <h3>Error Handling</h3>
                <p>The API uses standard HTTP status codes:</p>
                <ul>
                    <li><code>200</code> - Success</li>
                    <li><code>400</code> - Bad Request</li>
                    <li><code>401</code> - Unauthorized</li>
                    <li><code>403</code> - Forbidden</li>
                    <li><code>404</code> - Not Found</li>
                    <li><code>429</code> - Rate Limit Exceeded</li>
                    <li><code>500</code> - Internal Server Error</li>
                </ul>
                
                <h5>Error Response Format:</h5>
                <pre><code>{
  "success": false,
  "error": {
    "code": "INVALID_REQUEST",
    "message": "Missing required parameter: name"
  }
}</code></pre>
            </section>
        </div>
    </div>
    
    <!-- Usage Tab -->
    <div class="tab-content" id="usage-tab">
        <div class="usage-container">
            <h2>API Usage</h2>
            <p>Monitor your API usage and performance metrics.</p>
            
            <div class="usage-stats">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3>Total Requests</h3>
                        <p class="stat-value">Coming Soon</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-content">
                        <h3>Avg Response Time</h3>
                        <p class="stat-value">Coming Soon</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3>Success Rate</h3>
                        <p class="stat-value">Coming Soon</p>
                    </div>
                </div>
            </div>
            
            <p class="coming-soon-note">Detailed usage analytics will be available soon.</p>
        </div>
    </div>
</div>

<!-- Create API Key Modal -->
<div id="createKeyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create API Key</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="create_api_key">
            
            <div class="form-group">
                <label for="key-name">API Key Name *</label>
                <input type="text" id="key-name" name="name" class="form-control" 
                       placeholder="e.g., My App - Production" required>
                <small>Choose a descriptive name to identify this key</small>
            </div>
            
            <div class="form-group">
                <label for="environment">Environment *</label>
                <select id="environment" name="environment" class="form-control" required>
                    <option value="sandbox">Sandbox (Testing)</option>
                    <option value="live">Live (Production)</option>
                </select>
                <small>Sandbox keys are for testing only. Use Live keys for production.</small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create API Key</button>
            </div>
        </form>
    </div>
</div>

<style>
.dev-portal-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.dev-portal-header {
    text-align: center;
    margin-bottom: 40px;
}

.dev-portal-header h1 {
    font-size: 36px;
    color: #1f2937;
    margin-bottom: 10px;
}

.dev-portal-header p {
    color: #6b7280;
    font-size: 18px;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.alert-success {
    background: #d1fae5;
    border: 1px solid #10b981;
    color: #065f46;
}

.alert-error {
    background: #fee2e2;
    border: 1px solid #ef4444;
    color: #991b1b;
}

.api-secret-display {
    margin-top: 15px;
    padding: 15px;
    background: white;
    border-radius: 6px;
    border: 2px solid #f59e0b;
}

.api-secret-display code {
    display: block;
    padding: 10px;
    background: #f3f4f6;
    border-radius: 4px;
    margin: 10px 0;
    font-size: 14px;
    word-break: break-all;
}

.dev-portal-nav {
    display: flex;
    gap: 10px;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 30px;
}

.tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    color: #6b7280;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.tab-btn:hover {
    color: #0654ba;
}

.tab-btn.active {
    color: #0654ba;
    border-bottom-color: #0654ba;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.section-header h2 {
    color: #1f2937;
    font-size: 28px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 30px;
}

.api-keys-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.api-key-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.key-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.key-info h3 {
    color: #1f2937;
    font-size: 18px;
    margin-bottom: 8px;
}

.env-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.env-sandbox {
    background: #fef3c7;
    color: #92400e;
}

.env-live {
    background: #d1fae5;
    color: #065f46;
}

.status-indicator {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-indicator.active {
    background: #d1fae5;
    color: #065f46;
}

.status-indicator.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.key-details {
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.detail-row .label {
    font-weight: 600;
    color: #6b7280;
    min-width: 100px;
}

.api-key-display {
    flex: 1;
    padding: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 13px;
    font-family: monospace;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-copy {
    background: #0654ba;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn-copy:hover {
    background: #044a99;
}

.key-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #0654ba;
    color: white;
}

.btn-primary:hover {
    background: #044a99;
}

.btn-outline {
    background: white;
    border: 1px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f3f4f6;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 14px;
}

.btn-danger {
    background: #dc2626;
    color: white;
}

.btn-danger:hover {
    background: #b91c1c;
}

/* Documentation Styles */
.docs-container {
    max-width: 900px;
}

.doc-section {
    margin-bottom: 40px;
}

.doc-section h3 {
    color: #1f2937;
    font-size: 24px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e7eb;
}

.doc-section h4 {
    color: #374151;
    font-size: 18px;
    margin: 20px 0 10px;
}

.doc-section h5 {
    color: #4b5563;
    font-size: 16px;
    margin: 15px 0 10px;
}

.code-block {
    background: #f3f4f6;
    padding: 15px;
    border-radius: 6px;
    margin: 15px 0;
}

pre {
    background: #1f2937;
    color: #e5e7eb;
    padding: 15px;
    border-radius: 6px;
    overflow-x: auto;
    margin: 15px 0;
}

pre code {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.6;
}

code {
    background: #f3f4f6;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

.endpoint {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 15px 0;
}

.method {
    background: #10b981;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 12px;
}

.params-table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
}

.params-table th,
.params-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.params-table th {
    background: #f3f4f6;
    font-weight: 600;
    color: #374151;
}

/* Usage Styles */
.usage-container {
    max-width: 900px;
}

.usage-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    font-size: 48px;
}

.stat-content h3 {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 8px;
}

.stat-value {
    color: #1f2937;
    font-size: 28px;
    font-weight: 700;
}

.coming-soon-note {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    margin-top: 40px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    justify-content: center;
    align-items: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    padding: 30px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.modal-header h2 {
    color: #1f2937;
    font-size: 24px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #6b7280;
    cursor: pointer;
}

.modal-close:hover {
    color: #1f2937;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #374151;
    font-weight: 600;
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-group small {
    display: block;
    color: #6b7280;
    font-size: 13px;
    margin-top: 5px;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 25px;
}

@media (max-width: 768px) {
    .api-keys-grid {
        grid-template-columns: 1fr;
    }
    
    .dev-portal-nav {
        flex-wrap: wrap;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
</style>

<script>
// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Remove active class from all tabs and buttons
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked button and corresponding content
        btn.classList.add('active');
        const tabId = btn.getAttribute('data-tab') + '-tab';
        document.getElementById(tabId).classList.add('active');
    });
});

// Modal functions
function showCreateKeyModal() {
    document.getElementById('createKeyModal').classList.add('show');
}

function closeModal() {
    document.getElementById('createKeyModal').classList.remove('show');
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('API key copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

// Close modal when clicking outside
document.getElementById('createKeyModal').addEventListener('click', (e) => {
    if (e.target.id === 'createKeyModal') {
        closeModal();
    }
});
</script>

<?php includeFooter(); ?>
