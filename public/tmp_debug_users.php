<?php
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$pdo = getDbConnection();
if (!$pdo) {
    die('DB connection failed');
}

$stmt = $pdo->query('SELECT id, name, email, is_admin FROM users ORDER BY id');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<pre>';
print_r($users);
echo '</pre>';
