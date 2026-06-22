<?php
// ============================================================
// FILE: public/cancel-order.php
// PURPOSE: Cancel order handler
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF USER IS LOGGED IN
// ============================================

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to cancel order.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// ============================================
// GET ORDER ID
// ============================================

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$cancelReason = isset($_POST['cancel_reason']) ? sanitize($_POST['cancel_reason']) : '';

if ($orderId <= 0) {
    $_SESSION['error_message'] = 'Invalid order ID.';
    header('Location: ' . SITE_URL . 'orders.php');
    exit;
}

// ============================================
// CANCEL ORDER
// ============================================

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT user_id, status FROM orders WHERE id = :id
    ");
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $_SESSION['error_message'] = 'Order not found.';
        header('Location: ' . SITE_URL . 'orders.php');
        exit;
    }
    
    // Check if user owns this order or is admin
    $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    $isOwner = $order['user_id'] == $_SESSION['user_id'];
    
    if (!$isAdmin && !$isOwner) {
        $_SESSION['error_message'] = 'You do not have permission to cancel this order.';
        header('Location: ' . SITE_URL . 'orders.php');
        exit;
    }
    
    // Check if order can be cancelled
    $cancelableStatuses = ['pending', 'processing'];
    if (!in_array($order['status'], $cancelableStatuses)) {
        $_SESSION['error_message'] = 'This order cannot be cancelled.';
        header('Location: ' . SITE_URL . 'order-details.php?id=' . $orderId);
        exit;
    }
    
    // ============================================
    // UPDATE ORDER STATUS
    // ============================================
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update main order
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'cancelled', updated_at = NOW() 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $orderId]);
    
    // Update vendor orders
    $stmt = $pdo->prepare("
        UPDATE vendor_orders 
        SET status = 'cancelled', updated_at = NOW() 
        WHERE order_id = :order_id
    ");
    $stmt->execute([':order_id' => $orderId]);
    
    // ============================================
    // RESTORE PRODUCT STOCK
    // ============================================
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT product_id, quantity FROM order_items WHERE order_id = :order_id
    ");
    $stmt->execute([':order_id' => $orderId]);
    $items = $stmt->fetchAll();
    
    foreach ($items as $item) {
        if ($item['product_id']) {
            $stockStmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + :quantity 
                WHERE id = :product_id
            ");
            $stockStmt->execute([
                ':quantity' => $item['quantity'],
                ':product_id' => $item['product_id']
            ]);
        }
    }
    
    // ============================================
    // LOG CANCELLATION REASON (Optional)
    // ============================================
    
    if (!empty($cancelReason)) {
        // You can store this in a separate table if needed
        error_log("Order #{$orderId} cancelled. Reason: {$cancelReason}");
    }
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success_message'] = '✅ Order cancelled successfully!';
    header('Location: ' . SITE_URL . 'order-details.php?id=' . $orderId);
    exit;
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('Cancel order error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Database error occurred. Please try again.';
    header('Location: ' . SITE_URL . 'order-details.php?id=' . $orderId);
    exit;
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('Cancel order error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred. Please try again.';
    header('Location: ' . SITE_URL . 'order-details.php?id=' . $orderId);
    exit;
}
?>