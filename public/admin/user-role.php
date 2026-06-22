<?php
// ============================================================
// FILE: public/admin/user-role.php
// PURPOSE: Update user role (Admin/Customer)
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
$newRole = isset($_POST['new_role']) ? (int)$_POST['new_role'] : 0;

if ($userId <= 0) {
    $_SESSION['error_message'] = 'Invalid user ID.';
    header('Location: users.php');
    exit;
}

// Prevent changing your own role
if ($userId == $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You cannot change your own role.';
    header('Location: users.php');
    exit;
}

// ============================================
// UPDATE USER ROLE
// ============================================

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Get user info
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error_message'] = 'User not found.';
        header('Location: users.php');
        exit;
    }
    
    // Update role
    $stmt = $pdo->prepare("UPDATE users SET is_admin = :role WHERE id = :id");
    $result = $stmt->execute([
        ':id' => $userId,
        ':role' => $newRole
    ]);
    
    if ($result) {
        $roleName = $newRole == 1 ? 'Admin' : 'Customer';
        $_SESSION['success_message'] = "✅ {$user['name']} is now a $roleName!";
    } else {
        $_SESSION['error_message'] = '❌ Failed to update role.';
    }
    
} catch (PDOException $e) {
    error_log('User role error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log('User role error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred.';
}

header('Location: users.php');
exit;
?>