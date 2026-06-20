<?php
// ============================================================
// FILE: public/index.php
// PURPOSE: Front controller - entry point for all requests
// ============================================================

// ============================================
// FIX: Use DIRECTORY_SEPARATOR for Windows compatibility
// ============================================

// Get the absolute path to the project root
// __DIR__ = C:\xampp\htdocs\my-shop\public
// dirname(__DIR__) = C:\xampp\htdocs\my-shop
// realpath() resolves the path and fixes slashes
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Debug: Uncomment to see what ABSPATH is
// echo ABSPATH; die();  // Should show: C:\xampp\htdocs\my-shop\

// ============================================
// Load all required files using the correct path
// ============================================

// Configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Helpers
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// ============================================
// Start session
// ============================================
session_start();

// ============================================
// Get database connection
// ============================================
$pdo = getDbConnection();

// ============================================
// Get categories for navigation
// ============================================
$categories = [];
if ($pdo !== null) {
    try {
        $stmt = $pdo->query('SELECT * FROM categories ORDER BY name');
        $categories = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching categories: ' . $e->getMessage());
    }
}

// ============================================
// Get featured products
// ============================================
$featuredProducts = [];
if ($pdo !== null) {
    try {
        $stmt = $pdo->query('
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.created_at DESC 
            LIMIT 6
        ');
        $featuredProducts = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching featured products: ' . $e->getMessage());
    }
}

// ============================================
// Set page title
// ============================================
$pageTitle = 'Home';

// ============================================
// Include the header
// ============================================
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     PAGE CONTENT
     ============================================ -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Welcome to My Shop</h1>
                <p>Your one-stop destination for quality products</p>
                <a href="shop.php" class="btn btn-primary">Shop Now</a>
            </div>
        </div>
    </div>
</section>

<section class="featured-products">
    <div class="container">
        <h2>Featured Products</h2>
        <div class="products-grid">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="product-card">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p>$<?php echo number_format($product['price'], 2); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products available.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// ============================================
// Include the footer
// ============================================
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>