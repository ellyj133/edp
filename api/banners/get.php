<?php
/**
 * Banner Get API
 * Retrieves banner data for editing
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../admin/auth.php';

header('Content-Type: application/json');

try {
    // Get banner ID
    $bannerId = $_GET['id'] ?? null;
    
    if (!$bannerId) {
        throw new Exception('Banner ID is required');
    }
    
    // Validate it's a number
    if (!is_numeric($bannerId)) {
        throw new Exception('Invalid banner ID');
    }
    
    // Get database connection
    $pdo = db();
    
    // Fetch banner data
    $sql = "SELECT id, title, subtitle, description, image_url, link_url, button_text,
                   background_color, text_color, position, sort_order, status
            FROM homepage_banners 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $bannerId, PDO::PARAM_INT);
    $stmt->execute();
    
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$banner) {
        throw new Exception('Banner not found');
    }
    
    echo json_encode([
        'success' => true,
        'banner' => $banner
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}