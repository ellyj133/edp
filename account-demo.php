<?php
/**
 * User Account Dashboard - Demo Mode
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Demo mode - create mock user and data
$current_user = [
    'id' => 1,
    'username' => 'demo_user',
    'email' => 'demo@example.com',
    'first_name' => 'Demo',
    'last_name' => 'User',
    'phone' => '+1234567890',
    'two_factor_enabled' => 0,
    'login_email_alerts' => 1,
    'login_sms_alerts' => 0,
    'new_device_alerts' => 1,
    'suspicious_activity_alerts' => 1
];

// Mock login devices
$loginDevices = [
    [
        'id' => 1,
        'session_token' => 'current_session_token_123',
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'is_active' => 1
    ],
    [
        'id' => 2,
        'session_token' => 'another_session_token_456',
        'ip_address' => '10.0.0.50',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Mobile/15E148 Safari/604.1',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'is_active' => 1
    ],
    [
        'id' => 3,
        'session_token' => 'old_session_token_789',
        'ip_address' => '172.16.0.1',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'is_active' => 1
    ]
];

// Mock security logs
$securityLogs = [
    [
        'event_type' => 'login_success',
        'severity' => 'low',
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Chrome on Windows',
        'details' => json_encode(['action' => 'successful_login']),
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
    ],
    [
        'event_type' => 'password_change',
        'severity' => 'medium',
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Chrome on Windows',
        'details' => json_encode(['action' => 'password_updated']),
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ],
    [
        'event_type' => 'login_success',
        'severity' => 'low',
        'ip_address' => '10.0.0.50',
        'user_agent' => 'Safari on iPhone',
        'details' => json_encode(['action' => 'mobile_login']),
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ]
];

// Handle form submissions for demo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'change_password':
            Session::setFlash('success', 'Password changed successfully! (Demo Mode)');
            break;
        case 'enable_2fa':
            Session::setFlash('success', '2FA has been enabled for your account! (Demo Mode)');
            break;
        case 'disable_2fa':
            Session::setFlash('success', '2FA has been disabled for your account. (Demo Mode)');
            break;
        case 'revoke_session':
            Session::setFlash('success', 'Device has been logged out successfully. (Demo Mode)');
            break;
        case 'update_login_alerts':
            Session::setFlash('success', 'Login alert preferences updated successfully. (Demo Mode)');
            break;
    }
    
    redirect('/account-demo.php?tab=' . ($_GET['tab'] ?? 'overview'));
}

// Get current tab
$currentTab = $_GET['tab'] ?? 'overview';
$validTabs = ['overview', 'orders', 'addresses', 'payments', 'security', 'preferences'];

if (!in_array($currentTab, $validTabs)) {
    $currentTab = 'overview';
}

// Helper functions
function parseUserAgent($userAgent) {
    $browser = 'Unknown Browser';
    $os = 'Unknown OS';
    $deviceType = 'desktop';
    
    if (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
    elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
    elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) $browser = 'Safari';
    elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
    
    if (strpos($userAgent, 'Windows') !== false) $os = 'Windows';
    elseif (strpos($userAgent, 'Mac') !== false) $os = 'macOS';
    elseif (strpos($userAgent, 'Linux') !== false) $os = 'Linux';
    elseif (strpos($userAgent, 'Android') !== false) { $os = 'Android'; $deviceType = 'mobile'; }
    elseif (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
        $os = 'iOS';
        $deviceType = strpos($userAgent, 'iPad') !== false ? 'tablet' : 'mobile';
    }
    
    return ['browser' => $browser, 'os' => $os, 'device_type' => $deviceType];
}

function getDeviceIcon($deviceType) {
    switch ($deviceType) {
        case 'mobile': return '📱';
        case 'tablet': return '📱';
        default: return '💻';
    }
}

$page_title = 'My FezaMarket Account - Demo Mode';
includeHeader($page_title);
?>

<!-- Account-specific styles -->
<link rel="stylesheet" href="/css/account.css">

<div class="container">
    <!-- Demo Mode Notice -->
    <div class="alert alert-info" style="margin: 1rem 0; padding: 1rem; background: #e3f2fd; border: 1px solid #2196f3; border-radius: 8px;">
        <strong>🚀 Demo Mode Active</strong> - This is a demonstration of the enhanced security features. All functionality is simulated.
    </div>

    <!-- Account Header -->
    <div class="account-header">
        <h1>My Account</h1>
        <p class="account-subtitle">Manage your FezaMarket account settings and preferences</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="account-navigation">
        <div class="nav-tabs">
            <a href="?tab=overview" class="nav-tab <?php echo $currentTab === 'overview' ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span>
                Overview
            </a>
            <a href="?tab=orders" class="nav-tab <?php echo $currentTab === 'orders' ? 'active' : ''; ?>">
                <span class="nav-icon">📦</span>
                Orders
            </a>
            <a href="?tab=addresses" class="nav-tab <?php echo $currentTab === 'addresses' ? 'active' : ''; ?>">
                <span class="nav-icon">📍</span>
                Addresses
            </a>
            <a href="?tab=payments" class="nav-tab <?php echo $currentTab === 'payments' ? 'active' : ''; ?>">
                <span class="nav-icon">💳</span>
                Payments
            </a>
            <a href="?tab=security" class="nav-tab <?php echo $currentTab === 'security' ? 'active' : ''; ?>">
                <span class="nav-icon">🔒</span>
                Security
            </a>
            <a href="?tab=preferences" class="nav-tab <?php echo $currentTab === 'preferences' ? 'active' : ''; ?>">
                <span class="nav-icon">⚙️</span>
                Preferences
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (Session::hasFlash('success')): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars(Session::getFlash('success')); ?>
        </div>
    <?php endif; ?>

    <?php if (Session::hasFlash('error')): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars(Session::getFlash('error')); ?>
        </div>
    <?php endif; ?>

    <!-- Tab Content -->
    <div class="account-content">
        <?php if ($currentTab === 'security'): ?>
            <!-- Security Tab -->
            <div class="account-section full-width">
                <div class="account-card">
                    <div class="card-header">
                        <h2>Security Settings</h2>
                        <p class="card-description">Keep your account safe and secure</p>
                    </div>
                    
                    <div class="security-options">
                        <div class="security-item">
                            <div class="security-info">
                                <h4>Password</h4>
                                <p>Change your account password</p>
                            </div>
                            <div class="security-action">
                                <button class="btn btn-outline" onclick="openPasswordModal()">Change Password</button>
                            </div>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-info">
                                <h4>Two-Factor Authentication</h4>
                                <p>Add an extra layer of security to your account</p>
                            </div>
                            <div class="security-action">
                                <?php $twoFactorEnabled = $current_user['two_factor_enabled']; ?>
                                <span class="badge <?php echo $twoFactorEnabled ? 'badge-enabled' : 'badge-disabled'; ?>">
                                    <?php echo $twoFactorEnabled ? 'Enabled' : 'Not Enabled'; ?>
                                </span>
                                <?php if ($twoFactorEnabled): ?>
                                    <button class="btn btn-outline" onclick="open2FAModal('disable')">Disable 2FA</button>
                                <?php else: ?>
                                    <button class="btn btn-outline" onclick="open2FAModal('enable')">Enable 2FA</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-info">
                                <h4>Login Devices</h4>
                                <p>Manage devices that can access your account</p>
                            </div>
                            <div class="security-action">
                                <span class="badge badge-info"><?php echo count($loginDevices); ?> Active</span>
                                <button class="btn btn-outline" onclick="openLoginDevicesModal()">Manage Devices</button>
                            </div>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-info">
                                <h4>Login Alerts</h4>
                                <p>Get notified when someone logs into your account</p>
                            </div>
                            <div class="security-action">
                                <span class="badge badge-enabled">Enabled</span>
                                <button class="btn btn-outline" onclick="openLoginAlertsModal()">Configure</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Other tabs (demo content) -->
            <div class="account-section full-width">
                <div class="account-card">
                    <div class="card-header">
                        <h2><?php echo ucfirst($currentTab); ?></h2>
                        <p class="card-description">Demo content for <?php echo $currentTab; ?> section</p>
                    </div>
                    
                    <div class="demo-content" style="padding: 2rem; text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🚧</div>
                        <h3>Feature Preview</h3>
                        <p>This <?php echo $currentTab; ?> section is fully designed and ready for implementation.</p>
                        <p>Click on the <strong>Security</strong> tab to see the enhanced security features!</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Include the modals and JavaScript from the main account.php file -->
<?php include __DIR__ . '/account-modals.php'; ?>

<?php includeFooter(); ?>