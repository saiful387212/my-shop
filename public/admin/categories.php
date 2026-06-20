<?php
// ============================================================
// FILE: public/admin/categories.php
// PURPOSE: Display all categories with edit/delete options
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load Category model
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Category.php';

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
// FETCH ALL CATEGORIES
// ============================================

try {
    $categoryModel = new Category();
    $categories = $categoryModel->getAllWithProductCount();
} catch (Exception $e) {
    error_log('Categories page error: ' . $e->getMessage());
    $categories = [];
}

// ============================================
// CHECK FOR SUCCESS/ERROR MESSAGES
// ============================================

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ============================================================
           ADMIN CATEGORIES STYLES
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
        
        /* ============================================
           SIDEBAR
           ============================================ */
        
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
        
        .sidebar-nav ul li a .badge {
            margin-left: auto;
            background: #e74c3c;
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
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
        
        /* ============================================
           MAIN CONTENT
           ============================================ */
        
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
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
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
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #219a52;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d68910;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #1a1a2e;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* ============================================
           MESSAGES
           ============================================ */
        
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        /* ============================================
           CATEGORIES TABLE
           ============================================ */
        
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #eef2f7;
            overflow-x: auto;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .table-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background: #f8fafc;
        }
        
        table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid #e2e8f0;
        }
        
        table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            font-size: 14px;
        }
        
        table tr:hover td {
            background: #f8fafc;
        }
        
        .category-name {
            font-weight: 600;
            font-size: 15px;
        }
        
        .category-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            background: #e8edf9;
            color: #2C3E8F;
        }
        
        .product-count-badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            background: #e8edf9;
            color: #2C3E8F;
        }
        
        .action-buttons {
            display: flex;
            gap: 6px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #cbd5e0;
            margin-bottom: 16px;
        }
        
        .empty-state h3 {
            font-size: 20px;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #6c757d;
        }
        
        .relationship-diagram {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 1px dashed #cbd5e0;
        }
        
        .relationship-diagram h4 {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 12px;
        }
        
        .relationship-diagram .diagram {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #4a5568;
            white-space: pre;
            line-height: 1.8;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        
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
            
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            table {
                font-size: 13px;
            }
            
            table td, table th {
                padding: 8px 10px;
            }
        }
        
        @media (max-width: 480px) {
            .table-container {
                padding: 16px;
            }
            
            .category-description-cell {
                display: none;
            }
        }
    </style>
</head>
<body>
    
    <!-- ============================================
         SIDEBAR OVERLAY (Mobile)
         ============================================ -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- ============================================
         SIDEBAR
         ============================================ -->
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
    
    <!-- ============================================
         MAIN CONTENT
         ============================================ -->
    <main class="main-content">
        
        <!-- Top Bar -->
        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1>Manage Categories</h1>
                    <div class="breadcrumb">
                        <a href="dashboard.php" style="color:#2C3E8F;text-decoration:none;">Dashboard</a>
                        / <span>Categories</span>
                    </div>
                </div>
            </div>
            <div class="topbar-right">
                <a href="category-add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Category
                </a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <!-- Categories Table -->
        <div class="table-container">
            
            <div class="table-header">
                <h3>
                    <i class="fas fa-tags" style="color:#2C3E8F;"></i>
                    All Categories (<?php echo count($categories); ?>)
                </h3>
            </div>
            
            <?php if (!empty($categories)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $index => $category): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div class="category-icon">
                                            <?php 
                                            $icons = ['fa-tag', 'fa-laptop', 'fa-book', 'fa-tshirt', 'fa-home', 'fa-gem', 'fa-tools', 'fa-paw', 'fa-car', 'fa-utensils'];
                                            $iconIndex = $category['id'] % count($icons);
                                            ?>
                                            <i class="fas <?php echo $icons[$iconIndex]; ?>"></i>
                                        </div>
                                        <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                    </div>
                                </td>
                                <td class="category-description-cell">
                                    <?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 60)); ?>
                                    <?php if (strlen($category['description'] ?? '') > 60): ?>...<?php endif; ?>
                                </td>
                                <td>
                                    <span class="product-count-badge">
                                        <?php echo $category['product_count'] ?? 0; ?> products
                                    </span>
                                </td>
                                <td style="font-size:13px;color:#6c757d;">
                                    <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="category-edit.php?id=<?php echo $category['id']; ?>" 
                                           class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-danger btn-sm delete-category" 
                                                data-id="<?php echo $category['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                data-products="<?php echo $category['product_count'] ?? 0; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h3>No Categories Found</h3>
                    <p>Start by adding your first product category.</p>
                    <a href="category-add.php" class="btn btn-primary" style="margin-top:16px;">
                        <i class="fas fa-plus"></i> Add Category
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- ============================================
                 DATABASE RELATIONSHIP DIAGRAM
                 ============================================ -->
            <div class="relationship-diagram">
                <h4><i class="fas fa-sitemap" style="color:#2C3E8F;"></i> Database Relationship</h4>
                <div class="diagram">
┌─────────────────────────────────────────────────────────────────────────────┐
│                           categories                                       │
│                                                                             │
│    ┌──────────────────────────────────────────────────────┐                │
│    │  id (PRIMARY KEY)    name    description    created │                │
│    └──────────────────────────────────────────────────────┘                │
│                              │                                             │
│                              │ ONE                                         │
│                              │                                             │
│                              ▼                                             │
│    ┌──────────────────────────────────────────────────────┐                │
│    │  products                                            │                │
│    ├──────────────────────────────────────────────────────┤                │
│    │  id (PRIMARY KEY)                                    │                │
│    │  category_id (FOREIGN KEY → categories.id)  ◄────────┘                │
│    │  name    description    price    stock_quantity    image_url          │
│    └──────────────────────────────────────────────────────┘                │
│                                                                             │
│    Relationship: One Category → Many Products                              │
│    Constraint: ON DELETE RESTRICT (can't delete category with products)    │
└─────────────────────────────────────────────────────────────────────────────┘
                </div>
            </div>
        </div>
        
    </main>
    
    <!-- ============================================
         DELETE CONFIRMATION MODAL
         ============================================ -->
    <div id="deleteModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
        <div style="background:white;padding:32px;border-radius:16px;max-width:450px;width:90%;text-align:center;">
            <i class="fas fa-exclamation-triangle" style="font-size:48px;color:#e74c3c;margin-bottom:16px;"></i>
            <h3 style="margin-bottom:8px;">Delete Category</h3>
            <p style="color:#6c757d;margin-bottom:8px;">
                Are you sure you want to delete <strong id="deleteCategoryName"></strong>?
            </p>
            <p id="deleteWarning" style="color:#e74c3c;font-size:14px;font-weight:600;margin-bottom:20px;"></p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">
                    Cancel
                </button>
                <form id="deleteForm" method="POST" action="category-delete.php">
                    <input type="hidden" name="category_id" id="deleteCategoryId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script>
        // ============================================
        // SIDEBAR TOGGLE
        // ============================================
        
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
        
        // ============================================
        // DELETE CONFIRMATION
        // ============================================
        
        const deleteModal = document.getElementById('deleteModal');
        const deleteCategoryId = document.getElementById('deleteCategoryId');
        const deleteCategoryName = document.getElementById('deleteCategoryName');
        const deleteWarning = document.getElementById('deleteWarning');
        
        document.querySelectorAll('.delete-category').forEach(function(button) {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const productCount = parseInt(this.getAttribute('data-products')) || 0;
                
                deleteCategoryId.value = id;
                deleteCategoryName.textContent = name;
                
                if (productCount > 0) {
                    deleteWarning.textContent = '⚠️ This category has ' + productCount + ' product(s). Deleting it will require reassigning products.';
                    deleteWarning.style.display = 'block';
                } else {
                    deleteWarning.textContent = 'This category has no products and can be safely deleted.';
                    deleteWarning.style.display = 'block';
                    deleteWarning.style.color = '#27ae60';
                }
                
                deleteModal.style.display = 'flex';
            });
        });
        
        function closeDeleteModal() {
            deleteModal.style.display = 'none';
        }
        
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
        
        console.log('✅ Category management loaded successfully!');
    </script>
    
</body>
</html>