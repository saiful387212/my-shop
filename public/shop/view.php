<?php
// ============================================================
// FILE: public/shop/view.php
// PURPOSE: View a vendor's shop page - FIXED
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// GET SHOP SLUG
// ============================================

$shopSlug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($shopSlug)) {
    header('Location: ' . SITE_URL . 'shops.php');
    exit;
}

// ============================================
// FETCH SHOP DETAILS
// ============================================

$shop = null;
$products = [];
$error = null;

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // ============================================
    // FIX: Get shop by slug
    // ============================================
    $stmt = $pdo->prepare("
        SELECT s.*, u.name as owner_name 
        FROM shops s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.shop_slug = :slug
    ");
    $stmt->execute([':slug' => $shopSlug]);
    $shop = $stmt->fetch();
    
    if (!$shop) {
        header('Location: ' . SITE_URL . 'shops.php');
        exit;
    }
    
    // Check if shop is approved
    if (!$shop['is_approved']) {
        header('Location: ' . SITE_URL . 'shops.php');
        exit;
    }
    
    // ============================================
    // FIX: Get shop products - SHOW ALL PRODUCTS
    // ============================================
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.shop_id = :shop_id
        AND p.is_active = 1
        AND p.status = 'approved'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([':shop_id' => $shop['id']]);
    $products = $stmt->fetchAll();
    
    // ============================================
    // DEBUG: Log product count
    // ============================================
    error_log("Shop ID: " . $shop['id'] . " - Products found: " . count($products));
    
} catch (Exception $e) {
    error_log('Shop view error: ' . $e->getMessage());
    $error = 'Could not load shop details.';
}

// ============================================
// GET CATEGORIES FOR HEADER
// ============================================

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Shop view categories error: ' . $e->getMessage());
}

// ============================================
// HELPER FUNCTION FOR IMAGES
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
// PAGE SETUP
// ============================================

$cartCount = getCartTotalItems();
$pageTitle = $shop ? $shop['shop_name'] . ' - Shop' : 'Shop';
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     SHOP VIEW PAGE
     ============================================ -->
<section class="shop-view-page">
    <div class="container">
        
        <?php if ($shop): ?>
            
            <!-- Shop Header -->
            <div class="shop-header">
                <div class="shop-header-content">
                    <div class="shop-info">
                        <div class="shop-avatar">
                            <?php echo strtoupper(substr($shop['shop_name'], 0, 1)); ?>
                        </div>
                        <div class="shop-details">
                            <h1><?php echo htmlspecialchars($shop['shop_name']); ?></h1>
                            <div class="shop-meta">
                                <span class="shop-owner">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($shop['owner_name'] ?? 'Vendor'); ?>
                                </span>
                                <span class="shop-badge verified">
                                    <i class="fas fa-check-circle"></i>
                                    Verified Seller
                                </span>
                                <span class="shop-products-count">
                                    <i class="fas fa-box"></i>
                                    <?php echo count($products); ?> Products
                                </span>
                            </div>
                            <?php if (!empty($shop['description'])): ?>
                                <p class="shop-description"><?php echo htmlspecialchars($shop['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($shop['address']) || !empty($shop['phone']) || !empty($shop['email'])): ?>
                        <div class="shop-contact">
                            <h4>Contact Information</h4>
                            <?php if (!empty($shop['address'])): ?>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($shop['address']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($shop['phone'])): ?>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($shop['phone']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($shop['email'])): ?>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($shop['email']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Products Section -->
            <div class="shop-products-section">
                <div class="section-header">
                    <h2>Products from <span class="highlight"><?php echo htmlspecialchars($shop['shop_name']); ?></span></h2>
                    <p><?php echo count($products); ?> products available</p>
                </div>
                
                <?php if (!empty($products)): ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                
                                <!-- Product Image -->
                                <div class="product-image">
                                    <a href="<?php echo SITE_URL; ?>product_details.php?id=<?php echo $product['id']; ?>">
                                        <img src="<?php echo getProductImageUrl($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             loading="lazy"
                                             onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                                    </a>
                                    
                                    <?php if ($product['stock_quantity'] <= 0): ?>
                                        <span class="badge out-of-stock">Out of Stock</span>
                                    <?php elseif ($product['stock_quantity'] < 10): ?>
                                        <span class="badge low-stock">Only <?php echo $product['stock_quantity']; ?> left</span>
                                    <?php else: ?>
                                        <span class="badge in-stock">In Stock</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Product Info -->
                                <div class="product-info">
                                    <?php if (!empty($product['category_name'])): ?>
                                        <span class="product-category">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($product['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <h3 class="product-name">
                                        <a href="<?php echo SITE_URL; ?>product_details.php?id=<?php echo $product['id']; ?>">
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
                    <div class="no-products">
                        <div class="no-products-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h3>No Products Yet</h3>
                        <p>This shop hasn't added any products yet.</p>
                        <a href="<?php echo SITE_URL; ?>products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Browse All Products
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <div class="shop-not-found">
                <div class="not-found-icon">
                    <i class="fas fa-store"></i>
                </div>
                <h2>Shop Not Found</h2>
                <p>The shop you are looking for does not exist.</p>
                <a href="<?php echo SITE_URL; ?>shops.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Browse All Shops
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</section>

<style>
/* ============================================
   SHOP VIEW PAGE STYLES
   ============================================ */

.shop-view-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* ============================================
   SHOP HEADER
   ============================================ */

.shop-header {
    background: white;
    border-radius: 16px;
    padding: 30px;
    border: 1px solid #eef2f7;
    margin-bottom: 40px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.shop-header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 20px;
}

.shop-info {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}

.shop-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #2C3E8F;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 700;
    flex-shrink: 0;
}

.shop-details h1 {
    font-size: 28px;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.shop-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
    margin-bottom: 8px;
}

.shop-meta span {
    font-size: 14px;
    color: #4a5568;
}

.shop-meta i {
    margin-right: 4px;
    color: #2C3E8F;
}

.shop-badge.verified {
    background: #d4edda;
    color: #155724;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.shop-badge.verified i {
    color: #27ae60;
}

.shop-description {
    color: #4a5568;
    line-height: 1.8;
    margin-top: 4px;
    max-width: 500px;
}

.shop-contact {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px 20px;
    min-width: 200px;
}

.shop-contact h4 {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.shop-contact p {
    font-size: 13px;
    color: #4a5568;
    margin-bottom: 4px;
}

.shop-contact p i {
    width: 18px;
    color: #2C3E8F;
}

/* ============================================
   SECTION HEADER
   ============================================ */

.section-header {
    text-align: center;
    margin-bottom: 40px;
}

.section-header h2 {
    font-size: 28px;
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

/* ============================================
   PRODUCTS GRID
   ============================================ */

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

.product-info {
    padding: 16px 20px 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
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

.product-card .btn-secondary {
    background: #e2e8f0;
    color: #6c757d;
    cursor: not-allowed;
}

/* ============================================
   NOT FOUND
   ============================================ */

.shop-not-found {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 16px;
    border: 1px solid #eef2f7;
}

.not-found-icon {
    font-size: 64px;
    color: #cbd5e0;
    margin-bottom: 16px;
}

.shop-not-found h2 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.shop-not-found p {
    color: #6c757d;
    margin-bottom: 24px;
}

.no-products {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    border: 1px solid #eef2f7;
    grid-column: 1 / -1;
}

.no-products-icon {
    font-size: 48px;
    color: #cbd5e0;
    margin-bottom: 16px;
}

.no-products h3 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
}

.no-products p {
    color: #6c757d;
    margin-bottom: 20px;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1024px) {
    .shop-header-content {
        flex-direction: column;
    }
    
    .shop-contact {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .shop-view-page {
        padding: 20px 0 40px;
    }
    
    .shop-header {
        padding: 20px;
    }
    
    .shop-info {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .shop-avatar {
        width: 60px;
        height: 60px;
        font-size: 24px;
    }
    
    .shop-details h1 {
        font-size: 22px;
    }
    
    .shop-meta {
        justify-content: center;
    }
    
    .shop-description {
        max-width: 100%;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    
    .product-name {
        font-size: 14px;
    }
    
    .product-price {
        font-size: 17px;
    }
    
    .product-card .btn {
        font-size: 12px;
        padding: 8px;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .product-name {
        font-size: 13px;
    }
    
    .product-price {
        font-size: 15px;
    }
    
    .product-card .btn {
        font-size: 11px;
        padding: 6px;
    }
    
    .badge {
        font-size: 9px;
        padding: 2px 10px;
        top: 8px;
        left: 8px;
    }
}
</style>

<script src="<?php echo SITE_URL; ?>assets/js/cart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Shop view page loaded successfully!');
});
</script>

<?php require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php'; ?>