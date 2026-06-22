<?php
// ============================================================
// FILE: public/admin/order-update.php
// PURPOSE: Update order status
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
// ADMIN CHECK
// ============================================

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// ============================================
// GET POST DATA
// ============================================

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

$allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

// Validate
if ($orderId <= 0 || !in_array($status, $allowedStatuses)) {
    $_SESSION['error_message'] = 'Invalid order or status.';
    header('Location: orders.php');
    exit;
}

// ============================================
// UPDATE ORDER STATUS
// ============================================

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Update status
    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
    $result = $stmt->execute([
        ':id' => $orderId,
        ':status' => $status
    ]);
    
    if ($result) {
        $_SESSION['success_message'] = '✅ Order status updated successfully!';
    } else {
        $_SESSION['error_message'] = '❌ Failed to update order status.';
    }
    
} catch (PDOException $e) {
    error_log('Order update error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log('Order update error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred.';
}

// Redirect back to orders page
header('Location: orders.php');
exit;
?>