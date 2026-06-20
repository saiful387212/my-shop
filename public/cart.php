<?php
// ============================================================
// FILE: public/cart.php
// PURPOSE: Display and manage shopping cart
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
// CART HELPER FUNCTIONS
// ============================================

/**
 * Get cart items from session
 */
function getCartItems() {
    return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
}

/**
 * Save cart items to session
 */
function saveCartItems($cart) {
    $_SESSION['cart'] = $cart;
}

/**
 * Get total number of items in cart
 */
function getCartTotalItems() {
    $cart = getCartItems();
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['quantity'];
    }
    return $total;
}

/**
 * Get cart subtotal (sum of all items)
 */
function getCartSubtotal() {
    $cart = getCartItems();
    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    return $subtotal;
}

/**
 * Add item to cart
 */
function addToCart($productId, $productName, $productPrice, $productImage = null) {
    $cart = getCartItems();
    
    // Check if product already exists in cart
    if (isset($cart[$productId])) {
        // Increase quantity
        $cart[$productId]['quantity'] += 1;
    } else {
        // Add new item
        $cart[$productId] = [
            'id' => $productId,
            'name' => $productName,
            'price' => $productPrice,
            'quantity' => 1,
            'image' => $productImage
        ];
    }
    
    saveCartItems($cart);
    return true;
}

/**
 * Remove item from cart
 */
function removeFromCart($productId) {
    $cart = getCartItems();
    
    if (isset($cart[$productId])) {
        unset($cart[$productId]);
        saveCartItems($cart);
        return true;
    }
    
    return false;
}

/**
 * Update item quantity
 */
function updateCartQuantity($productId, $quantity) {
    $cart = getCartItems();
    
    if (isset($cart[$productId])) {
        if ($quantity <= 0) {
            // Remove if quantity is 0 or less
            unset($cart[$productId]);
        } else {
            // Update quantity
            $cart[$productId]['quantity'] = $quantity;
        }
        saveCartItems($cart);
        return true;
    }
    
    return false;
}

/**
 * Clear entire cart
 */
function clearCart() {
    $_SESSION['cart'] = [];
    return true;
}

// ============================================
// PROCESS CART ACTIONS
// ============================================

$action = isset($_GET['action']) ? $_GET['action'] : '';
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';

// Process actions
switch ($action) {
    case 'add':
        // Add to cart
        $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $productName = isset($_GET['name']) ? urldecode($_GET['name']) : '';
        $productPrice = isset($_GET['price']) ? (float)$_GET['price'] : 0;
        $productImage = isset($_GET['image']) ? $_GET['image'] : null;
        
        if ($productId > 0 && !empty($productName) && $productPrice > 0) {
            addToCart($productId, $productName, $productPrice, $productImage);
            $message = 'Product added to cart!';
            $messageType = 'success';
            
            // Check if AJAX request
            if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
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
        // Remove from cart
        if ($productId > 0) {
            if (removeFromCart($productId)) {
                $message = 'Item removed from cart.';
                $messageType = 'success';
            }
        }
        break;
        
    case 'update':
        // Update quantity (AJAX)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
            
            if ($productId > 0) {
                updateCartQuantity($productId, $quantity);
                
                // Return JSON response for AJAX
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

// Get cart items
$cartItems = getCartItems();
$subtotal = getCartSubtotal();
$totalItems = getCartTotalItems();

// Calculate shipping (free over $50)
$shipping = 0;
$freeShippingThreshold = 50;
$showFreeShippingNotice = ($subtotal > 0 && $subtotal < $freeShippingThreshold);

if ($subtotal > 0 && $subtotal < $freeShippingThreshold) {
    $shipping = 5.00; // Flat rate shipping
}

// Calculate total
$total = $subtotal + $shipping;

// Set page title
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
            
            <!-- ============================================
                 CART TABLE
                 ============================================ -->
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
                            ? 'assets/uploads/products/' . htmlspecialchars($item['image']) 
                            : 'assets/images/no-image.png';
                        ?>
                        <div class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                            
                            <!-- Product -->
                            <div class="col-product">
                                <div class="product-info">
                                    <div class="product-image">
                                        <img src="<?php echo $imagePath; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             onerror="this.src='assets/images/no-image.png'">
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
                                            data-id="<?php echo $item['id']; ?>">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="qty-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="99"
                                           data-id="<?php echo $item['id']; ?>">
                                    <button class="qty-btn increase-qty" 
                                            data-id="<?php echo $item['id']; ?>">
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
                
                <!-- ============================================
                     CART SUMMARY
                     ============================================ -->
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
                    
                    <?php if ($showFreeShippingNotice): ?>
                        <div class="free-shipping-notice">
                            <i class="fas fa-truck"></i>
                            Add $<?php echo number_format($freeShippingThreshold - $subtotal, 2); ?> more for free shipping!
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="totalDisplay">$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="checkout.php" class="btn btn-primary checkout-btn">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary checkout-btn">
                            <i class="fas fa-sign-in-alt"></i> Login to Checkout
                        </a>
                        <p class="login-notice">Please login to complete your order.</p>
                    <?php endif; ?>
                    
                    <!-- Trust badges -->
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
            
            <!-- ============================================
                 EMPTY CART
                 ============================================ -->
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

<?php
// Include footer
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>