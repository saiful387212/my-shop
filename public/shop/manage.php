<?php
// ============================================================
// FILE: public/shop/manage.php
// PURPOSE: Vendor shop management - COMPLETE FIXED VERSION
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
// SIMPLE ACCESS CONTROL - NO ADMIN CHECK
// ============================================

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to manage your shop.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$shopModel = new Shop();

// ============================================
// GET USER'S SHOP
// ============================================

$shop = $shopModel->getByUser($_SESSION['user_id']);

if (!$shop) {
    header('Location: ' . SITE_URL . 'shop/create.php');
    exit;
}

// ============================================
// GET SHOP DATA
// ============================================

$products = [];
$stats = null;
$orders = [];
$error = null;

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // ============================================
    // Get products for this shop
    // ============================================
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.shop_id = :shop_id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([':shop_id' => $shop['id']]);
    $products = $stmt->fetchAll();
    
    // ============================================
    // Get shop stats
    // ============================================
    $stats = $shopModel->getStats($shop['id']);
    
    // ============================================
    // FIX: Get vendor orders - DIRECT QUERY
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
        WHERE vo.shop_id = :shop_id
        ORDER BY vo.created_at DESC
    ");
    $stmt->execute([':shop_id' => $shop['id']]);
    $orders = $stmt->fetchAll();
    
    // ============================================
    // DEBUG: Log order count
    // ============================================
    error_log("Shop ID: " . $shop['id'] . " - Orders found: " . count($orders));
    
} catch (Exception $e) {
    error_log('Shop manage error: ' . $e->getMessage());
    $error = 'Could not load shop data.';
}

// ============================================
// GET CATEGORIES FOR HEADER
// ============================================

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Shop manage categories error: ' . $e->getMessage());
}

// ============================================
// CHECK MESSAGES
// ============================================

$successMsg = $_SESSION['success_message'] ?? '';
$errorMsg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$cartCount = getCartTotalItems();
$pageTitle = 'My Shop';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<section class="manage-shop-page">
    <div class="container">
        
        <div class="page-header">
            <h1>My <span class="highlight">Shop</span></h1>
            <p><?php echo htmlspecialchars($shop['shop_name']); ?></p>
        </div>
        
        <!-- Shop Status -->
        <div class="shop-status">
            <div class="status-badge <?php echo $shop['is_approved'] ? 'approved' : 'pending'; ?>">
                <?php if ($shop['is_approved']): ?>
                    <i class="fas fa-check-circle"></i> Shop Approved
                <?php else: ?>
                    <i class="fas fa-clock"></i> Pending Approval
                <?php endif; ?>
            </div>
            <?php if (!$shop['is_approved']): ?>
                <p class="status-message">Your shop is waiting for admin approval. You'll be notified once approved.</p>
            <?php endif; ?>
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
        
        <?php if ($shop['is_approved']): ?>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-box"></i></div>
                    <div class="stat-number"><?php echo count($products); ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-number"><?php echo count($orders); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-number">$<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?php echo $stats['pending_orders'] ?? 0; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="product-add.php" class="quick-action">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Product</span>
                </a>
                <a href="#orders" class="quick-action">
                    <i class="fas fa-shopping-bag"></i>
                    <span>View Orders</span>
                </a>
                <a href="settings.php" class="quick-action">
                    <i class="fas fa-cog"></i>
                    <span>Shop Settings</span>
                </a>
            </div>
            
            <!-- Products Section -->
            <div class="products-section" id="products">
                <h2>My Products</h2>
                
                <?php if (!empty($products)): ?>
                    <div class="products-table-container">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $imagePath = !empty($product['image_url']) 
                                                ? SITE_URL . 'uploads/products/' . htmlspecialchars($product['image_url']) 
                                                : SITE_URL . 'assets/images/no-image.png';
                                            ?>
                                            <img src="<?php echo $imagePath; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 class="product-thumb"
                                                 onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td>
                                            <span class="stock-badge <?php 
                                                echo $product['stock_quantity'] <= 0 ? 'out-of-stock' : 
                                                    ($product['stock_quantity'] < 10 ? 'low-stock' : 'in-stock'); 
                                            ?>">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $product['status'] ?? 'approved'; ?>">
                                                <?php echo ucfirst($product['status'] ?? 'Approved'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="product-edit.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo SITE_URL; ?>product_details.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-primary btn-sm" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="product-delete.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('Delete this product?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <p>You haven't added any products yet.</p>
                        <a href="product-add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Your First Product
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- ============================================
                 ORDERS SECTION - FIXED
                 ============================================ -->
            <div class="orders-section" id="orders">
                <h2>Recent Orders</h2>
                
                <?php if (!empty($orders)): ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <span class="order-number">#<?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></span>
                                    <span class="order-customer">
                                        <?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?>
                                    </span>
                                    <span class="order-date">
                                        <?php echo date('M d, Y', strtotime($order['order_date'] ?? $order['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="order-amount">
                                    $<?php echo number_format($order['total_amount'] ?? 0, 2); ?>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span class="status-badge <?php echo $order['status'] ?? 'pending'; ?>">
                                        <?php echo ucfirst($order['status'] ?? 'Pending'); ?>
                                    </span>
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <p>No orders yet.</p>
                        <p style="font-size:13px;color:#6c757d;margin-top:4px;">
                            Orders will appear here when customers purchase from your shop.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            
            <!-- Shop Not Approved Yet -->
            <div class="pending-notice">
                <div class="pending-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Shop Pending Approval</h3>
                <p>Your shop is waiting for admin approval. You will be notified once approved.</p>
                <p class="pending-info">You can still <a href="settings.php">update your shop settings</a> while waiting.</p>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<style>
/* ============================================
   MANAGE SHOP PAGE - COMPLETE STYLES
   ============================================ */

.manage-shop-page {
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

/* Shop Status */
.shop-status {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    border: 1px solid #eef2f7;
}

.status-badge {
    display: inline-block;
    padding: 6px 24px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.status-badge.approved {
    background: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-message {
    margin-top: 8px;
    color: #6c757d;
    font-size: 14px;
}

/* Alerts */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Stats */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #eef2f7;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-size: 20px;
}

.stat-icon.blue { background: #e8edf9; color: #2C3E8F; }
.stat-icon.orange { background: #fef3e7; color: #f39c12; }
.stat-icon.green { background: #e6f7ed; color: #27ae60; }
.stat-icon.red { background: #fde8e8; color: #e74c3c; }

.stat-number {
    font-size: 28px;
    font-weight: 800;
    color: #1a1a2e;
}

.stat-label {
    font-size: 14px;
    color: #6c757d;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 30px;
}

.quick-action {
    background: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #eef2f7;
    text-decoration: none;
    color: #1a1a2e;
    transition: all 0.3s ease;
}

.quick-action:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    border-color: #2C3E8F;
}

.quick-action i {
    font-size: 28px;
    color: #2C3E8F;
    display: block;
    margin-bottom: 8px;
}

.quick-action span {
    font-weight: 600;
}

/* Products Section */
.products-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 30px;
    border: 1px solid #eef2f7;
}

.products-section h2 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 16px;
}

.products-table-container {
    overflow-x: auto;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
}

.products-table th {
    background: #f8fafc;
    padding: 10px 14px;
    text-align: left;
    font-size: 12px;
    text-transform: uppercase;
    color: #6c757d;
    border-bottom: 2px solid #e2e8f0;
}

.products-table td {
    padding: 10px 14px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.products-table tr:hover td {
    background: #f8fafc;
}

.product-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    background: #f8fafc;
}

.stock-badge {
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.stock-badge.in-stock { background: #d4edda; color: #155724; }
.stock-badge.low-stock { background: #fff3cd; color: #856404; }
.stock-badge.out-of-stock { background: #f8d7da; color: #721c24; }

.status-badge {
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.approved { background: #d4edda; color: #155724; }
.status-badge.rejected { background: #f8d7da; color: #721c24; }

.status-badge.accepted { background: #cce5ff; color: #004085; }
.status-badge.processing { background: #cce5ff; color: #004085; }
.status-badge.shipped { background: #d4edda; color: #155724; }
.status-badge.delivered { background: #d4edda; color: #155724; }
.status-badge.cancelled { background: #f8d7da; color: #721c24; }

.action-buttons {
    display: flex;
    gap: 4px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-sm {
    padding: 4px 10px;
    font-size: 11px;
}

.btn-primary {
    background: #2C3E8F;
    color: white;
}

.btn-primary:hover {
    background: #1a2a6c;
}

.btn-warning {
    background: #f39c12;
    color: white;
}

.btn-warning:hover {
    background: #d68910;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.no-products {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.no-products i {
    font-size: 32px;
    color: #cbd5e0;
    display: block;
    margin-bottom: 8px;
}

/* Orders Section */
.orders-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #eef2f7;
}

.orders-section h2 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 16px;
}

.orders-list {
    overflow-x: auto;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.order-item:last-child {
    border-bottom: none;
}

.order-info {
    display: flex;
    gap: 20px;
    align-items: center;
}

.order-number {
    font-weight: 600;
    color: #2C3E8F;
}

.order-customer {
    color: #4a5568;
}

.order-date {
    color: #6c757d;
    font-size: 13px;
}

.order-amount {
    font-weight: 700;
    color: #2C3E8F;
}

.no-orders {
    text-align: center;
    padding: 30px 20px;
    color: #6c757d;
}

.no-orders i {
    font-size: 32px;
    color: #cbd5e0;
    display: block;
    margin-bottom: 8px;
}

/* Pending Notice */
.pending-notice {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    border: 1px solid #eef2f7;
}

.pending-icon {
    font-size: 48px;
    color: #f39c12;
    margin-bottom: 16px;
}

.pending-notice h3 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.pending-notice p {
    color: #6c757d;
    font-size: 16px;
}

.pending-info {
    margin-top: 16px;
    font-size: 14px;
}

.pending-info a {
    color: #2C3E8F;
    text-decoration: none;
}

.pending-info a:hover {
    text-decoration: underline;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .order-info {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .products-table {
        font-size: 13px;
    }
    
    .products-table th,
    .products-table td {
        padding: 6px 10px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .stat-number {
        font-size: 22px;
    }
    
    .products-table .product-thumb {
        width: 40px;
        height: 40px;
    }
}
</style>

<?php require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php'; ?>