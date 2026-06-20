<?php
// ============================================================
// FILE: app/config/config.php
// PURPOSE: Global application configuration
// ============================================================

// ============================================
// Security: Prevent direct access
// ============================================
if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

// ============================================
// Site Configuration
// ============================================

// Site name
define('SITE_NAME', 'My Shop');

// Site URL - IMPORTANT: Change this to your actual URL
// For local development:
define('SITE_URL', 'http://localhost/my-shop/public/');

// For production:
// define('SITE_URL', 'https://yourdomain.com/');

// Admin URL
define('ADMIN_URL', SITE_URL . 'admin/');

// ============================================
// Timezone
// ============================================
date_default_timezone_set('America/New_York');

// ============================================
// Environment Settings
// ============================================

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

// ============================================
// Upload Settings
// ============================================
define('MAX_UPLOAD_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ============================================
// Session Settings
// ============================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// ============================================
// Test: Check if config loaded correctly
// ============================================
// Uncomment to test:
// echo 'Config loaded successfully!'; die();
?>