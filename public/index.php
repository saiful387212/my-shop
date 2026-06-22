<?php
// ============================================================
// FILE: public/index.php
// PURPOSE: DU Student Marketplace - Complete Homepage
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load cart functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';

// Load models
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function getProductImageUrl($imageUrl) {
    $placeholder = SITE_URL . 'assets/images/no-image.png';
    
    if (empty($imageUrl)) {
        return $placeholder;
    }
    
    if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        return $imageUrl;
    }
    
    $uploadPath = ABSPATH . 'public/uploads/products/' . $imageUrl;
    if (file_exists($uploadPath)) {
        return SITE_URL . 'uploads/products/' . $imageUrl;
    }
    
    return $placeholder;
}

// ============================================
// FETCH DATA
// ============================================

try {
    $productModel = new Product();
    $shopModel = new Shop();
    $pdo = getDbConnection();
    
    // Get categories
    $categories = $productModel->getAllCategories();
    
    // Get featured products
    $featuredProducts = [];
    if ($pdo !== null) {
        $stmt = $pdo->query("
            SELECT p.*, c.name as category_name, s.shop_name, s.shop_slug, u.name as vendor_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN shops s ON p.shop_id = s.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE p.is_active = 1 AND p.status = 'approved'
            ORDER BY p.created_at DESC
            LIMIT 8
        ");
        $featuredProducts = $stmt->fetchAll();
    }
    
    // Get DU shops
    $duShops = [];
    if ($pdo !== null) {
        $stmt = $pdo->query("
            SELECT s.*, u.name as owner_name 
            FROM shops s
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.is_approved = 1
            ORDER BY s.created_at DESC
            LIMIT 6
        ");
        $duShops = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    error_log('Homepage error: ' . $e->getMessage());
    $categories = [];
    $featuredProducts = [];
    $duShops = [];
}

// Get user info
$cartCount = getCartTotalItems();
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$userShop = null;

if ($isLoggedIn) {
    try {
        $userShop = $shopModel->getByUser($_SESSION['user_id']);
    } catch (Exception $e) {
        // Ignore
    }
}

$pageTitle = 'DU Marketplace';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DU Student Marketplace - Dhaka University</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Cart CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/cart.css">
    
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
        }
        
        .btn-small {
            padding: 6px 14px;
            font-size: 12px;
        }
        
        .btn-large {
            padding: 14px 32px;
            font-size: 16px;
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
           HERO SECTION WITH CURZON HALL BACKGROUND
           ============================================ */
        
        .hero {
            padding: 80px 0;
            /* ============================================
               CURZON HALL IMAGE - CHANGE PATH IF NEEDED
               ============================================ */
            background-image: url('images/carzon.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
            color: white;
            min-height: 500px;
            display: flex;
            align-items: center;
        }
        
        /* Fallback color if image doesn't load */
        .hero {
            background-color: #0a0a2e;
        }
        
        /* Dark overlay for text readability */
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero-badge {
            display: inline-block;
            background: rgba(255, 215, 0, 0.2);
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 6px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            color: #FFD700;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 16px;
        }
        
        .hero-title .highlight {
            color: #FFD700;
        }
        
        .hero-subtitle {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 12px;
            line-height: 1.8;
        }
        
        .hero-description {
            font-size: 16px;
            opacity: 0.8;
            margin-bottom: 30px;
            line-height: 1.8;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .hero-buttons .btn-primary {
            background: #FFD700;
            color: #1a1a2e;
            border-color: #FFD700;
        }
        
        .hero-buttons .btn-primary:hover {
            background: #f0c800;
            border-color: #f0c800;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }
        
        .hero-buttons .btn-outline {
            color: white;
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .hero-buttons .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
            transform: translateY(-2px);
        }
        
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 50px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: #FFD700;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.8;
            display: block;
        }
        
        /* ============================================
           DU FEATURES
           ============================================ */
        
        .du-features {
            padding: 60px 0;
            background: white;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .section-header h2 {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a2e;
        }
        
        .section-header h2 .highlight {
            color: #2C3E8F;
        }
        
        .section-header p {
            color: #6c757d;
            font-size: 16px;
            margin-top: 4px;
        }
        
        .view-all {
            display: inline-block;
            margin-top: 8px;
            color: #2C3E8F;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .view-all:hover {
            transform: translateX(4px);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }
        
        .feature-card {
            text-align: center;
            padding: 30px 20px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            border-color: #2C3E8F;
        }
        
        .feature-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            background: #e8edf9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #2C3E8F;
        }
        
        .feature-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
        }
        
        .feature-card p {
            font-size: 14px;
            color: #6c757d;
            margin-top: 4px;
        }
        
        /* ============================================
           PRODUCTS SECTION
           ============================================ */
        
        .products-section {
            padding: 60px 0;
            background: #f8fafc;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
        }
        
        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #eef2f7;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
            border-color: #2C3E8F;
        }
        
        .product-image {
            position: relative;
            aspect-ratio: 1 / 1;
            background: #f8fafc;
            overflow: hidden;
        }
        
        .product-image a {
            display: block;
            width: 100%;
            height: 100%;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.08);
        }
        
        .badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            z-index: 2;
        }
        
        .badge.in-stock {
            background: #27ae60;
            color: white;
        }
        
        .badge.low-stock {
            background: #f39c12;
            color: white;
        }
        
        .badge.out-of-stock {
            background: #e74c3c;
            color: white;
        }
        
        .badge.du-exclusive {
            background: #FFD700;
            color: #1a1a2e;
            left: auto;
            right: 12px;
        }
        
        .product-info {
            padding: 16px 20px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-vendor {
            font-size: 13px;
            color: #2C3E8F;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }
        
        .product-vendor a {
            color: #2C3E8F;
            text-decoration: none;
            font-weight: 500;
        }
        
        .product-vendor a:hover {
            text-decoration: underline;
        }
        
        .product-category {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .product-category i {
            color: #2C3E8F;
            margin-right: 4px;
        }
        
        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 6px;
            line-height: 1.3;
        }
        
        .product-name a {
            color: inherit;
            text-decoration: none;
        }
        
        .product-name a:hover {
            color: #2C3E8F;
        }
        
        .product-price {
            font-size: 20px;
            font-weight: 700;
            color: #2C3E8F;
            margin-bottom: 10px;
        }
        
        .product-stock {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 12px;
        }
        
        .stock-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .stock-indicator.in-stock {
            background: #27ae60;
        }
        
        .stock-indicator.out-of-stock {
            background: #e74c3c;
        }
        
        .product-card .btn {
            width: 100%;
            justify-content: center;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-top: auto;
        }
        
        /* ============================================
           DU VENDORS
           ============================================ */
        
        .vendors-section {
            padding: 60px 0;
            background: white;
        }
        
        .vendors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 24px;
        }
        
        .vendor-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            text-decoration: none;
            color: #1a1a2e;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .vendor-card:hover {
            border-color: #2C3E8F;
            transform: translateY(-6px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            background: white;
        }
        
        .vendor-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #2C3E8F;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            margin: 0 auto 12px;
        }
        
        .vendor-name {
            font-size: 16px;
            font-weight: 600;
        }
        
        .vendor-badge {
            display: inline-block;
            background: #FFD700;
            color: #1a1a2e;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 6px;
        }
        
        /* ============================================
           DU CTA SECTION
           ============================================ */
        
        .du-cta {
            padding: 60px 0;
            background: linear-gradient(135deg, #2C3E8F, #4A6CCF);
            color: white;
            text-align: center;
        }
        
        .du-cta h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
        }
        
        .du-cta p {
            font-size: 18px;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 24px;
        }
        
        .du-cta .btn {
            background: #FFD700;
            color: #1a1a2e;
            padding: 14px 40px;
            font-size: 16px;
            font-weight: 700;
            border: none;
            border-radius: 50px;
            text-decoration: none;
        }
        
        .du-cta .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        
        /* ============================================
           FOOTER
           ============================================ */
        
        .footer {
            background: #1a1a2e;
            color: rgba(255, 255, 255, 0.8);
            padding: 40px 0 20px;
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
        
        @media (max-width: 1024px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
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
            
            .hero {
                padding: 60px 0;
                background-attachment: scroll;
                min-height: 400px;
            }
            
            .hero-title {
                font-size: 32px;
            }
            
            .hero-subtitle {
                font-size: 16px;
            }
            
            .hero-stats {
                flex-direction: column;
                gap: 12px;
            }
            
            .features-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .vendors-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .hero {
                padding: 40px 0;
                min-height: 350px;
            }
            
            .hero-title {
                font-size: 26px;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .hero-buttons .btn {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }
            
            .features-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            
            .feature-card {
                padding: 20px 12px;
            }
            
            .products-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            
            .product-name {
                font-size: 14px;
            }
            
            .product-price {
                font-size: 16px;
            }
            
            .product-card .btn {
                font-size: 12px;
                padding: 8px;
            }
            
            .vendors-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
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
                        <i class="fas fa-university logo-icon"></i>
                        <div class="logo-text">
                            <span class="brand">DU <span>Market</span></span>
                            <span class="tagline">Dhaka University Marketplace</span>
                        </div>
                    </a>
                </div>
                
                <div class="search-bar">
                    <form action="products.php" method="GET">
                        <input type="text" name="search" placeholder="Search for books, gadgets, services...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="header-actions">
                    
                    <?php if ($isLoggedIn && $userShop): ?>
                        <a href="<?php echo SITE_URL; ?>shop/manage.php" class="btn btn-success btn-small">
                            <i class="fas fa-store"></i> My Shop
                        </a>
                    <?php elseif ($isLoggedIn && !$userShop): ?>
                        <a href="<?php echo SITE_URL; ?>shop/create.php" class="btn btn-primary btn-small">
                            <i class="fas fa-plus-circle"></i> Open Shop
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo SITE_URL; ?>account.php" class="profile-btn">
                            <span class="avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
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
                    
                    <a href="<?php echo SITE_URL; ?>cart.php" class="cart-icon">
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
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                    <li><a href="shops.php"><i class="fas fa-store"></i> Vendors</a></li>
                    
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <li><a href="products.php?category=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
        </nav>
    </header>
    
    <!-- ============================================
         HERO SECTION WITH CURZON HALL BACKGROUND
         ============================================ -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-university"></i> Dhaka University
                </div>
                <h1 class="hero-title">
                    <span class="highlight">DU Students</span> <br>Buy & Sell Marketplace
                </h1>
                <p class="hero-subtitle">
                    Where tradition meets modern commerce
                </p>
                <p class="hero-description">
                    Connect with fellow DU students to buy, sell, and trade items within the university community. 
                    From textbooks to gadgets, find what you need from trusted sellers on campus.
                </p>
                <div class="hero-buttons">
                    <a href="products.php" class="btn btn-primary btn-large">
                        <i class="fas fa-shopping-bag"></i> Start Shopping
                    </a>
                    <?php if ($isLoggedIn && !$userShop): ?>
                        <a href="shop/create.php" class="btn btn-outline btn-large">
                            <i class="fas fa-store"></i> Open Your Shop
                        </a>
                    <?php elseif ($isLoggedIn && $userShop): ?>
                        <a href="shop/manage.php" class="btn btn-outline btn-large">
                            <i class="fas fa-store"></i> Manage Your Shop
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-outline btn-large">
                            <i class="fas fa-user-plus"></i> Join as Seller
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Active Students</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">200+</span>
                        <span class="stat-label">Items Listed</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">4.9⭐</span>
                        <span class="stat-label">Student Rating</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">DU Shops</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- ============================================
         DU FEATURES
         ============================================ -->
    <section class="du-features">
        <div class="container">
            <div class="section-header">
                <h2>Why <span class="highlight">DU Students</span> Love This Platform</h2>
                <p>Built exclusively for the Dhaka University community</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-user-graduate"></i></div>
                    <h3>Student Only</h3>
                    <p>Exclusively for DU students and faculty</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-book"></i></div>
                    <h3>Textbook Exchange</h3>
                    <p>Buy and sell used textbooks easily</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-handshake"></i></div>
                    <h3>Trusted Community</h3>
                    <p>Buy and sell within the DU community</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-location-dot"></i></div>
                    <h3>Campus Delivery</h3>
                    <p>Easy meetups on campus</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- ============================================
         FEATURED PRODUCTS
         ============================================ -->
    <section class="products-section" id="featured">
        <div class="container">
            <div class="section-header">
                <h2>Latest <span class="highlight">Listings</span></h2>
                <p>What DU students are selling right now</p>
                <a href="products.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <?php if (!empty($featuredProducts)): ?>
                <div class="products-grid">
                    <?php foreach ($featuredProducts as $product): ?>
                        <div class="product-card">
                            
                            <div class="product-image">
                                <a href="product_details.php?id=<?php echo $product['id']; ?>">
                                    <img src="<?php echo getProductImageUrl($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         loading="lazy"
                                         onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                                </a>
                                
                                <?php if ($product['stock_quantity'] <= 0): ?>
                                    <span class="badge out-of-stock">Sold</span>
                                <?php elseif ($product['stock_quantity'] < 10): ?>
                                    <span class="badge low-stock">Only <?php echo $product['stock_quantity']; ?> left</span>
                                <?php else: ?>
                                    <span class="badge in-stock">Available</span>
                                <?php endif; ?>
                                
                                <span class="badge du-exclusive">DU Student</span>
                            </div>
                            
                            <div class="product-info">
                                
                                <?php if (!empty($product['shop_name'])): ?>
                                    <div class="product-vendor">
                                        <i class="fas fa-store"></i>
                                        <a href="shop/view.php?slug=<?php echo htmlspecialchars($product['shop_slug']); ?>">
                                            <?php echo htmlspecialchars($product['shop_name']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['category_name'])): ?>
                                    <span class="product-category">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <h3 class="product-name">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                
                                <div class="product-price">
                                    ৳<?php echo number_format($product['price'], 2); ?>
                                </div>
                                
                                <div class="product-stock">
                                    <span class="stock-indicator <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>"></span>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <span><?php echo $product['stock_quantity']; ?> available</span>
                                    <?php else: ?>
                                        <span>Sold out</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <button class="btn btn-primary add-to-cart" 
                                            data-id="<?php echo $product['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            data-price="<?php echo $product['price']; ?>"
                                            data-image="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-times-circle"></i> Sold Out
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align:center;padding:60px 20px;">
                    <i class="fas fa-box-open" style="font-size:48px;color:#cbd5e0;margin-bottom:16px;"></i>
                    <h3>No Listings Yet</h3>
                    <p style="color:#6c757d;">Be the first DU student to list an item!</p>
                    <?php if ($isLoggedIn && !$userShop): ?>
                        <a href="shop/create.php" class="btn btn-primary" style="margin-top:16px;">
                            <i class="fas fa-plus-circle"></i> Open Your Shop
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- ============================================
         DU VENDORS
         ============================================ -->
    <section class="vendors-section">
        <div class="container">
            <div class="section-header">
                <h2>DU <span class="highlight">Vendors</span></h2>
                <p>Trusted sellers from the Dhaka University community</p>
                <a href="shops.php" class="view-all">View All Vendors <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <?php if (!empty($duShops)): ?>
                <div class="vendors-grid">
                    <?php foreach ($duShops as $shop): ?>
                        <a href="shop/view.php?slug=<?php echo $shop['shop_slug']; ?>" class="vendor-card">
                            <div class="vendor-avatar">
                                <?php echo strtoupper(substr($shop['shop_name'], 0, 1)); ?>
                            </div>
                            <div class="vendor-name"><?php echo htmlspecialchars($shop['shop_name']); ?></div>
                            <div class="vendor-badge">DU Student</div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align:center;color:#6c757d;">No vendors yet. Be the first DU student to open a shop!</p>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- ============================================
         DU CTA SECTION
         ============================================ -->
    <section class="du-cta">
        <div class="container">
            <h2>🚀 Start Your DU Business Today!</h2>
            <p>Join the Dhaka University student marketplace. Sell your items and connect with fellow students.</p>
            
            <?php if ($isLoggedIn && $userShop): ?>
                <a href="<?php echo SITE_URL; ?>shop/manage.php" class="btn">
                    <i class="fas fa-store"></i> Manage Your Shop
                </a>
            <?php elseif ($isLoggedIn && !$userShop): ?>
                <a href="<?php echo SITE_URL; ?>shop/create.php" class="btn">
                    <i class="fas fa-plus-circle"></i> Open Your Shop
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>register.php" class="btn">
                    <i class="fas fa-user-plus"></i> Join as a DU Student
                </a>
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
                        <i class="fas fa-university"></i>
                        <span>DU Market</span>
                    </div>
                    <p class="footer-description">Dhaka University Student Marketplace - Where tradition meets modern commerce.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="shops.php">Vendors</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>For Students</h4>
                    <ul>
                        <?php if ($isLoggedIn && $userShop): ?>
                            <li><a href="shop/manage.php">My Shop</a></li>
                        <?php elseif ($isLoggedIn): ?>
                            <li><a href="shop/create.php">Open Shop</a></li>
                        <?php else: ?>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                        <li><a href="terms.php">Terms</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> Dhaka University</li>
                        <li><i class="fas fa-envelope"></i> info@dumarket.com</li>
                        <li><i class="fas fa-phone-alt"></i> +880 2-9661920</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> DU Market. All rights reserved. Built for Dhaka University Students.</p>
                <div class="payment-methods">
                    <i class="fas fa-university"></i>
                    <i class="fas fa-book"></i>
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script src="<?php echo SITE_URL; ?>assets/js/cart.js"></script>
    
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
            
            console.log('✅ DU Student Marketplace loaded successfully!');
            console.log('🎓 Curzon Hall - Where tradition meets modern academia.');
        });
    </script>
    
</body>
</html>