<?php
session_start();
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

$pdo = getDbConnection();
if (!$pdo) {
    die('DB connection failed');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$_SESSION['user_id'] = 1;
$cartItems = [
    1 => [
        'id' => 1,
        'name' => 'Debug Product',
        'price' => 15.50,
        'quantity' => 2,
        'image' => ''
    ]
];

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shippingAmount = ($subtotal > 0 && $subtotal < 50) ? 5.00 : 0;
$totalAmount = $subtotal + $shippingAmount;

$orderNumber = 'ORD-DEBUG-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
$userId = (int)($_SESSION['user_id'] ?? 0);
$shippingAddress = '123 Test Street, Test City, 12345';

$sql = "
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
";

$stmt = $pdo->prepare($sql);
$bind = [
    'user_id' => $userId,
    'order_number' => $orderNumber,
    'total_amount' => $totalAmount,
    'shipping_address' => $shippingAddress,
    'status' => 'pending'
];

echo '<pre>';
print_r($bind);
echo "\nSQL:\n$sql\n";
try {
    $stmt->execute($bind);
    echo "ORDER INSERT OK\n";
    echo 'lastInsertId=' . $pdo->lastInsertId() . "\n";
} catch (Exception $e) {
    echo 'ORDER INSERT ERROR: ' . $e->getMessage() . "\n";
    echo 'ErrorInfo: '; print_r($stmt->errorInfo());
}
echo '</pre>';

