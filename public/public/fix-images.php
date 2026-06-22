<?php
// ============================================================
// FILE: fix-images.php
// PURPOSE: Completely fix all images
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$pdo = getDbConnection();

// Get all products
$products = $pdo->query("SELECT id, name FROM products")->fetchAll();

// Update all products to use online placeholder images
foreach ($products as $product) {
    $color = dechex(rand(0x000000, 0xFFFFFF));
    $imageUrl = 'https://via.placeholder.com/300x300/' . $color . '/FFFFFF?text=' . urlencode(substr($product['name'], 0, 15));
    
    $stmt = $pdo->prepare("UPDATE products SET image_url = :image WHERE id = :id");
    $stmt->execute(['image' => $imageUrl, 'id' => $product['id']]);
    
    echo "✅ Updated: " . $product['name'] . "<br>";
}

echo "<br>All products updated!<br>";
echo "<a href='products.php'>View Products</a> | ";
echo "<a href='index.php'>Home</a>";
?>