<?php
// ============================================================
// FILE: public/search.php
// PURPOSE: Search results page and AJAX endpoint
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load Product model
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// HANDLE AJAX REQUESTS
// ============================================

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == 1;
$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';

try {
    $productModel = new Product();
    
    // Get suggestions for autocomplete
    if (isset($_GET['suggest']) && $_GET['suggest'] == 1) {
        $suggestions = $productModel->getSuggestions($query, 8);
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['suggestions' => $suggestions]);
            exit;
        }
    }
    
    // Get search results
    $products = $productModel->searchAdvanced($query, $categoryId, $sort);
    $categories = $productModel->getAllCategories();
    
} catch (Exception $e) {
    error_log('Search error: ' . $e->getMessage());
    $products = [];
    $categories = [];
}

// ============================================
// RETURN JSON FOR AJAX REQUESTS
// ============================================

if ($isAjax) {
    header('Content-Type: application/json');
    
    // Format results for display
    $results = array_map(function($product) {
        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => number_format($product['price'], 2),
            'image' => !empty($product['image_url']) 
                ? SITE_URL . 'assets/uploads/products/' . $product['image_url']
                : SITE_URL . 'assets/images/no-image.png',
            'category' => $product['category_name'] ?? 'Uncategorized',
            'stock' => $product['stock_quantity'],
            'url' => SITE_URL . 'product_details.php?id=' . $product['id']
        ];
    }, $products);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results),
        'query' => $query
    ]);
    exit;
}

// ============================================
// PAGE SETUP
// ============================================

$pageTitle = !empty($query) ? 'Search: ' . $query : 'Search Products';

// Include header
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     SEARCH PAGE CONTENT
     ============================================ -->
<section class="search-page">
    <div class="container">
        
        <!-- Search Header -->
        <div class="search-header">
            <h1><?php echo !empty($query) ? 'Search Results for "' . htmlspecialchars($query) . '"' : 'Search Products'; ?></h1>
            <p class="result-count">
                <?php echo count($products); ?> product(s) found
            </p>
        </div>
        
        <!-- Search Filters -->
        <div class="search-filters">
            <div class="filter-group">
                <div class="filter-item">
                    <label for="categoryFilter">Category:</label>
                    <select id="categoryFilter" class="filter-select" onchange="updateSearch()">
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
                    <label for="sortFilter">Sort by:</label>
                    <select id="sortFilter" class="filter-select" onchange="updateSearch()">
                        <option value="relevance" <?php echo ($sort == 'relevance') ? 'selected' : ''; ?>>Relevance</option>
                        <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?php echo ($sort == 'name') ? 'selected' : ''; ?>>Name: A to Z</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Search Results -->
        <div id="searchResults">
            <?php if (!empty($products)): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
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
                                <?php else: ?>
                                    <span class="badge in-stock">In Stock</span>
                                <?php endif; ?>
                            </div>
                            
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
                                
                                <div class="product-price">
                                    <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
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
                                        <i class="fas fa-times-circle"></i> Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h2>No products found</h2>
                    <p>
                        <?php if (!empty($query)): ?>
                            No products match "<strong><?php echo htmlspecialchars($query); ?></strong>".
                        <?php else: ?>
                            Please enter a search term to find products.
                        <?php endif; ?>
                    </p>
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Browse All Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</section>

<?php
// Include footer
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>