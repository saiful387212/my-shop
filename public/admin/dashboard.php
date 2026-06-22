<?php
// ============================================================
// FILE: public/admin/dashboard.php
// PURPOSE: Admin Dashboard with Multi-Vendor Support
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load models
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'User.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

// Start session


// ============================================
// FETCH STATISTICS
// ============================================

$stats = [
    'total_users' => 0,
    'total_products' => 0,
    'total_orders' => 0,
    'total_revenue' => 0,
    'pending_orders' => 0,
    'low_stock' => 0,
    'total_shops' => 0,
    'pending_shops' => 0,
    'total_vendors' => 0
];

$recent_orders = [];
$pending_shops = [];
$error = null;

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
    $stats['total_users'] = $stmt->fetch()['total'] ?? 0;
    
    // Total Products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $stats['total_products'] = $stmt->fetch()['total'] ?? 0;
    
    // Total Orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $stats['total_orders'] = $stmt->fetch()['total'] ?? 0;
    
    // Total Revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // Pending Orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
    $stats['pending_orders'] = $stmt->fetch()['total'] ?? 0;
    
    // Low Stock Products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity > 0 AND stock_quantity < 10");
    $stats['low_stock'] = $stmt->fetch()['total'] ?? 0;
    
    // Total Shops
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shops");
    $stats['total_shops'] = $stmt->fetch()['total'] ?? 0;
    
    // Pending Shops
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shops WHERE is_approved = 0");
    $stats['pending_shops'] = $stmt->fetch()['total'] ?? 0;
    
    // Total Vendors (users who have shops)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM shops");
    $stats['total_vendors'] = $stmt->fetch()['total'] ?? 0;
    
    // Recent Orders
    $stmt = $pdo->query("
        SELECT 
            o.*,
            u.name as customer_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll();
    
    // Pending Shops
    $stmt = $pdo->query("
        SELECT s.*, u.name as owner_name 
        FROM shops s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.is_approved = 0
        ORDER BY s.created_at DESC
        LIMIT 5
    ");
    $pending_shops = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $error = 'Database error occurred.';
} catch (Exception $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $error = 'An error occurred.';
}

// Check for messages
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$currentPage = basename($_SERVER['PHP_SELF']);
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'admin@myshop.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - My Shop</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ============================================================
           ALL CSS EMBEDDED
           ============================================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5;
            color: #1a1a2e;
            display: flex;
            min-height: 100vh;
        }
        
        /* ============================================
           SIDEBAR
           ============================================ */
        
        .sidebar {
            width: 260px;
            background: #1a1a2e;
            color: rgba(255, 255, 255, 0.8);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-brand i {
            font-size: 28px;
            color: #2C3E8F;
            background: white;
            padding: 8px;
            border-radius: 10px;
        }
        
        .sidebar-brand h2 {
            font-size: 20px;
            font-weight: 800;
            color: white;
        }
        
        .sidebar-brand small {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
        }
        
        .sidebar-nav .nav-section {
            padding: 0 16px 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-nav ul li {
            margin: 2px 12px;
        }
        
        .sidebar-nav ul li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .sidebar-nav ul li a i {
            width: 20px;
            font-size: 16px;
            text-align: center;
        }
        
        .sidebar-nav ul li a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }
        
        .sidebar-nav ul li a.active {
            background: #2C3E8F;
            color: white;
            box-shadow: 0 4px 12px rgba(44, 62, 143, 0.3);
        }
        
        .sidebar-nav ul li a .badge {
            margin-left: auto;
            background: #e74c3c;
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
        }
        
        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .sidebar-footer .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-footer .admin-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2C3E8F;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }
        
        .sidebar-footer .admin-info .admin-name {
            font-size: 14px;
            font-weight: 600;
            color: white;
        }
        
        .sidebar-footer .admin-info .admin-email {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar-footer .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.4);
            text-decoration: none;
            font-size: 13px;
            padding: 8px 0;
            transition: color 0.3s ease;
            margin-top: 8px;
        }
        
        .sidebar-footer .logout-btn:hover {
            color: #e74c3c;
        }
        
        /* ============================================
           MAIN CONTENT
           ============================================ */
        
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 24px 32px;
            min-height: 100vh;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #1a1a2e;
            cursor: pointer;
            padding: 4px;
        }
        
        .topbar-left h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .topbar-left .breadcrumb {
            font-size: 14px;
            color: #6c757d;
        }
        
        .topbar-left .breadcrumb span {
            color: #2C3E8F;
            font-weight: 600;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .topbar-right .date-time {
            font-size: 14px;
            color: #6c757d;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 62, 143, 0.3);
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
        
        /* ============================================
           MESSAGES
           ============================================ */
        
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
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
        
        .alert i {
            font-size: 20px;
        }
        
        /* ============================================
           STATS CARDS
           ============================================ */
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            border: 1px solid #eef2f7;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .stat-card .stat-header .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .stat-card .stat-header .stat-icon.blue { background: #e8edf9; color: #2C3E8F; }
        .stat-card .stat-header .stat-icon.green { background: #e6f7ed; color: #27ae60; }
        .stat-card .stat-header .stat-icon.orange { background: #fef3e7; color: #f39c12; }
        .stat-card .stat-header .stat-icon.purple { background: #f0ecf9; color: #8e44ad; }
        .stat-card .stat-header .stat-icon.red { background: #fde8e8; color: #e74c3c; }
        .stat-card .stat-header .stat-icon.teal { background: #e0f7fa; color: #00838f; }
        .stat-card .stat-header .stat-icon.pink { background: #fce4ec; color: #c62828; }
        
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a2e;
        }
        
        .stat-card .stat-label {
            font-size: 13px;
            color: #6c757d;
            margin-top: 2px;
        }
        
        /* ============================================
           DASHBOARD GRID
           ============================================ */
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }
        
        /* Recent Orders */
        .recent-orders {
            background: white;
            padding: 20px 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #eef2f7;
        }
        
        .recent-orders .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .recent-orders .card-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .recent-orders .card-header a {
            color: #2C3E8F;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        
        .recent-orders .card-header a:hover {
            text-decoration: underline;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item .order-info {
            display: flex;
            flex-direction: column;
        }
        
        .order-item .order-info .order-number {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a2e;
        }
        
        .order-item .order-info .order-customer {
            font-size: 13px;
            color: #6c757d;
        }
        
        .order-item .order-info .order-date {
            font-size: 12px;
            color: #a0aec0;
        }
        
        .order-item .order-amount {
            font-size: 16px;
            font-weight: 700;
            color: #2C3E8F;
        }
        
        .order-item .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .order-status.pending { background: #fef3e7; color: #f39c12; }
        .order-status.processing { background: #e8edf9; color: #2C3E8F; }
        .order-status.shipped { background: #e6f7ed; color: #27ae60; }
        .order-status.delivered { background: #e6f7ed; color: #27ae60; }
        .order-status.cancelled { background: #fde8e8; color: #e74c3c; }
        
        /* Quick Actions */
        .quick-actions {
            background: white;
            padding: 20px 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #eef2f7;
        }
        
        .quick-actions .card-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 16px;
        }
        
        .quick-action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: #f8fafc;
            border-radius: 8px;
            text-decoration: none;
            color: #1a1a2e;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .quick-action-btn:hover {
            background: #e8edf9;
            border-color: #2C3E8F;
            transform: translateY(-2px);
        }
        
        .quick-action-btn i {
            font-size: 18px;
            color: #2C3E8F;
            width: 24px;
        }
        
        .quick-action-btn span {
            font-size: 13px;
            font-weight: 500;
        }
        
        /* Pending Shops */
        .pending-shops {
            margin-top: 24px;
            background: white;
            padding: 20px 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #eef2f7;
        }
        
        .pending-shops .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .pending-shops .card-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .pending-shops .card-header a {
            color: #2C3E8F;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        
        .shop-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .shop-item:last-child {
            border-bottom: none;
        }
        
        .shop-item .shop-info {
            display: flex;
            flex-direction: column;
        }
        
        .shop-item .shop-info .shop-name {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a2e;
        }
        
        .shop-item .shop-info .shop-owner {
            font-size: 13px;
            color: #6c757d;
        }
        
        .shop-item .shop-info .shop-date {
            font-size: 12px;
            color: #a0aec0;
        }
        
        .shop-item .shop-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #fef3e7;
            color: #f39c12;
        }
        
        /* ============================================
           SIDEBAR OVERLAY
           ============================================ */
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .topbar-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .stat-card {
                padding: 16px;
            }
            
            .stat-card .stat-number {
                font-size: 22px;
            }
            
            .quick-action-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .stat-card .stat-number {
                font-size: 18px;
            }
            
            .quick-action-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- ============================================
         SIDEBAR
         ============================================ -->
    <aside class="sidebar" id="sidebar">
        
        <div class="sidebar-brand">
            <i class="fas fa-store"></i>
            <div>
                <h2>My Shop</h2>
                <small>Admin Panel</small>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">Main</div>
            <ul>
                <li>
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
            </ul>
            
            <div class="nav-section" style="margin-top: 16px;">Management</div>
            <ul>
                <li>
                    <a href="products.php">
                        <i class="fas fa-box"></i> Products
                        <?php if ($stats['low_stock'] > 0): ?>
                            <span class="badge"><?php echo $stats['low_stock']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="categories.php">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li>
                    <a href="orders.php">
                        <i class="fas fa-shopping-bag"></i> Orders
                        <?php if ($stats['pending_orders'] > 0): ?>
                            <span class="badge"><?php echo $stats['pending_orders']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            
            <!-- ============================================
                 VENDOR MANAGEMENT SECTION
                 ============================================ -->
            <div class="nav-section" style="margin-top: 16px;">Vendors</div>
            <ul>
                <li>
                    <a href="vendors.php">
                        <i class="fas fa-store"></i> All Vendors
                        <span class="badge"><?php echo $stats['total_vendors']; ?></span>
                    </a>
                </li>
                <li>
                    <a href="vendors.php?pending=1">
                        <i class="fas fa-clock"></i> Pending Shops
                        <?php if ($stats['pending_shops'] > 0): ?>
                            <span class="badge"><?php echo $stats['pending_shops']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="vendor-orders.php">
                        <i class="fas fa-truck"></i> Vendor Orders
                    </a>
                </li>
            </ul>
            
            <div class="nav-section" style="margin-top: 16px;">Users</div>
            <ul>
                <li>
                    <a href="users.php">
                        <i class="fas fa-users"></i> All Users
                        <span class="badge"><?php echo $stats['total_users']; ?></span>
                    </a>
                </li>
            </ul>
            
            <div class="nav-section" style="margin-top: 16px;">Settings</div>
            <ul>
                <li>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="avatar">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
                <div>
                    <div class="admin-name"><?php echo htmlspecialchars($userName); ?></div>
                    <div class="admin-email"><?php echo htmlspecialchars($userEmail); ?></div>
                </div>
            </div>
            <a href="<?php echo SITE_URL; ?>logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- ============================================
         MAIN CONTENT
         ============================================ -->
    <main class="main-content">
        
        <!-- Top Bar -->
        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1>Dashboard</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo SITE_URL; ?>index.php" style="color:#2C3E8F;text-decoration:none;">Home</a>
                        / <span>Dashboard</span>
                    </div>
                </div>
            </div>
            <div class="topbar-right">
                <span class="date-time">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('l, F j, Y'); ?>
                </span>
                <span class="date-time">
                    <i class="far fa-clock"></i>
                    <?php echo date('h:i A'); ?>
                </span>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($stats['total_products']); ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-icon green"><i class="fas fa-box"></i></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-icon orange"><i class="fas fa-shopping-bag"></i></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-icon purple"><i class="fas fa-dollar-sign"></i></div>
                </div>
            </div>
            
            <!-- ============================================
                 VENDOR STATS
                 ============================================ -->
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($stats['total_vendors']); ?></div>
                        <div class="stat-label">Total Vendors</div>
                    </div>
                    <div class="stat-icon teal"><i class="fas fa-store"></i></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($stats['total_shops']); ?></div>
                        <div class="stat-label">Total Shops</div>
                    </div>
                    <div class="stat-icon pink"><i class="fas fa-store-alt"></i></div>
                </div>
            </div>
            
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            
            <!-- Recent Orders -->
            <div class="recent-orders">
                <div class="card-header">
                    <h3><i class="fas fa-clock" style="color:#2C3E8F;margin-right:8px;"></i> Recent Orders</h3>
                    <a href="orders.php">View All →</a>
                </div>
                
                <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <span class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                                <span class="order-customer">
                                    <i class="fas fa-user"></i> 
                                    <?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?>
                                </span>
                                <span class="order-date">
                                    <i class="far fa-calendar-alt"></i> 
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                </span>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <span class="order-amount">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                <span class="order-status <?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#6c757d;text-align:center;padding:20px 0;">No orders yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="card-header">
                    <h3><i class="fas fa-bolt" style="color:#2C3E8F;margin-right:8px;"></i> Quick Actions</h3>
                </div>
                
                <div class="quick-action-grid">
                    <a href="product-add.php" class="quick-action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Product</span>
                    </a>
                    <a href="orders.php" class="quick-action-btn">
                        <i class="fas fa-shipping-fast"></i>
                        <span>Process Orders</span>
                    </a>
                    <a href="categories.php" class="quick-action-btn">
                        <i class="fas fa-tag"></i>
                        <span>Manage Categories</span>
                    </a>
                    <a href="users.php" class="quick-action-btn">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                    <!-- ============================================
                         VENDOR QUICK ACTIONS
                         ============================================ -->
                    <a href="vendors.php" class="quick-action-btn">
                        <i class="fas fa-store"></i>
                        <span>Manage Vendors</span>
                    </a>
                    <a href="vendors.php?pending=1" class="quick-action-btn">
                        <i class="fas fa-clock"></i>
                        <span>Approve Shops</span>
                        <?php if ($stats['pending_shops'] > 0): ?>
                            <span style="background:#e74c3c;color:white;padding:0 8px;border-radius:12px;font-size:10px;margin-left:auto;">
                                <?php echo $stats['pending_shops']; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="vendor-orders.php" class="quick-action-btn">
                        <i class="fas fa-truck"></i>
                        <span>Vendor Orders</span>
                    </a>
                    <a href="settings.php" class="quick-action-btn">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- ============================================
             PENDING SHOPS SECTION
             ============================================ -->
        <?php if (!empty($pending_shops)): ?>
            <div class="pending-shops">
                <div class="card-header">
                    <h3><i class="fas fa-clock" style="color:#f39c12;margin-right:8px;"></i> Pending Shop Approvals</h3>
                    <a href="vendors.php?pending=1">View All →</a>
                </div>
                
                <?php foreach ($pending_shops as $shop): ?>
                    <div class="shop-item">
                        <div class="shop-info">
                            <span class="shop-name"><?php echo htmlspecialchars($shop['shop_name']); ?></span>
                            <span class="shop-owner">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($shop['owner_name'] ?? 'Unknown'); ?>
                            </span>
                            <span class="shop-date">
                                <i class="far fa-calendar-alt"></i> 
                                <?php echo date('M d, Y', strtotime($shop['created_at'])); ?>
                            </span>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span class="shop-status">Pending</span>
                            <a href="vendors.php?action=approve&id=<?php echo $shop['id']; ?>" 
                               class="btn btn-success btn-sm" 
                               onclick="return confirm('Approve this shop?')">
                                <i class="fas fa-check"></i> Approve
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </main>
    
    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script>
        // Sidebar Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('active');
            });
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('active');
            });
        }
        
        // Close sidebar on window resize (desktop)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('active');
            }
        });
        
        console.log('✅ Admin dashboard loaded successfully!');
        console.log('📊 Total Users:', <?php echo $stats['total_users']; ?>);
        console.log('📦 Total Products:', <?php echo $stats['total_products']; ?>);
        console.log('🛒 Total Orders:', <?php echo $stats['total_orders']; ?>);
        console.log('💰 Total Revenue: $<?php echo number_format($stats['total_revenue'], 2); ?>');
        console.log('🏪 Total Vendors:', <?php echo $stats['total_vendors']; ?>);
        console.log('📋 Pending Shops:', <?php echo $stats['pending_shops']; ?>);
    </script>
    
</body>
</html>