<?php
/**
 * Template Helper Functions
 * Functions to include headers and footers consistently
 */

/**
 * Include the main site header
 */
function includeHeader($pageTitle = 'FezaMarket', $metaDescription = null) {
    global $page_title, $meta_description;
    $page_title = $pageTitle;
    $meta_description = $metaDescription;
    
    include __DIR__ . '/../templates/header.php';
}

/**
 * Include the main site footer
 */
function includeFooter() {
    include __DIR__ . '/../templates/footer.php';
}

/**
 * Get current user role safely
 */
function getCurrentUserRole() {
    if (!Session::isLoggedIn()) {
        return 'guest';
    }
    
    return $_SESSION['user_role'] ?? 'user';
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    $currentRole = getCurrentUserRole();
    
    switch ($role) {
        case 'admin':
            return $currentRole === 'admin';
        case 'seller':
        case 'vendor':
            return in_array($currentRole, ['admin', 'seller', 'vendor']);
        case 'user':
            return in_array($currentRole, ['admin', 'seller', 'vendor', 'user']);
        default:
            return false;
    }
}

/**
 * Generate CSRF token
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get user avatar URL
 */
function getUserAvatar($user, $size = 32) {
    if (isset($user['avatar']) && !empty($user['avatar'])) {
        return $user['avatar'];
    }
    
    // Default avatar using Gravatar or placeholder
    $email = $user['email'] ?? 'user@example.com';
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
}
?>