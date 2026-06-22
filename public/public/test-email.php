<?php
// ============================================================
// FILE: public/test-email.php
// PURPOSE: Test email functionality
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'email_helper.php';

echo "<h1>Email Test</h1>";

// Get the latest order
$pdo = getDbConnection();
$stmt = $pdo->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
$latestOrder = $stmt->fetch();

if ($latestOrder) {
    echo "Testing email for order ID: " . $latestOrder['id'] . "<br>";
    
    $result = sendOrderConfirmation($latestOrder['id']);
    
    if ($result) {
        echo "<p style='color:green;'>✅ Email sent successfully!</p>";
    } else {
        echo "<p style='color:red;'>❌ Email failed to send. Check your mail server configuration.</p>";
    }
} else {
    echo "No orders found. Please place an order first.";
    echo "<br><a href='products.php'>Go to Products</a>";
}

echo "<br><br>";
echo "<a href='orders.php'>View Orders</a> | ";
echo "<a href='checkout.php'>Go to Checkout</a>";
?>