<?php
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

$pdo = getDbConnection();
?>
<!doctype html>
<html>
<body>
<h1>Schema Debug</h1>
<?php if (!$pdo): ?>
<p>DB connection failed</p>
<?php else: ?>
<p>DB connected</p>
<pre>
<?php
foreach (['orders', 'order_items', 'products', 'users'] as $table) {
    echo "TABLE $table\n";
    try {
        foreach ($pdo->query('DESCRIBE ' . $table) as $row) {
            echo $row['Field'] . ' | ' . $row['Type'] . '\n';
        }
    } catch (Exception $e) {
        echo 'ERROR: ' . $e->getMessage() . '\n';
    }
    echo "\n";
}
?>
</pre>
<?php endif; ?>
</body>
</html>
