<?php
// ============================================================
// FILE: public/order-success.php
// PURPOSE: Show order confirmation after successful checkout
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load models
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'OrderItem.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF ORDER EXISTS
// ============================================

$orderId = isset($_SESSION['last_order_id']) ? (int)$_SESSION['last_order_id'] : 0;
$orderNumber = isset($_SESSION['last_order_number']) ? $_SESSION['last_order_number'] : '';

if ($orderId <= 0 || empty($orderNumber)) {
    // No order found - redirect to home
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

// ============================================
// FETCH ORDER DETAILS
// ============================================

try {
    $orderModel = new Order();
    $order = $orderModel->find($orderId);
    
    if (!$order) {
        header('Location: ' . SITE_URL . 'index.php');
        exit;
    }
    
    // Get order items
    $orderItems = $orderModel->getItems($orderId);
    
} catch (Exception $e) {
    error_log('Order success error: ' . $e->getMessage());
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

// Clear the session data for the order (prevent showing again on refresh)
unset($_SESSION['last_order_id']);
unset($_SESSION['last_order_number']);

// Set page title
$pageTitle = 'Order Success';

// Include header
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     ORDER SUCCESS CONTENT
     ============================================ -->
<section class="order-success-page">
    <div class="container">
        
        <!-- Success Icon -->
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <!-- Success Message -->
        <div class="success-message">
            <h1>Thank You for Your Order!</h1>
            <p>Your order has been placed successfully.</p>
        </div>
        
        <!-- Order Details -->
        <div class="order-details">
            
            <!-- Order Info -->
            <div class="order-info">
                <div class="info-item">
                    <span class="label">Order Number</span>
                    <span class="value"><?php echo htmlspecialchars($order['order_number']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Order Date</span>
                    <span class="value"><?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Order Status</span>
                    <span class="value status-pending">Pending</span>
                </div>
                <div class="info-item">
                    <span class="label">Total Amount</span>
                    <span class="value">$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <div class="shipping-info">
                <h3>Shipping Address</h3>
                <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            </div>
            
            <!-- Order Items -->
            <div class="order-items">
                <h3>Order Items</h3>
                
                <?php foreach ($orderItems as $item): ?>
                    <div class="order-item">
                        <div class="item-info">
                            <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                            <span class="item-quantity">× <?php echo $item['quantity']; ?></span>
                        </div>
                        <div class="item-price">
                            $<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($order['total_amount'] - 5.00, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping</span>
                        <span>$5.00</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="order-actions">
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> View My Orders
            </a>
        </div>
        
        <!-- Email Notice -->
        <div class="email-notice">
            <i class="fas fa-envelope"></i>
            <p>We've sent a confirmation email to <strong><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></strong></p>
        </div>
        
    </div>
</section>

<?php
// Include footer
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>