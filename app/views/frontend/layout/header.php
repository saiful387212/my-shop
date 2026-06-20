<?php
// ============================================================
// FILE: app/views/frontend/layout/header.php
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
    <!-- Page Specific CSS -->
<?php if (isset($pageTitle) && $pageTitle == 'Checkout'): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/checkout.css">
<?php endif; ?>

<?php if (isset($pageTitle) && $pageTitle == 'Order Success'): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/order-success.css">
<?php endif; ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - My Shop' : 'My Shop'; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/responsive.css">
    
    <!-- ============================================
         CART STYLES AND SCRIPTS
         ============================================ -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/cart.css">
    
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>assets/images/favicon.ico">
</head>
<!-- ============================================
     JAVASCRIPT
     ============================================ -->
<script src="<?php echo SITE_URL; ?>assets/js/script.js"></script>
<script src="<?php echo SITE_URL; ?>assets/js/cart.js"></script>
<body>
    <header class="header" role="banner">
        <div class="container">
            <div class="header-inner">
                
                <!-- Logo -->
                <div class="logo">
                    <a href="<?php echo SITE_URL; ?>index.php" aria-label="My Shop Home">
                        <img src="<?php echo SITE_URL; ?>assets/images/logo.png" 
                             alt="My Shop Logo" 
                             width="40" 
                             height="40">
                        <span class="logo-text">My Shop</span>
                    </a>
                </div>
                
                <!-- Search Bar -->
                <div class="search-bar">
                    <form action="<?php echo SITE_URL; ?>products.php" method="GET" role="search">
                        <input type="text" 
                               name="search" 
                               placeholder="Search products..." 
                               aria-label="Search products"
                               id="searchInput">
                        <button type="submit" aria-label="Submit search">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- Header Actions -->
                <div class="header-actions">
                    
                    <!-- User Menu -->
                    <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                        <div class="user-menu">
                            <span class="user-name">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                            </span>
                            <div class="user-dropdown">
                                <a href="<?php echo SITE_URL; ?>account.php">
                                    <i class="fas fa-user"></i> My Account
                                </a>
                                <a href="<?php echo SITE_URL; ?>orders.php">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                                    <a href="<?php echo SITE_URL; ?>admin/dashboard.php">
                                        <i class="fas fa-cog"></i> Admin Panel
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>logout.php" class="logout-link">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>login.php" class="btn btn-outline btn-small">
                            <i class="fas fa-sign-in-alt"></i> 
                            <span class="btn-text">Login</span>
                        </a>
                        <a href="<?php echo SITE_URL; ?>register.php" class="btn btn-primary btn-small">
                            <i class="fas fa-user-plus"></i> 
                            <span class="btn-text">Register</span>
                        </a>
                    <?php endif; ?>
                    
                    <!-- ============================================
                         SHOPPING CART ICON WITH COUNT
                         ============================================ -->
                    <a href="<?php echo SITE_URL; ?>cart.php" class="cart-icon" aria-label="View cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php 
                        $cartCount = 0;
                        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                            foreach ($_SESSION['cart'] as $item) {
                                $cartCount += isset($item['quantity']) ? (int)$item['quantity'] : 0;
                            }
                        }
                        ?>
                        <span class="cart-count" id="cartCount">
                            <?php echo $cartCount; ?>
                        </span>
                    </a>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" 
                            aria-label="Toggle navigation menu" 
                            aria-expanded="false"
                            id="mobileMenuToggle">
                        <span class="bar"></span>
                        <span class="bar"></span>
                        <span class="bar"></span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="navbar" role="navigation" aria-label="Main navigation" id="mainNav">
            <div class="container">
                <ul class="nav-menu">
                    <li>
                        <a href="<?php echo SITE_URL; ?>index.php" 
                           class="<?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Home
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
                    <?php else: ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>products.php" 
                               class="<?php echo $currentPage == 'products.php' ? 'active' : ''; ?>">
                                Products
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li>
                        <a href="<?php echo SITE_URL; ?>about.php" 
                           class="<?php echo $currentPage == 'about.php' ? 'active' : ''; ?>">
                            About
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>contact.php" 
                           class="<?php echo $currentPage == 'contact.php' ? 'active' : ''; ?>">
                            Contact
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    
    <!-- Main Content Starts -->
    <main class="main-content" role="main">

    <!-- ============================================
         JAVASCRIPT FILES - LOADED AT END OF BODY
         But we need cart.js loaded before page content
         ============================================ -->
    <script src="<?php echo SITE_URL; ?>assets/js/cart.js"></script>
    <!-- In app/views/frontend/layout/footer.php -->
<script src="<?php echo SITE_URL; ?>assets/js/search.js"></script>
<!-- In app/views/frontend/layout/header.php -->
<head>
    <!-- CSS Variables -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/variables.css">
    
    <!-- Main Styles -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    
    <!-- Responsive Styles -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/responsive.css">
</head>