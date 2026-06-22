<?php
// ============================================================
// FILE: public/login.php
// PURPOSE: User login page with full styling
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load models
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF USER IS ALREADY LOGGED IN
// ============================================

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

// ============================================
// GET CATEGORIES FOR HEADER
// ============================================

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Login categories error: ' . $e->getMessage());
}

// ============================================
// PROCESS LOGIN FORM
// ============================================

$errors = [];
$formData = ['email' => '', 'password' => '', 'remember' => false];
$shopModel = new Shop();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['remember'] = isset($_POST['remember']) ? true : false;
    
    // Validate
    if (empty($formData['email'])) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required.';
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            $stmt = $pdo->prepare("
                SELECT id, name, email, password, is_admin 
                FROM users 
                WHERE email = :email
            ");
            $stmt->execute([':email' => $formData['email']]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($formData['password'], $user['password'])) {
                
                // Set session
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = (int)$user['is_admin'];
                $_SESSION['login_time'] = time();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                
                // Handle Remember Me
                if ($formData['remember']) {
                    $cookieParams = session_get_cookie_params();
                    setcookie(
                        session_name(),
                        session_id(),
                        time() + (30 * 24 * 60 * 60),
                        $cookieParams['path'],
                        $cookieParams['domain'],
                        $cookieParams['secure'],
                        $cookieParams['httponly']
                    );
                }
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $updateStmt->execute([':id' => $user['id']]);
                
                // Check if user has a shop
                $userShop = $shopModel->getByUser($user['id']);
                
                // Redirect based on role
                if ($user['is_admin'] == 1) {
                    header('Location: ' . ADMIN_URL . 'dashboard.php');
                } elseif ($userShop) {
                    header('Location: ' . SITE_URL . 'shop/manage.php');
                } else {
                    header('Location: ' . SITE_URL . 'index.php');
                }
                exit;
                
            } else {
                $errors['general'] = 'Invalid email or password. Please try again.';
            }
            
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - My Shop</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ============================================================
           ALL CSS EMBEDDED - COMPLETE LOGIN PAGE STYLES
           ============================================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5;
            color: #1a1a2e;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* ============================================
           HEADER
           ============================================ */
        
        .header {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .logo a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .logo .logo-icon {
            font-size: 28px;
            color: #2C3E8F;
        }
        
        .logo-text .brand {
            font-size: 22px;
            font-weight: 800;
            color: #1a1a2e;
        }
        
        .logo-text .brand span {
            color: #2C3E8F;
        }
        
        .logo-text .tagline {
            font-size: 9px;
            color: #6c757d;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .search-bar {
            flex: 1;
            max-width: 450px;
            min-width: 180px;
        }
        
        .search-bar form {
            display: flex;
            align-items: center;
            background: #f1f4f9;
            border-radius: 50px;
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .search-bar form:focus-within {
            border-color: #2C3E8F;
            background: white;
        }
        
        .search-bar input {
            flex: 1;
            padding: 9px 18px;
            border: none;
            background: transparent;
            font-size: 14px;
            outline: none;
        }
        
        .search-bar button {
            padding: 9px 18px;
            background: transparent;
            border: none;
            color: #2C3E8F;
            cursor: pointer;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: 2px solid transparent;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-primary {
            background: #2C3E8F;
            color: white;
            border-color: #2C3E8F;
        }
        
        .btn-primary:hover {
            background: #1a2a6c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44,62,143,0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: #2C3E8F;
            border-color: #2C3E8F;
        }
        
        .btn-outline:hover {
            background: #2C3E8F;
            color: white;
        }
        
        .btn-small {
            padding: 6px 14px;
            font-size: 12px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        
        .cart-icon {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #f1f4f9;
            border-radius: 50%;
            color: #1a1a2e;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .cart-icon:hover {
            background: #2C3E8F;
            color: white;
            transform: scale(1.05);
        }
        
        .cart-count {
            position: absolute;
            top: -3px;
            right: -3px;
            background: #e74c3c;
            color: white;
            font-size: 9px;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            gap: 4px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 6px;
        }
        
        .mobile-menu-toggle .bar {
            width: 24px;
            height: 3px;
            background: #1a1a2e;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-toggle.active .bar:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        .mobile-menu-toggle.active .bar:nth-child(2) {
            opacity: 0;
        }
        .mobile-menu-toggle.active .bar:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }
        
        .navbar {
            background: #2C3E8F;
            padding: 0;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
        }
        
        .nav-menu li a {
            display: block;
            padding: 11px 18px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-menu li a:hover {
            background: rgba(255, 255, 255, 0.12);
            color: white;
        }
        
        .nav-menu li a i {
            margin-right: 6px;
        }
        
        /* ============================================
           LOGIN SECTION
           ============================================ */
        
        .login-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: calc(100vh - 200px);
        }
        
        .login-wrapper {
            max-width: 460px;
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 40px 45px;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* ============================================
           LOGIN HEADER
           ============================================ */
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .login-header h1::before {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: #2C3E8F;
            margin: 0 auto 15px;
            border-radius: 2px;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 16px;
        }
        
        .login-header p strong {
            color: #2C3E8F;
        }
        
        /* ============================================
           ALERT MESSAGES
           ============================================ */
        
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert i {
            font-size: 18px;
        }
        
        /* ============================================
           FORM STYLES
           ============================================ */
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .form-group label {
            font-weight: 600;
            font-size: 14px;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group label .required {
            color: #e74c3c;
            margin-left: 2px;
            font-weight: 700;
        }
        
        .form-group label i {
            color: #2C3E8F;
            font-size: 14px;
        }
        
        .form-control {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: #f8fafc;
            color: #1a1a2e;
            width: 100%;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2C3E8F;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(44, 62, 143, 0.1);
        }
        
        .form-control.is-invalid {
            border-color: #e74c3c;
            background: #fff5f5;
        }
        
        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1);
        }
        
        .form-control::placeholder {
            color: #a0aec0;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
        }
        
        .error-message i {
            font-size: 14px;
        }
        
        /* ============================================
           PASSWORD TOGGLE
           ============================================ */
        
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-wrapper .form-control {
            padding-right: 50px;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #a0aec0;
            font-size: 18px;
            padding: 4px 8px;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: #2C3E8F;
        }
        
        .toggle-password:focus {
            outline: none;
        }
        
        /* ============================================
           FORM OPTIONS
           ============================================ */
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 4px 0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 14px;
            color: #4a5568;
            font-weight: 500;
        }
        
        .checkbox-label input[type="checkbox"] {
            display: none;
        }
        
        .checkmark {
            min-width: 18px;
            height: 18px;
            background: #f8fafc;
            border: 2px solid #cbd5e0;
            border-radius: 4px;
            display: inline-block;
            position: relative;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }
        
        .checkbox-label:hover .checkmark {
            border-color: #2C3E8F;
        }
        
        .checkbox-label input:checked + .checkmark {
            background: #2C3E8F;
            border-color: #2C3E8F;
        }
        
        .checkbox-label input:checked + .checkmark::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 10px;
        }
        
        .forgot-link {
            color: #2C3E8F;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .forgot-link:hover {
            color: #1a2a6c;
            text-decoration: underline;
        }
        
        /* ============================================
           LOGIN BUTTON
           ============================================ */
        
        .btn-login {
            width: 100%;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 700;
            margin-top: 4px;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .btn-login.loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-login.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
        
        /* ============================================
           REGISTER LINK
           ============================================ */
        
        .register-link {
            text-align: center;
            font-size: 14px;
            color: #4a5568;
            margin-top: 4px;
        }
        
        .register-link a {
            color: #2C3E8F;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            color: #1a2a6c;
            text-decoration: underline;
        }
        
        /* ============================================
           SECURITY INFO
           ============================================ */
        
        .login-security {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
            text-align: center;
        }
        
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0f4ff;
            color: #2C3E8F;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-security p {
            font-size: 13px;
            color: #6c757d;
            margin: 0;
        }
        
        /* ============================================
           FOOTER
           ============================================ */
        
        .footer {
            background: #1a1a2e;
            color: rgba(255, 255, 255, 0.8);
            padding: 40px 0 20px;
            margin-top: auto;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 12px;
        }
        
        .footer-description {
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.8;
            margin-bottom: 16px;
            max-width: 300px;
        }
        
        .social-links {
            display: flex;
            gap: 12px;
        }
        
        .social-links a {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .social-links a:hover {
            background: #2C3E8F;
            transform: translateY(-3px);
        }
        
        .footer-col h4 {
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 8px;
        }
        
        .footer-col ul li a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .footer-col ul li a:hover {
            color: white;
            padding-left: 4px;
        }
        
        .contact-info li {
            display: flex;
            gap: 12px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .contact-info li i {
            color: #2C3E8F;
            margin-top: 4px;
            width: 18px;
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.4);
        }
        
        .payment-methods {
            display: flex;
            gap: 16px;
            font-size: 24px;
        }
        
        .payment-methods i {
            color: rgba(255, 255, 255, 0.4);
            transition: color 0.3s ease;
        }
        
        .payment-methods i:hover {
            color: white;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        
        @media (max-width: 768px) {
            .header-inner {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .search-bar {
                order: 3;
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .mobile-menu-toggle {
                display: flex;
            }
            
            .navbar {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 8px 30px rgba(0,0,0,0.15);
                z-index: 999;
            }
            
            .navbar.active {
                display: block;
            }
            
            .nav-menu {
                flex-direction: column;
                padding: 8px 0;
            }
            
            .nav-menu li a {
                color: #1a1a2e;
                padding: 10px 20px;
                border-bottom: 1px solid #f1f4f9;
            }
            
            .login-wrapper {
                padding: 30px 24px;
                margin: 0 16px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .login-wrapper {
                padding: 24px 16px;
            }
            
            .login-header h1 {
                font-size: 20px;
            }
            
            .form-control {
                font-size: 14px;
                padding: 10px 14px;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .btn-login {
                font-size: 14px;
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>
    
    <!-- ============================================
         HEADER
         ============================================ -->
    <header class="header">
        <div class="container">
            <div class="header-inner">
                
                <div class="logo">
                    <a href="index.php">
                        <i class="fas fa-store logo-icon"></i>
                        <div class="logo-text">
                            <span class="brand">My <span>Shop</span></span>
                            <span class="tagline">Multi-Vendor Marketplace</span>
                        </div>
                    </a>
                </div>
                
                <div class="search-bar">
                    <form action="products.php" method="GET">
                        <input type="text" name="search" placeholder="Search products...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="header-actions">
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </a>
                    
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <span class="bar"></span>
                        <span class="bar"></span>
                        <span class="bar"></span>
                    </button>
                </div>
                
            </div>
        </div>
        
        <nav class="navbar" id="mainNav">
            <div class="container">
                <ul class="nav-menu">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                    <li><a href="shops.php"><i class="fas fa-store"></i> Shops</a></li>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <li><a href="products.php?category=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
        </nav>
    </header>
    
    <!-- ============================================
         LOGIN SECTION
         ============================================ -->
    <section class="login-section">
        <div class="container">
            <div class="login-wrapper">
                
                <!-- Header -->
                <div class="login-header">
                    <h1>Welcome Back</h1>
                    <p>Login to your <strong>My Shop</strong> account</p>
                </div>
                
                <!-- General Error -->
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['general']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
                      method="POST" 
                      class="login-form" 
                      id="loginForm"
                      novalidate>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address
                            <span class="required">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                               placeholder="Enter your email address"
                               value="<?php echo htmlspecialchars($formData['email']); ?>"
                               required
                               autofocus>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['email']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                            <span class="required">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                   placeholder="Enter your password"
                                   required>
                            <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['password']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Options -->
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" <?php echo $formData['remember'] ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Remember Me
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                    </div>
                    
                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </button>
                    
                    <!-- Register Link -->
                    <div class="register-link">
                        Don't have an account? <a href="register.php">Create one now</a>
                    </div>
                </form>
                
                <!-- Security -->
                <div class="login-security">
                    <div class="security-badge">
                        <i class="fas fa-lock"></i>
                        <span>Secure Login</span>
                    </div>
                    <p>Your information is protected with industry-standard encryption.</p>
                </div>
                
            </div>
        </div>
    </section>
    
    <!-- ============================================
         FOOTER
         ============================================ -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <i class="fas fa-store"></i>
                        <span>My Shop</span>
                    </div>
                    <p class="footer-description">Your one-stop multi-vendor marketplace.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="shops.php">Vendors</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Customer Service</h4>
                    <ul>
                        <li><a href="returns.php">Returns</a></li>
                        <li><a href="shipping.php">Shipping</a></li>
                        <li><a href="privacy.php">Privacy</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Main St</li>
                        <li><i class="fas fa-envelope"></i> info@myshop.com</li>
                        <li><i class="fas fa-phone-alt"></i> +1 (555) 123-4567</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> My Shop. All rights reserved.</p>
                <div class="payment-methods">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-amex"></i>
                    <i class="fab fa-cc-paypal"></i>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // ============================================
            // MOBILE MENU TOGGLE
            // ============================================
            
            const menuToggle = document.getElementById('mobileMenuToggle');
            const mainNav = document.getElementById('mainNav');
            
            if (menuToggle && mainNav) {
                menuToggle.addEventListener('click', function() {
                    this.classList.toggle('active');
                    mainNav.classList.toggle('active');
                });
            }
            
            // Close menu on outside click
            document.addEventListener('click', function(event) {
                if (menuToggle && mainNav) {
                    const isClickInside = menuToggle.contains(event.target) || mainNav.contains(event.target);
                    if (!isClickInside && mainNav.classList.contains('active')) {
                        menuToggle.classList.remove('active');
                        mainNav.classList.remove('active');
                    }
                }
            });
            
            // ============================================
            // PASSWORD TOGGLE
            // ============================================
            
            const toggleButtons = document.querySelectorAll('.toggle-password');
            
            toggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const input = this.closest('.password-wrapper').querySelector('input');
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                        this.setAttribute('aria-label', 'Hide password');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                        this.setAttribute('aria-label', 'Show password');
                    }
                });
            });
            
            // ============================================
            // FORM VALIDATION
            // ============================================
            
            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            
            function validateEmail() {
                const value = email.value.trim();
                email.classList.remove('is-invalid', 'is-valid');
                
                if (value.length === 0) {
                    email.classList.add('is-invalid');
                    showError(email, 'Email address is required.');
                    return false;
                }
                
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    email.classList.add('is-invalid');
                    showError(email, 'Please enter a valid email address.');
                    return false;
                }
                
                email.classList.add('is-valid');
                removeError(email);
                return true;
            }
            
            function validatePassword() {
                const value = password.value;
                password.classList.remove('is-invalid', 'is-valid');
                
                if (value.length === 0) {
                    password.classList.add('is-invalid');
                    showError(password, 'Password is required.');
                    return false;
                }
                
                password.classList.add('is-valid');
                removeError(password);
                return true;
            }
            
            function showError(input, message) {
                const parent = input.parentElement;
                let errorElement = parent.querySelector('.error-message');
                
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'error-message';
                    parent.appendChild(errorElement);
                }
                
                errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            }
            
            function removeError(input) {
                const parent = input.parentElement;
                const errorElement = parent.querySelector('.error-message');
                if (errorElement) {
                    errorElement.remove();
                }
            }
            
            // Real-time validation
            email.addEventListener('input', function() {
                if (this.value.length > 0) validateEmail();
            });
            email.addEventListener('blur', validateEmail);
            
            password.addEventListener('input', function() {
                if (this.value.length > 0) validatePassword();
            });
            password.addEventListener('blur', function() {
                if (this.value.length > 0) validatePassword();
            });
            
            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const isEmailValid = validateEmail();
                const isPasswordValid = validatePassword();
                
                if (isEmailValid && isPasswordValid) {
                    loginBtn.disabled = true;
                    loginBtn.classList.add('loading');
                    loginBtn.innerHTML = 'Logging in...';
                    form.submit();
                } else {
                    const firstError = form.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
            
            console.log('✅ Login page loaded successfully!');
        });
        // After password verification
if ($user && password_verify($formData['password'], $user['password'])) {
    
    // Store basic user data
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin'] = (int)$user['is_admin'];
    
    // ============================================
    // NEW: Store DU student data in session
    // ============================================
    $_SESSION['student_id'] = $user['student_id'] ?? '';
    $_SESSION['department'] = $user['department'] ?? '';
    $_SESSION['batch'] = $user['batch'] ?? '';
    $_SESSION['hall'] = $user['hall'] ?? '';
    $_SESSION['is_verified'] = (int)($user['is_verified'] ?? 0);
    
    // ... rest of login code
}
    </script>
    
</body>
</html>