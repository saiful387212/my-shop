<?php
// ============================================================
// FILE: public/checkout.php
// PURPOSE: Simplified checkout - GUARANTEED TO WORK
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF CART HAS ITEMS
// ============================================

$cartItems = getCartItems();

if (empty($cartItems)) {
    $_SESSION['error_message'] = 'Your cart is empty.';
    header('Location: ' . SITE_URL . 'cart.php');
    exit;
}

// ============================================
// CALCULATE TOTALS
// ============================================

$subtotal = getCartSubtotal();
$shipping = ($subtotal > 0 && $subtotal < 50) ? 5.00 : 0;
$total = $subtotal + $shipping;

// ============================================
// PROCESS FORM
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
    
    // Validate
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($formData['full_name']) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters.';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    if (empty($formData['phone'])) {
        $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9+\-\s()]{10,20}$/', $formData['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    
    if (empty($formData['address'])) {
        $errors['address'] = 'Address is required.';
    }
    
    if (empty($formData['city'])) {
        $errors['city'] = 'City is required.';
    }
    
    if (empty($formData['postal_code'])) {
        $errors['postal_code'] = 'Postal code is required.';
    }
    
    // ============================================
    // PLACE ORDER - SIMPLIFIED
    // ============================================
    
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Get user ID
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $shippingAddress = $formData['address'] . ', ' . $formData['city'] . ', ' . $formData['postal_code'];
            
            // Calculate total
            $shippingAmount = ($subtotal > 0 && $subtotal < 50) ? 5.00 : 0;
            $totalAmount = $subtotal + $shippingAmount;
            
            // ============================================
            // START TRANSACTION
            // ============================================
            $pdo->beginTransaction();
            
            // ============================================
            // INSERT ORDER - SIMPLEST QUERY
            // ============================================
            $sql = "INSERT INTO orders (user_id, order_number, total_amount, shipping_address, status) 
                    VALUES (:user_id, :order_number, :total_amount, :shipping_address, :status)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $userId,
                ':order_number' => $orderNumber,
                ':total_amount' => $totalAmount,
                ':shipping_address' => $shippingAddress,
                ':status' => 'pending'
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create order.');
            }
            
            $orderId = $pdo->lastInsertId();
            
            // ============================================
            // INSERT ORDER ITEMS
            // ============================================
            foreach ($cartItems as $productId => $item) {
                $sql2 = "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity) 
                         VALUES (:order_id, :product_id, :product_name, :product_price, :quantity)";
                
                $stmt2 = $pdo->prepare($sql2);
                $stmt2->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $productId,
                    ':product_name' => $item['name'],
                    ':product_price' => $item['price'],
                    ':quantity' => $item['quantity']
                ]);
                
                // Update stock
                $stockSql = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id";
                $stockStmt = $pdo->prepare($stockSql);
                $stockStmt->execute([
                    ':quantity' => $item['quantity'],
                    ':product_id' => $productId
                ]);
            }
            
            // ============================================
            // CREATE VENDOR ORDERS - SIMPLIFIED
            // ============================================
            // Check if vendor_orders table exists
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'vendor_orders'");
            
            if ($tableCheck->rowCount() > 0) {
                // Group items by shop
                $shopItems = [];
                foreach ($cartItems as $productId => $item) {
                    $stmt3 = $pdo->prepare("SELECT shop_id FROM products WHERE id = :id");
                    $stmt3->execute([':id' => $productId]);
                    $product = $stmt3->fetch();
                    
                    if ($product && $product['shop_id']) {
                        $shopId = $product['shop_id'];
                        if (!isset($shopItems[$shopId])) {
                            $shopItems[$shopId] = [];
                        }
                        $shopItems[$shopId][] = $item;
                    }
                }
                
                // Create vendor orders
                foreach ($shopItems as $shopId => $items) {
                    $shopTotal = 0;
                    foreach ($items as $item) {
                        $shopTotal += $item['price'] * $item['quantity'];
                    }
                    
                    // Get vendor ID
                    $shopStmt = $pdo->prepare("SELECT user_id FROM shops WHERE id = :shop_id");
                    $shopStmt->execute([':shop_id' => $shopId]);
                    $shop = $shopStmt->fetch();
                    
                    // Insert vendor order
                    $vendorSql = "INSERT INTO vendor_orders (order_id, shop_id, vendor_id, total_amount, status) 
                                  VALUES (:order_id, :shop_id, :vendor_id, :total_amount, :status)";
                    
                    $vendorStmt = $pdo->prepare($vendorSql);
                    $vendorStmt->execute([
                        ':order_id' => $orderId,
                        ':shop_id' => $shopId,
                        ':vendor_id' => $shop['user_id'] ?? null,
                        ':total_amount' => $shopTotal,
                        ':status' => 'pending'
                    ]);
                }
            }
            
            // ============================================
            // COMMIT TRANSACTION
            // ============================================
            $pdo->commit();
            
            // ============================================
            // CLEAR CART
            // ============================================
            $_SESSION['cart'] = [];
            
            // ============================================
            // STORE ORDER INFO
            // ============================================
            $_SESSION['last_order_id'] = $orderId;
            $_SESSION['last_order_number'] = $orderNumber;
            
            // ============================================
            // REDIRECT
            // ============================================
            header('Location: ' . SITE_URL . 'order-success.php');
            exit;
            
        } catch (PDOException $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Checkout PDO Error: ' . $e->getMessage());
            error_log('Checkout Error Code: ' . $e->getCode());
            $errors['general'] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Checkout Error: ' . $e->getMessage());
            $errors['general'] = 'Error: ' . $e->getMessage();
        }
    }
}

// ============================================
// GET CATEGORIES FOR HEADER
// ============================================

$categories = [];
try {
    require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Checkout categories error: ' . $e->getMessage());
}

$pageTitle = 'Checkout';
$cartCount = getCartTotalItems();

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     CHECKOUT PAGE
     ============================================ -->
<section class="checkout-page">
    <div class="container">
        
        <div class="page-header">
            <h1>Checkout</h1>
            <p>Complete your order</p>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-container">
            
            <!-- Checkout Form -->
            <div class="checkout-form-wrapper">
                <h2>Shipping Information</h2>
                <p>Fill in your details to complete the order.</p>
                
                <form action="" method="POST">
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" 
                               class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                               placeholder="John Doe"
                               value="<?php echo htmlspecialchars($formData['full_name']); ?>" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['full_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" 
                               class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                               placeholder="john@example.com"
                               value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" 
                               class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                               placeholder="+1 (555) 123-4567"
                               value="<?php echo htmlspecialchars($formData['phone']); ?>" required>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['phone']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Address -->
                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <input type="text" id="address" name="address" 
                               class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>"
                               placeholder="123 Main Street"
                               value="<?php echo htmlspecialchars($formData['address']); ?>" required>
                        <?php if (isset($errors['address'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['address']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- City & Postal Code -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City <span class="required">*</span></label>
                            <input type="text" id="city" name="city" 
                                   class="form-control <?php echo isset($errors['city']) ? 'is-invalid' : ''; ?>"
                                   placeholder="New York"
                                   value="<?php echo htmlspecialchars($formData['city']); ?>" required>
                            <?php if (isset($errors['city'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['city']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="postal_code">Postal Code <span class="required">*</span></label>
                            <input type="text" id="postal_code" name="postal_code" 
                                   class="form-control <?php echo isset($errors['postal_code']) ? 'is-invalid' : ''; ?>"
                                   placeholder="10001"
                                   value="<?php echo htmlspecialchars($formData['postal_code']); ?>" required>
                            <?php if (isset($errors['postal_code'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['postal_code']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-place-order">
                        <i class="fas fa-lock"></i> Place Order
                    </button>
                    
                    <p class="secure-notice">
                        <i class="fas fa-shield-alt"></i> Your information is secure.
                    </p>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                
                <?php foreach ($cartItems as $item): ?>
                    <div class="summary-item">
                        <div class="item-info">
                            <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="item-quantity">× <?php echo $item['quantity']; ?></span>
                        </div>
                        <span class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?php echo ($shipping > 0) ? '$'.number_format($shipping, 2) : 'FREE'; ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</section>

<style>
.checkout-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.page-header { text-align: center; margin-bottom: 40px; }
.page-header h1 { font-size: 32px; font-weight: 800; color: #1a1a2e; }
.page-header h1::after { content: ''; display: block; width: 60px; height: 4px; background: #2C3E8F; margin: 10px auto 0; border-radius: 2px; }
.page-header p { color: #6c757d; font-size: 16px; margin-top: 8px; }

.alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
.alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.alert i { font-size: 20px; }

.checkout-container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
.checkout-form-wrapper { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #eef2f7; }
.checkout-form-wrapper h2 { font-size: 22px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
.checkout-form-wrapper p { color: #6c757d; margin-bottom: 20px; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-weight: 600; font-size: 14px; color: #1a1a2e; margin-bottom: 6px; }
.form-group label .required { color: #e74c3c; }
.form-control { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-family: 'Inter', sans-serif; transition: all 0.3s ease; background: #f8fafc; box-sizing: border-box; color: #1a1a2e; }
.form-control:focus { outline: none; border-color: #2C3E8F; background: white; box-shadow: 0 0 0 4px rgba(44,62,143,0.1); }
.form-control.is-invalid { border-color: #e74c3c; background: #fff5f5; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.error-message { color: #e74c3c; font-size: 13px; font-weight: 500; margin-top: 4px; display: flex; align-items: center; gap: 6px; }

.btn-place-order { width: 100%; padding: 14px; font-size: 16px; font-weight: 700; margin-top: 8px; border-radius: 10px; background: #2C3E8F; color: white; border: none; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-place-order:hover { background: #1a2a6c; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(44,62,143,0.3); }
.secure-notice { text-align: center; font-size: 13px; color: #6c757d; margin-top: 12px; }

.order-summary { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #eef2f7; height: fit-content; position: sticky; top: 100px; }
.order-summary h3 { font-size: 18px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #f0f0f0; }
.summary-items { margin-bottom: 16px; }
.summary-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
.summary-item:last-child { border-bottom: none; }
.summary-item .item-info { display: flex; gap: 8px; }
.summary-item .item-name { color: #1a1a2e; }
.summary-item .item-quantity { color: #6c757d; }
.summary-item .item-price { font-weight: 600; color: #2C3E8F; }

.summary-totals { border-top: 2px solid #f0f0f0; padding-top: 16px; }
.summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 15px; color: #4a5568; }
.summary-row.total { font-size: 20px; font-weight: 800; color: #1a1a2e; padding-top: 12px; border-top: 2px solid #f0f0f0; margin-top: 4px; }
.summary-row .free { color: #27ae60; font-weight: 700; }

@media (max-width: 1024px) { .checkout-container { grid-template-columns: 1fr; } .order-summary { position: static; } }
@media (max-width: 768px) { .checkout-page { padding: 30px 0 40px; } .form-row { grid-template-columns: 1fr; } .checkout-form-wrapper { padding: 24px; } .order-summary { padding: 24px; } }
@media (max-width: 480px) { .page-header h1 { font-size: 24px; } .checkout-form-wrapper { padding: 16px; } .order-summary { padding: 16px; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Checkout page loaded successfully!');
});
</script>

<?php
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>