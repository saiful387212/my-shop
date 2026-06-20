<?php
// ============================================================
// FILE: public/admin/debug.php
// PURPOSE: Debug session and admin access
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Admin Debug</h1>";

echo "<h2>Session Status</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "</pre>";

echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>isLoggedIn()</h2>";
echo "Result: " . (isLoggedIn() ? "✅ TRUE" : "❌ FALSE") . "<br>";

echo "<h2>isAdmin()</h2>";
echo "Result: " . (isAdmin() ? "✅ TRUE" : "❌ FALSE") . "<br>";

echo "<h2>User Info</h2>";
echo "User ID: " . (getUserId() ?? 'Not set') . "<br>";
echo "User Name: " . (getUserName() ?? 'Not set') . "<br>";

echo "<h2>Actions</h2>";
echo '<a href="' . SITE_URL . 'logout.php">Logout</a> | ';
echo '<a href="dashboard.php">Dashboard</a> | ';
echo '<a href="category-add.php">Add Category</a>';
?>