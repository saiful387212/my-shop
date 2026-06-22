<?php
// ============================================================
// FILE: public/check-email-log.php
// PURPOSE: Check if emails were sent
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>📧 Email Status Check</h1>";

// Check session status
echo "<h2>Session Status</h2>";
echo "Email sent status: " . (isset($_SESSION['email_sent']) ? ($_SESSION['email_sent'] ? '✅ Sent' : '❌ Not Sent') : '⚠️ Not Set') . "<br>";

// Check latest orders
echo "<h2>Latest Orders</h2>";

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Get latest 5 orders
    $stmt = $pdo->query("
        SELECT id, order_number, email, created_at 
        FROM orders 
        ORDER BY id DESC 
        LIMIT 5
    ");
    $orders = $stmt->fetchAll();
    
    if (!empty($orders)) {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>Order #</th><th>Email</th><th>Date</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
            echo "<td>" . htmlspecialchars($order['email'] ?? 'No email') . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($order['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No orders found.<br>";
    }
    
    // Check if email helper exists
    echo "<h2>Email Helper</h2>";
    $emailHelperPath = ABSPATH . 'app/helpers/email_helper.php';
    if (file_exists($emailHelperPath)) {
        echo "✅ email_helper.php exists<br>";
    } else {
        echo "❌ email_helper.php NOT found at: $emailHelperPath<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>