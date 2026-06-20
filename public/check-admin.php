<?php
// ============================================================
// FILE: public/check-admin.php
// PURPOSE: Check if current user is admin
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Admin Status Check</h1>";

echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>isLoggedIn()</h2>";
echo isLoggedIn() ? "✅ TRUE" : "❌ FALSE";
echo "<br>";

echo "<h2>isAdmin()</h2>";
echo isAdmin() ? "✅ TRUE" : "❌ FALSE";
echo "<br>";

echo "<h2>User Info</h2>";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . "<br>";
echo "User Name: " . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Not set') . "<br>";
echo "User Email: " . (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'Not set') . "<br>";
echo "is_admin: " . (isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 'Not set') . "<br>";

if (isset($_SESSION['user_id'])) {
    echo "<h2>Check Database</h2>";
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT id, name, email, is_admin FROM users WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<pre>";
            print_r($user);
            echo "</pre>";
            
            if ($user['is_admin'] == 1) {
                echo "<p style='color:green;font-weight:bold;'>✅ This user IS an admin in the database!</p>";
                echo "<a href='admin/dashboard.php'>Go to Admin Dashboard</a>";
            } else {
                echo "<p style='color:red;font-weight:bold;'>❌ This user is NOT an admin in the database. (is_admin = " . $user['is_admin'] . ")</p>";
                echo "<form method='POST'>";
                echo "<button type='submit' name='make_admin' value='1' style='padding:10px 20px;background:#2C3E8F;color:white;border:none;border-radius:5px;cursor:pointer;'>";
                echo "Make This User an Admin";
                echo "</button>";
                echo "</form>";
            }
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle make admin
if (isset($_POST['make_admin']) && isset($_SESSION['user_id'])) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('UPDATE users SET is_admin = 1 WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $_SESSION['is_admin'] = 1;
        echo "<p style='color:green;'>✅ User is now an admin! Refreshing...</p>";
        echo "<script>setTimeout(function(){ window.location.href = 'check-admin.php'; }, 2000);</script>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>