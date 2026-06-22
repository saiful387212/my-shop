<?php
// ============================================================
// FILE: public/check-email-helper.php
// PURPOSE: Check if email_helper.php exists and is working
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

echo "<h1>Email Helper Check</h1>";

$filePath = ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'email_helper.php';

echo "Looking for: " . $filePath . "<br>";

if (file_exists($filePath)) {
    echo "✅ File exists!<br>";
    
    require_once $filePath;
    
    if (function_exists('sendOrderConfirmation')) {
        echo "✅ Function 'sendOrderConfirmation' exists!<br>";
    } else {
        echo "❌ Function 'sendOrderConfirmation' not found!<br>";
    }
} else {
    echo "❌ File does NOT exist!<br>";
    echo "Please create the file at: " . $filePath;
}

echo "<br><br>";
echo "<a href='checkout.php'>Go to Checkout</a> | ";
echo "<a href='cart.php'>Go to Cart</a>";
?>