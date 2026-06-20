<?php
// ============================================================
// FILE: public/admin/product-delete.php
// PURPOSE: Delete a product from the database
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// ADMIN ACCESS CONTROL
// ============================================

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = 'Access denied. Admin only.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// ============================================
// GET PRODUCT ID
// ============================================

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($productId <= 0) {
    $_SESSION['error_message'] = 'Invalid product ID.';
    header('Location: products.php');
    exit;
}

// ============================================
// DELETE PRODUCT
// ============================================

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Get product image path first
    $stmt = $pdo->prepare('SELECT image_url FROM products WHERE id = :id');
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();
    
    // Delete the product
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $result = $stmt->execute(['id' => $productId]);
    
    if ($result) {
        // Delete image file if exists
        if ($product && $product['image_url']) {
            $imagePath = ABSPATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . $product['image_url'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $_SESSION['success_message'] = 'Product deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to delete product.';
    }
    
} catch (PDOException $e) {
    error_log('Delete product error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log('Delete product error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred.';
}

header('Location: products.php');
exit;
?>