<?php
// ============================================================
// FILE: public/shop/debug-orders.php
// PURPOSE: Debug vendor orders
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    die('Please login.');
}

$shopModel = new Shop();
$shop = $shopModel->getByUser($_SESSION['user_id']);

if (!$shop) {
    die('No shop found.');
}

echo "<h1>Shop Orders Debug</h1>";
echo "<p>Shop ID: " . $shop['id'] . "</p>";
echo "<p>Shop Name: " . $shop['shop_name'] . "</p>";

try {
    $pdo = getDbConnection();
    
    echo "<h2>1. Check vendor_orders table</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'vendor_orders'");
    if ($stmt->rowCount() > 0) {
        echo "✅ vendor_orders table exists<br>";
    } else {
        echo "❌ vendor_orders table does NOT exist!<br>";
    }
    
    echo "<h2>2. Check if vendor_orders has data for this shop</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM vendor_orders WHERE shop_id = :shop_id");
    $stmt->execute([':shop_id' => $shop['id']]);
    $count = $stmt->fetch()['count'];
    echo "Orders found: " . $count . "<br>";
    
    if ($count > 0) {
        echo "<h2>3. Show orders</h2>";
        $stmt = $pdo->prepare("
            SELECT 
                vo.*,
                o.order_number,
                o.created_at as order_date,
                u.name as customer_name
            FROM vendor_orders vo
            LEFT JOIN orders o ON vo.order_id = o.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE vo.shop_id = :shop_id
            ORDER BY vo.created_at DESC
        ");
        $stmt->execute([':shop_id' => $shop['id']]);
        $orders = $stmt->fetchAll();
        
        echo "<pre>";
        print_r($orders);
        echo "</pre>";
    } else {
        echo "<h2>⚠️ No orders found</h2>";
        echo "<p>Possible reasons:</p>";
        echo "<ul>";
        echo "<li>No orders have been placed yet</li>";
        echo "<li>Orders exist but vendor_orders table is empty</li>";
        echo "<li>Checkout is not creating vendor orders</li>";
        echo "</ul>";
    }
    
    echo "<h2>4. Check all vendor_orders</h2>";
    $stmt = $pdo->query("SELECT * FROM vendor_orders");
    $allOrders = $stmt->fetchAll();
    echo "Total vendor orders in database: " . count($allOrders) . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>