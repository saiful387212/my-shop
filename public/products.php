<?php
// ============================================================
// FILE: public/products.php
// PURPOSE: Display products with working add to cart
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// ============================================
// LOAD CART FUNCTIONS
// ============================================
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';

// Load Product model
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// GET FILTER PARAMETERS
// ============================================

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// ============================================
// FETCH PRODUCTS
// ============================================

try {
    $productModel = new Product();
    
    if (!empty($searchQuery)) {
        $products = $productModel->search($searchQuery);
    } elseif ($categoryId > 0) {
        $products = $productModel->getByCategory($categoryId);
    } else {
        $products = $productModel->getAllWithCategory();
    }
    
    $products = sortProducts($products, $sort);
    $categories = $productModel->getAllCategories();
    
} catch (Exception $e) {
    error_log('Products page error: ' . $e->getMessage());
    $products = [];
    $categories = [];
}

// ============================================
// SORTING FUNCTION
// ============================================

function sortProducts($products, $sort) {
    switch ($sort) {
        case 'price_low':
            usort($products, function($a, $b) {
                return $a['price'] <=> $b['price'];
            });
            break;
        case 'price_high':
            usort($products, function($a, $b) {
                return $b['price'] <=> $a['price'];
            });
            break;
        case 'name':
            usort($products, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            break;
        case 'newest':
        default:
            usort($products, function($a, $b) {
                return strtotime($b['created_at']) <=> strtotime($a['created_at']);
            });
            break;
    }
    return $products;
}

// ============================================
// GET CART COUNT
// ============================================

$cartCount = getCartTotalItems();

$pageTitle = 'Products';

// Include header
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     PRODUCTS PAGE CONTENT
     ============================================ -->
<section class="products-page">
    <div class="container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1>Our Products</h1>
            <p class="product-count"><?php echo count($products); ?> products found</p>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <div class="filter-item">
                    <label for="categoryFilter">
                        <i class="fas fa-filter"></i> Category:
                    </label>
                    <select id="categoryFilter" class="filter-select" onchange="window.location.href='?category='+this.value">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo ($categoryId == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="sortFilter">
                        <i class="fas fa-sort"></i> Sort by:
                    </label>
                    <select id="sortFilter" class="filter-select" onchange="window.location.href='?sort='+this.value">
                        <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?php echo ($sort == 'name') ? 'selected' : ''; ?>>Name: A to Z</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <?php if (!empty($products)): ?>
            <div class="products-grid">
                
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        
                        <!-- Product Image -->
                        <div class="product-image">
                            <a href="product_details.php?id=<?php echo $product['id']; ?>">
                                <?php 
                                $imagePath = !empty($product['image_url']) 
                                    ? 'assets/uploads/products/' . htmlspecialchars($product['image_url']) 
                                    : 'assets/images/no-image.png';
                                ?>
                                <img src="<?php echo SITE_URL . $imagePath; ?>" 
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
                                <a href="product_details.php?id=<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($product['description'])): ?>
                                <p class="product-description">
                                    <?php 
                                    $description = strip_tags($product['description']);
                                    echo htmlspecialchars(substr($description, 0, 80)) . (strlen($description) > 80 ? '...' : '');
                                    ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Price -->
                            <div class="product-price">
                                <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                            </div>
                            
                            <!-- Stock -->
                            <div class="product-stock">
                                <span class="stock-indicator <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>"></span>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <span><?php echo $product['stock_quantity']; ?> available</span>
                                <?php else: ?>
                                    <span>Sold out</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- ============================================
                                 ADD TO CART BUTTON - FIXED WITH PROPER CLASSES
                                 ============================================ -->
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
                                    <i class="fas fa-times-circle"></i> Out of Stock
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
                <h2>No Products Found</h2>
                <p>No products are currently available. Please check back later.</p>
                <a href="products.php" class="btn btn-primary" style="display:inline-flex;width:auto;padding:12px 30px;">
                    <i class="fas fa-arrow-left"></i> View All Products
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</section>

<?php
// ============================================
// INCLUDE FOOTER - cart.js is loaded here
// ============================================
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>