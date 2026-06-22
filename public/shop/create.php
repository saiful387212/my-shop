<?php
// ============================================================
// FILE: public/shop/create.php
// PURPOSE: Create a new shop
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF USER IS LOGGED IN
// ============================================

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to create a shop.';
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$shopModel = new Shop();

// ============================================
// CHECK IF USER ALREADY HAS A SHOP
// ============================================

if ($shopModel->userHasShop($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'shop/manage.php');
    exit;
}

// ============================================
// PROCESS FORM SUBMISSION
// ============================================

$errors = [];
$formData = ['shop_name' => '', 'description' => '', 'address' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $formData['shop_name'] = sanitize($_POST['shop_name'] ?? '');
    $formData['description'] = sanitize($_POST['description'] ?? '');
    $formData['address'] = sanitize($_POST['address'] ?? '');
    $formData['phone'] = sanitize($_POST['phone'] ?? '');
    
    // Validation
    if (empty($formData['shop_name'])) {
        $errors['shop_name'] = 'Shop name is required.';
    } elseif (strlen($formData['shop_name']) < 3) {
        $errors['shop_name'] = 'Shop name must be at least 3 characters.';
    } elseif (strlen($formData['shop_name']) > 50) {
        $errors['shop_name'] = 'Shop name cannot exceed 50 characters.';
    }
    
    if (empty($errors)) {
        $data = [
            'shop_name' => $formData['shop_name'],
            'description' => $formData['description'],
            'address' => $formData['address'],
            'phone' => $formData['phone'],
            'email' => $_SESSION['user_email'] ?? ''
        ];
        
        if ($shopModel->create($_SESSION['user_id'], $data)) {
            $_SESSION['success_message'] = '✅ Shop created successfully! Waiting for admin approval.';
            header('Location: ' . SITE_URL . 'shop/manage.php');
            exit;
        } else {
            $errors['general'] = 'Failed to create shop. Please try again.';
        }
    }
}

// ============================================
// GET CATEGORIES FOR HEADER
// ============================================

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Create shop categories error: ' . $e->getMessage());
}

$cartCount = getCartTotalItems();
$pageTitle = 'Create Shop';

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<section class="create-shop-page">
    <div class="container">
        
        <div class="page-header">
            <h1>Create Your <span class="highlight">Shop</span></h1>
            <p>Start selling your products today</p>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <div class="shop-form-container">
            
            <div class="form-header">
                <i class="fas fa-store"></i>
                <h2>Shop Information</h2>
                <p>Fill in the details below to create your shop</p>
            </div>
            
            <form method="POST" action="">
                
                <div class="form-group">
                    <label for="shop_name">Shop Name <span class="required">*</span></label>
                    <input type="text" id="shop_name" name="shop_name" 
                           class="form-control <?php echo isset($errors['shop_name']) ? 'is-invalid' : ''; ?>"
                           placeholder="Enter your shop name"
                           value="<?php echo htmlspecialchars($formData['shop_name']); ?>" required>
                    <?php if (isset($errors['shop_name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['shop_name']); ?></div>
                    <?php endif; ?>
                    <small class="form-hint">This will be your shop URL: myshop.com/shop/your-shop-name</small>
                </div>
                
                <div class="form-group">
                    <label for="description">Shop Description</label>
                    <textarea id="description" name="description" rows="4" 
                              class="form-control"
                              placeholder="Tell customers about your shop"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Shop Address</label>
                        <input type="text" id="address" name="address" 
                               class="form-control"
                               placeholder="123 Main Street, City"
                               value="<?php echo htmlspecialchars($formData['address']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               class="form-control"
                               placeholder="+1 (555) 123-4567"
                               value="<?php echo htmlspecialchars($formData['phone']); ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-create">
                        <i class="fas fa-store"></i> Create Shop
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Note:</strong> After creating your shop, an admin will review and approve it. 
                    You will be notified once your shop is approved.
                </div>
            </div>
        </div>
        
    </div>
</section>

<style>
/* ============================================
   CREATE SHOP PAGE
   ============================================ */

.create-shop-page {
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

.shop-form-container {
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
    margin-top: 8px;
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

.btn-create {
    flex: 1;
    justify-content: center;
    padding: 14px;
    font-size: 16px;
}

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert i {
    font-size: 20px;
}

.info-box {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    background: #f0f4ff;
    border-radius: 10px;
    margin-top: 20px;
    border: 1px solid #cce5ff;
}

.info-box i {
    color: #2C3E8F;
    font-size: 20px;
    margin-top: 2px;
}

.info-box div {
    font-size: 14px;
    color: #4a5568;
    line-height: 1.6;
}

.info-box strong {
    color: #1a1a2e;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 768px) {
    .shop-form-container {
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
    .shop-form-container {
        padding: 16px;
    }
    
    .page-header h1 {
        font-size: 24px;
    }
}
</style>

<?php require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php'; ?>