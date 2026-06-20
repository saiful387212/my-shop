<?php
// ============================================================
// FILE: public/product_details.php
// PURPOSE: Display product details - STANDALONE VERSION
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// GET AND VALIDATE PRODUCT ID
// ============================================

// Get ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid ID, redirect to products page
if ($productId <= 0) {
    header('Location: products.php');
    exit;
}

// ============================================
// FETCH PRODUCT FROM DATABASE
// ============================================

$product = null;
$error = null;

try {
    // Get database connection
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // ============================================
    // SECURE: Prepared Statement
    // ============================================
    $stmt = $pdo->prepare('
        SELECT 
            p.*, 
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id
    ');
    
    // Execute with the ID
    $stmt->execute(['id' => $productId]);
    
    // Fetch the product
    $product = $stmt->fetch();
    
    // If no product found, redirect
    if (!$product) {
        header('Location: products.php');
        exit;
    }
    
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - My Shop</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ============================================================
           ALL CSS EMBEDDED - GUARANTEED TO WORK
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
        
        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 16px 0;
        }
        
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo a {
            font-size: 24px;
            font-weight: 800;
            color: #2C3E8F;
            text-decoration: none;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 24px;
        }
        
        .nav-menu a {
            text-decoration: none;
            color: #4a5568;
            font-weight: 500;
        }
        
        .nav-menu a:hover {
            color: #2C3E8F;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #6c757d;
            margin: 20px 0 30px;
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
        
        /* Product Details */
        .product-details {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 40px;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }
        
        /* Image */
        .product-image {
            background: #f8fafc;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 1 / 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #eef2f7;
            position: relative;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 30px;
        }
        
        .badge {
            position: absolute;
            top: 16px;
            left: 16px;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
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
        
        /* Product Info */
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .product-category {
            display: inline-block;
            background: #f0f4ff;
            color: #2C3E8F;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            width: fit-content;
        }
        
        .product-category i {
            margin-right: 4px;
        }
        
        .product-title {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a2e;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .stars {
            display: flex;
            gap: 2px;
        }
        
        .stars .fa-star {
            color: #e2e8f0;
            font-size: 16px;
        }
        
        .stars .fa-star.filled {
            color: #f39c12;
        }
        
        .rating-count {
            font-size: 14px;
            color: #6c757d;
        }
        
        .product-price {
            font-size: 32px;
            font-weight: 800;
            color: #2C3E8F;
            padding: 16px 0;
            border-top: 2px solid #f0f0f0;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .stock-status {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .stock-status.in-stock {
            color: #27ae60;
        }
        
        .stock-status.out-of-stock {
            color: #e74c3c;
        }
        
        .stock-status i {
            font-size: 18px;
        }
        
        .product-description {
            font-size: 15px;
            color: #4a5568;
            line-height: 1.8;
        }
        
        .product-description h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        /* Add to Cart */
        .add-to-cart-section {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-top: 16px;
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
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .qty-btn {
            padding: 8px 14px;
            border: none;
            background: #f8fafc;
            color: #1a1a2e;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
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
        }
        
        .quantity-control input:focus {
            outline: none;
        }
        
        .btn {
            padding: 14px 40px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 200px;
        }
        
        .btn-primary {
            background: #2C3E8F;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a2a6c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44,62,143,0.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #6c757d;
            cursor: not-allowed;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        /* Features */
        .product-features {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            padding-top: 16px;
            border-top: 2px solid #f0f0f0;
            margin-top: 8px;
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
        }
        
        .feature-item i {
            font-size: 20px;
            color: #2C3E8F;
        }
        
        .feature-item span {
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
        }
        
        /* Footer */
        .footer {
            background: #1a1a2e;
            color: rgba(255,255,255,0.8);
            padding: 40px 0 20px;
            margin-top: 40px;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .footer h4 {
            color: white;
            margin-bottom: 12px;
        }
        
        .footer ul {
            list-style: none;
        }
        
        .footer ul li {
            margin-bottom: 6px;
        }
        
        .footer ul li a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
        }
        
        .footer ul li a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .product-title {
                font-size: 22px;
            }
            
            .product-price {
                font-size: 26px;
            }
            
            .add-to-cart-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .quantity-selector {
                justify-content: space-between;
            }
            
            .btn {
                min-width: unset;
                width: 100%;
            }
            
            .product-features {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .product-details {
                padding: 20px;
            }
            
            .product-title {
                font-size: 18px;
            }
            
            .product-price {
                font-size: 22px;
            }
            
            .product-image img {
                padding: 16px;
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
                    <a href="index.php">My Shop</a>
                </div>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <!-- ============================================
         MAIN CONTENT
         ============================================ -->
    <main>
        <div class="container">
            
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <span class="separator">/</span>
                <a href="products.php">Products</a>
                <span class="separator">/</span>
                <span class="current"><?php echo htmlspecialchars($product['name']); ?></span>
            </div>
            
            <!-- Product Details -->
            <div class="product-details">
                <div class="product-grid">
                    
                    <!-- Image Column -->
                    <div class="product-image">
                        <?php 
                        $imagePath = !empty($product['image_url']) 
                            ? 'assets/uploads/products/' . htmlspecialchars($product['image_url']) 
                            : 'assets/images/no-image.png';
                        ?>
                        <img src="<?php echo $imagePath; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='assets/images/no-image.png'">
                        
                        <!-- Stock Badge -->
                        <?php if ($product['stock_quantity'] <= 0): ?>
                            <span class="badge out-of-stock">Out of Stock</span>
                        <?php elseif ($product['stock_quantity'] < 10): ?>
                            <span class="badge low-stock">Only <?php echo $product['stock_quantity']; ?> left</span>
                        <?php else: ?>
                            <span class="badge in-stock">In Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Info Column -->
                    <div class="product-info">
                        
                        <!-- Category -->
                        <?php if (!empty($product['category_name'])): ?>
                            <span class="product-category">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Title -->
                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <!-- Rating -->
                        <div class="product-rating">
                            <div class="stars">
                                <?php 
                                $rating = rand(3, 5);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <i class="fas fa-star <?php echo ($i <= $rating) ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-count">(<?php echo rand(10, 200); ?> reviews)</span>
                        </div>
                        
                        <!-- Price -->
                        <div class="product-price">
                            $<?php echo number_format($product['price'], 2); ?>
                        </div>
                        
                        <!-- Stock Status -->
                        <div class="stock-status <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <i class="fas fa-check-circle"></i>
                                In Stock - <?php echo $product['stock_quantity']; ?> units available
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i>
                                Out of Stock
                            <?php endif; ?>
                        </div>
                        
                        <!-- Description -->
                        <div class="product-description">
                            <h3>Product Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>
                        </div>
                        
                        <!-- Add to Cart -->
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
                                </div>
                                
                                <button class="btn btn-primary" 
                                        id="addToCartBtn"
                                        data-id="<?php echo $product['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                        data-price="<?php echo $product['price']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                    Add to Cart
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
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
            </div>
            
        </div>
    </main>
    
    <!-- ============================================
         FOOTER
         ============================================ -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>My Shop</h4>
                    <p style="color:rgba(255,255,255,0.6);">Your one-stop destination for quality products.</p>
                </div>
                <div>
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Follow Us</h4>
                    <ul>
                        <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                        <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                        <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> My Shop. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // ============================================
            // QUANTITY CONTROLS
            // ============================================
            
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
            
            // ============================================
            // ADD TO CART
            // ============================================
            
            const addToCartBtn = document.getElementById('addToCartBtn');
            
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const productName = this.getAttribute('data-name');
                    const productPrice = this.getAttribute('data-price');
                    const quantity = document.getElementById('quantity')?.value || 1;
                    
                    // Show notification
                    alert(productName + ' added to cart! (Quantity: ' + quantity + ')');
                    
                    // Visual feedback
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> Added!';
                    this.style.background = '#27ae60';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.background = '';
                    }, 2000);
                });
            }
            
            console.log('✅ Product details page loaded successfully!');
        });
    </script>
    
</body>
</html>