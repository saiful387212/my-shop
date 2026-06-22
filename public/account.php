<?php
// ============================================================
// FILE: public/account.php
// PURPOSE: User account management page
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

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
        SELECT id, name, email, is_admin, created_at, last_login
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

$pageTitle = 'My Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - My Shop</title>
    
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
            box-shadow: 0 4px 12px rgba(44,62,143,0.3);
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
        
        .btn-secondary {
            background: #e2e8f0;
            color: #1a1a2e;
            border-color: #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
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
           ACCOUNT PAGE
           ============================================ */
        
        .account-page {
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
        
        /* ===== ACCOUNT GRID ===== */
        .account-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        /* ===== PROFILE CARD ===== */
        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
            text-align: center;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #2C3E8F;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 700;
            margin: 0 auto 16px;
        }
        
        .profile-card .name {
            font-size: 22px;
            font-weight: 700;
        }
        
        .profile-card .email {
            color: #6c757d;
            font-size: 14px;
        }
        
        .profile-card .role {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .profile-card .role.admin {
            background: #cce5ff;
            color: #004085;
        }
        
        .profile-card .role.user {
            background: #d4edda;
            color: #155724;
        }
        
        .profile-card .member-since {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
            color: #6c757d;
        }
        
        .profile-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        
        /* ===== STATS CARD ===== */
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
        }
        
        .stats-card h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .stat-item {
            text-align: center;
            padding: 16px;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        .stat-item .number {
            font-size: 28px;
            font-weight: 800;
            color: #2C3E8F;
        }
        
        .stat-item .label {
            font-size: 13px;
            color: #6c757d;
            display: block;
        }
        
        /* ===== RECENT ORDERS ===== */
        .recent-orders {
            grid-column: 1 / -1;
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
        }
        
        .recent-orders h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .recent-orders .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .recent-orders .order-item:last-child {
            border-bottom: none;
        }
        
        .recent-orders .order-number {
            font-weight: 600;
            color: #2C3E8F;
        }
        
        .recent-orders .order-date {
            font-size: 13px;
            color: #6c757d;
        }
        
        .recent-orders .order-total {
            font-weight: 700;
        }
        
        .recent-orders .order-status {
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
            padding: 30px 20px;
            color: #6c757d;
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
            
            .account-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .recent-orders {
                grid-column: 1;
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
            
            .profile-avatar {
                width: 70px;
                height: 70px;
                font-size: 28px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .stat-item .number {
                font-size: 22px;
            }
            
            .profile-actions .btn {
                width: 100%;
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
                    <a href="account.php" class="profile-btn">
                        <span class="avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></span>
                        <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                    </a>
                    
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo $cartCount; ?></span>
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
         ACCOUNT PAGE CONTENT
         ============================================ -->
    <section class="account-page">
        <div class="container">
            
            <div class="page-header">
                <h1>My Account</h1>
                <p>Manage your profile and orders</p>
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
                    
                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <div class="name"><?php echo htmlspecialchars($user['name']); ?></div>
                        <div class="email"><?php echo htmlspecialchars($user['email']); ?></div>
                        <div class="role <?php echo $user['is_admin'] == 1 ? 'admin' : 'user'; ?>">
                            <?php echo $user['is_admin'] == 1 ? '👑 Admin' : '👤 Customer'; ?>
                        </div>
                        <div class="member-since">
                            Member since <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </div>
                        <div class="profile-actions">
                            <a href="logout.php" class="btn btn-danger btn-small">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                    
                    <!-- Stats Card -->
                    <div class="stats-card">
                        <h3><i class="fas fa-chart-simple" style="color:#2C3E8F;"></i> Your Stats</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="number"><?php echo $orderCount; ?></span>
                                <span class="label">Total Orders</span>
                            </div>
                            <div class="stat-item">
                                <span class="number"><?php 
                                    try {
                                        $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE user_id = :user_id AND status != 'cancelled'");
                                        $stmt->execute([':user_id' => $userId]);
                                        $total = $stmt->fetch()['total'] ?? 0;
                                        echo '$' . number_format($total, 0);
                                    } catch (Exception $e) {
                                        echo '$0';
                                    }
                                ?></span>
                                <span class="label">Total Spent</span>
                            </div>
                            <div class="stat-item">
                                <span class="number"><?php 
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = :user_id");
                                        $stmt->execute([':user_id' => $userId]);
                                        echo $stmt->fetch()['count'] ?? 0;
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                ?></span>
                                <span class="label">Cart Items</span>
                            </div>
                            <div class="stat-item">
                                <span class="number"><?php 
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id AND status = 'pending'");
                                        $stmt->execute([':user_id' => $userId]);
                                        echo $stmt->fetch()['count'] ?? 0;
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                ?></span>
                                <span class="label">Pending Orders</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
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
        
            console.log('✅ Account page loaded successfully!');
        });
    </script>
</body>
</html>