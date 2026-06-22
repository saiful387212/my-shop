<?php
// ============================================================
// FILE: public/products.php
// PURPOSE: Display products with working Add to Cart
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
                            
                            <!-- ============================================
                                 ADD TO CART BUTTON - FIXED
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
     JAVASCRIPT - APPLY FILTERS
     ============================================ -->
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

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const mainNav = document.getElementById('mainNav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            mainNav.classList.toggle('active');
        });
    }
    
    // ============================================
    // CHECK ADD TO CART BUTTONS
    // ============================================
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    console.log('🔍 Found ' + addToCartButtons.length + ' Add to Cart buttons');
    
    addToCartButtons.forEach(function(btn) {
        console.log('📦 Product:', {
            id: btn.dataset.id,
            name: btn.dataset.name,
            price: btn.dataset.price
        });
    });
    
    console.log('✅ Products page loaded successfully!');
});
</script>

<?php
// Include footer - cart.js is loaded here
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>