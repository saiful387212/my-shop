<?php
$pdo = new PDO('mysql:host=localhost;dbname=my_shop;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

foreach (['orders', 'order_items'] as $table) {
    echo 'TABLE ' . $table . PHP_EOL;
    foreach ($pdo->query('DESCRIBE ' . $table) as $row) {
        echo $row['Field'] . ' | ' . $row['Type'] . PHP_EOL;
    }
    echo PHP_EOL;
}
