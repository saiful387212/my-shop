<?php
// ============================================================
// FILE: public/test-add-to-cart.php
// PURPOSE: Test add to cart functionality
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if we're adding a product
$message = '';
if (isset($_GET['add'])) {
    $id = (int)$_GET['add'];
    $name = $_GET['name'] ?? 'Test Product';
    $price = (float)($_GET['price'] ?? 19.99);
    
    addToCart($id, $name, $price);
    $message = "✅ Added: $name";
}

// Get cart items
$cartItems = getCartItems();
$totalItems = getCartTotalItems();
$subtotal = getCartSubtotal();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Add to Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .btn { padding: 10px 20px; background: #2C3E8F; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #1a2a6c; }
        .btn-success { background: #27ae60; }
        .btn-danger { background: #e74c3c; }
        .cart-item { padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; }
        .success { background: #d1fae5; padding: 10px; border-radius: 5px; color: #065f46; }
        .cart-count { background: #e74c3c; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛒 Test Add to Cart</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <h2>📦 Products</h2>
        <div>
            <a href="?add=1&name=Wireless%20Headphones&price=79.99" class="btn">Add Headphones ($79.99)</a>
            <a href="?add=2&name=Smartphone&price=699.00" class="btn">Add Smartphone ($699.00)</a>
            <a href="?add=3&name=T-Shirt&price=19.95" class="btn">Add T-Shirt ($19.95)</a>
            <a href="?clear=1" class="btn btn-danger">Clear Cart</a>
        </div>
        
        <?php if (isset($_GET['clear'])): ?>
            <?php clearCart(); ?>
            <p style="color:orange;">✅ Cart cleared!</p>
        <?php endif; ?>
        
        <h2>🛒 Your Cart (<span id="cartCount"><?php echo $totalItems; ?></span> items)</h2>
        
        <?php if (!empty($cartItems)): ?>
            <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <span><strong><?php echo htmlspecialchars($item['name']); ?></strong> × <?php echo $item['quantity']; ?></span>
                    <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            <div style="text-align:right;padding:10px;font-weight:bold;font-size:18px;">
                Subtotal: $<?php echo number_format($subtotal, 2); ?>
            </div>
        <?php else: ?>
            <p style="color:#6c757d;">Cart is empty. Add some products above!</p>
        <?php endif; ?>
        
        <hr>
        <div style="margin-top:20px;">
            <a href="cart.php" class="btn">View Full Cart</a>
            <a href="products.php" class="btn btn-success">Go to Products Page</a>
            <a href="checkout.php" class="btn">Checkout</a>
        </div>
    </div>
</body>
</html>