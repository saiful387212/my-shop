<?php
// ============================================================
// FILE: public/admin/category-delete.php
// PURPOSE: Delete a category from the database
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// ADMIN ACCESS CONTROL
// ============================================

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = 'Access denied. Admin only.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// ============================================
// GET CATEGORY ID
// ============================================

$categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

if ($categoryId <= 0) {
    $_SESSION['error_message'] = 'Invalid category ID.';
    header('Location: categories.php');
    exit;
}

// ============================================
// CHECK IF CATEGORY HAS PRODUCTS
// ============================================

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Check if category has products
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM products WHERE category_id = :category_id');
    $stmt->execute(['category_id' => $categoryId]);
    $productCount = $stmt->fetch()['count'] ?? 0;
    
    if ($productCount > 0) {
        $_SESSION['error_message'] = "Cannot delete this category. It has $productCount product(s) assigned to it. Please reassign or delete those products first.";
        header('Location: categories.php');
        exit;
    }
    
    // Delete the category
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $result = $stmt->execute(['id' => $categoryId]);
    
    if ($result) {
        $_SESSION['success_message'] = 'Category deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to delete category.';
    }
    
} catch (PDOException $e) {
    error_log('Delete category error: ' . $e->getMessage());
    
    // Check if it's a foreign key constraint error
    if ($e->getCode() == 23000) {
        $_SESSION['error_message'] = 'Cannot delete this category because it has products assigned to it.';
    } else {
        $_SESSION['error_message'] = 'Database error occurred.';
    }
} catch (Exception $e) {
    error_log('Delete category error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred.';
}

header('Location: categories.php');
exit;
?>