<?php
// ============================================================
// FILE: public/order-details.php
// PURPOSE: View detailed order information with cancellation
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF USER IS LOGGED IN
// ============================================

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to view order details.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// ============================================
// GET ORDER ID
// ============================================

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    header('Location: ' . SITE_URL . 'orders.php');
    exit;
}

// ============================================
// FETCH ORDER DETAILS
// ============================================

$order = null;
$orderItems = [];
$vendorOrders = [];
$error = null;

try {
    $orderModel = new Order();
    $shopModel = new Shop();
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Get order with customer info
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            u.name as customer_name,
            u.email as customer_email
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = :id
    ");
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: ' . SITE_URL . 'orders.php');
        exit;
    }
    
    // Check if user owns this order or is admin
    $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    $isOwner = $order['user_id'] == $_SESSION['user_id'];
    
    if (!$isAdmin && !$isOwner) {
        $_SESSION['error_message'] = 'You do not have permission to view this order.';
        header('Location: ' . SITE_URL . 'orders.php');
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT 
            oi.*,
            p.id as product_id,
            p.image_url,
            s.shop_name,
            s.shop_slug
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN shops s ON p.shop_id = s.id
        WHERE oi.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $orderId]);
    $orderItems = $stmt->fetchAll();
    
    // Get vendor orders for this order
    $stmt = $pdo->prepare("
        SELECT 
            vo.*,
            s.shop_name,
            s.shop_slug,
            v.name as vendor_name
        FROM vendor_orders vo
        LEFT JOIN shops s ON vo.shop_id = s.id
        LEFT JOIN users v ON vo.vendor_id = v.id
        WHERE vo.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $orderId]);
    $vendorOrders = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Order details error: ' . $e->getMessage());
    $error = 'Could not load order details.';
}

// ============================================
// GET CATEGORIES FOR HEADER
// ============================================

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Order details categories error: ' . $e->getMessage());
}

$cartCount = getCartTotalItems();
$pageTitle = 'Order Details';

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<section class="order-details-page">
    <div class="container">
        
        <div class="page-header">
            <h1>Order <span class="highlight">Details</span></h1>
            <p>Order #<?php echo htmlspecialchars($order['order_number'] ?? ''); ?></p>
        </div>
        
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
                        <span class="value"><?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
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
            
            <!-- Shipping Address -->
            <div class="shipping-section">
                <h3><i class="fas fa-map-marker-alt"></i> Shipping Address</h3>
                <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            </div>
            
            <!-- Vendor Orders -->
            <?php if (!empty($vendorOrders)): ?>
                <div class="vendor-section">
                    <h3><i class="fas fa-store"></i> Vendor Orders</h3>
                    <?php foreach ($vendorOrders as $vendorOrder): ?>
                        <div class="vendor-card">
                            <div class="vendor-header">
                                <div class="vendor-info">
                                    <i class="fas fa-store"></i>
                                    <strong><?php echo htmlspecialchars($vendorOrder['shop_name'] ?? 'Unknown Shop'); ?></strong>
                                    <span class="vendor-badge">Vendor</span>
                                </div>
                                <div>
                                    <span class="status-badge <?php echo $vendorOrder['status']; ?>">
                                        <?php echo ucfirst($vendorOrder['status']); ?>
                                    </span>
                                    <span class="vendor-amount">$<?php echo number_format($vendorOrder['total_amount'], 2); ?></span>
                                </div>
                            </div>
                            <div class="vendor-details">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($vendorOrder['vendor_name'] ?? 'Unknown'); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($vendorOrder['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Order Items -->
            <div class="items-section">
                <h3><i class="fas fa-shopping-bag"></i> Order Items</h3>
                
                <?php if (!empty($orderItems)): ?>
                    <div class="items-list">
                        <div class="items-header">
                            <span>Product</span>
                            <span>Shop</span>
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
                                            <a href="product_details.php?id=<?php echo $item['product_id']; ?>" class="item-link">
                                                View Product
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="item-shop">
                                    <?php if (!empty($item['shop_name'])): ?>
                                        <a href="shop/view.php?slug=<?php echo htmlspecialchars($item['shop_slug']); ?>" class="shop-link">
                                            <i class="fas fa-store"></i> <?php echo htmlspecialchars($item['shop_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#6c757d;">N/A</span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-price">$<?php echo number_format($item['product_price'], 2); ?></div>
                                <div class="item-qty">× <?php echo $item['quantity']; ?></div>
                                <div class="item-subtotal">$<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="items-totals">
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
                <?php else: ?>
                    <p style="color:#6c757d;">No items found in this order.</p>
                <?php endif; ?>
            </div>
            
            <!-- ============================================
                 ORDER ACTIONS WITH CANCEL FEATURE
                 ============================================ -->
            <div class="order-actions">
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
                
                <!-- ============================================
                     CANCEL ORDER BUTTON (Only for eligible orders)
                     ============================================ -->
                <?php 
                $cancelableStatuses = ['pending', 'processing'];
                if (in_array($order['status'], $cancelableStatuses)): 
                ?>
                    <button class="btn btn-danger" onclick="openCancelModal()">
                        <i class="fas fa-times-circle"></i> Cancel Order
                    </button>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<!-- ============================================
     CANCEL ORDER MODAL
     ============================================ -->
<div class="modal-overlay" id="cancelModal">
    <div class="modal-content">
        <div class="modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Cancel Order</h3>
        <p>Are you sure you want to cancel this order?</p>
        <p style="color:#6c757d;font-size:14px;margin-bottom:16px;">
            Order #<strong id="cancelOrderNumber"><?php echo htmlspecialchars($order['order_number'] ?? ''); ?></strong>
        </p>
        
        <form method="POST" action="cancel-order.php" id="cancelForm">
            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
            
            <div class="form-group">
                <label for="cancel_reason">Reason for cancellation (optional)</label>
                <select name="cancel_reason" id="cancel_reason" class="form-control">
                    <option value="">Select a reason...</option>
                    <option value="Changed my mind">Changed my mind</option>
                    <option value="Found better price">Found better price</option>
                    <option value="Ordered by mistake">Ordered by mistake</option>
                    <option value="Shipping takes too long">Shipping takes too long</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="closeCancelModal()">
                    <i class="fas fa-times"></i> Keep Order
                </button>
                <button type="submit" class="btn btn-danger" id="confirmCancelBtn">
                    <i class="fas fa-check-circle"></i> Yes, Cancel Order
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ============================================
   ORDER DETAILS STYLES
   ============================================ */

.order-details-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 32px;
    font-weight: 800;
    color: #1a1a2e;
}

.page-header h1 .highlight {
    color: #2C3E8F;
}

.page-header h1::after {
    content: '';
    display: block;
    width: 60px;
    height: 4px;
    background: #2C3E8F;
    margin: 10px auto 0;
    border-radius: 2px;
}

.page-header p {
    color: #6c757d;
    font-size: 16px;
    margin-top: 8px;
}

/* Alert */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert i {
    font-size: 20px;
}

/* Order Summary */
.order-summary {
    background: white;
    border-radius: 16px;
    padding: 24px 30px;
    border: 1px solid #eef2f7;
    margin-bottom: 24px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.summary-item {
    display: flex;
    flex-direction: column;
}

.summary-item .label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
}

.summary-item .value {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a2e;
    margin-top: 2px;
}

.summary-item .value.status-pending { color: #f39c12; }
.summary-item .value.status-processing { color: #2C3E8F; }
.summary-item .value.status-shipped { color: #27ae60; }
.summary-item .value.status-delivered { color: #27ae60; }
.summary-item .value.status-cancelled { color: #e74c3c; }

/* Shipping Section */
.shipping-section {
    background: white;
    border-radius: 16px;
    padding: 24px 30px;
    border: 1px solid #eef2f7;
    margin-bottom: 24px;
}

.shipping-section h3 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 8px;
}

.shipping-section p {
    color: #4a5568;
    line-height: 1.6;
}

/* Vendor Section */
.vendor-section {
    background: white;
    border-radius: 16px;
    padding: 24px 30px;
    border: 1px solid #eef2f7;
    margin-bottom: 24px;
}

.vendor-section h3 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 16px;
}

.vendor-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 12px;
    border: 1px solid #eef2f7;
}

.vendor-card:last-child {
    margin-bottom: 0;
}

.vendor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.vendor-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.vendor-info i {
    color: #2C3E8F;
}

.vendor-badge {
    background: #cce5ff;
    color: #004085;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
}

.status-badge {
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.accepted { background: #cce5ff; color: #004085; }
.status-badge.processing { background: #cce5ff; color: #004085; }
.status-badge.shipped { background: #d4edda; color: #155724; }
.status-badge.delivered { background: #d4edda; color: #155724; }
.status-badge.cancelled { background: #f8d7da; color: #721c24; }

.vendor-amount {
    font-weight: 700;
    color: #2C3E8F;
}

.vendor-details {
    display: flex;
    gap: 20px;
    margin-top: 8px;
    font-size: 13px;
    color: #6c757d;
}

.vendor-details i {
    margin-right: 4px;
}

/* Items Section */
.items-section {
    background: white;
    border-radius: 16px;
    padding: 24px 30px;
    border: 1px solid #eef2f7;
    margin-bottom: 24px;
}

.items-section h3 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 16px;
}

.items-header {
    display: grid;
    grid-template-columns: 3fr 1.5fr 1fr 1fr 1.2fr;
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
    grid-template-columns: 3fr 1.5fr 1fr 1fr 1.2fr;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
    align-items: center;
}

.item-row:last-child {
    border-bottom: none;
}

.item-product {
    display: flex;
    align-items: center;
    gap: 12px;
}

.item-product img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
}

.item-name {
    font-weight: 500;
    color: #1a1a2e;
}

.item-link {
    font-size: 12px;
    color: #2C3E8F;
    text-decoration: none;
}

.item-link:hover {
    text-decoration: underline;
}

.item-shop .shop-link {
    color: #2C3E8F;
    text-decoration: none;
    font-size: 13px;
}

.item-shop .shop-link:hover {
    text-decoration: underline;
}

.item-price {
    color: #4a5568;
}

.item-qty {
    color: #6c757d;
}

.item-subtotal {
    font-weight: 700;
    color: #2C3E8F;
}

.items-totals {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 2px solid #f0f0f0;
    max-width: 300px;
    margin-left: auto;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 14px;
    color: #4a5568;
}

.total-row.grand-total {
    font-size: 18px;
    font-weight: 800;
    color: #1a1a2e;
    padding-top: 8px;
    border-top: 2px solid #f0f0f0;
    margin-top: 4px;
}

/* ============================================
   MODAL STYLES
   ============================================ */

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: white;
    padding: 32px;
    border-radius: 16px;
    max-width: 450px;
    width: 90%;
    text-align: center;
}

.modal-icon {
    font-size: 48px;
    color: #e74c3c;
    margin-bottom: 12px;
}

.modal-content h3 {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 8px;
}

.modal-content .form-group {
    text-align: left;
    margin: 16px 0;
}

.modal-content .form-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 6px;
}

.modal-content .form-control {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
}

.btn-group {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-family: 'Inter', sans-serif;
}

.btn-secondary {
    background: #e2e8f0;
    color: #1a1a2e;
}

.btn-secondary:hover {
    background: #cbd5e0;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Order Actions */
.order-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1024px) {
    .summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .summary-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .items-header {
        display: none;
    }
    
    .item-row {
        grid-template-columns: 1fr;
        gap: 6px;
        padding: 16px 0;
    }
    
    .item-product {
        font-weight: 600;
    }
    
    .item-price::before {
        content: 'Price: ';
        font-weight: 600;
        color: #6c757d;
    }
    
    .item-qty::before {
        content: 'Qty: ';
        font-weight: 600;
        color: #6c757d;
    }
    
    .item-subtotal::before {
        content: 'Subtotal: ';
        font-weight: 600;
        color: #6c757d;
    }
    
    .vendor-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .vendor-details {
        flex-wrap: wrap;
    }
    
    .order-actions {
        flex-direction: column;
    }
    
    .order-actions .btn {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .order-summary,
    .shipping-section,
    .vendor-section,
    .items-section {
        padding: 16px;
    }
}
</style>

<!-- ============================================
     JAVASCRIPT FOR MODAL
     ============================================ -->
<script>
function openCancelModal() {
    document.getElementById('cancelModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal on outside click
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCancelModal();
    }
});

// Confirm cancel with loading state
document.getElementById('cancelForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('confirmCancelBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
});

console.log('✅ Order details page loaded successfully!');
</script>

<?php require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php'; ?>