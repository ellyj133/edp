<?php
/**
 * Handles product image uploads, including thumbnail and gallery images.
 * This script processes files, moves them to a designated directory,
 * and records their paths in the database.
 */

if (!function_exists('handleProductImageUploads')) {
    /**
     * Processes and saves product images.
     *
     * @param int $productId The ID of the product to associate images with.
     * @param int $sellerId The ID of the seller uploading the images.
     * @return array An array containing success status, a list of errors, and upload details.
     */
    function handleProductImageUploads(int $productId, int $sellerId): array
    {
        $errors = [];
        $uploads = [];
        $baseUploadDir = __DIR__ . '/../uploads/products/' . date('Y/m');
        
        if (!is_dir($baseUploadDir) && !mkdir($baseUploadDir, 0775, true)) {
            $errors['directory'] = 'Failed to create image upload directory.';
            error_log("Image Upload Error: Failed to create directory: " . $baseUploadDir);
            return ['success' => false, 'errors' => $errors, 'uploads' => $uploads];
        }

        $processFile = function(array $file, bool $isThumbnail = false) use ($productId, $sellerId, $baseUploadDir, &$errors, &$uploads) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Error uploading file {$file['name']}.";
                }
                return;
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $safeFilename = 'prod_' . $productId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $destinationPath = $baseUploadDir . '/' . $safeFilename;
            $publicPath = '/uploads/products/' . date('Y/m') . '/' . $safeFilename;

            if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
                $errors[] = "Failed to move uploaded file: {$file['name']}.";
                return;
            }
            
            try {
                Database::query(
                    "INSERT INTO product_images (product_id, image_path, is_thumbnail, uploaded_by) VALUES (?, ?, ?, ?)",
                    [$productId, $publicPath, $isThumbnail ? 1 : 0, $sellerId]
                );
                
                if ($isThumbnail) {
                    $uploads['thumbnail'] = $publicPath;
                } else {
                    $uploads['gallery'][] = $publicPath;
                }
            } catch (Throwable $e) {
                // =================================================================
                // TEMPORARY: Display the exact database error for debugging.
                // =================================================================
                $errors[] = "DATABASE DEBUG: " . $e->getMessage();
                // =================================================================
                
                error_log("Image DB Error for product ID {$productId}: " . $e->getMessage());
                if (file_exists($destinationPath)) {
                    unlink($destinationPath);
                }
            }
        };

        if (isset($_FILES['product_thumbnail']) && $_FILES['product_thumbnail']['error'] === UPLOAD_ERR_OK) {
            $processFile($_FILES['product_thumbnail'], true);
        }

        if (isset($_FILES['product_gallery']) && is_array($_FILES['product_gallery']['name'])) {
            $galleryFiles = [];
            foreach ($_FILES['product_gallery']['name'] as $key => $name) {
                if ($_FILES['product_gallery']['error'][$key] === UPLOAD_ERR_OK) {
                    $galleryFiles[] = [
                        'name' => $name,
                        'type' => $_FILES['product_gallery']['type'][$key],
                        'tmp_name' => $_FILES['product_gallery']['tmp_name'][$key],
                        'error' => $_FILES['product_gallery']['error'][$key],
                        'size' => $_FILES['product_gallery']['size'][$key],
                    ];
                }
            }

            foreach ($galleryFiles as $file) {
                $processFile($file, false);
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'uploads' => $uploads
        ];
    }
}