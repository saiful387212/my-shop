<?php
// ============================================================
// FILE: public/shop/product-edit.php
// PURPOSE: Shop owner edit product (NO ADMIN REQUIRED)
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// SIMPLE ACCESS CONTROL
// ============================================

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$shopModel = new Shop();
$shop = $shopModel->getByUser($_SESSION['user_id']);

if (!$shop || !$shop['is_approved']) {
    header('Location: ' . SITE_URL . 'shop/manage.php');
    exit;
}

// ============================================
// GET PRODUCT ID
// ============================================

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: manage.php');
    exit;
}

// ============================================
// GET PRODUCT
// ============================================

$product = null;
try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM products WHERE id = :id AND shop_id = :shop_id
    ");
    $stmt->execute([':id' => $productId, ':shop_id' => $shop['id']]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: manage.php');
        exit;
    }
    
    // Get categories
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
    
} catch (Exception $e) {
    error_log('Edit product error: ' . $e->getMessage());
    header('Location: manage.php');
    exit;
}

// ============================================
// PROCESS FORM
// ============================================

$errors = [];
$formData = [
    'name' => $product['name'],
    'price' => $product['price'],
    'description' => $product['description'] ?? '',
    'category_id' => $product['category_id'],
    'stock_quantity' => $product['stock_quantity'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $formData['name'] = sanitize($_POST['name'] ?? '');
    $formData['price'] = sanitize($_POST['price'] ?? '');
    $formData['description'] = sanitize($_POST['description'] ?? '');
    $formData['category_id'] = (int)($_POST['category_id'] ?? 0);
    $formData['stock_quantity'] = (int)($_POST['stock_quantity'] ?? 0);
    
    // Validation
    if (empty($formData['name'])) {
        $errors['name'] = 'Product name is required.';
    }
    
    if (empty($formData['price']) || !is_numeric($formData['price']) || $formData['price'] <= 0) {
        $errors['price'] = 'Price must be a positive number.';
    }
    
    if ($formData['category_id'] <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }
    
    // Image upload
    $imageName = $product['image_url'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($fileExt, $allowedExtensions)) {
            $errors['image'] = 'Invalid image format.';
        } elseif ($file['size'] > 5242880) {
            $errors['image'] = 'Image size cannot exceed 5MB.';
        }
        
        if (empty($errors)) {
            $newFileName = 'product_' . time() . '_' . uniqid() . '.' . $fileExt;
            $uploadPath = ABSPATH . 'public/uploads/products/';
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            // Delete old image
            if ($product['image_url'] && file_exists($uploadPath . $product['image_url'])) {
                unlink($uploadPath . $product['image_url']);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath . $newFileName)) {
                $imageName = $newFileName;
            } else {
                $errors['image'] = 'Failed to upload image.';
            }
        }
    }
    
    // Update product
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET 
                    category_id = :category_id,
                    name = :name,
                    description = :description,
                    price = :price,
                    stock_quantity = :stock_quantity,
                    image_url = :image_url,
                    updated_at = NOW()
                WHERE id = :id AND shop_id = :shop_id
            ");
            
            $result = $stmt->execute([
                ':id' => $productId,
                ':shop_id' => $shop['id'],
                ':category_id' => $formData['category_id'],
                ':name' => $formData['name'],
                ':description' => $formData['description'],
                ':price' => $formData['price'],
                ':stock_quantity' => $formData['stock_quantity'],
                ':image_url' => $imageName
            ]);
            
            if ($result) {
                $_SESSION['success_message'] = '✅ Product updated successfully!';
                header('Location: manage.php');
                exit;
            } else {
                $errors['general'] = 'Failed to update product.';
            }
            
        } catch (PDOException $e) {
            error_log('Edit product error: ' . $e->getMessage());
            $errors['general'] = 'Database error occurred.';
        }
    }
}

$pageTitle = 'Edit Product';
$categoriesForNav = $categories;
$cartCount = getCartTotalItems();

require_once ABSPATH . 'app/views/frontend/layout/header.php';
?>

<!-- Similar form as product-add.php with pre-filled values -->
<section class="add-product-page">
    <div class="container">
        <div class="page-header">
            <h1>Edit <span class="highlight">Product</span></h1>
            <p>Update your product details</p>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="name">Product Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" 
                           class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['name']); ?></div>
                    <?php endif; ?>
                </div>
                
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
                               class="form-control"
                               value="<?php echo htmlspecialchars($formData['stock_quantity']); ?>">
                    </div>
                </div>
                
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
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" 
                              class="form-control"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/*" 
                           class="form-control" onchange="previewImage(this)">
                    <div class="form-hint">Leave empty to keep current image. Allowed: JPG, PNG, GIF, WEBP. Max: 5MB</div>
                    
                    <?php if ($product['image_url']): ?>
                        <div class="current-image">
                            <img src="<?php echo SITE_URL; ?>uploads/products/<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="Current image"
                                 onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                            <span>Current image</span>
                        </div>
                    <?php endif; ?>
                    
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
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-save"></i> Update Product
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
/* Same styles as product-add.php */
.add-product-page { padding: 40px 0 60px; background: #f8fafc; min-height: calc(100vh - 200px); }
.page-header { text-align: center; margin-bottom: 40px; }
.page-header h1 { font-size: 32px; font-weight: 800; color: #1a1a2e; }
.page-header h1 .highlight { color: #2C3E8F; }
.page-header h1::after { content: ''; display: block; width: 60px; height: 4px; background: #2C3E8F; margin: 10px auto 0; border-radius: 2px; }
.page-header p { color: #6c757d; font-size: 16px; margin-top: 8px; }

.alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
.alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.alert i { font-size: 20px; }

.form-container { max-width: 700px; margin: 0 auto; background: white; padding: 35px; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #eef2f7; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-weight: 600; font-size: 14px; color: #1a1a2e; margin-bottom: 6px; }
.form-group label .required { color: #e74c3c; }
.form-control { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-family: 'Inter', sans-serif; transition: all 0.3s ease; background: #f8fafc; box-sizing: border-box; color: #1a1a2e; }
.form-control:focus { outline: none; border-color: #2C3E8F; background: white; box-shadow: 0 0 0 4px rgba(44,62,143,0.1); }
.form-control.is-invalid { border-color: #e74c3c; background: #fff5f5; }
textarea.form-control { resize: vertical; min-height: 120px; }
.error-message { color: #e74c3c; font-size: 13px; font-weight: 500; margin-top: 4px; display: flex; align-items: center; gap: 6px; }
.form-hint { color: #6c757d; font-size: 12px; margin-top: 4px; }

.image-preview { margin-top: 12px; border: 2px dashed #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; min-height: 150px; display: flex; align-items: center; justify-content: center; background: #f8fafc; }
.image-preview img { max-width: 100%; max-height: 200px; border-radius: 8px; }
.preview-placeholder { color: #a0aec0; }
.preview-placeholder i { font-size: 48px; display: block; margin-bottom: 8px; }
.preview-placeholder p { margin: 0; font-size: 14px; }

.current-image { display: flex; align-items: center; gap: 12px; margin-top: 8px; }
.current-image img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
.current-image span { font-size: 13px; color: #6c757d; }

.form-actions { display: flex; gap: 12px; margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f0f0; }
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.3s ease; text-decoration: none; }
.btn-primary { background: #2C3E8F; color: white; }
.btn-primary:hover { background: #1a2a6c; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(44,62,143,0.3); }
.btn-secondary { background: #e2e8f0; color: #1a1a2e; }
.btn-secondary:hover { background: #cbd5e0; }
.btn-submit { flex: 1; justify-content: center; padding: 14px; font-size: 16px; }

@media (max-width: 768px) { .form-container { padding: 24px; } .form-row { grid-template-columns: 1fr; } .form-actions { flex-direction: column; } }
@media (max-width: 480px) { .form-container { padding: 16px; } .page-header h1 { font-size: 24px; } }
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
        preview.innerHTML = `<div class="preview-placeholder"><i class="fas fa-image"></i><p>No image selected</p></div>`;
    }
}
</script>

<?php require_once ABSPATH . 'app/views/frontend/layout/footer.php'; ?>