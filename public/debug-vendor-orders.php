<?php
// ============================================================
// FILE: public/debug-vendor-orders.php
// PURPOSE: Debug vendor orders
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

session_start();

echo "<h1>🔍 Vendor Orders Debug</h1>";

// Check login
if (!isset($_SESSION['user_id'])) {
    echo "❌ Please login first.<br>";
    echo "<a href='login.php'>Login</a>";
    exit;
}

echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
echo "✅ User Name: " . ($_SESSION['user_name'] ?? 'Unknown') . "<br><br>";

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    echo "✅ Database connected<br><br>";
    
    // ============================================
    // 1. Check tables
    // ============================================
    echo "<h2>1. Check Tables</h2>";
    $tables = ['shops', 'products', 'orders', 'vendor_orders', 'order_items'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ $table table exists<br>";
        } else {
            echo "❌ $table table does NOT exist!<br>";
        }
    }
    echo "<br>";
    
    // ============================================
    // 2. Get user's shop
    // ============================================
    echo "<h2>2. User's Shop</h2>";
    $shopModel = new Shop();
    $shop = $shopModel->getByUser($_SESSION['user_id']);
    
    if ($shop) {
        echo "✅ Shop found:<br>";
        echo "Shop ID: " . $shop['id'] . "<br>";
        echo "Shop Name: " . $shop['shop_name'] . "<br>";
        echo "Shop Approved: " . ($shop['is_approved'] ? '✅ Yes' : '❌ No') . "<br>";
    } else {
        echo "❌ No shop found for this user.<br>";
        echo "<a href='shop/create.php'>Create a shop</a>";
        exit;
    }
    echo "<br>";
    
    // ============================================
    // 3. Check products with shop_id
    // ============================================
    echo "<h2>3. Products in Shop</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE shop_id = :shop_id");
    $stmt->execute([':shop_id' => $shop['id']]);
    $count = $stmt->fetch()['count'];
    echo "Products in this shop: " . $count . "<br>";
    
    if ($count > 0) {
        $stmt = $pdo->prepare("SELECT id, name, shop_id FROM products WHERE shop_id = :shop_id LIMIT 5");
        $stmt->execute([':shop_id' => $shop['id']]);
        $products = $stmt->fetchAll();
        echo "<ul>";
        foreach ($products as $p) {
            echo "<li>ID: {$p['id']} - {$p['name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "⚠️ No products in this shop. Add some products first.<br>";
    }
    echo "<br>";
    
    // ============================================
    // 4. Check vendor orders for this shop
    // ============================================
    echo "<h2>4. Vendor Orders for This Shop</h2>";
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
    
    if (!empty($orders)) {
        echo "✅ Found " . count($orders) . " vendor orders:<br><br>";
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>" . $order['id'] . "</td>";
            echo "<td>" . ($order['order_number'] ?? 'N/A') . "</td>";
            echo "<td>" . ($order['customer_name'] ?? 'Guest') . "</td>";
            echo "<td>$" . number_format($order['total_amount'], 2) . "</td>";
            echo "<td>" . ucfirst($order['status']) . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($order['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No vendor orders found for this shop.<br>";
        echo "<br>Possible reasons:<br>";
        echo "<ul>";
        echo "<li>No orders have been placed yet</li>";
        echo "<li>Checkout is not creating vendor orders</li>";
        echo "<li>Products don't have shop_id assigned</li>";
        echo "</ul>";
    }
    echo "<br>";
    
    // ============================================
    // 5. Check all vendor orders (global)
    // ============================================
    echo "<h2>5. All Vendor Orders (Global)</h2>";
    $stmt = $pdo->query("SELECT * FROM vendor_orders ORDER BY id DESC LIMIT 10");
    $allOrders = $stmt->fetchAll();
    
    if (!empty($allOrders)) {
        echo "Total vendor orders in database: " . count($allOrders) . "<br>";
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Order ID</th><th>Shop ID</th><th>Vendor ID</th><th>Amount</th><th>Status</th></tr>";
        foreach ($allOrders as $o) {
            echo "<tr>";
            echo "<td>" . $o['id'] . "</td>";
            echo "<td>" . $o['order_id'] . "</td>";
            echo "<td>" . $o['shop_id'] . "</td>";
            echo "<td>" . $o['vendor_id'] . "</td>";
            echo "<td>$" . number_format($o['total_amount'], 2) . "</td>";
            echo "<td>" . ucfirst($o['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No vendor orders exist in the database at all!<br>";
        echo "You need to place an order or insert test data.<br>";
    }
    echo "<br>";
    
    // ============================================
    // 6. Quick fix - Insert test vendor order
    // ============================================
    echo "<h2>6. Quick Fix - Insert Test Order</h2>";
    
    // Check if there are any orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $orderCount = $stmt->fetch()['count'];
    
    if ($orderCount == 0) {
        echo "⚠️ No orders in the database. Place an order first.<br>";
        echo "<a href='products.php'>Go to Products</a> | ";
        echo "<a href='cart.php'>Go to Cart</a>";
    } else {
        // Get latest order
        $stmt = $pdo->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
        $latestOrder = $stmt->fetch();
        
        // Check if vendor order already exists
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM vendor_orders 
            WHERE order_id = :order_id AND shop_id = :shop_id
        ");
        $stmt->execute([
            ':order_id' => $latestOrder['id'],
            ':shop_id' => $shop['id']
        ]);
        $exists = $stmt->fetch()['count'];
        
        if ($exists > 0) {
            echo "✅ Vendor order already exists for order #{$latestOrder['id']}<br>";
        } else {
            // Insert test vendor order
            $stmt = $pdo->prepare("
                INSERT INTO vendor_orders (order_id, shop_id, vendor_id, total_amount, status) 
                VALUES (:order_id, :shop_id, :vendor_id, :total_amount, 'pending')
            ");
            
            $result = $stmt->execute([
                ':order_id' => $latestOrder['id'],
                ':shop_id' => $shop['id'],
                ':vendor_id' => $_SESSION['user_id'],
                ':total_amount' => 99.99
            ]);
            
            if ($result) {
                echo "✅ Test vendor order inserted successfully!<br>";
                echo "<a href='shop/manage.php'>Go to Shop Manage</a>";
            } else {
                echo "❌ Failed to insert test vendor order.";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><br>";
echo "<a href='shop/manage.php'>Go to Shop Manage</a> | ";
echo "<a href='products.php'>Go to Products</a> | ";
echo "<a href='cart.php'>Go to Cart</a>";
?>