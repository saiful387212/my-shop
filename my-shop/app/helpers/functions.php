<?php
// ============================================================
// FILE: my-shop/app/helpers/functions.php
// PURPOSE: Global helper functions
// ============================================================

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

/**
 * Dump and die (debugging function)
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Sanitize user input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Get current URL
 */
function currentUrl() {
    return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . 
           $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}
?>