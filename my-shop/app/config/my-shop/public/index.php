<?php
// ============================================================
// FILE: my-shop/public/index.php
// PURPOSE: Front controller - entry point for all requests
// ============================================================

// Define the absolute path to the root directory
// dirname(__DIR__) goes up one level from 'public' to 'my-shop'
define('ABSPATH', dirname(__DIR__) . '/');

// Load configuration
require_once ABSPATH . 'app/config/config.php';

// Load database connection
require_once ABSPATH . 'app/config/database.php';

// Load helper functions
require_once ABSPATH . 'app/helpers/functions.php';

// Load the router
require_once ABSPATH . 'app/core/Router.php';

// Start session
session_start();

// Get database connection
$pdo = getDbConnection();

if ($pdo === null) {
    // Show a user-friendly error page
    die('We are experiencing technical difficulties. Please try again later.');
}

// Initialize router and handle the request
$router = new Router();
$router->dispatch($_SERVER['REQUEST_URI']);
?>