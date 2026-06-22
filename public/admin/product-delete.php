<?php
// ============================================================
// FILE: public/admin/product-delete.php
// PURPOSE: Delete a product with foreign key handling
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
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
// DELETE PRODUCT WITH FOREIGN KEY HANDLING
// ============================================

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // ============================================
    // STEP 1: Check if product has order items
    // ============================================
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count FROM order_items WHERE product_id = :product_id
    ');
    $stmt->execute(['product_id' => $productId]);
    $orderCount = $stmt->fetch()['count'] ?? 0;
    
    // ============================================
    // STEP 2: Get product image path
    // ============================================
    $stmt = $pdo->prepare('SELECT image_url FROM products WHERE id = :id');
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();
    
    // ============================================
    // STEP 3: Start transaction
    // ============================================
    $pdo->beginTransaction();
    
    // ============================================
    // STEP 4: Delete order items (if any)
    // ============================================
    if ($orderCount > 0) {
        $stmt = $pdo->prepare('
            DELETE FROM order_items WHERE product_id = :product_id
        ');
        $stmt->execute(['product_id' => $productId]);
        
        // Also check vendor_orders if exists
        // This is optional - depends on your structure
    }
    
    // ============================================
    // STEP 5: Delete the product
    // ============================================
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $result = $stmt->execute(['id' => $productId]);
    
    if ($result) {
        // ============================================
        // STEP 6: Delete image file if exists
        // ============================================
        if ($product && $product['image_url']) {
            $imagePath = ABSPATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . $product['image_url'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // ============================================
        // STEP 7: Commit transaction
        // ============================================
        $pdo->commit();
        
        $_SESSION['success_message'] = 'Product deleted successfully!';
    } else {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Failed to delete product.';
    }
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    // Check if it's a foreign key constraint error
    if ($e->getCode() == 23000) {
        $_SESSION['error_message'] = 'Cannot delete this product because it has order items. Please delete the order items first.';
    } else {
        error_log('Delete product error: ' . $e->getMessage());
        $_SESSION['error_message'] = 'Database error occurred.';
    }
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('Delete product error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred.';
}

header('Location: products.php');
exit;
?>