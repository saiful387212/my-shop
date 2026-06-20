<?php
// ============================================================
// FILE: my-shop/public/admin/index.php
// PURPOSE: Admin panel entry point
// ============================================================

// Define the absolute path
define('ABSPATH', dirname(__DIR__, 2) . '/');

// Load configuration
require_once ABSPATH . 'app/config/config.php';

// Load database connection
require_once ABSPATH . 'app/config/database.php';

// Load helper functions
require_once ABSPATH . 'app/helpers/functions.php';

// Start session
session_start();

// Check if user is admin
if (!isAdmin()) {
    redirect(SITE_URL . 'login.php');
}

// Get database connection
$pdo = getDbConnection();

if ($pdo === null) {
    die('Database connection failed.');
}

// Load admin dashboard view
require_once ABSPATH . 'app/views/admin/dashboard.php';
?>