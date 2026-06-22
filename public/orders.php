<?php
// ============================================================
// FILE: public/orders.php
// PURPOSE: Order history page for customers
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF USER IS LOGGED IN
// ============================================

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to view your orders.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// ============================================
// FETCH ORDERS FOR CURRENT USER
// ============================================

$orders = [];
$error = null;

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    $userId = (int)$_SESSION['user_id'];
    
    // Get orders for this user
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        WHERE o.user_id = :user_id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $orders = $stmt->fetchAll();
    
    // Get items for each order
    foreach ($orders as &$order) {
        $itemStmt = $pdo->prepare("
            SELECT 
                product_name,
                product_price,
                quantity,
                (product_price * quantity) as subtotal
            FROM order_items
            WHERE order_id = :order_id
        ");
        $itemStmt->execute([':order_id' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log('Orders error: ' . $e->getMessage());
    $error = 'Database error occurred.';
} catch (Exception $e) {
    error_log('Orders error: ' . $e->getMessage());
    $error = 'An error occurred.';
}

// Get categories for navigation
$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Orders categories error: ' . $e->getMessage());
}

$pageTitle = 'My Orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - My Shop</title>
    
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
            background: #f8fafc;
            color: #1a1a2e;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* ============================================
           HEADER
           ============================================ */
        
        .header {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .logo a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .logo .logo-icon {
            font-size: 28px;
            color: #2C3E8F;
        }
        
        .logo-text .brand {
            font-size: 22px;
            font-weight: 800;
            color: #1a1a2e;
        }
        
        .logo-text .brand span {
            color: #2C3E8F;
        }
        
        .search-bar {
            flex: 1;
            max-width: 450px;
            min-width: 180px;
        }
        
        .search-bar form {
            display: flex;
            align-items: center;
            background: #f1f4f9;
            border-radius: 50px;
            overflow: hidden;
            border: 2px solid transparent;
        }
        
        .search-bar form:focus-within {
            border-color: #2C3E8F;
            background: white;
        }
        
        .search-bar input {
            flex: 1;
            padding: 9px 18px;
            border: none;
            background: transparent;
            font-size: 14px;
            outline: none;
        }
        
        .search-bar button {
            padding: 9px 18px;
            background: transparent;
            border: none;
            color: #2C3E8F;
            cursor: pointer;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: 2px solid transparent;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-primary {
            background: #2C3E8F;
            color: white;
            border-color: #2C3E8F;
        }
        
        .btn-primary:hover {
            background: #1a2a6c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 62, 143, 0.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #1a1a2e;
            border-color: #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .btn-outline {
            background: transparent;
            color: #2C3E8F;
            border-color: #2C3E8F;
        }
        
        .btn-outline:hover {
            background: #2C3E8F;
            color: white;
        }
        
        .btn-small {
            padding: 6px 14px;
            font-size: 12px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px 6px 6px;
            border-radius: 50px;
            background: #f1f4f9;
            color: #1a1a2e;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }
        
        .profile-btn .avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #2C3E8F;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
        }
        
        .cart-icon {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #f1f4f9;
            border-radius: 50%;
            color: #1a1a2e;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .cart-icon:hover {
            background: #2C3E8F;
            color: white;
            transform: scale(1.05);
        }
        
        .cart-count {
            position: absolute;
            top: -3px;
            right: -3px;
            background: #e74c3c;
            color: white;
            font-size: 9px;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            gap: 4px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 6px;
        }
        
        .mobile-menu-toggle .bar {
            width: 24px;
            height: 3px;
            background: #1a1a2e;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-toggle.active .bar:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        .mobile-menu-toggle.active .bar:nth-child(2) {
            opacity: 0;
        }
        .mobile-menu-toggle.active .bar:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }
        
        .navbar {
            background: #2C3E8F;
            padding: 0;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
        }
        
        .nav-menu li a {
            display: block;
            padding: 11px 18px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-menu li a:hover {
            background: rgba(255, 255, 255, 0.12);
            color: white;
        }
        
        .nav-menu li a i {
            margin-right: 6px;
        }
        
        /* ============================================
           ORDERS PAGE
           ============================================ */
        
        .orders-page {
            padding: 40px 0 60px;
            min-height: calc(100vh - 200px);
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
            margin-bottom: 24px;
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
        
        /* Order Cards */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .order-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        /* Order Header */
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .order-info {
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
        }
        
        .order-info .label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        
        .order-info .value {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a2e;
        }
        
        .order-number .value {
            color: #2C3E8F;
        }
        
        .order-status-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .status-badge {
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-badge.pending {
            background: #fef3e7;
            color: #f39c12;
        }
        
        .status-badge.processing {
            background: #e8edf9;
            color: #2C3E8F;
        }
        
        .status-badge.shipped {
            background: #e6f7ed;
            color: #27ae60;
        }
        
        .status-badge.delivered {
            background: #e6f7ed;
            color: #27ae60;
        }
        
        .status-badge.cancelled {
            background: #fde8e8;
            color: #e74c3c;
        }
        
        .order-total {
            font-size: 20px;
            font-weight: 800;
            color: #2C3E8F;
        }
        
        /* Order Items */
        .order-items {
            padding: 16px 0;
        }
        
        .items-header {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr;
            gap: 12px;
            padding: 8px 0;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-item {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f8fafc;
            font-size: 14px;
            align-items: center;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item .item-name {
            font-weight: 500;
            color: #1a1a2e;
        }
        
        .order-item .item-price {
            color: #4a5568;
        }
        
        .order-item .item-quantity {
            color: #6c757d;
        }
        
        .order-item .item-subtotal {
            font-weight: 700;
            color: #2C3E8F;
        }
        
        /* Order Footer */
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .item-count {
            font-size: 14px;
            color: #6c757d;
        }
        
        .item-count i {
            color: #2C3E8F;
            margin-right: 6px;
        }
        
        .order-footer .btn {
            padding: 6px 16px;
            font-size: 13px;
        }
        
        /* Empty Orders */
        .empty-orders {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
        }
        
        .empty-icon {
            font-size: 80px;
            color: #cbd5e0;
            margin-bottom: 20px;
        }
        
        .empty-orders h2 {
            font-size: 24px;
            color: #1a1a2e;
            margin-bottom: 12px;
        }
        
        .empty-orders p {
            color: #6c757d;
            margin-bottom: 24px;
        }
        
        /* ============================================
           FOOTER
           ============================================ */
        
        .footer {
            background: #1a1a2e;
            color: rgba(255, 255, 255, 0.8);
            padding: 40px 0 20px;
            margin-top: 40px;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 12px;
        }
        
        .footer-description {
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.8;
            margin-bottom: 16px;
            max-width: 300px;
        }
        
        .social-links {
            display: flex;
            gap: 12px;
        }
        
        .social-links a {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .social-links a:hover {
            background: #2C3E8F;
            transform: translateY(-3px);
        }
        
        .footer-col h4 {
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 8px;
        }
        
        .footer-col ul li a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .footer-col ul li a:hover {
            color: white;
            padding-left: 4px;
        }
        
        .contact-info li {
            display: flex;
            gap: 12px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .contact-info li i {
            color: #2C3E8F;
            margin-top: 4px;
            width: 18px;
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.4);
        }
        
        .payment-methods {
            display: flex;
            gap: 16px;
            font-size: 24px;
        }
        
        .payment-methods i {
            color: rgba(255, 255, 255, 0.4);
            transition: color 0.3s ease;
        }
        
        .payment-methods i:hover {
            color: white;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        
        @media (max-width: 768px) {
            .header-inner {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .search-bar {
                order: 3;
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .mobile-menu-toggle {
                display: flex;
            }
            
            .navbar {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 8px 30px rgba(0,0,0,0.15);
                z-index: 999;
            }
            
            .navbar.active {
                display: block;
            }
            
            .nav-menu {
                flex-direction: column;
                padding: 8px 0;
            }
            
            .nav-menu li a {
                color: #1a1a2e;
                padding: 10px 20px;
                border-bottom: 1px solid #f1f4f9;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-info {
                flex-direction: column;
                gap: 8px;
            }
            
            .order-status-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .items-header {
                display: none;
            }
            
            .order-item {
                grid-template-columns: 1fr;
                gap: 4px;
                padding: 12px 0;
            }
            
            .order-item .item-name {
                font-weight: 600;
                font-size: 15px;
            }
            
            .order-item .item-price::before {
                content: 'Price: ';
                font-weight: 600;
                color: #6c757d;
            }
            
            .order-item .item-quantity::before {
                content: 'Qty: ';
                font-weight: 600;
                color: #6c757d;
            }
            
            .order-item .item-subtotal::before {
                content: 'Subtotal: ';
                font-weight: 600;
                color: #6c757d;
            }
            
            .order-footer {
                flex-direction: column;
                align-items: stretch;
            }
            
            .order-footer .btn {
                justify-content: center;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .page-header h1 {
                font-size: 24px;
            }
            
            .order-card {
                padding: 16px;
            }
            
            .order-total {
                font-size: 17px;
            }
        }
    </style>
</head>
<body>
    
    <!-- ============================================
         HEADER
         ============================================ -->
    <header class="header">
        <div class="container">
            <div class="header-inner">
                
                <div class="logo">
                    <a href="index.php">
                        <i class="fas fa-store logo-icon"></i>
                        <div class="logo-text">
                            <span class="brand">My <span>Shop</span></span>
                            <span class="tagline">Your Online Store</span>
                        </div>
                    </a>
                </div>
                
                <div class="search-bar">
                    <form action="products.php" method="GET">
                        <input type="text" name="search" placeholder="Search products...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="header-actions">
                    <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                        <a href="account.php" class="profile-btn">
                            <span class="avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></span>
                            <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline btn-small"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <a href="register.php" class="btn btn-primary btn-small"><i class="fas fa-user-plus"></i> Register</a>
                    <?php endif; ?>
                    
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo getCartTotalItems(); ?></span>
                    </a>
                    
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <span class="bar"></span>
                        <span class="bar"></span>
                        <span class="bar"></span>
                    </button>
                </div>
                
            </div>
        </div>
        
        <nav class="navbar" id="mainNav">
            <div class="container">
                <ul class="nav-menu">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <li><a href="products.php?category=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                    <?php endif; ?>
                    <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
        </nav>
    </header>
    
    <!-- ============================================
         ORDERS CONTENT
         ============================================ -->
    <section class="orders-page">
        <div class="container">
            
            <div class="page-header">
                <h1>My Orders</h1>
                <p>View all your past orders</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
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
                                        <span class="value"><?php echo date('M d, Y \a\t h:i A', strtotime($order['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="order-status-right">
                                    <span class="status-badge <?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                    <span class="order-total">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                            
                            <!-- Order Items -->
                            <div class="order-items">
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
                            </div>
                            
                            <!-- Order Footer -->
                            <div class="order-footer">
                                <span class="item-count">
                                    <i class="fas fa-box"></i>
                                    <?php echo $order['item_count']; ?> item(s)
                                </span>
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">
                                    View Details <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                
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
    
    <!-- ============================================
         FOOTER
         ============================================ -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <i class="fas fa-store"></i>
                        <span>My Shop</span>
                    </div>
                    <p class="footer-description">Your one-stop destination for quality products.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Customer Service</h4>
                    <ul>
                        <li><a href="returns.php">Returns</a></li>
                        <li><a href="shipping.php">Shipping</a></li>
                        <li><a href="privacy.php">Privacy</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Main St</li>
                        <li><i class="fas fa-envelope"></i> info@myshop.com</li>
                        <li><i class="fas fa-phone-alt"></i> +1 (555) 123-4567</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> My Shop. All rights reserved.</p>
                <div class="payment-methods">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-amex"></i>
                    <i class="fab fa-cc-paypal"></i>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const mainNav = document.getElementById('mainNav');
            
            if (menuToggle && mainNav) {
                menuToggle.addEventListener('click', function() {
                    this.classList.toggle('active');
                    mainNav.classList.toggle('active');
                });
            }
            
            console.log('✅ Orders page loaded successfully!');
        });
    </script>
    
</body>
</html>