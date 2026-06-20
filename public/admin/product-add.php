<?php
// ============================================================
// FILE: public/admin/product-add.php
// PURPOSE: Add a new product with image upload
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load Product model
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

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
// FETCH CATEGORIES FOR DROPDOWN
// ============================================

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Error fetching categories: ' . $e->getMessage());
}

// ============================================
// PROCESS FORM SUBMISSION
// ============================================

$errors = [];
$formData = [
    'name' => '',
    'price' => '',
    'description' => '',
    'category_id' => '',
    'stock_quantity' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data
    $formData['name'] = sanitize($_POST['name'] ?? '');
    $formData['price'] = sanitize($_POST['price'] ?? '');
    $formData['description'] = sanitize($_POST['description'] ?? '');
    $formData['category_id'] = (int)($_POST['category_id'] ?? 0);
    $formData['stock_quantity'] = (int)($_POST['stock_quantity'] ?? 0);
    
    // ============================================
    // VALIDATION
    // ============================================
    
    // Name validation
    if (empty($formData['name'])) {
        $errors['name'] = 'Product name is required.';
    } elseif (strlen($formData['name']) < 2) {
        $errors['name'] = 'Product name must be at least 2 characters.';
    } elseif (strlen($formData['name']) > 200) {
        $errors['name'] = 'Product name cannot exceed 200 characters.';
    }
    
    // Price validation
    if (empty($formData['price'])) {
        $errors['price'] = 'Price is required.';
    } elseif (!is_numeric($formData['price']) || $formData['price'] <= 0) {
        $errors['price'] = 'Price must be a positive number.';
    }
    
    // Category validation
    if ($formData['category_id'] <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }
    
    // Stock validation
    if ($formData['stock_quantity'] < 0) {
        $errors['stock_quantity'] = 'Stock quantity cannot be negative.';
    }
    
    // ============================================
    // IMAGE UPLOAD
    // ============================================
    
    $imageName = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        // Get file extension
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Validate file extension
        if (!in_array($fileExt, $allowedExtensions)) {
            $errors['image'] = 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP.';
        }
        
        // Validate file size (max 5MB)
        if ($fileSize > 5242880) {
            $errors['image'] = 'Image size cannot exceed 5MB.';
        }
        
        // If no errors, process upload
        if (empty($errors)) {
            // Generate unique filename
            $newFileName = 'product_' . time() . '_' . uniqid() . '.' . $fileExt;
            $uploadPath = ABSPATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR;
            
            // Create uploads folder if it doesn't exist
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($fileTmp, $uploadPath . $newFileName)) {
                $imageName = $newFileName;
            } else {
                $errors['image'] = 'Failed to upload image. Please try again.';
            }
        }
    }
    
    // ============================================
    // CREATE PRODUCT
    // ============================================
    
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO products (category_id, name, description, price, stock_quantity, image_url) 
                VALUES (:category_id, :name, :description, :price, :stock_quantity, :image_url)
            ');
            
            $result = $stmt->execute([
                'category_id' => $formData['category_id'],
                'name' => $formData['name'],
                'description' => $formData['description'],
                'price' => $formData['price'],
                'stock_quantity' => $formData['stock_quantity'],
                'image_url' => $imageName
            ]);
            
            if ($result) {
                $_SESSION['success_message'] = 'Product "' . $formData['name'] . '" added successfully!';
                header('Location: products.php');
                exit;
            } else {
                $errors['general'] = 'Failed to add product. Please try again.';
            }
            
        } catch (PDOException $e) {
            error_log('Add product error: ' . $e->getMessage());
            $errors['general'] = 'Database error occurred. Please try again.';
        } catch (Exception $e) {
            error_log('Add product error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Add Product';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ============================================================
           SIMPLIFIED STYLES (Same as products.php but with form)
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
        
        /* Sidebar - Same as products.php */
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
            max-width: 800px;
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
        
        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        
        .image-preview {
            margin-top: 12px;
            max-width: 200px;
        }
        
        .image-preview img {
            width: 100%;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        /* Alert */
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
            
            .form-row {
                grid-template-columns: 1fr;
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
                    <a href="products.php" class="active">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li>
                    <a href="categories.php">
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
                    <h1>Add Product</h1>
                    <div class="breadcrumb">
                        <a href="dashboard.php" style="color:#2C3E8F;text-decoration:none;">Dashboard</a>
                        / <a href="products.php" style="color:#2C3E8F;text-decoration:none;">Products</a>
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
            <form action="" method="POST" enctype="multipart/form-data">
                
                <!-- Name -->
                <div class="form-group">
                    <label for="name">Product Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['name']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Price & Stock Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0" 
                               class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($formData['price']); ?>" required>
                        <?php if (isset($errors['price'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['price']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" 
                               class="form-control <?php echo isset($errors['stock_quantity']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($formData['stock_quantity']); ?>">
                        <?php if (isset($errors['stock_quantity'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['stock_quantity']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Category -->
                <div class="form-group">
                    <label for="category_id">Category <span class="required">*</span></label>
                    <select id="category_id" name="category_id" class="form-control <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo ($formData['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['category_id']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Description -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" 
                              class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['description']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Image Upload -->
                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/*" 
                           class="form-control" onchange="previewImage(this)">
                    <div class="form-hint">Allowed: JPG, PNG, GIF, WEBP. Max: 5MB</div>
                    <div class="image-preview" id="imagePreview"></div>
                    <?php if (isset($errors['image'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['image']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Product
                    </button>
                    <a href="products.php" class="btn btn-secondary">
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
        
        // Image Preview
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = '';
            }
        }
        
        console.log('✅ Add product page loaded successfully!');
    </script>
    
</body>
</html>