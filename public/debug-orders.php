<?php
// ============================================================
// FILE: public/debug-orders.php
// PURPOSE: Debug orders query
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Orders Debug</h1>";

// Check if logged in
if (!isLoggedIn()) {
    echo "❌ Not logged in. <a href='login.php'>Login</a>";
    exit;
}

echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
echo "✅ User Name: " . $_SESSION['user_name'] . "<br><br>";

$pdo = getDbConnection();

if ($pdo === null) {
    die("❌ Database connection failed.");
}

echo "✅ Database connected.<br><br>";

// ============================================
// TEST QUERY 1: Check if orders exist
// ============================================
echo "<h2>Test 1: Check if orders exist for user</h2>";

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id');
$stmt->execute(['user_id' => $userId]);
$count = $stmt->fetch()['count'];

echo "Orders found: " . $count . "<br>";

if ($count == 0) {
    echo "⚠️ No orders found for this user.<br>";
    echo "Create a test order:<br>";
    echo "<a href='debug-orders.php?create=1'>Create Test Order</a>";
}

// ============================================
// CREATE TEST ORDER
// ============================================
if (isset($_GET['create'])) {
    try {
        // Check if products exist
        $stmt = $pdo->query('SELECT id, name, price FROM products LIMIT 1');
        $product = $stmt->fetch();
        
        if (!$product) {
            echo "❌ No products found. Add products first.";
            exit;
        }
        
        // Create order
        $orderNumber = 'ORD-TEST-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmt = $pdo->prepare('
            INSERT INTO orders (user_id, order_number, total_amount, shipping_address, status) 
            VALUES (:user_id, :order_number, :total_amount, :shipping_address, :status)
        ');
        $stmt->execute([
            'user_id' => $userId,
            'order_number' => $orderNumber,
            'total_amount' => $product['price'] * 2,
            'shipping_address' => '123 Test Street, Test City',
            'status' => 'pending'
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Add order items
        $stmt = $pdo->prepare('
            INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity) 
            VALUES (:order_id, :product_id, :product_name, :product_price, :quantity)
        ');
        $stmt->execute([
            'order_id' => $orderId,
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'product_price' => $product['price'],
            'quantity' => 2
        ]);
        
        echo "✅ Test order created! Order #: " . $orderNumber . "<br>";
        echo "<a href='orders.php'>View Orders</a>";
        
    } catch (Exception $e) {
        echo "❌ Error creating test order: " . $e->getMessage();
    }
}

// ============================================
// TEST QUERY 2: Run the actual query
// ============================================
echo "<h2>Test 2: Run the orders query</h2>";

try {
    $stmt = $pdo->prepare('
        SELECT 
            o.id,
            o.order_number,
            o.total_amount,
            o.status,
            o.created_at,
            u.name as customer_name,
            u.email as customer_email,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.user_id = :user_id
        ORDER BY o.created_at DESC
    ');
    
    $stmt->execute(['user_id' => $userId]);
    $orders = $stmt->fetchAll();
    
    if (!empty($orders)) {
        echo "✅ Query successful! Found " . count($orders) . " orders.<br>";
        echo "<pre>";
        print_r($orders);
        echo "</pre>";
    } else {
        echo "⚠️ Query returned no results.<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
}

// ============================================
// DEBUG INFO
// ============================================
echo "<h2>Debug Info</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . $userId . "\n";
echo "User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "\n";
echo "Tables: \n";
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
echo "</pre>";
?>