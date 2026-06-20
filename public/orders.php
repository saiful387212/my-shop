<?php
// ============================================================
// FILE: public/orders.php
// PURPOSE: Display order history for logged-in users
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load Order model
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF USER IS LOGGED IN
// ============================================

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    $_SESSION['error_message'] = 'Please login to view your orders.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// ============================================
// FETCH ORDERS FOR CURRENT USER
// ============================================

$orders = [];
$orderItems = [];
$error = null;

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    // ============================================
    // MAIN QUERY WITH JOINS
    // ============================================
    $stmt = $pdo->prepare('
        SELECT 
            o.id,
            o.order_number,
            o.total_amount,
            o.status,
            o.created_at,
            u.name as customer_name,
            u.email as customer_email,
            COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = :user_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ');
    
    $stmt->execute(['user_id' => $userId]);
    $orders = $stmt->fetchAll();
    
    // ============================================
    // FETCH ITEMS FOR EACH ORDER
    // ============================================
    foreach ($orders as &$order) {
        $itemStmt = $pdo->prepare('
            SELECT 
                product_name,
                product_price,
                quantity,
                (product_price * quantity) as subtotal
            FROM order_items
            WHERE order_id = :order_id
        ');
        $itemStmt->execute(['order_id' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log('Orders error: ' . $e->getMessage());
    $error = 'Database error occurred. Please try again.';
} catch (Exception $e) {
    error_log('Orders error: ' . $e->getMessage());
    $error = 'An error occurred. Please try again.';
}

// ============================================
// SET PAGE TITLE
// ============================================

$pageTitle = 'My Orders';

// Include header
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     ORDERS PAGE CONTENT
     ============================================ -->
<section class="orders-page">
    <div class="container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1>My Orders</h1>
            <p>View all your past orders</p>
        </div>
        
        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- ============================================
             ORDERS LIST
             ============================================ -->
        <?php if (!empty($orders)): ?>
            
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        
                        <!-- Order Header -->
                        <div class="order-header">
                            <div class="order-info">
                                <div class="order-number">
                                    <span class="label">Order #</span>
                                    <span class="value"><?php echo htmlspecialchars($order['order_number']); ?></span>
                                </div>
                                <div class="order-date">
                                    <span class="label">Date</span>
                                    <span class="value">
                                        <?php echo date('M d, Y \a\t h:i A', strtotime($order['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="order-status">
                                <span class="status-badge <?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <span class="order-total">
                                    $<?php echo number_format($order['total_amount'], 2); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <div class="order-items">
                            <?php if (!empty($order['items'])): ?>
                                <div class="items-header">
                                    <span>Product</span>
                                    <span>Price</span>
                                    <span>Quantity</span>
                                    <span>Subtotal</span>
                                </div>
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                        <span class="item-price">$<?php echo number_format($item['product_price'], 2); ?></span>
                                        <span class="item-quantity">× <?php echo $item['quantity']; ?></span>
                                        <span class="item-subtotal">$<?php echo number_format($item['subtotal'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Order Footer -->
                        <div class="order-footer">
                            <span class="item-count">
                                <i class="fas fa-box"></i>
                                <?php echo $order['item_count']; ?> item(s)
                            </span>
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            
            <!-- ============================================
                 NO ORDERS
                 ============================================ -->
            <div class="empty-orders">
                <div class="empty-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders yet. Start shopping today!</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Start Shopping
                </a>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<?php
// Include footer
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>