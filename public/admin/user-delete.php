<?php
// ============================================================
// FILE: public/admin/user-delete.php
// PURPOSE: Delete a user
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// ADMIN CHECK
// ============================================

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// ============================================
// GET POST DATA
// ============================================

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($userId <= 0) {
    $_SESSION['error_message'] = 'Invalid user ID.';
    header('Location: users.php');
    exit;
}

// Prevent deleting yourself
if ($userId == $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You cannot delete your own account.';
    header('Location: users.php');
    exit;
}

// ============================================
// DELETE USER
// ============================================

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Check if user has orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $orderCount = $stmt->fetch()['count'] ?? 0;
    
    if ($orderCount > 0) {
        $_SESSION['error_message'] = "Cannot delete user. They have $orderCount order(s).";
        header('Location: users.php');
        exit;
    }
    
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $result = $stmt->execute([':id' => $userId]);
    
    if ($result) {
        $_SESSION['success_message'] = 'User deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to delete user.';
    }
    
} catch (PDOException $e) {
    error_log('User delete error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log('User delete error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred.';
}

header('Location: users.php');
exit;
?>