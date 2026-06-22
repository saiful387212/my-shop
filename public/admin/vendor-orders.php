<?php
// ============================================================
// FILE: public/admin/vendor-orders.php
// PURPOSE: Admin view for all vendor orders
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

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
// GET VENDOR ORDERS
// ============================================

$vendorOrders = [];
$error = '';

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Get all vendor orders with details
    $stmt = $pdo->query("
        SELECT 
            vo.*,
            o.order_number,
            o.shipping_address,
            o.created_at as order_date,
            u.name as customer_name,
            u.email as customer_email,
            s.shop_name,
            s.shop_slug,
            v.name as vendor_name
        FROM vendor_orders vo
        LEFT JOIN orders o ON vo.order_id = o.id
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN shops s ON vo.shop_id = s.id
        LEFT JOIN users v ON vo.vendor_id = v.id
        ORDER BY vo.created_at DESC
    ");
    $vendorOrders = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Could not load vendor orders: ' . $e->getMessage();
    error_log('Admin vendor orders error: ' . $e->getMessage());
}

// ============================================
// MESSAGES
// ============================================

$successMsg = $_SESSION['success_message'] ?? '';
$errorMsg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$userName = $_SESSION['user_name'] ?? 'Admin';

// ============================================
// PROCESS STATUS UPDATE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $vendorOrderId = (int)$_POST['vendor_order_id'];
    $status = $_POST['status'] ?? '';
    
    $allowedStatuses = ['pending', 'accepted', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if ($vendorOrderId > 0 && in_array($status, $allowedStatuses)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE vendor_orders SET status = :status WHERE id = :id
            ");
            $result = $stmt->execute([':id' => $vendorOrderId, ':status' => $status]);
            
            if ($result) {
                $_SESSION['success_message'] = '✅ Vendor order status updated!';
            } else {
                $_SESSION['error_message'] = '❌ Failed to update status.';
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = '❌ Error: ' . $e->getMessage();
        }
        
        header('Location: vendor-orders.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Orders - Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            min-height: 100vh;
        }
        
        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 250px;
            background: #1a1a2e;
            color: white;
            min-height: 100vh;
            padding: 20px 0;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-brand h2 {
            font-size: 22px;
        }
        
        .sidebar-brand span {
            color: #4A6CCF;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0 10px;
        }
        
        .sidebar-menu li a {
            display: block;
            padding: 12px 16px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar-menu li a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu li a.active {
            background: #2C3E8F;
            color: white;
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 20px;
        }
        
        .sidebar-footer .admin-name {
            font-weight: bold;
            color: white;
        }
        
        .sidebar-footer .admin-email {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
        }
        
        .sidebar-footer .logout-btn {
            display: block;
            margin-top: 10px;
            color: #e74c3c;
            text-decoration: none;
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .topbar h1 {
            font-size: 24px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }
        
        .btn-primary {
            background: #2C3E8F;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a2a6c;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #219a52;
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
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* ===== ALERTS ===== */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* ===== TABLE ===== */
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .search-box input {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: #f8f9fa;
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #6c757d;
            border-bottom: 2px solid #dee2e6;
        }
        
        table td {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        table tr:hover td {
            background: #f8f9fa;
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
        
        .status-select {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 12px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 16px;
        }
        
        .shop-name {
            font-weight: 600;
            color: #2C3E8F;
        }
        
        .shop-name i {
            margin-right: 4px;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box input {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
    <div class="sidebar-brand">
        <h2>My <span>Shop</span></h2>
        <small style="color:rgba(255,255,255,0.5);">Admin Panel</small>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
        <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
        <li><a href="vendors.php"><i class="fas fa-store"></i> Vendors</a></li>
        <li><a href="vendor-orders.php" class="active"><i class="fas fa-truck"></i> Vendor Orders</a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
    </ul>
    
    <div class="sidebar-footer">
        <div class="admin-name"><?php echo htmlspecialchars($userName); ?></div>
        <div class="admin-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@myshop.com'); ?></div>
        <a href="<?php echo SITE_URL; ?>logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">
    
    <!-- Top Bar -->
    <div class="topbar">
        <div>
            <h1>Vendor Orders</h1>
            <small style="color:#6c757d;">Dashboard / Vendor Orders</small>
        </div>
        <div>
            <span style="background:#2C3E8F;color:white;padding:6px 14px;border-radius:20px;font-size:13px;">
                <i class="fas fa-truck"></i> <?php echo count($vendorOrders); ?> Orders
            </span>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Vendor Orders Table -->
    <div class="table-container">
        
        <div class="table-header">
            <h3><i class="fas fa-truck"></i> All Vendor Orders</h3>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search orders..." onkeyup="searchTable()">
            </div>
        </div>
        
        <?php if (!empty($vendorOrders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Vendor / Shop</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($vendorOrders as $order): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></strong>
                            </td>
                            <td>
                                <div class="shop-name">
                                    <i class="fas fa-store"></i>
                                    <?php echo htmlspecialchars($order['shop_name'] ?? 'Unknown Shop'); ?>
                                </div>
                                <div style="font-size:12px;color:#6c757d;">
                                    <i class="fas fa-user"></i> 
                                    <?php echo htmlspecialchars($order['vendor_name'] ?? 'Unknown Vendor'); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?>
                                <br>
                                <small style="color:#6c757d;"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></small>
                            </td>
                            <td style="font-weight:700;color:#2C3E8F;">
                                $<?php echo number_format($order['total_amount'], 2); ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td style="font-size:13px;color:#6c757d;">
                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                            </td>
                            <td>
                                <form method="POST" action="" style="display:flex;gap:4px;align-items:center;">
                                    <input type="hidden" name="vendor_order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="accepted" <?php echo $order['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <input type="submit" name="update_status" value="Update" class="btn btn-primary btn-sm" style="padding:4px 10px;font-size:11px;">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-truck"></i>
                <h3>No Vendor Orders</h3>
                <p style="color:#6c757d;">No orders have been placed from vendors yet.</p>
            </div>
        <?php endif; ?>
        
    </div>
    
</div>

<!-- ===== JAVASCRIPT ===== -->
<script>
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#tableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

console.log('✅ Admin vendor orders page loaded successfully!');
</script>

</body>
</html>