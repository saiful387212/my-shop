<?php
// ============================================================
// FILE: public/debug-order.php
// PURPOSE: Check if orders are being saved
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$pdo = getDbConnection();

if ($pdo === null) {
    die("❌ Database connection failed.");
}

echo "<h1>Order Debug</h1>";

// Check latest orders
$stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
$orders = $stmt->fetchAll();

if (!empty($orders)) {
    echo "<h2>Latest Orders</h2>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Order #</th><th>Total</th><th>Status</th><th>Date</th></tr>";
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>" . $order['id'] . "</td>";
        echo "<td>" . $order['order_number'] . "</td>";
        echo "<td>$" . number_format($order['total_amount'], 2) . "</td>";
        echo "<td>" . $order['status'] . "</td>";
        echo "<td>" . $order['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No orders found. Place an order first.";
}

echo "<br><br>";
echo "<a href='checkout.php'>Go to Checkout</a> | ";
echo "<a href='cart.php'>Go to Cart</a> | ";
echo "<a href='products.php'>Go to Products</a>";
?>