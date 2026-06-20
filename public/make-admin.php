<?php
// ============================================================
// FILE: public/make-admin.php
// PURPOSE: Make a specific user an admin
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Make User Admin</h1>";

$pdo = getDbConnection();

if ($pdo === null) {
    die('Database connection failed.');
}

// Get all users
$users = $pdo->query('SELECT id, name, email, is_admin FROM users ORDER BY id')->fetchAll();

echo "<h2>Users</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>is_admin</th><th>Action</th></tr>";

foreach ($users as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . htmlspecialchars($user['name']) . "</td>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>" . ($user['is_admin'] ? '✅ Admin' : '❌ User') . "</td>";
    echo "<td>";
    
    if ($user['is_admin'] == 0) {
        echo "<form method='POST' style='display:inline;'>";
        echo "<input type='hidden' name='user_id' value='" . $user['id'] . "'>";
        echo "<button type='submit' name='make_admin' style='background:#2C3E8F;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;'>Make Admin</button>";
        echo "</form>";
    } else {
        echo "<span style='color:green;'>✓ Already Admin</span>";
    }
    
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Handle make admin
if (isset($_POST['make_admin']) && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    
    try {
        $stmt = $pdo->prepare('UPDATE users SET is_admin = 1 WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        
        echo "<p style='color:green;font-weight:bold;'>✅ User ID $userId is now an admin!</p>";
        echo "<a href='make-admin.php'>Refresh</a> | ";
        echo "<a href='admin/dashboard.php'>Go to Admin Dashboard</a>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>