<?php
// ============================================================
// FILE: public/shop/order-details.php
// PURPOSE: Shop owner view order details - COMPLETE FIX
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// ACCESS CONTROL
// ============================================

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to manage your shop.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$shopModel = new Shop();
$shop = $shopModel->getByUser($_SESSION['user_id']);

if (!$shop || !$shop['is_approved']) {
    header('Location: ' . SITE_URL . 'shop/manage.php');
    exit;
}

// ============================================
// GET ORDER ID
// ============================================

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    $_SESSION['error_message'] = 'Invalid order ID.';
    header('Location: manage.php');
    exit;
}

// ============================================
// FETCH ORDER DETAILS
// ============================================

$order = null;
$orderItems = [];
$error = null;

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // ============================================
    // FIX: Get vendor order details with proper query
    // ============================================
    $stmt = $pdo->prepare("
        SELECT 
            vo.*,
            o.order_number,
            o.shipping_address,
            o.created_at as order_date,
            u.name as customer_name,
            u.email as customer_email
        FROM vendor_orders vo
        LEFT JOIN orders o ON vo.order_id = o.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE vo.id = :vendor_order_id 
        AND vo.shop_id = :shop_id
    ");
    
    $stmt->execute([
        ':vendor_order_id' => $orderId,
        ':shop_id' => $shop['id']
    ]);
    $order = $stmt->fetch();
    
    // ============================================
    // DEBUG: Log what was found
    // ============================================
    error_log("Order ID: $orderId, Shop ID: " . $shop['id']);
    error_log("Order found: " . ($order ? 'YES' : 'NO'));
    
    if (!$order) {
        // Try to get the order without shop_id check (for debugging)
        $stmt2 = $pdo->prepare("
            SELECT 
                vo.*,
                o.order_number,
                o.shipping_address,
                o.created_at as order_date,
                u.name as customer_name,
                u.email as customer_email
            FROM vendor_orders vo
            LEFT JOIN orders o ON vo.order_id = o.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE vo.id = :vendor_order_id
        ");
        $stmt2->execute([':vendor_order_id' => $orderId]);
        $orderDebug = $stmt2->fetch();
        
        if ($orderDebug) {
            error_log("Order exists but shop_id mismatch. Order shop_id: " . $orderDebug['shop_id'] . ", Your shop_id: " . $shop['id']);
            $error = 'This order does not belong to your shop.';
        } else {
            error_log("Order not found in vendor_orders table");
            $error = 'Order not found.';
        }
        
        // Redirect back if no order
        if (!$orderDebug) {
            header('Location: manage.php');
            exit;
        }
    }
    
    // ============================================
    // Get order items for this vendor order
    // ============================================
    if ($order) {
        $stmt = $pdo->prepare("
            SELECT 
                oi.*,
                p.id as product_id,
                p.image_url
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :order_id
        ");
        $stmt->execute([':order_id' => $order['order_id']]);
        $orderItems = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log('Shop order details PDO Error: ' . $e->getMessage());
    $error = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    error_log('Shop order details Error: ' . $e->getMessage());
    $error = 'Error: ' . $e->getMessage();
}

// ============================================
// PROCESS STATUS UPDATE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $order) {
    $newStatus = $_POST['status'] ?? '';
    $allowedStatuses = ['pending', 'accepted', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($newStatus, $allowedStatuses)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE vendor_orders 
                SET status = :status, updated_at = NOW() 
                WHERE id = :id AND shop_id = :shop_id
            ");
            $result = $stmt->execute([
                ':id' => $orderId,
                ':shop_id' => $shop['id'],
                ':status' => $newStatus
            ]);
            
            if ($result) {
                $_SESSION['success_message'] = '✅ Order status updated successfully!';
                header('Location: order-details.php?id=' . $orderId);
                exit;
            } else {
                $error = 'Failed to update order status.';
            }
            
        } catch (Exception $e) {
            error_log('Update order status error: ' . $e->getMessage());
            $error = 'An error occurred.';
        }
    }
}

// ============================================
// GET CATEGORIES FOR HEADER
// ============================================

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Shop order details categories error: ' . $e->getMessage());
}

// ============================================
// MESSAGES
// ============================================

$successMsg = $_SESSION['success_message'] ?? '';
$errorMsg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$cartCount = getCartTotalItems();
$pageTitle = 'Order Details';

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<section class="order-details-page">
    <div class="container">
        
        <div class="page-header">
            <h1>Order <span class="highlight">Details</span></h1>
            <p>
                <?php if ($order): ?>
                    Order #<?php echo htmlspecialchars($order['order_number'] ?? ''); ?>
                <?php else: ?>
                    Order Details
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Messages -->
        <?php if ($successMsg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($order): ?>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="label">Order Number</span>
                        <span class="value">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Order Date</span>
                        <span class="value"><?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Order Status</span>
                        <span class="value status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Total Amount</span>
                        <span class="value">$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Customer Information -->
            <div class="customer-section">
                <h3><i class="fas fa-user"></i> Customer Information</h3>
                <div class="customer-grid">
                    <div class="customer-item">
                        <span class="label">Name</span>
                        <span class="value"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></span>
                    </div>
                    <div class="customer-item">
                        <span class="label">Email</span>
                        <span class="value"><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <div class="shipping-section">
                <h3><i class="fas fa-map-marker-alt"></i> Shipping Address</h3>
                <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            </div>
            
            <!-- Order Items -->
            <div class="items-section">
                <h3><i class="fas fa-shopping-bag"></i> Order Items</h3>
                
                <?php if (!empty($orderItems)): ?>
                    <div class="items-list">
                        <div class="items-header">
                            <span>Product</span>
                            <span>Price</span>
                            <span>Quantity</span>
                            <span>Subtotal</span>
                        </div>
                        
                        <?php foreach ($orderItems as $item): ?>
                            <div class="item-row">
                                <div class="item-product">
                                    <?php 
                                    $imagePath = !empty($item['image_url']) 
                                        ? SITE_URL . 'uploads/products/' . htmlspecialchars($item['image_url']) 
                                        : SITE_URL . 'assets/images/no-image.png';
                                    ?>
                                    <img src="<?php echo $imagePath; ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                                    <div>
                                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <?php if ($item['product_id']): ?>
                                            <a href="<?php echo SITE_URL; ?>product_details.php?id=<?php echo $item['product_id']; ?>" 
                                               class="item-link" target="_blank">
                                                View Product
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="item-price">$<?php echo number_format($item['product_price'], 2); ?></div>
                                <div class="item-qty">× <?php echo $item['quantity']; ?></div>
                                <div class="item-subtotal">$<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="items-totals">
                            <div class="total-row">
                                <span>Total</span>
                                <span class="grand-total">$<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="color:#6c757d;">No items found in this order.</p>
                <?php endif; ?>
            </div>
            
            <!-- Status Update -->
            <div class="status-section">
                <h3><i class="fas fa-edit"></i> Update Order Status</h3>
                
                <form method="POST" action="" class="status-form">
                    <div class="status-form-group">
                        <label for="status">Change Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="accepted" <?php echo $order['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>
            </div>
            
            <!-- Actions -->
            <div class="order-actions">
                <a href="manage.php#orders" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
            
        <?php else: ?>
            
            <!-- Order Not Found -->
            <div class="order-not-found">
                <div class="not-found-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>Order Not Found</h3>
                <p><?php echo $error ?: 'The order you are looking for does not exist.'; ?></p>
                <a href="manage.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<style>
/* ============================================
   ORDER DETAILS PAGE STYLES
   ============================================ */

.order-details-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

.container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }

.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 32px;
    font-weight: 800;
    color: #1a1a2e;
}

.page-header h1 .highlight { color: #2C3E8F; }
.page-header h1::after { content: ''; display: block; width: 60px; height: 4px; background: #2C3E8F; margin: 10px auto 0; border-radius: 2px; }
.page-header p { color: #6c757d; font-size: 16px; margin-top: 8px; }

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
}
.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.alert i { font-size: 20px; }

.order-summary {
    background: white;
    border-radius: 16px;
    padding: 24px 30px;
    border: 1px solid #eef2f7;
    margin-bottom: 24px;
}
.summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.summary-item { display: flex; flex-direction: column; }
.summary-item .label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
.summary-item .value { font-size: 16px; font-weight: 700; color: #1a1a2e; margin-top: 2px; }
.summary-item .value.status-pending { color: #f39c12; }
.summary-item .value.status-processing { color: #2C3E8F; }
.summary-item .value.status-shipped { color: #27ae60; }
.summary-item .value.status-delivered { color: #27ae60; }
.summary-item .value.status-cancelled { color: #e74c3c; }

.customer-section {
    background: white;
    border-radius: 16px;
    padding: 24px 30px;
    border: 1px solid #eef2f7;
    margin-bottom: 24px;
}
.customer-section h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
.customer-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.customer-item { display: flex; flex-direction: column; }
.customer-item .label { font-size: 12px; font-weight: 600; color: #6c757d; }
.customer-item .value { font-size: 15px; font-weight: 500; color: #1a1a2e; margin-top: 2px; }

.shipping-section {
    background: white;
    border-radius: 16px;
    padding: 24px 30px;
    border: 1px solid #eef2f7;
    margin-bottom: 24px;
}
.shipping-section h3 { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
.shipping-section p { color: #4a5568; line-height: 1.6; }

.items-section {
    background: white;
    border-radius: 16px;
    padding: 24px 30px;
    border: 1px solid #eef2f7;
    margin-bottom: 24px;
}
.items-section h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
.items-header {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1.2fr;
    gap: 12px;
    padding: 8px 0;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    border-bottom: 2px solid #f0f0f0;
}
.item-row {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1.2fr;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
    align-items: center;
}
.item-row:last-child { border-bottom: none; }
.item-product { display: flex; align-items: center; gap: 12px; }
.item-product img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; background: #f8fafc; border: 1px solid #eef2f7; }
.item-name { font-weight: 500; color: #1a1a2e; }
.item-link { font-size: 12px; color: #2C3E8F; text-decoration: none; }
.item-link:hover { text-decoration: underline; }
.item-price { color: #4a5568; }
.item-qty { color: #6c757d; }
.item-subtotal { font-weight: 700; color: #2C3E8F; }
.items-totals { margin-top: 16px; padding-top: 16px; border-top: 2px solid #f0f0f0; text-align: right; }
.total-row { display: flex; justify-content: flex-end; padding: 4px 0; font-size: 18px; font-weight: 800; color: #2C3E8F; }

.status-section {
    background: white;
    border-radius: 16px;
    padding: 24px 30px;
    border: 1px solid #eef2f7;
    margin-bottom: 24px;
}
.status-section h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; }
.status-form { display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap; }
.status-form-group { flex: 1; min-width: 200px; }
.status-form-group label { display: block; font-weight: 600; font-size: 14px; color: #1a1a2e; margin-bottom: 4px; }
.form-control { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; background: #f8fafc; color: #1a1a2e; }
.form-control:focus { outline: none; border-color: #2C3E8F; background: white; }

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-family: 'Inter', sans-serif;
}
.btn-primary { background: #2C3E8F; color: white; }
.btn-primary:hover { background: #1a2a6c; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(44,62,143,0.3); }
.btn-secondary { background: #e2e8f0; color: #1a1a2e; }
.btn-secondary:hover { background: #cbd5e0; }

.order-actions { display: flex; gap: 12px; flex-wrap: wrap; }

.order-not-found {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 16px;
    border: 1px solid #eef2f7;
}
.not-found-icon { font-size: 64px; color: #e74c3c; margin-bottom: 16px; }
.order-not-found h3 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
.order-not-found p { color: #6c757d; margin-bottom: 24px; }

@media (max-width: 1024px) {
    .summary-grid { grid-template-columns: repeat(2, 1fr); }
    .customer-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .summary-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
    .customer-grid { grid-template-columns: 1fr; gap: 12px; }
    .items-header { display: none; }
    .item-row { grid-template-columns: 1fr; gap: 6px; padding: 16px 0; }
    .item-product { font-weight: 600; }
    .item-price::before { content: 'Price: '; font-weight: 600; color: #6c757d; }
    .item-qty::before { content: 'Qty: '; font-weight: 600; color: #6c757d; }
    .item-subtotal::before { content: 'Subtotal: '; font-weight: 600; color: #6c757d; }
    .status-form { flex-direction: column; align-items: stretch; }
    .order-actions { flex-direction: column; }
    .order-actions .btn { justify-content: center; }
}
@media (max-width: 480px) {
    .summary-grid { grid-template-columns: 1fr; }
    .order-summary, .customer-section, .shipping-section, .items-section, .status-section { padding: 16px; }
}
</style>

<?php require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php'; ?>