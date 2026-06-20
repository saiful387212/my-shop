<?php
// ============================================================
// FILE: public/checkout.php
// PURPOSE: Collect customer information and place order
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load models
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'OrderItem.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF CART HAS ITEMS
// ============================================

$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

if (empty($cartItems)) {
    $_SESSION['error_message'] = 'Your cart is empty. Please add items before checkout.';
    header('Location: ' . SITE_URL . 'cart.php');
    exit;
}

// ============================================
// CALCULATE CART TOTALS
// ============================================

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping = ($subtotal > 0 && $subtotal < 50) ? 5.00 : 0;
$total = $subtotal + $shipping;

// ============================================
// PROCESS FORM SUBMISSION
// ============================================

$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'postal_code' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data
    $formData['full_name'] = sanitize($_POST['full_name'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['phone'] = sanitize($_POST['phone'] ?? '');
    $formData['address'] = sanitize($_POST['address'] ?? '');
    $formData['city'] = sanitize($_POST['city'] ?? '');
    $formData['postal_code'] = sanitize($_POST['postal_code'] ?? '');
    
    // ============================================
    // VALIDATION
    // ============================================
    
    // Full Name
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($formData['full_name']) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters.';
    }
    
    // Email
    if (empty($formData['email'])) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    // Phone
    if (empty($formData['phone'])) {
        $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9+\-\s()]{10,20}$/', $formData['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    
    // Address
    if (empty($formData['address'])) {
        $errors['address'] = 'Address is required.';
    }
    
    // City
    if (empty($formData['city'])) {
        $errors['city'] = 'City is required.';
    }
    
    // Postal Code
    if (empty($formData['postal_code'])) {
        $errors['postal_code'] = 'Postal code is required.';
    } elseif (!preg_match('/^[0-9]{5,10}$/', $formData['postal_code'])) {
        $errors['postal_code'] = 'Please enter a valid postal code.';
    }
    
    // ============================================
    // PLACE ORDER
    // ============================================
    
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            // ============================================
            // START TRANSACTION
            // ============================================
            $pdo->beginTransaction();
            
            // ============================================
            // STEP 1: Generate Order Number
            // ============================================
            $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // ============================================
            // STEP 2: Calculate Total
            // ============================================
            $shippingAmount = ($subtotal > 0 && $subtotal < 50) ? 5.00 : 0;
            $totalAmount = $subtotal + $shippingAmount;
            
            // ============================================
            // STEP 3: Insert Order
            // ============================================
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $shippingAddress = $formData['address'] . ', ' . $formData['city'] . ', ' . $formData['postal_code'];
            
            $stmt = $pdo->prepare('
                INSERT INTO orders (
                    user_id, 
                    order_number, 
                    total_amount, 
                    shipping_address,
                    status,
                    created_at
                ) VALUES (
                    :user_id,
                    :order_number,
                    :total_amount,
                    :shipping_address,
                    :status,
                    NOW()
                )
            ');
            
            $result = $stmt->execute([
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'shipping_address' => $shippingAddress,
                'status' => 'pending'
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create order.');
            }
            
            // Get the order ID
            $orderId = $pdo->lastInsertId();
            
            // ============================================
            // STEP 4: Insert Order Items
            // ============================================
            foreach ($cartItems as $productId => $item) {
                $stmt = $pdo->prepare('
                    INSERT INTO order_items (
                        order_id,
                        product_id,
                        product_name,
                        product_price,
                        quantity
                    ) VALUES (
                        :order_id,
                        :product_id,
                        :product_name,
                        :product_price,
                        :quantity
                    )
                ');
                
                $stmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'product_name' => $item['name'],
                    'product_price' => $item['price'],
                    'quantity' => $item['quantity']
                ]);
                
                // ============================================
                // STEP 5: Update Stock
                // ============================================
                $stockStmt = $pdo->prepare('
                    UPDATE products 
                    SET stock_quantity = stock_quantity - :quantity 
                    WHERE id = :product_id 
                    AND stock_quantity >= :quantity
                ');
                
                $stockStmt->execute([
                    'quantity' => $item['quantity'],
                    'product_id' => $productId
                ]);
            }
            
            // ============================================
            // STEP 6: Commit Transaction
            // ============================================
            $pdo->commit();
            
            // ============================================
            // STEP 7: Clear Cart
            // ============================================
            $_SESSION['cart'] = [];
            
            // ============================================
            // STEP 8: Store Order Info for Success Page
            // ============================================
            $_SESSION['last_order_id'] = $orderId;
            $_SESSION['last_order_number'] = $orderNumber;
            
            // ============================================
            // STEP 9: Redirect to Success Page
            // ============================================
            header('Location: ' . SITE_URL . 'order-success.php');
            exit;
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Checkout error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred while processing your order. Please try again.';
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Checkout error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
}

// ============================================
// SET PAGE TITLE
// ============================================

$pageTitle = 'Checkout';

// Include header
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     CHECKOUT PAGE CONTENT
     ============================================ -->
<section class="checkout-page">
    <div class="container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1>Checkout</h1>
            <p>Complete your order</p>
        </div>
        
        <!-- Error Messages -->
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-container">
            
            <!-- ============================================
                 CHECKOUT FORM
                 ============================================ -->
            <div class="checkout-form-wrapper">
                <form action="" method="POST" class="checkout-form" id="checkoutForm">
                    
                    <h3>Shipping Information</h3>
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                               placeholder="John Doe"
                               value="<?php echo htmlspecialchars($formData['full_name']); ?>"
                               required>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['full_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                               placeholder="john@example.com"
                               value="<?php echo htmlspecialchars($formData['email']); ?>"
                               required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                               placeholder="+1 (555) 123-4567"
                               value="<?php echo htmlspecialchars($formData['phone']); ?>"
                               required>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['phone']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Address -->
                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <input type="text" 
                               id="address" 
                               name="address" 
                               class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>"
                               placeholder="123 Main Street"
                               value="<?php echo htmlspecialchars($formData['address']); ?>"
                               required>
                        <?php if (isset($errors['address'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['address']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- City & Postal Code Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City <span class="required">*</span></label>
                            <input type="text" 
                                   id="city" 
                                   name="city" 
                                   class="form-control <?php echo isset($errors['city']) ? 'is-invalid' : ''; ?>"
                                   placeholder="New York"
                                   value="<?php echo htmlspecialchars($formData['city']); ?>"
                                   required>
                            <?php if (isset($errors['city'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['city']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="postal_code">Postal Code <span class="required">*</span></label>
                            <input type="text" 
                                   id="postal_code" 
                                   name="postal_code" 
                                   class="form-control <?php echo isset($errors['postal_code']) ? 'is-invalid' : ''; ?>"
                                   placeholder="10001"
                                   value="<?php echo htmlspecialchars($formData['postal_code']); ?>"
                                   required>
                            <?php if (isset($errors['postal_code'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['postal_code']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-place-order" id="placeOrderBtn">
                        <i class="fas fa-lock"></i> Place Order
                    </button>
                    
                    <p class="secure-notice">
                        <i class="fas fa-shield-alt"></i>
                        Your information is secure and encrypted.
                    </p>
                </form>
            </div>
            
            <!-- ============================================
                 ORDER SUMMARY
                 ============================================ -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                
                <!-- Items -->
                <div class="summary-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="summary-item">
                            <div class="item-info">
                                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="item-quantity">× <?php echo $item['quantity']; ?></span>
                            </div>
                            <span class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Totals -->
                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>
                            <?php if ($shipping > 0): ?>
                                $<?php echo number_format($shipping, 2); ?>
                            <?php else: ?>
                                <span class="free">FREE</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($shipping > 0): ?>
                        <div class="shipping-notice">
                            <i class="fas fa-truck"></i>
                            Add $<?php echo number_format(50 - $subtotal, 2); ?> more for free shipping!
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
                
                <!-- Trust Badges -->
                <div class="trust-badges">
                    <div class="trust-badge">
                        <i class="fas fa-lock"></i>
                        <span>Secure Checkout</span>
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
        
    </div>
</section>

<?php
// Include footer
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>