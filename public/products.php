<?php
// ============================================================
// FILE: public/products.php
// PURPOSE: Display products with full header CSS
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
// GET PRODUCT IMAGE URL
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
// GET FILTER PARAMETERS
// ============================================

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;

// ============================================
// FETCH PRODUCTS
// ============================================

try {
    $productModel = new Product();
    $shopModel = new Shop();
    
    // Get all products with shop info
    if (!empty($searchQuery)) {
        $allProducts = $productModel->search($searchQuery);
    } elseif ($categoryId > 0) {
        $allProducts = $productModel->getByCategory($categoryId);
    } else {
        $allProducts = $productModel->getAllWithCategory();
    }
    
    // Sort products
    $allProducts = sortProducts($allProducts, $sort);
    
    // Get categories for filter
    $categories = $productModel->getAllCategories();
    
    // Pagination
    $totalProducts = count($allProducts);
    $totalPages = ceil($totalProducts / $perPage);
    
    if ($page < 1) $page = 1;
    if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
    
    $offset = ($page - 1) * $perPage;
    $products = array_slice($allProducts, $offset, $perPage);
    
    // Get shop names for products
    foreach ($products as &$product) {
        if (!empty($product['shop_id'])) {
            $shop = $shopModel->getById($product['shop_id']);
            if ($shop) {
                $product['shop_name'] = $shop['shop_name'];
                $product['shop_slug'] = $shop['shop_slug'];
            }
        }
    }
    
} catch (Exception $e) {
    error_log('Products page error: ' . $e->getMessage());
    $products = [];
    $allProducts = [];
    $categories = [];
    $totalProducts = 0;
    $totalPages = 0;
    $page = 1;
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
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

// ============================================
// INCLUDE HEADER - THIS LOADS ALL CSS
// ============================================
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
            <p class="product-count">
                <?php echo $totalProducts; ?> products found
                <?php if (!empty($searchQuery)): ?>
                    for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <div class="filter-item">
                    <label for="categoryFilter">
                        <i class="fas fa-filter"></i> Category:
                    </label>
                    <select id="categoryFilter" class="filter-select" onchange="applyFilters()">
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
                    <select id="sortFilter" class="filter-select" onchange="applyFilters()">
                        <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?php echo ($sort == 'name') ? 'selected' : ''; ?>>Name: A to Z</option>
                    </select>
                </div>
            </div>
            
            <?php if (!empty($searchQuery)): ?>
                <a href="products.php" class="clear-search">
                    <i class="fas fa-times"></i> Clear Search
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Products Grid -->
        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        
                        <!-- Product Image -->
                        <div class="product-image">
                            <a href="product_details.php?id=<?php echo $product['id']; ?>">
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
                            
                            <!-- Vendor / Shop Info -->
                            <?php if (!empty($product['shop_name'])): ?>
                                <div class="product-vendor">
                                    <i class="fas fa-store"></i>
                                    <a href="shop/view.php?slug=<?php echo htmlspecialchars($product['shop_slug']); ?>">
                                        <?php echo htmlspecialchars($product['shop_name']); ?>
                                    </a>
                                    <span class="vendor-badge">Verified</span>
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
                            
                            <?php if (!empty($product['description'])): ?>
                                <p class="product-description">
                                    <?php 
                                    $description = strip_tags($product['description']);
                                    echo htmlspecialchars(substr($description, 0, 80)) . (strlen($description) > 80 ? '...' : '');
                                    ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Rating -->
                            <div class="product-rating">
                                <?php 
                                $rating = rand(3, 5);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <i class="fas fa-star <?php echo ($i <= $rating) ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                                <span class="rating-count">(<?php echo rand(10, 100); ?>)</span>
                            </div>
                            
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
                            
                            <!-- Add to Cart -->
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
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&category=<?php echo $categoryId; ?>&sort=<?php echo $sort; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                           class="page-btn">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <span class="page-btn disabled">
                            <i class="fas fa-chevron-left"></i> Previous
                        </span>
                    <?php endif; ?>
                    
                    <div class="page-numbers">
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?page=1&category=<?php echo $categoryId; ?>&sort=<?php echo $sort; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                               class="page-btn">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&category=<?php echo $categoryId; ?>&sort=<?php echo $sort; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                               class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $totalPages; ?>&category=<?php echo $categoryId; ?>&sort=<?php echo $sort; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                               class="page-btn"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&category=<?php echo $categoryId; ?>&sort=<?php echo $sort; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                           class="page-btn">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-btn disabled">
                            Next <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            
            <!-- No Products -->
            <div class="no-products">
                <div class="no-products-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <h2>No Products Found</h2>
                <p>
                    <?php if (!empty($searchQuery)): ?>
                        No products match "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>".
                    <?php elseif ($categoryId > 0): ?>
                        No products available in this category.
                    <?php else: ?>
                        No products are currently available.
                    <?php endif; ?>
                </p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> View All Products
                </a>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<!-- ============================================
     PRODUCTS PAGE STYLES
     ============================================ -->
<style>
.products-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
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
    margin: 8px auto 0;
    border-radius: 2px;
}

.product-count {
    color: #6c757d;
    font-size: 14px;
    margin-top: 8px;
}

.filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 12px;
}

.filter-group {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-item label {
    font-size: 14px;
    font-weight: 600;
    color: #4a5568;
}

.filter-item label i {
    color: #2C3E8F;
}

.filter-select {
    padding: 8px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    background: white;
    color: #1a1a2e;
    cursor: pointer;
    min-width: 150px;
}

.filter-select:focus {
    outline: none;
    border-color: #2C3E8F;
}

.clear-search {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #e74c3c;
    text-decoration: none;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
    border: 2px solid #fee2e2;
    background: #fff5f5;
    transition: all 0.3s ease;
}

.clear-search:hover {
    background: #fee2e2;
    border-color: #e74c3c;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
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
    transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
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

.product-description {
    font-size: 14px;
    color: #6c757d;
    line-height: 1.5;
    margin-bottom: 10px;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 8px;
}

.product-rating .fa-star {
    color: #e2e8f0;
    font-size: 14px;
}

.product-rating .fa-star.filled {
    color: #f39c12;
}

.rating-count {
    font-size: 12px;
    color: #6c757d;
    margin-left: 4px;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.current-price {
    font-size: 20px;
    font-weight: 700;
    color: #2C3E8F;
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

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.page-btn {
    padding: 8px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    color: #1a1a2e;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
}

.page-btn:hover:not(.disabled):not(.active) {
    border-color: #2C3E8F;
    color: #2C3E8F;
}

.page-btn.active {
    background: #2C3E8F;
    border-color: #2C3E8F;
    color: white;
}

.page-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-numbers {
    display: flex;
    gap: 4px;
}

.page-dots {
    display: flex;
    align-items: center;
    padding: 0 4px;
    color: #6c757d;
}

.no-products {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    grid-column: 1 / -1;
}

.no-products-icon {
    font-size: 64px;
    color: #cbd5e0;
    margin-bottom: 16px;
}

.no-products h2 {
    font-size: 24px;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.no-products p {
    color: #6c757d;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-select {
        width: 100%;
        min-width: unset;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .product-name {
        font-size: 14px;
    }
    
    .current-price {
        font-size: 17px;
    }
}
</style>

<script>
function applyFilters() {
    const category = document.getElementById('categoryFilter').value;
    const sort = document.getElementById('sortFilter').value;
    const search = '<?php echo !empty($searchQuery) ? $searchQuery : ''; ?>';
    
    let url = 'products.php?';
    if (category) url += 'category=' + category + '&';
    if (sort) url += 'sort=' + sort + '&';
    if (search) url += 'search=' + encodeURIComponent(search) + '&';
    
    url = url.replace(/[?&]$/, '');
    window.location.href = url;
}

document.addEventListener('DOMContentLoaded', function() {
    // Check if header loaded properly
    console.log('✅ Products page loaded successfully!');
    console.log('🔍 Total products found:', <?php echo $totalProducts; ?>);
    
    // Ensure cart.js is loaded
    const cartScript = document.querySelector('script[src*="cart.js"]');
    if (!cartScript) {
        console.warn('⚠️ cart.js not found in page!');
    }
});
</script>

<?php
// Include footer - cart.js is loaded here
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>