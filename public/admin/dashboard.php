<?php
// ============================================================
// FILE: public/admin/dashboard.php
// PURPOSE: Admin dashboard with statistics and overview
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

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// ADMIN ACCESS CONTROL
// ============================================

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error_message'] = 'Please login to access admin panel.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// Check if user is admin
if (!isAdmin()) {
    $_SESSION['error_message'] = 'You do not have permission to access admin panel.';
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

// ============================================
// FETCH STATISTICS
// ============================================

try {
    // Get database connection
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // ============================================
    // TOTAL USERS
    // ============================================
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM users WHERE is_admin = 0');
    $totalUsers = $stmt->fetch()['total'] ?? 0;
    
    // ============================================
    // TOTAL PRODUCTS
    // ============================================
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM products');
    $totalProducts = $stmt->fetch()['total'] ?? 0;
    
    // ============================================
    // TOTAL ORDERS
    // ============================================
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM orders');
    $totalOrders = $stmt->fetch()['total'] ?? 0;
    
    // ============================================
    // TOTAL REVENUE
    // ============================================
    $stmt = $pdo->query('SELECT SUM(total_amount) as total FROM orders WHERE status != "cancelled"');
    $totalRevenue = $stmt->fetch()['total'] ?? 0;
    
    // ============================================
    // LOW STOCK PRODUCTS
    // ============================================
    $stmt = $pdo->query('
        SELECT COUNT(*) as total 
        FROM products 
        WHERE stock_quantity > 0 AND stock_quantity < 10
    ');
    $lowStockCount = $stmt->fetch()['total'] ?? 0;
    
    // ============================================
    // RECENT ORDERS
    // ============================================
    $stmt = $pdo->query('
        SELECT 
            o.*,
            u.name as customer_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ');
    $recentOrders = $stmt->fetchAll();
    
    // ============================================
    // ORDER STATUS COUNTS
    // ============================================
    $stmt = $pdo->query('
        SELECT 
            status,
            COUNT(*) as count
        FROM orders
        GROUP BY status
    ');
    $orderStatuses = [];
    while ($row = $stmt->fetch()) {
        $orderStatuses[$row['status']] = $row['count'];
    }
    
} catch (PDOException $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());
    $error = 'Database error occurred.';
} catch (Exception $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());
    $error = 'An error occurred.';
}

// ============================================
// GET CURRENT PAGE FOR SIDEBAR
// ============================================
$currentPage = basename($_SERVER['PHP_SELF']);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* ============================================================
           ADMIN DASHBOARD STYLES - ALL EMBEDDED
           ============================================================ */
        
        /* Reset & Base */
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
        
        /* Sidebar Navigation */
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
        
        /* Sidebar Footer */
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
        
        /* Top Bar */
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
        
        /* ============================================
           STATS CARDS
           ============================================ */
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
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
            margin-bottom: 12px;
        }
        
        .stat-card .stat-header .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stat-card .stat-header .stat-icon.blue {
            background: #e8edf9;
            color: #2C3E8F;
        }
        
        .stat-card .stat-header .stat-icon.green {
            background: #e6f7ed;
            color: #27ae60;
        }
        
        .stat-card .stat-header .stat-icon.orange {
            background: #fef3e7;
            color: #f39c12;
        }
        
        .stat-card .stat-header .stat-icon.purple {
            background: #f0ecf9;
            color: #8e44ad;
        }
        
        .stat-card .stat-header .stat-icon.red {
            background: #fde8e8;
            color: #e74c3c;
        }
        
        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a2e;
        }
        
        .stat-card .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-top: 4px;
        }
        
        .stat-card .stat-change {
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .stat-card .stat-change.positive {
            color: #27ae60;
        }
        
        .stat-card .stat-change.negative {
            color: #e74c3c;
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
        
        /* Chart Card */
        .chart-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #eef2f7;
        }
        
        .chart-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-card .card-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .chart-card .card-header select {
            padding: 6px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            background: white;
        }
        
        .chart-wrapper {
            height: 250px;
            position: relative;
        }
        
        /* ============================================
           RECENT ORDERS
           ============================================ */
        
        .recent-orders {
            background: white;
            padding: 24px;
            border-radius: 16px;
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
            padding: 12px 0;
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
        
        .order-status.pending {
            background: #fef3e7;
            color: #f39c12;
        }
        
        .order-status.processing {
            background: #e8edf9;
            color: #2C3E8F;
        }
        
        .order-status.shipped {
            background: #e6f7ed;
            color: #27ae60;
        }
        
        .order-status.delivered {
            background: #e6f7ed;
            color: #27ae60;
        }
        
        .order-status.cancelled {
            background: #fde8e8;
            color: #e74c3c;
        }
        
        /* ============================================
           QUICK ACTIONS
           ============================================ */
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .quick-action {
            background: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #eef2f7;
            text-decoration: none;
            color: #1a1a2e;
        }
        
        .quick-action:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            border-color: #2C3E8F;
        }
        
        .quick-action i {
            font-size: 32px;
            color: #2C3E8F;
            margin-bottom: 8px;
        }
        
        .quick-action h4 {
            font-size: 14px;
            font-weight: 600;
        }
        
        .quick-action p {
            font-size: 12px;
            color: #6c757d;
        }
        
        /* ============================================
           RESPONSIVE DESIGN
           ============================================ */
        
        /* Tablet */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Mobile */
        @media (max-width: 768px) {
            /* Sidebar */
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            /* Overlay */
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
            
            /* Stats */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .stat-card {
                padding: 16px;
            }
            
            .stat-card .stat-number {
                font-size: 24px;
            }
            
            /* Topbar */
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .topbar-right {
                width: 100%;
                justify-content: space-between;
            }
            
            /* Orders */
            .order-item {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .order-item .order-amount {
                margin-left: auto;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Small Mobile */
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .stat-card .stat-number {
                font-size: 20px;
            }
            
            .stat-card .stat-label {
                font-size: 12px;
            }
            
            .topbar-left h1 {
                font-size: 18px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    
    <!-- ============================================
         SIDEBAR OVERLAY (Mobile)
         ============================================ -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- ============================================
         SIDEBAR
         ============================================ -->
    <aside class="sidebar" id="sidebar">
        
        <!-- Brand -->
        <div class="sidebar-brand">
            <i class="fas fa-store"></i>
            <div>
                <h2>My Shop</h2>
                <small>Admin Panel</small>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section">Main</div>
            <ul>
                <li>
                    <a href="dashboard.php" class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
            </ul>
            
            <div class="nav-section" style="margin-top: 16px;">Management</div>
            <ul>
                <li>
                    <a href="products.php" class="<?php echo $currentPage == 'products.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i> Products
                        <?php if ($lowStockCount > 0): ?>
                            <span class="badge"><?php echo $lowStockCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="categories.php" class="<?php echo $currentPage == 'categories.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li>
                    <a href="orders.php" class="<?php echo $currentPage == 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-bag"></i> Orders
                        <?php if (isset($orderStatuses['pending']) && $orderStatuses['pending'] > 0): ?>
                            <span class="badge"><?php echo $orderStatuses['pending']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            
            <div class="nav-section" style="margin-top: 16px;">Customers</div>
            <ul>
                <li>
                    <a href="users.php" class="<?php echo $currentPage == 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
            </ul>
            
            <div class="nav-section" style="margin-top: 16px;">Settings</div>
            <ul>
                <li>
                    <a href="settings.php" class="<?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Footer -->
        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <div class="admin-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
                    <div class="admin-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@myshop.com'); ?></div>
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
        
        <!-- ============================================
             STATS CARDS
             ============================================ -->
        <div class="stats-grid">
            
            <!-- Total Users -->
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 12% this month
                </div>
            </div>
            
            <!-- Total Products -->
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($totalProducts); ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                <div class="stat-change <?php echo $lowStockCount > 0 ? 'negative' : 'positive'; ?>">
                    <?php if ($lowStockCount > 0): ?>
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $lowStockCount; ?> low stock
                    <?php else: ?>
                        <i class="fas fa-check-circle"></i> All stocked
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Total Orders -->
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($totalOrders); ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-icon orange">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 8% this month
                </div>
            </div>
            
            <!-- Total Revenue -->
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-number">$<?php echo number_format($totalRevenue, 0); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-icon purple">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 15% this month
                </div>
            </div>
        </div>
        
        <!-- ============================================
             DASHBOARD GRID
             ============================================ -->
        <div class="dashboard-grid">
            
            <!-- Chart -->
            <div class="chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar" style="color:#2C3E8F;margin-right:8px;"></i> Revenue Overview</h3>
                    <select id="chartPeriod">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                    </select>
                </div>
                <div class="chart-wrapper">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="recent-orders">
                <div class="card-header">
                    <h3><i class="fas fa-clock" style="color:#2C3E8F;margin-right:8px;"></i> Recent Orders</h3>
                    <a href="orders.php">View All →</a>
                </div>
                
                <?php if (!empty($recentOrders)): ?>
                    <?php foreach ($recentOrders as $order): ?>
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
        </div>
        
        <!-- ============================================
             QUICK ACTIONS
             ============================================ -->
        <h3 style="margin-bottom:16px;font-size:16px;font-weight:700;color:#1a1a2e;">
            <i class="fas fa-bolt" style="color:#2C3E8F;margin-right:8px;"></i> Quick Actions
        </h3>
        <div class="quick-actions">
            <a href="products.php?action=add" class="quick-action">
                <i class="fas fa-plus-circle"></i>
                <h4>Add Product</h4>
                <p>Add a new product to your store</p>
            </a>
            <a href="orders.php" class="quick-action">
                <i class="fas fa-shipping-fast"></i>
                <h4>Process Orders</h4>
                <p>View and manage pending orders</p>
            </a>
            <a href="categories.php" class="quick-action">
                <i class="fas fa-tag"></i>
                <h4>Manage Categories</h4>
                <p>Add or edit product categories</p>
            </a>
            <a href="users.php" class="quick-action">
                <i class="fas fa-user-plus"></i>
                <h4>Manage Users</h4>
                <p>View and manage customers</p>
            </a>
        </div>
        
    </main>
    
    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // ============================================
            // SIDEBAR TOGGLE (Mobile)
            // ============================================
            
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
            
            // ============================================
            // REVENUE CHART
            // ============================================
            
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            // Sample data - in production, fetch from database
            const chartData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                values: [1250, 1800, 1450, 2200, 1900, 2600, 3100, 2800, 3500, 4200, 3800, 4500]
            };
            
            const revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: chartData.values,
                        borderColor: '#2C3E8F',
                        backgroundColor: 'rgba(44, 62, 143, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#2C3E8F',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
            
            // ============================================
            // CHART PERIOD CHANGE (Demo)
            // ============================================
            
            document.getElementById('chartPeriod').addEventListener('change', function() {
                // In production, fetch new data based on period
                console.log('Chart period changed to:', this.value);
            });
            
            // ============================================
            // CONSOLE LOG
            // ============================================
            
            console.log('✅ Admin dashboard loaded successfully!');
            console.log('📊 Total Users:', <?php echo $totalUsers; ?>);
            console.log('📦 Total Products:', <?php echo $totalProducts; ?>);
            console.log('🛒 Total Orders:', <?php echo $totalOrders; ?>);
            console.log('💰 Total Revenue: $<?php echo number_format($totalRevenue, 2); ?>');
        });
    </script>
    
</body>
</html>