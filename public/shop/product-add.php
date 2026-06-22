<?php
// ============================================================
// FILE: public/shop/product-add.php
// PURPOSE: Shop owner add product page (NO ADMIN REQUIRED)
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// SIMPLE ACCESS CONTROL - NO ADMIN CHECK
// ============================================

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to manage your shop.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$shopModel = new Shop();

// ============================================
// GET USER'S SHOP
// ============================================

$shop = $shopModel->getByUser($_SESSION['user_id']);

if (!$shop) {
    header('Location: ' . SITE_URL . 'shop/create.php');
    exit;
}

// Check if shop is approved
if (!$shop['is_approved']) {
    $_SESSION['error_message'] = 'Your shop is pending approval. You cannot add products yet.';
    header('Location: ' . SITE_URL . 'shop/manage.php');
    exit;
}

// ============================================
// GET CATEGORIES
// ============================================

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Product add categories error: ' . $e->getMessage());
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

$success = false;

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
    
    if (empty($formData['name'])) {
        $errors['name'] = 'Product name is required.';
    } elseif (strlen($formData['name']) < 2) {
        $errors['name'] = 'Product name must be at least 2 characters.';
    } elseif (strlen($formData['name']) > 200) {
        $errors['name'] = 'Product name cannot exceed 200 characters.';
    }
    
    if (empty($formData['price'])) {
        $errors['price'] = 'Price is required.';
    } elseif (!is_numeric($formData['price']) || $formData['price'] <= 0) {
        $errors['price'] = 'Price must be a positive number.';
    }
    
    if ($formData['category_id'] <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }
    
    if ($formData['stock_quantity'] < 0) {
        $errors['stock_quantity'] = 'Stock quantity cannot be negative.';
    }
    
    // ============================================
    // IMAGE UPLOAD
    // ============================================
    
    $imageName = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($fileExt, $allowedExtensions)) {
            $errors['image'] = 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP.';
        }
        
        if ($file['size'] > 5242880) {
            $errors['image'] = 'Image size cannot exceed 5MB.';
        }
        
        if (empty($errors)) {
            $newFileName = 'product_' . time() . '_' . uniqid() . '.' . $fileExt;
            $uploadPath = ABSPATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR;
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath . $newFileName)) {
                $imageName = $newFileName;
            } else {
                $errors['image'] = 'Failed to upload image. Please try again.';
            }
        }
    }
    
    // ============================================
    // INSERT PRODUCT
    // ============================================
    
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    shop_id,
                    category_id,
                    name,
                    description,
                    price,
                    stock_quantity,
                    image_url,
                    status,
                    is_active
                ) VALUES (
                    :shop_id,
                    :category_id,
                    :name,
                    :description,
                    :price,
                    :stock_quantity,
                    :image_url,
                    'pending',
                    1
                )
            ");
            
            $result = $stmt->execute([
                ':shop_id' => $shop['id'],
                ':category_id' => $formData['category_id'],
                ':name' => $formData['name'],
                ':description' => $formData['description'],
                ':price' => $formData['price'],
                ':stock_quantity' => $formData['stock_quantity'],
                ':image_url' => $imageName
            ]);
            
            if ($result) {
                $success = true;
                $_SESSION['success_message'] = '✅ Product added successfully! It will be visible after admin approval.';
                $formData = ['name' => '', 'price' => '', 'description' => '', 'category_id' => '', 'stock_quantity' => ''];
            } else {
                $errors['general'] = 'Failed to add product. Please try again.';
            }
            
        } catch (PDOException $e) {
            error_log('Add product error: ' . $e->getMessage());
            $errors['general'] = 'Database error occurred.';
        } catch (Exception $e) {
            error_log('Add product error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred.';
        }
    }
}

// ============================================
// GET CATEGORIES FOR HEADER
// ============================================

$categoriesForNav = [];
try {
    $productModel = new Product();
    $categoriesForNav = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Product add categories error: ' . $e->getMessage());
}

$cartCount = getCartTotalItems();
$pageTitle = 'Add Product';

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<section class="add-product-page">
    <div class="container">
        
        <div class="page-header">
            <h1>Add <span class="highlight">Product</span></h1>
            <p>Add a new product to your shop</p>
        </div>
        
        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Error Messages -->
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            
            <div class="form-header">
                <i class="fas fa-box"></i>
                <h2>Product Details</h2>
                <p>Fill in the information below to add a new product</p>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data">
                
                <!-- Product Name -->
                <div class="form-group">
                    <label for="name">Product Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" 
                           class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                           placeholder="Enter product name"
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
                               placeholder="0.00"
                               value="<?php echo htmlspecialchars($formData['price']); ?>" required>
                        <?php if (isset($errors['price'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['price']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity <span class="required">*</span></label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" 
                               class="form-control <?php echo isset($errors['stock_quantity']) ? 'is-invalid' : ''; ?>"
                               placeholder="0"
                               value="<?php echo htmlspecialchars($formData['stock_quantity']); ?>" required>
                        <?php if (isset($errors['stock_quantity'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['stock_quantity']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Category -->
                <div class="form-group">
                    <label for="category_id">Category <span class="required">*</span></label>
                    <select id="category_id" name="category_id" 
                            class="form-control <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>" required>
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
                              class="form-control"
                              placeholder="Describe your product"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                </div>
                
                <!-- Image Upload -->
                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/*" 
                           class="form-control" onchange="previewImage(this)">
                    <div class="form-hint">Allowed: JPG, PNG, GIF, WEBP. Max: 5MB</div>
                    <div class="image-preview" id="imagePreview">
                        <div class="preview-placeholder">
                            <i class="fas fa-image"></i>
                            <p>No image selected</p>
                        </div>
                    </div>
                    <?php if (isset($errors['image'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['image']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Shop Info -->
                <div class="shop-info-box">
                    <i class="fas fa-store"></i>
                    <div>
                        <strong>Shop:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?>
                        <br>
                        <small style="color:#6c757d;">Product will be added to this shop</small>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-save"></i> Add Product
                    </button>
                    <a href="manage.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
            
        </div>
        
    </div>
</section>

<style>
/* ============================================
   ADD PRODUCT PAGE
   ============================================ */

.add-product-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 32px;
    font-weight: 800;
    color: #1a1a2e;
}

.page-header h1 .highlight {
    color: #2C3E8F;
}

.page-header h1::after {
    content: '';
    display: block;
    width: 60px;
    height: 4px;
    background: #2C3E8F;
    margin: 10px auto 0;
    border-radius: 2px;
}

.page-header p {
    color: #6c757d;
    font-size: 16px;
    margin-top: 8px;
}

/* Alerts */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
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

/* Form Container */
.form-container {
    max-width: 700px;
    margin: 0 auto;
    background: white;
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
}

.form-header {
    text-align: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.form-header i {
    font-size: 40px;
    color: #2C3E8F;
    display: block;
    margin-bottom: 8px;
}

.form-header h2 {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a2e;
}

.form-header p {
    color: #6c757d;
    font-size: 14px;
}

.required {
    color: #e74c3c;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    color: #1a1a2e;
    margin-bottom: 6px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
    background: #f8fafc;
    box-sizing: border-box;
    color: #1a1a2e;
}

.form-control:focus {
    outline: none;
    border-color: #2C3E8F;
    background: white;
    box-shadow: 0 0 0 4px rgba(44,62,143,0.1);
}

.form-control.is-invalid {
    border-color: #e74c3c;
    background: #fff5f5;
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
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

/* Image Preview */
.image-preview {
    margin-top: 12px;
    border: 2px dashed #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    min-height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
}

.image-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
}

.preview-placeholder {
    color: #a0aec0;
}

.preview-placeholder i {
    font-size: 48px;
    display: block;
    margin-bottom: 8px;
}

.preview-placeholder p {
    margin: 0;
    font-size: 14px;
}

/* Shop Info Box */
.shop-info-box {
    background: #f0f4ff;
    border-radius: 10px;
    padding: 16px 20px;
    margin: 16px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #cce5ff;
}

.shop-info-box i {
    font-size: 24px;
    color: #2C3E8F;
}

.shop-info-box strong {
    color: #1a1a2e;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #f0f0f0;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-primary {
    background: #2C3E8F;
    color: white;
}

.btn-primary:hover {
    background: #1a2a6c;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(44,62,143,0.3);
}

.btn-secondary {
    background: #e2e8f0;
    color: #1a1a2e;
}

.btn-secondary:hover {
    background: #cbd5e0;
}

.btn-submit {
    flex: 1;
    justify-content: center;
    padding: 14px;
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .form-container {
        padding: 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .form-container {
        padding: 16px;
    }
    
    .page-header h1 {
        font-size: 24px;
    }
}
</style>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = `
            <div class="preview-placeholder">
                <i class="fas fa-image"></i>
                <p>No image selected</p>
            </div>
        `;
    }
}

console.log('✅ Add product page loaded successfully!');
</script>

<?php require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php'; ?>