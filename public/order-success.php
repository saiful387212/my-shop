<?php
// ============================================================
// FILE: public/order-success.php
// PURPOSE: Order confirmation page
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

$orderId = isset($_SESSION['last_order_id']) ? (int)$_SESSION['last_order_id'] : 0;
$orderNumber = isset($_SESSION['last_order_number']) ? $_SESSION['last_order_number'] : '';

if ($orderId <= 0 || empty($orderNumber)) {
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

try {
    $orderModel = new Order();
    $order = $orderModel->find($orderId);
    
    if (!$order) {
        header('Location: ' . SITE_URL . 'index.php');
        exit;
    }
    
    $orderItems = $orderModel->getItems($orderId);
    
} catch (Exception $e) {
    error_log('Order success error: ' . $e->getMessage());
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

unset($_SESSION['last_order_id']);
unset($_SESSION['last_order_number']);

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Order success categories error: ' . $e->getMessage());
}

$pageTitle = 'Order Success';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - My Shop</title>
    
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
            max-width: 900px;
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
        
        .logo-text .tagline {
            font-size: 9px;
            color: #6c757d;
            letter-spacing: 2px;
            text-transform: uppercase;
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
            transition: all 0.3s ease;
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
           ORDER SUCCESS PAGE
           ============================================ */
        
        .order-success-page {
            padding: 40px 0 60px;
            min-height: calc(100vh - 200px);
        }
        
        /* Success Header */
        .success-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .success-icon {
            font-size: 80px;
            color: #27ae60;
            margin-bottom: 16px;
            animation: scaleIn 0.6s ease;
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .success-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a2e;
        }
        
        .success-header p {
            color: #6c757d;
            font-size: 16px;
        }
        
        /* Order Details */
        .order-details {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
            margin-bottom: 30px;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 20px;
        }
        
        .order-info-item {
            display: flex;
            flex-direction: column;
        }
        
        .order-info-item .label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        
        .order-info-item .value {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
            margin-top: 2px;
        }
        
        .order-info-item .value.status-pending {
            color: #f39c12;
        }
        
        /* Shipping Address */
        .shipping-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .shipping-section h3 {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .shipping-section p {
            font-size: 14px;
            color: #4a5568;
            line-height: 1.6;
        }
        
        /* Order Items */
        .order-items-section h3 {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 12px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item .item-name {
            font-weight: 500;
            color: #1a1a2e;
        }
        
        .order-item .item-price {
            font-weight: 600;
            color: #2C3E8F;
        }
        
        .order-totals {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px solid #f0f0f0;
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
        
        /* Actions */
        .order-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .order-actions .btn {
            padding: 12px 28px;
            font-size: 15px;
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
            
            .order-info-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            
            .order-details {
                padding: 24px;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .success-icon {
                font-size: 60px;
            }
            
            .success-header h1 {
                font-size: 24px;
            }
            
            .order-info-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            
            .order-details {
                padding: 16px;
            }
            
            .order-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .order-actions .btn {
                justify-content: center;
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
                        <span class="cart-count">0</span>
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
         ORDER SUCCESS CONTENT
         ============================================ -->
    <section class="order-success-page">
        <div class="container">
            
            <!-- Success Header -->
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Thank You for Your Order!</h1>
                <p>Your order has been placed successfully.</p>
            </div>
            
            <!-- Order Details -->
            <div class="order-details">
                
                <!-- Order Info -->
                <div class="order-info-grid">
                    <div class="order-info-item">
                        <span class="label">Order Number</span>
                        <span class="value"><?php echo htmlspecialchars($order['order_number']); ?></span>
                    </div>
                    <div class="order-info-item">
                        <span class="label">Order Date</span>
                        <span class="value"><?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="order-info-item">
                        <span class="label">Order Status</span>
                        <span class="value status-pending"><?php echo ucfirst($order['status']); ?></span>
                    </div>
                    <div class="order-info-item">
                        <span class="label">Total Amount</span>
                        <span class="value">$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
                
                <!-- Shipping Address -->
                <div class="shipping-section">
                    <h3>Shipping Address</h3>
                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                </div>
                
                <!-- Order Items -->
                <div class="order-items-section">
                    <h3>Order Items</h3>
                    
                    <?php foreach ($orderItems as $item): ?>
                        <div class="order-item">
                            <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?> × <?php echo $item['quantity']; ?></span>
                            <span class="item-price">$<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></span>
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
            
            console.log('✅ Order success page loaded successfully!');
        });
    </script>
    
</body>
</html>