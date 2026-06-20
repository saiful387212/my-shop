<?php
// ============================================================
// FILE: my-shop/app/config/config.php
// PURPOSE: Global application configuration
// ============================================================

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

// Site configuration
define('SITE_NAME', 'My Shop');
define('SITE_URL', 'http://localhost/my-shop/public/');
define('ADMIN_URL', SITE_URL . 'admin/');

// Default timezone
date_default_timezone_set('UTC');

// Development mode (set to false in production)
define('DEBUG_MODE', true);

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Upload settings
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
?>