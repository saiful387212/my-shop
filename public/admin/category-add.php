<?php
// ============================================================
// FILE: public/admin/category-add.php
// PURPOSE: Add a new category
// ============================================================

// ============================================
// FIX 1: Define the absolute path correctly
// ============================================
// dirname(__DIR__, 2) goes up 2 levels from /admin/ to /public/
// Then from /public/ to /my-shop/
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// ============================================
// FIX 2: Load required files
// ============================================
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// ============================================
// FIX 3: Start session - CRITICAL!
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// FIX 4: Debug - Log session data
// ============================================
error_log('=== category-add.php ===');
error_log('Session ID: ' . session_id());
error_log('Session Data: ' . print_r($_SESSION, true));

// ============================================
// FIX 5: ADMIN ACCESS CONTROL
// ============================================

// Check if user is logged in
if (!isLoggedIn()) {
    error_log('Not logged in - redirecting to login');
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// Check if user is admin
if (!isAdmin()) {
    error_log('Not admin - redirecting to home');
    $_SESSION['error_message'] = 'You do not have permission to access the admin panel.';
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

error_log('Admin access granted for user: ' . $_SESSION['user_name']);

// ============================================
// PROCESS FORM SUBMISSION
// ============================================

$errors = [];
$formData = [
    'name' => '',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $formData['name'] = sanitize($_POST['name'] ?? '');
    $formData['description'] = sanitize($_POST['description'] ?? '');
    
    // Validation
    if (empty($formData['name'])) {
        $errors['name'] = 'Category name is required.';
    } elseif (strlen($formData['name']) < 2) {
        $errors['name'] = 'Category name must be at least 2 characters.';
    } elseif (strlen($formData['name']) > 100) {
        $errors['name'] = 'Category name cannot exceed 100 characters.';
    }
    
    // Check for duplicate
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = :name');
            $stmt->execute(['name' => $formData['name']]);
            
            if ($stmt->fetch()) {
                $errors['name'] = 'A category with this name already exists.';
            }
            
        } catch (PDOException $e) {
            error_log('Add category error: ' . $e->getMessage());
            $errors['general'] = 'Database error occurred.';
        } catch (Exception $e) {
            error_log('Add category error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred.';
        }
    }
    
    // Create category
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO categories (name, description) 
                VALUES (:name, :description)
            ');
            
            $result = $stmt->execute([
                'name' => $formData['name'],
                'description' => $formData['description']
            ]);
            
            if ($result) {
                $_SESSION['success_message'] = 'Category "' . $formData['name'] . '" added successfully!';
                header('Location: categories.php');
                exit;
            } else {
                $errors['general'] = 'Failed to add category. Please try again.';
            }
            
        } catch (PDOException $e) {
            error_log('Add category error: ' . $e->getMessage());
            $errors['general'] = 'Database error occurred.';
        } catch (Exception $e) {
            error_log('Add category error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred.';
        }
    }
}

$pageTitle = 'Add Category';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - Admin</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ============================================================
           SIMPLIFIED STYLES
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
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #1a1a2e;
            color: rgba(255, 255, 255, 0.8);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-brand i {
            font-size: 28px;
            color: #2C3E8F;
            background: white;
            padding: 8px;
            border-radius: 10px;
        }
        
        .sidebar-brand h2 {
            font-size: 20px;
            font-weight: 800;
            color: white;
        }
        
        .sidebar-brand small {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
        }
        
        .sidebar-nav .nav-section {
            padding: 0 16px 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-nav ul li {
            margin: 2px 12px;
        }
        
        .sidebar-nav ul li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .sidebar-nav ul li a i {
            width: 20px;
            font-size: 16px;
            text-align: center;
        }
        
        .sidebar-nav ul li a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }
        
        .sidebar-nav ul li a.active {
            background: #2C3E8F;
            color: white;
            box-shadow: 0 4px 12px rgba(44, 62, 143, 0.3);
        }
        
        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .sidebar-footer .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-footer .admin-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2C3E8F;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }
        
        .sidebar-footer .admin-info .admin-name {
            font-size: 14px;
            font-weight: 600;
            color: white;
        }
        
        .sidebar-footer .admin-info .admin-email {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar-footer .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.4);
            text-decoration: none;
            font-size: 13px;
            padding: 8px 0;
            transition: color 0.3s ease;
            margin-top: 8px;
        }
        
        .sidebar-footer .logout-btn:hover {
            color: #e74c3c;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 24px 32px;
            min-height: 100vh;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #1a1a2e;
            cursor: pointer;
            padding: 4px;
        }
        
        .topbar-left h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .topbar-left .breadcrumb {
            font-size: 14px;
            color: #6c757d;
        }
        
        .topbar-left .breadcrumb span {
            color: #2C3E8F;
            font-weight: 600;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #2C3E8F;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a2a6c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 62, 143, 0.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #1a1a2e;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        /* Form */
        .form-container {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #eef2f7;
            max-width: 600px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #1a1a2e;
            margin-bottom: 6px;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2C3E8F;
            background: white;
            box-shadow: 0 0 0 4px rgba(44, 62, 143, 0.1);
        }
        
        .form-control.is-invalid {
            border-color: #e74c3c;
            background: #fff5f5;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 13px;
            font-weight: 500;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .form-hint {
            color: #6c757d;
            font-size: 12px;
            margin-top: 4px;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        .relationship-info {
            background: #f8fafc;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .relationship-info p {
            font-size: 13px;
            color: #4a5568;
        }
        
        .relationship-info i {
            color: #2C3E8F;
            margin-right: 6px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        
        <div class="sidebar-brand">
            <i class="fas fa-store"></i>
            <div>
                <h2>My Shop</h2>
                <small>Admin Panel</small>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">Main</div>
            <ul>
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
            </ul>
            
            <div class="nav-section" style="margin-top: 16px;">Management</div>
            <ul>
                <li>
                    <a href="products.php">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li>
                    <a href="categories.php" class="active">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li>
                    <a href="orders.php">
                        <i class="fas fa-shopping-bag"></i> Orders
                    </a>
                </li>
            </ul>
            
            <div class="nav-section" style="margin-top: 16px;">Customers</div>
            <ul>
                <li>
                    <a href="users.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
            </ul>
            
            <div class="nav-section" style="margin-top: 16px;">Settings</div>
            <ul>
                <li>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <div class="admin-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
                    <div class="admin-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@myshop.com'); ?></div>
                </div>
            </div>
            <a href="<?php echo SITE_URL; ?>logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        
        <!-- Top Bar -->
        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1>Add Category</h1>
                    <div class="breadcrumb">
                        <a href="dashboard.php" style="color:#2C3E8F;text-decoration:none;">Dashboard</a>
                        / <a href="categories.php" style="color:#2C3E8F;text-decoration:none;">Categories</a>
                        / <span>Add</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Error Messages -->
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <div class="form-container">
            <form action="" method="POST">
                
                <!-- Category Name -->
                <div class="form-group">
                    <label for="name">Category Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" 
                           class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($formData['name']); ?>" 
                           placeholder="Enter category name"
                           required autofocus>
                    <?php if (isset($errors['name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['name']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">Examples: Electronics, Clothing, Books, Home & Garden</div>
                </div>
                
                <!-- Category Description -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              class="form-control"
                              placeholder="Enter a brief description of this category"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                    <div class="form-hint">Optional - helps describe what products belong in this category.</div>
                </div>
                
                <!-- Relationship Info -->
                <div class="relationship-info">
                    <p>
                        <i class="fas fa-info-circle"></i>
                        <strong>Database Relationship:</strong> 
                        Categories are linked to products. One category can have many products.
                        Deleting a category that has products will be restricted.
                    </p>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Category
                    </button>
                    <a href="categories.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
    </main>
    
    <!-- JavaScript -->
    <script>
        // Sidebar Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('active');
            });
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('active');
            });
        }
        
        console.log('✅ Add category page loaded successfully!');
    </script>
    
</body>
</html>