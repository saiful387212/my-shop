<?php
// ============================================================
// FILE: app/views/frontend/layout/header.php
// PURPOSE: Site header with working search
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Calculate cart count
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += isset($item['quantity']) ? (int)$item['quantity'] : 0;
    }
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Check if user has a shop
$userShop = null;
if ($isLoggedIn) {
    try {
        require_once ABSPATH . 'app/models/Shop.php';
        $shopModel = new Shop();
        $userShop = $shopModel->getByUser($_SESSION['user_id']);
    } catch (Exception $e) {
        // Ignore
    }
}

// Get search query from URL (for displaying in search box)
$searchQuery = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - My Shop' : 'My Shop'; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/responsive.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/cart.css">
    
    <style>
        /* ============================================
           HEADER STYLES - COMPLETE
           ============================================ */
        
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
            max-width: 1200px;
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
        
        /* ============================================
           LOGO
           ============================================ */
        
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
        
        /* ============================================
           SEARCH BAR - FIXED
           ============================================ */
        
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
            box-shadow: 0 4px 15px rgba(44, 62, 143, 0.1);
        }
        
        .search-bar input {
            flex: 1;
            padding: 9px 18px;
            border: none;
            background: transparent;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            outline: none;
            color: #1a1a2e;
            min-width: 80px;
        }
        
        .search-bar input::placeholder {
            color: #a0aec0;
        }
        
        .search-bar button {
            padding: 9px 18px;
            background: transparent;
            border: none;
            color: #2C3E8F;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        
        .search-bar button:hover {
            color: #1a2a6c;
            transform: scale(1.05);
        }
        
        /* ============================================
           BUTTONS
           ============================================ */
        
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
        
        .btn-success {
            background: #27ae60;
            color: white;
            border-color: #27ae60;
        }
        
        .btn-success:hover {
            background: #219a52;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39,174,96,0.3);
        }
        
        .btn-small {
            padding: 6px 14px;
            font-size: 12px;
        }
        
        .btn-large {
            padding: 14px 32px;
            font-size: 16px;
        }
        
        /* ============================================
           HEADER ACTIONS
           ============================================ */
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        
        /* ============================================
           PROFILE BUTTON
           ============================================ */
        
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
            transition: all 0.3s ease;
        }
        
        .profile-btn:hover {
            background: #e8edf9;
            color: #2C3E8F;
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
        
        /* ============================================
           CART ICON
           ============================================ */
        
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
        
        /* ============================================
           MOBILE MENU
           ============================================ */
        
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            gap: 4px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
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
        
        /* ============================================
           NAVIGATION
           ============================================ */
        
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
        
        .nav-menu li a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        .nav-menu li a i {
            margin-right: 6px;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        
        @media (max-width: 768px) {
            .header-inner {
                flex-wrap: wrap;
                gap: 8px;
                padding: 10px 0;
            }
            
            .logo-text .tagline {
                display: none;
            }
            
            .logo-text .brand {
                font-size: 18px;
            }
            
            .search-bar {
                order: 3;
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .search-bar input {
                padding: 8px 14px;
                font-size: 13px;
            }
            
            .profile-btn .profile-text {
                display: none;
            }
            
            .profile-btn {
                padding: 6px;
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
                max-height: 80vh;
                overflow-y: auto;
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
            
            .nav-menu li a:hover,
            .nav-menu li a.active {
                background: #f1f4f9;
                color: #2C3E8F;
            }
            
            .nav-menu li a i {
                color: #2C3E8F;
            }
        }
        
        @media (max-width: 480px) {
            .logo .logo-icon {
                font-size: 22px;
            }
            
            .logo-text .brand {
                font-size: 16px;
            }
            
            .header-actions .btn {
                padding: 4px 10px;
                font-size: 11px;
            }
            
            .cart-icon {
                width: 34px;
                height: 34px;
                font-size: 14px;
            }
            
            .cart-count {
                width: 16px;
                height: 16px;
                font-size: 8px;
                top: -2px;
                right: -2px;
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
                
                <!-- ============================================
                     LOGO
                     ============================================ -->
                <div class="logo">
                    <a href="<?php echo SITE_URL; ?>index.php">
                        <i class="fas fa-store logo-icon"></i>
                        <div class="logo-text">
                            <span class="brand">My <span>Shop</span></span>
                            <span class="tagline">Multi-Vendor Marketplace</span>
                        </div>
                    </a>
                </div>
                
                <!-- ============================================
                     SEARCH BAR - FIXED WITH WORKING SEARCH
                     ============================================ -->
                <div class="search-bar">
                    <form action="<?php echo SITE_URL; ?>products.php" method="GET" role="search">
                        <input type="text" 
                               name="search" 
                               placeholder="Search products..." 
                               aria-label="Search products"
                               value="<?php echo $searchQuery; ?>"
                               id="searchInput">
                        <button type="submit" aria-label="Submit search">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- ============================================
                     HEADER ACTIONS
                     ============================================ -->
                <div class="header-actions">
                    
                    <!-- ============================================
                         OPEN SHOP / MY SHOP (Vendor only)
                         ============================================ -->
                    <?php if ($isLoggedIn && $userShop): ?>
                        <a href="<?php echo SITE_URL; ?>shop/manage.php" class="btn btn-success btn-small">
                            <i class="fas fa-store"></i> My Shop
                        </a>
                    <?php elseif ($isLoggedIn && !$userShop): ?>
                        <a href="<?php echo SITE_URL; ?>shop/create.php" class="btn btn-primary btn-small">
                            <i class="fas fa-plus-circle"></i> Open Shop
                        </a>
                    <?php endif; ?>
                    
                    <!-- ============================================
                         PROFILE / LOGIN
                         ============================================ -->
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo SITE_URL; ?>account.php" class="profile-btn">
                            <span class="avatar">
                                <?php echo strtoupper(substr($userName, 0, 1)); ?>
                            </span>
                            <span class="profile-text"><?php echo htmlspecialchars($userName); ?></span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>login.php" class="btn btn-outline btn-small">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="<?php echo SITE_URL; ?>register.php" class="btn btn-primary btn-small">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    <?php endif; ?>
                    
                    <!-- ============================================
                         CART ICON WITH COUNT
                         ============================================ -->
                    <a href="<?php echo SITE_URL; ?>cart.php" class="cart-icon" aria-label="View cart">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cartCount"><?php echo $cartCount; ?></span>
                    </a>
                    
                    <!-- ============================================
                         MOBILE MENU TOGGLE
                         ============================================ -->
                    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
                        <span class="bar"></span>
                        <span class="bar"></span>
                        <span class="bar"></span>
                    </button>
                </div>
                
            </div>
        </div>
        
        <!-- ============================================
             NAVIGATION
             ============================================ -->
        <nav class="navbar" id="mainNav" role="navigation" aria-label="Main navigation">
            <div class="container">
                <ul class="nav-menu">
                    <li>
                        <a href="<?php echo SITE_URL; ?>index.php" class="<?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>products.php" class="<?php echo $currentPage == 'products.php' ? 'active' : ''; ?>">
                            <i class="fas fa-box"></i> Products
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>shops.php">
                            <i class="fas fa-store"></i> Shops
                        </a>
                    </li>
                    
                    <?php if (isset($categories) && !empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="<?php echo SITE_URL; ?>products.php?category=<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <li>
                        <a href="<?php echo SITE_URL; ?>about.php">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>contact.php">
                            <i class="fas fa-envelope"></i> Contact
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    
    <!-- ============================================
         MAIN CONTENT STARTS
         ============================================ -->
    <main class="main-content" role="main">