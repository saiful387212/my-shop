<?php
// ============================================================
// FILE: public/account.php
// PURPOSE: User account page with DU student profile
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF USER IS LOGGED IN
// ============================================

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to access your account.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// ============================================
// GET USER DATA
// ============================================

$user = null;
$error = '';

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    $stmt = $pdo->prepare("
        SELECT id, name, email, student_id, department, batch, hall, 
               is_admin, is_verified, created_at, last_login
        FROM users
        WHERE id = :id
    ");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found.');
    }
    
} catch (Exception $e) {
    error_log('Account error: ' . $e->getMessage());
    $error = 'Could not load account information.';
}

// ============================================
// GET ORDER COUNT
// ============================================

$orderCount = 0;
try {
    if ($pdo !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $orderCount = $stmt->fetch()['count'] ?? 0;
    }
} catch (Exception $e) {
    // Ignore
}

// ============================================
// MESSAGES
// ============================================

$successMsg = $_SESSION['success_message'] ?? '';
$errorMsg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get cart count
$cartCount = getCartTotalItems();

// Get categories for navigation
$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Account categories error: ' . $e->getMessage());
}

// Check if user has a shop
$shopModel = new Shop();
$userShop = $shopModel->getByUser($_SESSION['user_id']);

$pageTitle = 'My Account';

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     ACCOUNT PAGE CONTENT
     ============================================ -->
<section class="account-page">
    <div class="container">
        
        <div class="page-header">
            <h1>My <span class="highlight">Account</span></h1>
            <p>Manage your DU student profile</p>
        </div>
        
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
        
        <?php if ($user): ?>
            
            <div class="account-grid">
                
                <!-- ============================================
                     PROFILE CARD
                     ============================================ -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    
                    <!-- DU Status Badge -->
                    <div class="du-status">
                        <?php if ($user['is_verified'] == 1): ?>
                            <span class="verified-badge">
                                <i class="fas fa-check-circle"></i> Verified DU Student
                            </span>
                        <?php else: ?>
                            <span class="unverified-badge">
                                <i class="fas fa-clock"></i> Verification Pending
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-role">
                        <?php if ($user['is_admin'] == 1): ?>
                            <span class="role-badge admin">👑 Admin</span>
                        <?php else: ?>
                            <span class="role-badge student">🎓 Student</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- ============================================
                         DU STUDENT INFORMATION
                         ============================================ -->
                    <div class="du-info">
                        <h4><i class="fas fa-university"></i> DU Student Information</h4>
                        
                        <div class="du-info-item">
                            <span class="du-label">Student ID</span>
                            <span class="du-value"><?php echo htmlspecialchars($user['student_id'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="du-info-item">
                            <span class="du-label">Department</span>
                            <span class="du-value"><?php echo htmlspecialchars($user['department'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="du-info-item">
                            <span class="du-label">Batch</span>
                            <span class="du-value"><?php echo htmlspecialchars($user['batch'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="du-info-item">
                            <span class="du-label">Hall</span>
                            <span class="du-value"><?php echo htmlspecialchars($user['hall'] ?? 'Not provided'); ?></span>
                        </div>
                    </div>
                    
                    <div class="member-since">
                        Member since <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                    </div>
                    
                    <!-- Actions -->
                    <div class="profile-actions">
                        <?php if ($userShop): ?>
                            <a href="<?php echo SITE_URL; ?>shop/manage.php" class="btn btn-success">
                                <i class="fas fa-store"></i> My Shop
                            </a>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>shop/create.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Open Shop
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <!-- ============================================
                     STATS CARD
                     ============================================ -->
                <div class="stats-card">
                    <h3><i class="fas fa-chart-simple" style="color:#2C3E8F;"></i> Your Stats</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $orderCount; ?></span>
                            <span class="stat-label">Total Orders</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php 
                                try {
                                    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE user_id = :user_id AND status != 'cancelled'");
                                    $stmt->execute([':user_id' => $userId]);
                                    $total = $stmt->fetch()['total'] ?? 0;
                                    echo '$' . number_format($total, 0);
                                } catch (Exception $e) {
                                    echo '$0';
                                }
                            ?></span>
                            <span class="stat-label">Total Spent</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php 
                                try {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = :user_id");
                                    $stmt->execute([':user_id' => $userId]);
                                    echo $stmt->fetch()['count'] ?? 0;
                                } catch (Exception $e) {
                                    echo '0';
                                }
                            ?></span>
                            <span class="stat-label">Cart Items</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php 
                                try {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id AND status = 'pending'");
                                    $stmt->execute([':user_id' => $userId]);
                                    echo $stmt->fetch()['count'] ?? 0;
                                } catch (Exception $e) {
                                    echo '0';
                                }
                            ?></span>
                            <span class="stat-label">Pending Orders</span>
                        </div>
                    </div>
                    
                    <!-- DU Campus Info -->
                    <div class="campus-info">
                        <h4><i class="fas fa-map-marker-alt"></i> Campus Info</h4>
                        <div class="campus-item">
                            <span class="campus-label">Hall</span>
                            <span class="campus-value"><?php echo htmlspecialchars($user['hall'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="campus-item">
                            <span class="campus-label">Department</span>
                            <span class="campus-value"><?php echo htmlspecialchars($user['department'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="campus-item">
                            <span class="campus-label">Batch</span>
                            <span class="campus-value"><?php echo htmlspecialchars($user['batch'] ?? 'Not set'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- ============================================
                     RECENT ORDERS
                     ============================================ -->
                <div class="recent-orders">
                    <h3><i class="fas fa-clock" style="color:#2C3E8F;"></i> Recent Orders</h3>
                    
                    <?php 
                    try {
                        $stmt = $pdo->prepare("
                            SELECT id, order_number, total_amount, status, created_at 
                            FROM orders 
                            WHERE user_id = :user_id 
                            ORDER BY created_at DESC 
                            LIMIT 5
                        ");
                        $stmt->execute([':user_id' => $userId]);
                        $recentOrders = $stmt->fetchAll();
                    } catch (Exception $e) {
                        $recentOrders = [];
                    }
                    ?>
                    
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="order-item">
                                <div>
                                    <div class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                                    <div class="order-date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                </div>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <span class="order-total">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                    <span class="order-status <?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($orderCount > 5): ?>
                            <div style="text-align:center;margin-top:12px;">
                                <a href="orders.php" class="btn btn-secondary btn-small">
                                    View All Orders <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-shopping-bag" style="font-size:32px;color:#cbd5e0;margin-bottom:8px;"></i>
                            <p>You haven't placed any orders yet.</p>
                            <a href="products.php" class="btn btn-primary btn-small" style="margin-top:8px;">
                                <i class="fas fa-shopping-bag"></i> Start Shopping
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<style>
/* ============================================
   ACCOUNT PAGE STYLES
   ============================================ */

.account-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

.container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
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

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
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

.alert i {
    font-size: 20px;
}

/* Account Grid */
.account-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

/* Profile Card */
.profile-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    text-align: center;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #2C3E8F;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 700;
    margin: 0 auto 12px;
}

.profile-name {
    font-size: 22px;
    font-weight: 700;
}

.profile-email {
    color: #6c757d;
    font-size: 14px;
}

/* DU Status */
.du-status {
    margin: 12px 0;
}

.verified-badge {
    display: inline-block;
    background: #d4edda;
    color: #155724;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.verified-badge i {
    color: #27ae60;
    margin-right: 4px;
}

.unverified-badge {
    display: inline-block;
    background: #fff3cd;
    color: #856404;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.unverified-badge i {
    color: #f39c12;
    margin-right: 4px;
}

.profile-role {
    margin-bottom: 16px;
}

.role-badge {
    display: inline-block;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.role-badge.admin {
    background: #cce5ff;
    color: #004085;
}

.role-badge.student {
    background: #d4edda;
    color: #155724;
}

/* DU Info */
.du-info {
    background: #f8faff;
    border-radius: 12px;
    padding: 16px 20px;
    margin: 16px 0;
    border: 1px solid #e8edf9;
    text-align: left;
}

.du-info h4 {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 12px;
    text-align: center;
}

.du-info h4 i {
    color: #2C3E8F;
    margin-right: 6px;
}

.du-info-item {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
}

.du-info-item:last-child {
    border-bottom: none;
}

.du-label {
    color: #6c757d;
    font-weight: 500;
}

.du-value {
    color: #1a1a2e;
    font-weight: 600;
}

.member-since {
    font-size: 13px;
    color: #6c757d;
    margin: 12px 0;
}

.profile-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 16px;
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

.btn-primary {
    background: #2C3E8F;
    color: white;
}

.btn-primary:hover {
    background: #1a2a6c;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(44,62,143,0.3);
}

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-success:hover {
    background: #219a52;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-secondary {
    background: #e2e8f0;
    color: #1a1a2e;
}

.btn-secondary:hover {
    background: #cbd5e0;
}

.btn-small {
    padding: 6px 14px;
    font-size: 12px;
}

/* Stats Card */
.stats-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
}

.stats-card h3 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}

.stat-item {
    text-align: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
}

.stat-number {
    font-size: 24px;
    font-weight: 800;
    color: #2C3E8F;
}

.stat-label {
    font-size: 13px;
    color: #6c757d;
    display: block;
}

/* Campus Info */
.campus-info {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px 20px;
    border: 1px solid #eef2f7;
}

.campus-info h4 {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 12px;
}

.campus-info h4 i {
    color: #2C3E8F;
    margin-right: 6px;
}

.campus-item {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 14px;
}

.campus-label {
    color: #6c757d;
}

.campus-value {
    color: #1a1a2e;
    font-weight: 500;
}

/* Recent Orders */
.recent-orders {
    grid-column: 1 / -1;
    background: white;
    border-radius: 16px;
    padding: 24px 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
}

.recent-orders h3 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
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

.order-number {
    font-weight: 600;
    color: #2C3E8F;
}

.order-date {
    font-size: 13px;
    color: #6c757d;
}

.order-total {
    font-weight: 700;
}

.order-status {
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.order-status.pending { background: #fff3cd; color: #856404; }
.order-status.processing { background: #cce5ff; color: #004085; }
.order-status.shipped { background: #d4edda; color: #155724; }
.order-status.delivered { background: #d4edda; color: #155724; }
.order-status.cancelled { background: #f8d7da; color: #721c24; }

.no-orders {
    text-align: center;
    padding: 20px;
    color: #6c757d;
}

/* Responsive */
@media (max-width: 768px) {
    .account-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .recent-orders {
        grid-column: 1;
    }
    
    .du-info-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .campus-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .profile-avatar {
        width: 60px;
        height: 60px;
        font-size: 24px;
    }
    
    .profile-name {
        font-size: 18px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    
    .stat-number {
        font-size: 20px;
    }
    
    .profile-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .profile-actions .btn {
        justify-content: center;
    }
}
</style>

<?php require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php'; ?>