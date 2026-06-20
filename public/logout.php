<?php
// ============================================================
// FILE: public/logout.php
// PURPOSE: Destroy user session and log out
// ============================================================

// ============================================
// FIX 1: Define path correctly
// ============================================
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// ============================================
// FIX 2: Load only what's needed
// ============================================
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// ============================================
// FIX 3: Start session properly
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// FIX 4: Clear all session variables
// ============================================
$_SESSION = [];

// ============================================
// FIX 5: Destroy the session cookie
// ============================================
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// ============================================
// FIX 6: Destroy the session on the server
// ============================================
session_destroy();

// ============================================
// FIX 7: Start a new session for the message
// ============================================
session_start();
$_SESSION['success_message'] = 'You have been logged out successfully.';

// ============================================
// FIX 8: Redirect to login page
// ============================================
header('Location: ' . SITE_URL . 'login.php');
exit;
?>