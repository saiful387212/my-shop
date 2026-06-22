<?php
// ============================================================
// FILE: public/cart.php
// PURPOSE: Shopping cart page - COMPLETE FIXED VERSION
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

// Load Product model for categories
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// GET CART ITEMS
// ============================================

$cartItems = getCartItems();
$totalItems = getCartTotalItems();
$subtotal = getCartSubtotal();

// Calculate shipping
$shipping = ($subtotal > 0 && $subtotal < 50) ? 5.00 : 0;
$total = $subtotal + $shipping;

// ============================================
// PROCESS CART ACTIONS
// ============================================

$action = isset($_GET['action']) ? $_GET['action'] : '';
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';

switch ($action) {
    case 'add':
        $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $productName = isset($_GET['name']) ? urldecode($_GET['name']) : '';
        $productPrice = isset($_GET['price']) ? (float)$_GET['price'] : 0;
        $productImage = isset($_GET['image']) ? $_GET['image'] : null;
        
        if ($productId > 0 && !empty($productName) && $productPrice > 0) {
            addToCart($productId, $productName, $productPrice, $productImage);
            $message = 'Product added to cart!';
            $messageType = 'success';
            
            if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'cart_count' => getCartTotalItems(),
                    'cart_subtotal' => getCartSubtotal()
                ]);
                exit;
            }
        }
        break;
        
    case 'remove':
        if ($productId > 0) {
            if (removeFromCart($productId)) {
                $message = 'Item removed from cart.';
                $messageType = 'success';
            }
        }
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
            
            if ($productId > 0) {
                updateCartQuantity($productId, $quantity);
                
                // Return JSON response for AJAX
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'cart_total' => getCartTotalItems(),
                    'cart_subtotal' => getCartSubtotal(),
                    'item_subtotal' => $quantity > 0 ? getItemSubtotal($productId) : 0
                ]);
                exit;
            }
        }
        break;
        
    case 'clear':
        clearCart();
        $message = 'Cart cleared.';
        $messageType = 'info';
        break;
}

// Refresh cart data after actions
$cartItems = getCartItems();
$totalItems = getCartTotalItems();
$subtotal = getCartSubtotal();
$shipping = ($subtotal > 0 && $subtotal < 50) ? 5.00 : 0;
$total = $subtotal + $shipping;

// Get categories for navigation
$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Cart page error: ' . $e->getMessage());
}

$pageTitle = 'Shopping Cart';

// Include header
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     CART PAGE CONTENT
     ============================================ -->
<section class="cart-page">
    <div class="container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1>Shopping Cart</h1>
            <p><?php echo $totalItems; ?> items in your cart</p>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : ($messageType == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($cartItems)): ?>
            
            <div class="cart-container">
                
                <!-- Cart Items -->
                <div class="cart-items">
                    
                    <!-- Cart Header -->
                    <div class="cart-header">
                        <div class="col-product">Product</div>
                        <div class="col-price">Price</div>
                        <div class="col-quantity">Quantity</div>
                        <div class="col-subtotal">Subtotal</div>
                        <div class="col-actions">Actions</div>
                    </div>
                    
                    <!-- Cart Rows -->
                    <?php foreach ($cartItems as $item): ?>
                        <?php 
                        $itemSubtotal = $item['price'] * $item['quantity'];
                        $imagePath = !empty($item['image']) 
                            ? SITE_URL . 'uploads/products/' . htmlspecialchars($item['image']) 
                            : SITE_URL . 'assets/images/no-image.png';
                        ?>
                        <div class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                            
                            <!-- Product -->
                            <div class="col-product">
                                <div class="product-info">
                                    <div class="product-image">
                                        <img src="<?php echo $imagePath; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                                    </div>
                                    <div class="product-details">
                                        <h3 class="product-name">
                                            <a href="product_details.php?id=<?php echo $item['id']; ?>">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Price -->
                            <div class="col-price">
                                <span class="price">$<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            
                            <!-- Quantity -->
                            <div class="col-quantity">
                                <div class="quantity-control">
                                    <button class="qty-btn decrease-qty" 
                                            data-id="<?php echo $item['id']; ?>"
                                            type="button">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="qty-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="99"
                                           data-id="<?php echo $item['id']; ?>">
                                    <button class="qty-btn increase-qty" 
                                            data-id="<?php echo $item['id']; ?>"
                                            type="button">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Subtotal -->
                            <div class="col-subtotal">
                                <span class="item-subtotal" data-id="<?php echo $item['id']; ?>">
                                    $<?php echo number_format($itemSubtotal, 2); ?>
                                </span>
                            </div>
                            
                            <!-- Actions -->
                            <div class="col-actions">
                                <a href="?action=remove&id=<?php echo $item['id']; ?>" 
                                   class="remove-btn" 
                                   onclick="return confirm('Are you sure you want to remove this item?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Cart Footer -->
                    <div class="cart-footer">
                        <a href="?action=clear" class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to clear your entire cart?')">
                            <i class="fas fa-trash"></i> Clear Cart
                        </a>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>
                
                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotalDisplay">$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span id="shippingDisplay">
                            <?php if ($shipping > 0): ?>
                                $<?php echo number_format($shipping, 2); ?>
                            <?php else: ?>
                                <?php if ($subtotal > 0): ?>
                                    <span class="free-shipping">FREE</span>
                                <?php else: ?>
                                    $0.00
                                <?php endif; ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($subtotal > 0 && $subtotal < 50): ?>
                        <div class="free-shipping-notice">
                            <i class="fas fa-truck"></i>
                            Add $<?php echo number_format(50 - $subtotal, 2); ?> more for free shipping!
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="totalDisplay">$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                        <a href="checkout.php" class="btn btn-primary checkout-btn">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary checkout-btn">
                            <i class="fas fa-sign-in-alt"></i> Login to Checkout
                        </a>
                        <p class="login-notice">Please login to complete your order.</p>
                    <?php endif; ?>
                    
                    <!-- Trust Badges -->
                    <div class="trust-badges">
                        <div class="trust-badge">
                            <i class="fas fa-lock"></i>
                            <span>Secure Payment</span>
                        </div>
                        <div class="trust-badge">
                            <i class="fas fa-undo-alt"></i>
                            <span>30-Day Returns</span>
                        </div>
                        <div class="trust-badge">
                            <i class="fas fa-headset"></i>
                            <span>24/7 Support</span>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            
            <!-- Empty Cart -->
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Start Shopping
                </a>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<!-- ============================================
     CART JAVASCRIPT - COMPLETE FIX
     ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    console.log('✅ Cart page loaded');
    
    // ============================================
    // QUANTITY CONTROLS
    // ============================================
    
    const quantityInputs = document.querySelectorAll('.qty-input');
    const decreaseBtns = document.querySelectorAll('.decrease-qty');
    const increaseBtns = document.querySelectorAll('.increase-qty');
    
    /**
     * Update cart item quantity via AJAX
     */
    function updateCartQuantity(productId, quantity) {
        // Show loading state
        const item = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
        if (item) {
            item.style.opacity = '0.6';
        }
        
        // Send AJAX request
        fetch('cart.php?action=update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update subtotal for this item
                const subtotalElement = document.querySelector(`.item-subtotal[data-id="${productId}"]`);
                if (subtotalElement) {
                    subtotalElement.textContent = '$' + data.item_subtotal.toFixed(2);
                }
                
                // Update cart totals
                document.getElementById('subtotalDisplay').textContent = '$' + data.cart_subtotal.toFixed(2);
                document.getElementById('totalDisplay').textContent = '$' + (data.cart_subtotal + <?php echo $shipping; ?>).toFixed(2);
                
                // Update cart count in header
                updateCartCount(data.cart_total);
            }
            
            // Remove loading state
            if (item) {
                item.style.opacity = '1';
            }
        })
        .catch(error => {
            console.error('Error updating cart:', error);
            if (item) {
                item.style.opacity = '1';
            }
        });
    }
    
    /**
     * Update cart count in header
     */
    function updateCartCount(count) {
        const cartCount = document.getElementById('cartCount');
        if (cartCount) {
            cartCount.textContent = count;
        }
    }
    
    // ============================================
    // DECREASE QUANTITY
    // ============================================
    
    decreaseBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            const input = document.querySelector(`.qty-input[data-id="${productId}"]`);
            if (input) {
                let currentValue = parseInt(input.value) || 1;
                if (currentValue > 1) {
                    currentValue--;
                    input.value = currentValue;
                    updateCartQuantity(productId, currentValue);
                }
            }
        });
    });
    
    // ============================================
    // INCREASE QUANTITY
    // ============================================
    
    increaseBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            const input = document.querySelector(`.qty-input[data-id="${productId}"]`);
            if (input) {
                let currentValue = parseInt(input.value) || 1;
                if (currentValue < 99) {
                    currentValue++;
                    input.value = currentValue;
                    updateCartQuantity(productId, currentValue);
                }
            }
        });
    });
    
    // ============================================
    // MANUAL QUANTITY INPUT
    // ============================================
    
    quantityInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-id');
            let value = parseInt(this.value) || 1;
            
            if (value < 1) value = 1;
            if (value > 99) value = 99;
            
            this.value = value;
            updateCartQuantity(productId, value);
        });
        
        // Prevent invalid input
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                this.blur();
            }
        });
    });
    
    console.log('✅ Cart controls ready!');
});
</script>

<!-- ============================================
     ADDITIONAL CART STYLES
     ============================================ -->
<style>
/* ============================================
   CART PAGE
   ============================================ */

.cart-page {
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
   PAGE HEADER
   ============================================ */

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 32px;
    font-weight: 800;
    color: #1a1a2e;
    font-family: 'Inter', sans-serif;
}

.page-header h1::after {
    content: '';
    display: block;
    width: 60px;
    height: 4px;
    background: #2C3E8F;
    margin: 10px auto 0;
    border-radius: 2px;
}

.page-header p {
    color: #6c757d;
    font-size: 16px;
    margin-top: 8px;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   ALERT MESSAGES
   ============================================ */

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-info {
    background: #e0f2fe;
    color: #075985;
    border: 1px solid #bae6fd;
}

.alert i {
    font-size: 20px;
}

/* ============================================
   CART CONTAINER
   ============================================ */

.cart-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

/* ============================================
   CART ITEMS
   ============================================ */

.cart-items {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
}

.cart-header {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1fr 60px;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 2px solid #f0f0f0;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    font-family: 'Inter', sans-serif;
}

.cart-item {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1fr 60px;
    gap: 12px;
    padding: 16px 0;
    border-bottom: 1px solid #f0f0f0;
    align-items: center;
}

.cart-item:last-child {
    border-bottom: none;
}

.col-product .product-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.product-image {
    width: 70px;
    height: 70px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f8fafc;
    border: 1px solid #eef2f7;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-details .product-name {
    font-size: 14px;
    font-weight: 600;
    color: #1a1a2e;
    font-family: 'Inter', sans-serif;
}

.product-details .product-name a {
    color: inherit;
    text-decoration: none;
}

.product-details .product-name a:hover {
    color: #2C3E8F;
}

.col-price .price {
    font-weight: 600;
    color: #1a1a2e;
    font-family: 'Inter', sans-serif;
}

/* Quantity Control */
.quantity-control {
    display: flex;
    align-items: center;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    width: fit-content;
}

.qty-btn {
    padding: 6px 12px;
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

.qty-input {
    width: 40px;
    padding: 6px 0;
    border: none;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    background: white;
    -moz-appearance: textfield;
}

.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.col-subtotal .item-subtotal {
    font-weight: 700;
    color: #2C3E8F;
    font-family: 'Inter', sans-serif;
}

.remove-btn {
    color: #e74c3c;
    font-size: 18px;
    transition: all 0.3s ease;
}

.remove-btn:hover {
    color: #c0392b;
    transform: scale(1.2);
}

.cart-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 2px solid #f0f0f0;
    margin-top: 8px;
    flex-wrap: wrap;
    gap: 12px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #2C3E8F;
    color: white;
}

.btn-primary:hover {
    background: #1a2a6c;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(44, 62, 143, 0.3);
}

.btn-secondary {
    background: #e2e8f0;
    color: #1a1a2e;
}

.btn-secondary:hover {
    background: #cbd5e0;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.checkout-btn {
    width: 100%;
    justify-content: center;
    padding: 14px;
    font-size: 16px;
    margin-top: 12px;
}

/* ============================================
   CART SUMMARY
   ============================================ */

.cart-summary {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    height: fit-content;
    position: sticky;
    top: 100px;
}

.cart-summary h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
    font-family: 'Inter', sans-serif;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 15px;
    color: #4a5568;
    font-family: 'Inter', sans-serif;
}

.summary-row.total {
    font-size: 20px;
    font-weight: 800;
    color: #1a1a2e;
    padding-top: 12px;
    border-top: 2px solid #f0f0f0;
    margin-top: 8px;
}

.free-shipping {
    color: #27ae60;
    font-weight: 700;
}

.free-shipping-notice {
    background: #e6f7ed;
    color: #065f46;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin: 8px 0 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Inter', sans-serif;
}

.free-shipping-notice i {
    color: #27ae60;
}

.login-notice {
    text-align: center;
    font-size: 13px;
    color: #6c757d;
    margin-top: 8px;
    font-family: 'Inter', sans-serif;
}

.trust-badges {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 2px solid #f0f0f0;
}

.trust-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #6c757d;
    text-align: center;
    font-family: 'Inter', sans-serif;
}

.trust-badge i {
    font-size: 20px;
    color: #2C3E8F;
}

/* ============================================
   EMPTY CART
   ============================================ */

.empty-cart {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
}

.empty-cart-icon {
    font-size: 80px;
    color: #cbd5e0;
    margin-bottom: 20px;
}

.empty-cart-icon i {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

.empty-cart h2 {
    font-size: 24px;
    color: #1a1a2e;
    margin-bottom: 12px;
    font-family: 'Inter', sans-serif;
}

.empty-cart p {
    color: #6c757d;
    margin-bottom: 24px;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1024px) {
    .cart-container {
        grid-template-columns: 1fr;
    }
    
    .cart-summary {
        position: static;
    }
}

@media (max-width: 768px) {
    .cart-page {
        padding: 30px 0 40px;
    }
    
    .cart-header {
        display: none;
    }
    
    .cart-item {
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 16px 0;
    }
    
    .col-product .product-info {
        gap: 12px;
    }
    
    .product-image {
        width: 60px;
        height: 60px;
    }
    
    .col-price,
    .col-quantity,
    .col-subtotal,
    .col-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
    }
    
    .col-price::before {
        content: 'Price: ';
        font-weight: 600;
        color: #6c757d;
    }
    
    .col-quantity::before {
        content: 'Quantity: ';
        font-weight: 600;
        color: #6c757d;
    }
    
    .col-subtotal::before {
        content: 'Subtotal: ';
        font-weight: 600;
        color: #6c757d;
    }
    
    .col-actions {
        justify-content: flex-end;
        padding-top: 8px;
        border-top: 1px solid #f0f0f0;
    }
    
    .cart-footer {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 480px) {
    .page-header h1 {
        font-size: 24px;
    }
    
    .cart-items {
        padding: 16px;
    }
    
    .product-image {
        width: 50px;
        height: 50px;
    }
    
    .trust-badges {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}
</style>

<?php
// Include footer
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>