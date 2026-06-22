<?php
// ============================================================
// FILE: public/create-sample-images.php
// PURPOSE: Create sample product images
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

$uploadDir = ABSPATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR;

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    echo "✅ Created uploads directory<br>";
}

// Sample image URLs (free placeholder images)
$sampleImages = [
    'https://via.placeholder.com/300x300/2C3E8F/FFFFFF?text=Product+1',
    'https://via.placeholder.com/300x300/27AE60/FFFFFF?text=Product+2',
    'https://via.placeholder.com/300x300/E74C3C/FFFFFF?text=Product+3',
    'https://via.placeholder.com/300x300/F39C12/FFFFFF?text=Product+4',
    'https://via.placeholder.com/300x300/8E44AD/FFFFFF?text=Product+5',
    'https://via.placeholder.com/300x300/3498DB/FFFFFF?text=Product+6',
];

echo "<h2>Creating Sample Images...</h2>";

foreach ($sampleImages as $index => $url) {
    $filename = 'product_' . ($index + 1) . '.jpg';
    $filepath = $uploadDir . $filename;
    
    // Download image
    $imageData = file_get_contents($url);
    
    if ($imageData !== false) {
        file_put_contents($filepath, $imageData);
        echo "✅ Created: $filename<br>";
    } else {
        echo "❌ Failed: $filename<br>";
    }
}

echo "<br><a href='products.php'>View Products</a>";
?>