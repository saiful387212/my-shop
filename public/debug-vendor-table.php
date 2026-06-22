<?php
// ============================================================
// FILE: public/debug-vendor-table.php
// PURPOSE: Check vendor_orders table
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$pdo = getDbConnection();

echo "<h1>🔍 Vendor Orders Table Check</h1>";

// Check if table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'vendor_orders'");
if ($stmt->rowCount() > 0) {
    echo "✅ vendor_orders table exists<br>";
    
    // Show table structure
    $stmt = $pdo->query("DESCRIBE vendor_orders");
    echo "<h2>Table Structure</h2>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($stmt->fetchAll() as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vendor_orders");
    $count = $stmt->fetch()['count'];
    echo "<p>Total records: " . $count . "</p>";
    
} else {
    echo "❌ vendor_orders table does NOT exist!<br>";
    echo "<p>Please run this SQL to create it:</p>";
    echo "<pre style='background:#f0f0f0;padding:15px;border-radius:5px;'>
CREATE TABLE IF NOT EXISTS vendor_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    shop_id INT NOT NULL,
    vendor_id INT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'accepted', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE SET NULL
);</pre>";
}

echo "<br><br>";
echo "<a href='checkout.php'>Go to Checkout</a> | ";
echo "<a href='shop/manage.php'>Go to Shop Manage</a>";
?>