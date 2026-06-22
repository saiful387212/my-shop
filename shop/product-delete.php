<?php
// ============================================================
// FILE: public/shop/product-delete.php
// PURPOSE: Shop owner delete product
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

session_start();

// Check login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$shopModel = new Shop();
$shop = $shopModel->getByUser($_SESSION['user_id']);

if (!$shop || !$shop['is_approved']) {
    header('Location: manage.php');
    exit;
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: manage.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Check if product belongs to this shop
    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = :id AND shop_id = :shop_id");
    $stmt->execute([':id' => $productId, ':shop_id' => $shop['id']]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: manage.php');
        exit;
    }
    
    // Delete product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id AND shop_id = :shop_id");
    $result = $stmt->execute([':id' => $productId, ':shop_id' => $shop['id']]);
    
    if ($result) {
        // Delete image
        if ($product['image_url']) {
            $imagePath = ABSPATH . 'public/uploads/products/' . $product['image_url'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        $_SESSION['success_message'] = '✅ Product deleted successfully!';
    } else {
        $_SESSION['error_message'] = '❌ Failed to delete product.';
    }
    
} catch (Exception $e) {
    error_log('Delete product error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred.';
}

header('Location: manage.php');
exit;
?>