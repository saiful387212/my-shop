<?php
// ============================================================
// FILE: app/helpers/functions.php
// PURPOSE: Global helper functions
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

/**
 * Dump and die (debugging)
 */
function dd($data) {
    echo '<pre style="background: #f4f4f4; padding: 20px; border: 1px solid #ddd; margin: 20px; border-radius: 5px;">';
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
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Get current URL
 */
function currentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    // Check if session exists and has user_id
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['is_admin']) && 
           $_SESSION['is_admin'] == 1;
}

/**
 * Get current user ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user name
 */
function getUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Get current user email
 */
function getUserEmail() {
    return $_SESSION['user_email'] ?? '';
}

/**
 * Get cart count
 */
function getCartCount() {
    if (isset($_SESSION['cart_count'])) {
        return (int)$_SESSION['cart_count'];
    }
    return 0;
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Check if session is valid (prevent session fixation)
 */
function validateSession() {
    // Check if session has been validated already
    if (isset($_SESSION['validated'])) {
        return true;
    }
    
    // Check IP address
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        session_destroy();
        return false;
    }
    
    // Check User Agent
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_destroy();
        return false;
    }
    
    // Mark session as validated
    $_SESSION['validated'] = true;
    return true;
}

/**
 * Regenerate session ID (prevent session fixation)
 */
function regenerateSession() {
    session_regenerate_id(true);
    $_SESSION['validated'] = true;
}
?>
