<?php
// ============================================================
// FILE: public/admin/products.php
// PURPOSE: Admin product management
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// SIMPLE ADMIN CHECK
// ============================================

// If not logged in, go to login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

// If not admin, go to home
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

// ============================================
// GET PRODUCTS
// ============================================

$products = [];
$error = '';

try {
    $productModel = new Product();
    $products = $productModel->getAllWithCategory();
} catch (Exception $e) {
    $error = 'Could not load products: ' . $e->getMessage();
}

// Get messages
$successMsg = $_SESSION['success_message'] ?? '';
$errorMsg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$userName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            min-height: 100vh;
        }
        
        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 250px;
            background: #1a1a2e;
            color: white;
            min-height: 100vh;
            padding: 20px 0;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-brand h2 {
            font-size: 22px;
        }
        
        .sidebar-brand span {
            color: #4A6CCF;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0 10px;
        }
        
        .sidebar-menu li a {
            display: block;
            padding: 12px 16px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar-menu li a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu li a.active {
            background: #2C3E8F;
            color: white;
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 20px;
        }
        
        .sidebar-footer .admin-name {
            font-weight: bold;
            color: white;
        }
        
        .sidebar-footer .admin-email {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
        }
        
        .sidebar-footer .logout-btn {
            display: block;
            margin-top: 10px;
            color: #e74c3c;
            text-decoration: none;
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .topbar h1 {
            font-size: 24px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #2C3E8F;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a2a6c;
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
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        /* ===== ALERTS ===== */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* ===== TABLE ===== */
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .search-box input {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: #f8f9fa;
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #6c757d;
            border-bottom: 2px solid #dee2e6;
        }
        
        table td {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        table tr:hover td {
            background: #f8f9fa;
        }
        
        .product-image-cell {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            background: #f8f9fa;
        }
        
        .product-name {
            font-weight: 600;
        }
        
        .product-price {
            color: #2C3E8F;
            font-weight: 700;
        }
        
        .stock-badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .stock-badge.in-stock {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-badge.low-stock {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-badge.out-of-stock {
            background: #f8d7da;
            color: #721c24;
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
            font-size: 48px;
            color: #ccc;
            margin-bottom: 16px;
        }
        
        .empty-state h3 {
            color: #6c757d;
        }
        
        /* ===== MODAL ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        
        .modal-content .icon {
            font-size: 48px;
            color: #e74c3c;
            margin-bottom: 16px;
        }
        
        .modal-content .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box input {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
    <div class="sidebar-brand">
        <h2>My <span>Shop</span></h2>
        <small style="color:rgba(255,255,255,0.5);">Admin Panel</small>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="products.php" class="active"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
        <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
    </ul>
    
    <div class="sidebar-footer">
        <div class="admin-name"><?php echo htmlspecialchars($userName); ?></div>
        <div class="admin-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@myshop.com'); ?></div>
        <a href="<?php echo SITE_URL; ?>logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">
    
    <!-- Top Bar -->
    <div class="topbar">
        <div>
            <h1>Manage Products</h1>
            <small style="color:#6c757d;">Dashboard / Products</small>
        </div>
        <a href="product-add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Product
        </a>
    </div>
    
    <!-- Messages -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Products Table -->
    <div class="table-container">
        
        <div class="table-header">
            <h3><i class="fas fa-box"></i> All Products (<?php echo count($products); ?>)</h3>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search products..." onkeyup="searchTable()">
            </div>
        </div>
        
        <?php if (!empty($products)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <img src="<?php 
                                    echo !empty($p['image_url']) 
                                        ? '../uploads/products/' . htmlspecialchars($p['image_url']) 
                                        : '../assets/images/no-image.png'; 
                                ?>" 
                                     class="product-image-cell"
                                     onerror="this.src='../assets/images/no-image.png'">
                            </td>
                            <td>
                                <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div style="font-size:12px;color:#6c757d;">
                                    <?php echo htmlspecialchars(substr($p['description'] ?? '', 0, 40)); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($p['category_name'] ?? 'Uncategorized'); ?></td>
                            <td class="product-price">$<?php echo number_format($p['price'], 2); ?></td>
                            <td>
                                <?php 
                                if ($p['stock_quantity'] <= 0) {
                                    $cls = 'out-of-stock';
                                    $txt = 'Out of Stock';
                                } elseif ($p['stock_quantity'] < 10) {
                                    $cls = 'low-stock';
                                    $txt = 'Low: ' . $p['stock_quantity'];
                                } else {
                                    $cls = 'in-stock';
                                    $txt = $p['stock_quantity'];
                                }
                                ?>
                                <span class="stock-badge <?php echo $cls; ?>"><?php echo $txt; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="product-edit.php?id=<?php echo $p['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-danger btn-sm delete-btn" 
                                            data-id="<?php echo $p['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($p['name']); ?>">
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
                <i class="fas fa-box-open"></i>
                <h3>No Products Found</h3>
                <p style="color:#6c757d;">Start by adding your first product.</p>
                <a href="product-add.php" class="btn btn-primary" style="margin-top:16px;">
                    <i class="fas fa-plus"></i> Add Product
                </a>
            </div>
        <?php endif; ?>
        
    </div>
    
</div>

<!-- ===== DELETE MODAL ===== -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content">
        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h3>Delete Product</h3>
        <p>Are you sure you want to delete <strong id="deleteName"></strong>? This cannot be undone.</p>
        <div class="btn-group">
            <button class="btn" style="background:#e2e8f0;color:#1a1a2e;" onclick="closeModal()">Cancel</button>
            <form method="POST" action="product-delete.php">
                <input type="hidden" name="product_id" id="deleteId">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
            </form>
        </div>
    </div>
</div>

<!-- ===== JAVASCRIPT ===== -->
<script>
// Search
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#tableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Delete Modal
const modal = document.getElementById('deleteModal');
const deleteId = document.getElementById('deleteId');
const deleteName = document.getElementById('deleteName');

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        deleteId.value = this.dataset.id;
        deleteName.textContent = this.dataset.name;
        modal.classList.add('active');
    });
});

function closeModal() {
    modal.classList.remove('active');
}

modal.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

console.log('✅ Admin products page loaded');
</script>

</body>
</html>
