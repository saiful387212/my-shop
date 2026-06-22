<?php
// ============================================================
// FILE: public/product_details.php
// PURPOSE: Display detailed product information
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

// Load Product model
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// HELPER: Get Product Image URL
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
// GET AND VALIDATE PRODUCT ID
// ============================================

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: ' . SITE_URL . 'products.php');
    exit;
}

// ============================================
// FETCH PRODUCT FROM DATABASE
// ============================================

$product = null;
$relatedProducts = [];
$error = null;

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Get product with category
    $stmt = $pdo->prepare('
        SELECT 
            p.*, 
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id
    ');
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: ' . SITE_URL . 'products.php');
        exit;
    }
    
    // Get related products (same category, excluding current)
    $stmt = $pdo->prepare('
        SELECT 
            p.*, 
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = :category_id 
        AND p.id != :product_id
        ORDER BY RAND()
        LIMIT 4
    ');
    $stmt->execute([
        'category_id' => $product['category_id'],
        'product_id' => $product['id']
    ]);
    $relatedProducts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Product details error: ' . $e->getMessage());
    $error = 'Database error occurred.';
} catch (Exception $e) {
    error_log('Product details error: ' . $e->getMessage());
    $error = 'An error occurred.';
}

// If there was an error, show it
if ($error) {
    die($error);
}

// Generate random rating for demo
$rating = rand(3, 5);
$reviews = rand(10, 200);

// Set page title
$pageTitle = htmlspecialchars($product['name']);

// Get cart count
$cartCount = getCartTotalItems();

// Get categories for navigation
$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Product details categories error: ' . $e->getMessage());
}

// Include header
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     PRODUCT DETAILS CONTENT
     ============================================ -->
<section class="product-details">
    <div class="container">
        
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>index.php"><i class="fas fa-home"></i> Home</a>
            <span class="separator">/</span>
            <a href="<?php echo SITE_URL; ?>products.php">Products</a>
            <span class="separator">/</span>
            <span class="current"><?php echo htmlspecialchars($product['name']); ?></span>
        </div>
        
        <!-- ============================================
             PRODUCT DETAILS GRID
             ============================================ -->
        <div class="product-details-grid">
            
            <!-- ==============================
                 LEFT: PRODUCT IMAGE
                 ============================== -->
            <div class="product-gallery">
                <div class="main-image">
                    <?php 
                    $imageUrl = getProductImageUrl($product['image_url']);
                    ?>
                    <img src="<?php echo $imageUrl; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         id="mainProductImage"
                         onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                    
                    <?php if ($product['stock_quantity'] <= 0): ?>
                        <span class="badge out-of-stock">Out of Stock</span>
                    <?php elseif ($product['stock_quantity'] < 10): ?>
                        <span class="badge low-stock">Only <?php echo $product['stock_quantity']; ?> left!</span>
                    <?php else: ?>
                        <span class="badge in-stock">In Stock</span>
                    <?php endif; ?>
                </div>
                
                <!-- Thumbnails -->
                <div class="thumbnail-gallery">
                    <div class="thumbnail active">
                        <img src="<?php echo $imageUrl; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onclick="changeImage(this.src)"
                             onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                    </div>
                    <div class="thumbnail">
                        <img src="<?php echo $imageUrl; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onclick="changeImage(this.src)"
                             onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                    </div>
                    <div class="thumbnail">
                        <img src="<?php echo $imageUrl; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onclick="changeImage(this.src)"
                             onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                    </div>
                </div>
            </div>
            
            <!-- ==============================
                 RIGHT: PRODUCT INFO
                 ============================== -->
            <div class="product-info-details">
                
                <!-- Category -->
                <?php if (!empty($product['category_name'])): ?>
                    <div class="product-meta">
                        <span class="product-category">
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>
                        <span class="product-id">SKU: #<?php echo str_pad($product['id'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Product Name -->
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <!-- Rating -->
                <div class="product-rating">
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo ($i <= $rating) ? 'filled' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-count"><?php echo $reviews; ?> reviews</span>
                    <span class="rating-separator">|</span>
                    <span class="rating-text"><?php echo number_format($rating, 1); ?> / 5.0</span>
                </div>
                
                <!-- Price -->
                <div class="product-price-details">
                    <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                </div>
                
                <!-- Stock Status -->
                <div class="stock-status">
                    <div class="stock-indicator-wrapper">
                        <span class="stock-indicator <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>"></span>
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <span class="stock-text in-stock">
                                <i class="fas fa-check-circle"></i>
                                In Stock - <?php echo $product['stock_quantity']; ?> units available
                            </span>
                        <?php else: ?>
                            <span class="stock-text out-of-stock">
                                <i class="fas fa-times-circle"></i>
                                Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="shipping-info">
                        <i class="fas fa-truck"></i>
                        <span>Free shipping on orders over $50</span>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="product-description-full">
                    <h3>Product Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>
                </div>
                
                <!-- ==============================
                     ADD TO CART
                     ============================== -->
                <div class="add-to-cart-section">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <div class="quantity-control">
                                <button class="qty-btn" id="decreaseQty" type="button">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       id="quantity" 
                                       value="1" 
                                       min="1" 
                                       max="<?php echo $product['stock_quantity']; ?>"
                                       readonly>
                                <button class="qty-btn" id="increaseQty" type="button">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <span class="max-qty">Max: <?php echo $product['stock_quantity']; ?></span>
                        </div>
                        
                        <button class="btn btn-primary btn-add-to-cart add-to-cart" 
                                id="addToCartBtn"
                                data-id="<?php echo $product['id']; ?>"
                                data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                data-price="<?php echo $product['price']; ?>"
                                data-image="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>">
                            <i class="fas fa-cart-plus"></i>
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-add-to-cart" disabled>
                            <i class="fas fa-times-circle"></i>
                            Out of Stock
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Features -->
                <div class="product-features">
                    <div class="feature-item">
                        <i class="fas fa-shipping-fast"></i>
                        <span>Free Shipping</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-undo-alt"></i>
                        <span>30-Day Returns</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-lock"></i>
                        <span>Secure Checkout</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-headset"></i>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ============================================
             RELATED PRODUCTS
             ============================================ -->
        <?php if (!empty($relatedProducts)): ?>
            <section class="related-products">
                <div class="section-header">
                    <h2>You May Also Like</h2>
                    <p>Customers who bought this also bought these products</p>
                </div>
                
                <div class="related-products-grid">
                    <?php foreach ($relatedProducts as $related): ?>
                        <div class="related-product-card">
                            <a href="product_details.php?id=<?php echo $related['id']; ?>">
                                <div class="related-image">
                                    <?php 
                                    $relatedImage = getProductImageUrl($related['image_url']);
                                    ?>
                                    <img src="<?php echo $relatedImage; ?>" 
                                         alt="<?php echo htmlspecialchars($related['name']); ?>"
                                         loading="lazy"
                                         onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                                </div>
                                <h4 class="related-name"><?php echo htmlspecialchars($related['name']); ?></h4>
                                <span class="related-price">$<?php echo number_format($related['price'], 2); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        
    </div>
</section>

<!-- ============================================
     PRODUCT DETAILS STYLES
     ============================================ -->
<style>
/* ============================================
   PRODUCT DETAILS
   ============================================ */

.product-details {
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
   BREADCRUMB
   ============================================ */

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 30px;
    flex-wrap: wrap;
    font-family: 'Inter', sans-serif;
}

.breadcrumb a {
    color: #2C3E8F;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb .separator {
    color: #cbd5e0;
}

.breadcrumb .current {
    color: #1a1a2e;
    font-weight: 600;
}

/* ============================================
   PRODUCT DETAILS GRID
   ============================================ */

.product-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    margin-bottom: 40px;
}

/* ============================================
   PRODUCT GALLERY
   ============================================ */

.product-gallery {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.main-image {
    position: relative;
    background: #f8fafc;
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #eef2f7;
}

.main-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 20px;
    transition: transform 0.3s ease;
}

.main-image .badge {
    position: absolute;
    top: 16px;
    left: 16px;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    z-index: 2;
}

.main-image .badge.in-stock {
    background: #27ae60;
    color: white;
}

.main-image .badge.low-stock {
    background: #f39c12;
    color: white;
}

.main-image .badge.out-of-stock {
    background: #e74c3c;
    color: white;
}

/* Thumbnails */
.thumbnail-gallery {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.thumbnail {
    background: #f8fafc;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.thumbnail:hover {
    border-color: #cbd5e0;
}

.thumbnail.active {
    border-color: #2C3E8F;
    box-shadow: 0 0 0 4px rgba(44, 62, 143, 0.1);
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
}

/* ============================================
   PRODUCT INFO
   ============================================ */

.product-info-details {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: #6c757d;
}

.product-category {
    background: #f0f4ff;
    color: #2C3E8F;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
}

.product-id {
    color: #6c757d;
    font-family: 'Inter', sans-serif;
}

.product-title {
    font-size: 28px;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1.2;
    font-family: 'Inter', sans-serif;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-rating .stars {
    display: flex;
    gap: 2px;
}

.product-rating .stars .fa-star {
    color: #e2e8f0;
    font-size: 16px;
}

.product-rating .stars .fa-star.filled {
    color: #f39c12;
}

.rating-count {
    font-size: 14px;
    color: #6c757d;
    font-family: 'Inter', sans-serif;
}

.rating-separator {
    color: #e2e8f0;
}

.rating-text {
    font-size: 14px;
    font-weight: 600;
    color: #1a1a2e;
    font-family: 'Inter', sans-serif;
}

.product-price-details {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 0;
    border-top: 2px solid #f0f0f0;
    border-bottom: 2px solid #f0f0f0;
}

.product-price-details .current-price {
    font-size: 32px;
    font-weight: 800;
    color: #2C3E8F;
    font-family: 'Inter', sans-serif;
}

.stock-status {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 12px 0;
}

.stock-indicator-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stock-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.stock-indicator.in-stock {
    background: #27ae60;
}

.stock-indicator.out-of-stock {
    background: #e74c3c;
}

.stock-text {
    font-size: 14px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
}

.stock-text.in-stock {
    color: #27ae60;
}

.stock-text.out-of-stock {
    color: #e74c3c;
}

.stock-text i {
    margin-right: 6px;
}

.shipping-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #6c757d;
    font-family: 'Inter', sans-serif;
}

.shipping-info i {
    color: #2C3E8F;
}

.product-description-full {
    padding: 12px 0;
}

.product-description-full h3 {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 8px;
    font-family: 'Inter', sans-serif;
}

.product-description-full p {
    font-size: 15px;
    color: #4a5568;
    line-height: 1.8;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   ADD TO CART
   ============================================ */

.add-to-cart-section {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 0;
    border-top: 2px solid #f0f0f0;
    flex-wrap: wrap;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 12px;
}

.quantity-selector label {
    font-size: 14px;
    font-weight: 600;
    color: #4a5568;
    font-family: 'Inter', sans-serif;
}

.quantity-control {
    display: flex;
    align-items: center;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    background: white;
}

.qty-btn {
    padding: 8px 14px;
    border: none;
    background: #f8fafc;
    color: #1a1a2e;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover {
    background: #2C3E8F;
    color: white;
}

.quantity-control input {
    width: 50px;
    padding: 8px 0;
    border: none;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    background: white;
    color: #1a1a2e;
    -moz-appearance: textfield;
}

.quantity-control input::-webkit-outer-spin-button,
.quantity-control input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.max-qty {
    font-size: 12px;
    color: #6c757d;
    font-family: 'Inter', sans-serif;
}

.btn-add-to-cart {
    padding: 14px 40px;
    font-size: 16px;
    font-weight: 700;
    border-radius: 10px;
    flex: 1;
    min-width: 200px;
}

.btn-add-to-cart:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ============================================
   PRODUCT FEATURES
   ============================================ */

.product-features {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    padding: 16px 0;
    border-top: 2px solid #f0f0f0;
}

.feature-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 4px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.feature-item:hover {
    background: #f0f4ff;
    transform: translateY(-2px);
}

.feature-item i {
    font-size: 20px;
    color: #2C3E8F;
}

.feature-item span {
    font-size: 12px;
    font-weight: 600;
    color: #4a5568;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   RELATED PRODUCTS
   ============================================ */

.related-products {
    margin-top: 40px;
}

.section-header {
    text-align: center;
    margin-bottom: 30px;
}

.section-header h2 {
    font-size: 24px;
    font-weight: 800;
    color: #1a1a2e;
    font-family: 'Inter', sans-serif;
}

.section-header h2::after {
    content: '';
    display: block;
    width: 50px;
    height: 3px;
    background: #2C3E8F;
    margin: 8px auto 0;
    border-radius: 2px;
}

.section-header p {
    color: #6c757d;
    font-size: 14px;
    margin-top: 4px;
    font-family: 'Inter', sans-serif;
}

.related-products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.related-product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
}

.related-product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    border-color: #2C3E8F;
}

.related-product-card a {
    text-decoration: none;
    color: inherit;
    display: block;
}

.related-image {
    aspect-ratio: 1 / 1;
    background: #f8fafc;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.related-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 16px;
    transition: transform 0.3s ease;
}

.related-product-card:hover .related-image img {
    transform: scale(1.05);
}

.related-name {
    font-size: 14px;
    font-weight: 600;
    color: #1a1a2e;
    padding: 12px 16px 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
}

.related-price {
    display: block;
    font-size: 16px;
    font-weight: 700;
    color: #2C3E8F;
    padding: 0 16px 16px;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1024px) {
    .product-details-grid {
        grid-template-columns: 1fr;
        gap: 30px;
        padding: 30px;
    }
    
    .related-products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .product-details {
        padding: 20px 0 40px;
    }
    
    .product-details-grid {
        padding: 20px;
        gap: 20px;
    }
    
    .product-title {
        font-size: 22px;
    }
    
    .product-price-details .current-price {
        font-size: 26px;
    }
    
    .add-to-cart-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .quantity-selector {
        justify-content: space-between;
    }
    
    .btn-add-to-cart {
        width: 100%;
        min-width: unset;
    }
    
    .product-features {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .related-products-grid {
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
}

@media (max-width: 480px) {
    .product-details-grid {
        padding: 16px;
        border-radius: 12px;
    }
    
    .product-title {
        font-size: 18px;
    }
    
    .product-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
    
    .product-price-details .current-price {
        font-size: 22px;
    }
    
    .related-products-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .thumbnail-gallery {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .product-features {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
}
</style>

<!-- ============================================
     JAVASCRIPT
     ============================================ -->
<script>
// Change main image on thumbnail click
function changeImage(src) {
    document.getElementById('mainProductImage').src = src;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach(function(el) {
        el.classList.remove('active');
    });
    if (event && event.target) {
        event.target.closest('.thumbnail').classList.add('active');
    }
}

// Quantity controls
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('decreaseQty');
    const increaseBtn = document.getElementById('increaseQty');
    
    if (decreaseBtn && increaseBtn && quantityInput) {
        const maxQty = parseInt(quantityInput.getAttribute('max')) || 10;
        
        decreaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value) || 1;
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
        
        increaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value) || 1;
            if (currentValue < maxQty) {
                quantityInput.value = currentValue + 1;
            }
        });
    }
    
    console.log('✅ Product details page loaded successfully!');
});
</script>

<?php
// Include footer
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>