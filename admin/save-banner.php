<?php
/**
 * Admin Banner Save Handler
 * Handles AJAX requests to update banner content from homepage inline editing
 */

require_once __DIR__ . '/../includes/init.php';

// Ensure this is an AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Forbidden');
}

// Ensure admin is logged in
if (!Session::isLoggedIn() || Session::getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $required_fields = ['banner_id', 'banner_type'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Sanitize input data
    $banner_id = sanitizeInput($data['banner_id']);
    $banner_type = sanitizeInput($data['banner_type']);
    $title = sanitizeInput($data['title'] ?? '');
    $description = sanitizeInput($data['description'] ?? '');
    $image_url = sanitizeInput($data['image_url'] ?? '');
    $link_url = sanitizeInput($data['link_url'] ?? '');
    $button_text = sanitizeInput($data['button_text'] ?? '');
    
    // Get database connection
    $pdo = db();
    
    // Check if banner exists, if not create it
    $check_sql = "SELECT id FROM homepage_banners WHERE id = ? OR (title LIKE ? AND position = 'top')";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$banner_id, "%$banner_id%"]);
    $existing_banner = $check_stmt->fetch();
    
    if ($existing_banner) {
        // Update existing banner
        $update_sql = "UPDATE homepage_banners 
                       SET title = ?, subtitle = ?, description = ?, image_url = ?, 
                           link_url = ?, button_text = ?, updated_at = NOW()
                       WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([
            $title,
            $description, // Use description as subtitle for now
            $description,
            $image_url,
            $link_url,
            $button_text,
            $existing_banner['id']
        ]);
    } else {
        // Create new banner
        $insert_sql = "INSERT INTO homepage_banners 
                       (title, subtitle, description, image_url, link_url, button_text, 
                        position, status, created_by, created_at, updated_at)
                       VALUES (?, ?, ?, ?, ?, ?, 'top', 'active', ?, NOW(), NOW())";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([
            $title,
            $description,
            $description,
            $image_url,
            $link_url,
            $button_text,
            Session::getUserId()
        ]);
    }
    
    // Log the admin action
    if (function_exists('logAdminAction')) {
        logAdminAction(Session::getUserId(), 'banner_update', 'Updated banner: ' . $banner_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Banner updated successfully',
        'banner_id' => $banner_id
    ]);

} catch (Exception $e) {
    error_log("Banner save error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating banner: ' . $e->getMessage()
    ]);
}