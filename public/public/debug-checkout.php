<?php
// ============================================================
// FILE: public/debug-checkout.php
// PURPOSE: Debug checkout errors
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Checkout Debug</h1>";

// Check if cart has items
$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
echo "<h2>Cart Items</h2>";
echo "<pre>";
print_r($cartItems);
echo "</pre>";

if (empty($cartItems)) {
    echo "❌ Cart is empty!<br>";
    echo "<a href='products.php'>Add items to cart</a>";
    exit;
}

// Check database connection
$pdo = getDbConnection();
if ($pdo === null) {
    die("❌ Database connection failed.");
}
echo "✅ Database connected.<br><br>";

// ============================================
// TEST: Check orders table structure
// ============================================
echo "<h2>Orders Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(', ', $columns) . "<br>";
    
    $requiredColumns = ['id', 'user_id', 'order_number', 'total_amount', 'shipping_address', 'status', 'created_at'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ Column '$col' exists<br>";
        } else {
            echo "❌ Column '$col' MISSING!<br>";
        }
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// ============================================
// TEST: Check order_items table structure
// ============================================
echo "<h2>Order Items Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE order_items");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(', ', $columns) . "<br>";
    
    $requiredColumns = ['id', 'order_id', 'product_id', 'product_name', 'product_price', 'quantity'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ Column '$col' exists<br>";
        } else {
            echo "❌ Column '$col' MISSING!<br>";
        }
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// ============================================
// TEST: Try to insert a test order
// ============================================
echo "<h2>Test Insert</h2>";

$testOrderNumber = 'TEST-' . date('Ymd') . '-' . rand(1000, 9999);

try {
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id, 
            order_number, 
            total_amount, 
            shipping_address,
            status
        ) VALUES (
            :user_id,
            :order_number,
            :total_amount,
            :shipping_address,
            :status
        )
    ");
    
    $result = $stmt->execute([
        ':user_id' => null,
        ':order_number' => $testOrderNumber,
        ':total_amount' => 99.99,
        ':shipping_address' => '123 Test St, Test City, 12345',
        ':status' => 'pending'
    ]);
    
    if ($result) {
        $testOrderId = $pdo->lastInsertId();
        echo "✅ Test order inserted! ID: $testOrderId<br>";
        
        // Clean up test order
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :id");
        $stmt->execute([':id' => $testOrderId]);
        echo "✅ Test order deleted.<br>";
    } else {
        echo "❌ Test order failed.<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
}

echo "<br><br>";
echo "<a href='checkout.php'>Go to Checkout</a> | ";
echo "<a href='cart.php'>Go to Cart</a> | ";
echo "<a href='products.php'>Go to Products</a>";
?>