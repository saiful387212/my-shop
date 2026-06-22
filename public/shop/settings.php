<?php
// ============================================================
// FILE: public/shop/settings.php
// PURPOSE: Vendor shop settings management
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
// CHECK IF USER IS LOGGED IN
// ============================================

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

// ============================================
// PROCESS FORM SUBMISSION
// ============================================

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $shopName = sanitize($_POST['shop_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    
    // Validation
    if (empty($shopName)) {
        $errors['shop_name'] = 'Shop name is required.';
    } elseif (strlen($shopName) < 3) {
        $errors['shop_name'] = 'Shop name must be at least 3 characters.';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            $stmt = $pdo->prepare("
                UPDATE shops 
                SET 
                    shop_name = :shop_name,
                    description = :description,
                    address = :address,
                    phone = :phone,
                    email = :email,
                    updated_at = NOW()
                WHERE id = :id AND user_id = :user_id
            ");
            
            $result = $stmt->execute([
                ':shop_name' => $shopName,
                ':description' => $description,
                ':address' => $address,
                ':phone' => $phone,
                ':email' => $email,
                ':id' => $shop['id'],
                ':user_id' => $_SESSION['user_id']
            ]);
            
            if ($result) {
                $success = true;
                $_SESSION['success_message'] = '✅ Shop settings updated successfully!';
                
                // Refresh shop data
                $shop = $shopModel->getByUser($_SESSION['user_id']);
            } else {
                $errors['general'] = 'Failed to update shop settings.';
            }
            
        } catch (PDOException $e) {
            error_log('Shop settings error: ' . $e->getMessage());
            $errors['general'] = 'Database error occurred.';
        } catch (Exception $e) {
            error_log('Shop settings error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred.';
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
    error_log('Shop settings categories error: ' . $e->getMessage());
}

// ============================================
// MESSAGES
// ============================================

$successMsg = $_SESSION['success_message'] ?? '';
$errorMsg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$cartCount = getCartTotalItems();
$pageTitle = 'Shop Settings';

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<section class="shop-settings-page">
    <div class="container">
        
        <div class="page-header">
            <h1>Shop <span class="highlight">Settings</span></h1>
            <p>Manage your shop information</p>
        </div>
        
        <!-- Shop Status -->
        <div class="shop-status">
            <div class="status-badge <?php echo $shop['is_approved'] ? 'approved' : 'pending'; ?>">
                <?php if ($shop['is_approved']): ?>
                    <i class="fas fa-check-circle"></i> Shop Approved
                <?php else: ?>
                    <i class="fas fa-clock"></i> Pending Approval
                <?php endif; ?>
            </div>
            <?php if (!$shop['is_approved']): ?>
                <p class="status-message">Your shop is waiting for admin approval. You can still update settings.</p>
            <?php endif; ?>
        </div>
        
        <!-- Messages -->
        <?php if ($successMsg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Settings Form -->
        <div class="settings-container">
            
            <div class="form-header">
                <i class="fas fa-store"></i>
                <h2>Shop Information</h2>
                <p>Update your shop details</p>
            </div>
            
            <form method="POST" action="">
                
                <!-- Shop Name -->
                <div class="form-group">
                    <label for="shop_name">Shop Name <span class="required">*</span></label>
                    <input type="text" id="shop_name" name="shop_name" 
                           class="form-control <?php echo isset($errors['shop_name']) ? 'is-invalid' : ''; ?>"
                           placeholder="Enter your shop name"
                           value="<?php echo htmlspecialchars($shop['shop_name']); ?>" required>
                    <?php if (isset($errors['shop_name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['shop_name']); ?></div>
                    <?php endif; ?>
                    <small class="form-hint">Your shop URL: myshop.com/shop/<?php echo htmlspecialchars($shop['shop_slug']); ?></small>
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="email">Shop Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" 
                           class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                           placeholder="shop@example.com"
                           value="<?php echo htmlspecialchars($shop['email']); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Description -->
                <div class="form-group">
                    <label for="description">Shop Description</label>
                    <textarea id="description" name="description" rows="5" 
                              class="form-control"
                              placeholder="Tell customers about your shop"><?php echo htmlspecialchars($shop['description'] ?? ''); ?></textarea>
                    <small class="form-hint">Describe your shop and what you sell.</small>
                </div>
                
                <!-- Address -->
                <div class="form-group">
                    <label for="address">Shop Address</label>
                    <input type="text" id="address" name="address" 
                           class="form-control"
                           placeholder="123 Main Street, City, Country"
                           value="<?php echo htmlspecialchars($shop['address'] ?? ''); ?>">
                </div>
                
                <!-- Phone -->
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           class="form-control"
                           placeholder="+1 (555) 123-4567"
                           value="<?php echo htmlspecialchars($shop['phone'] ?? ''); ?>">
                </div>
                
                <!-- Shop Stats -->
                <div class="stats-box">
                    <h3>Shop Statistics</h3>
                    <?php 
                    $stats = $shopModel->getStats($shop['id']);
                    ?>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['total_products'] ?? 0; ?></span>
                            <span class="stat-label">Products</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['total_orders'] ?? 0; ?></span>
                            <span class="stat-label">Orders</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">$<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></span>
                            <span class="stat-label">Revenue</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['pending_orders'] ?? 0; ?></span>
                            <span class="stat-label">Pending Orders</span>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                    <a href="manage.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Shop
                    </a>
                </div>
            </form>
            
            <!-- Danger Zone -->
            <div class="danger-zone">
                <h3><i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i> Danger Zone</h3>
                <p>These actions cannot be undone. Please be careful.</p>
                
                <div class="danger-actions">
                    <button class="btn btn-danger" onclick="closeShop()">
                        <i class="fas fa-times-circle"></i> Close Shop
                    </button>
                    <button class="btn btn-danger" onclick="deleteShop()">
                        <i class="fas fa-trash"></i> Delete Shop
                    </button>
                </div>
            </div>
            
        </div>
        
    </div>
</section>

<style>
/* ============================================
   SHOP SETTINGS PAGE
   ============================================ */

.shop-settings-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
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

/* Shop Status */
.shop-status {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    border: 1px solid #eef2f7;
}

.status-badge {
    display: inline-block;
    padding: 6px 24px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.status-badge.approved {
    background: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-message {
    margin-top: 8px;
    color: #6c757d;
    font-size: 14px;
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

/* Settings Container */
.settings-container {
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

/* Stats Box */
.stats-box {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #eef2f7;
}

.stats-box h3 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 16px;
    color: #1a1a2e;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: 800;
    color: #2C3E8F;
}

.stat-label {
    font-size: 13px;
    color: #6c757d;
}

/* Form Actions */
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

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

/* Danger Zone */
.danger-zone {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid #fee2e2;
}

.danger-zone h3 {
    font-size: 16px;
    font-weight: 700;
    color: #e74c3c;
    margin-bottom: 8px;
}

.danger-zone p {
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 16px;
}

.danger-actions {
    display: flex;
    gap: 12px;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 768px) {
    .settings-container {
        padding: 24px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .danger-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .settings-container {
        padding: 16px;
    }
    
    .page-header h1 {
        font-size: 24px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .stat-number {
        font-size: 20px;
    }
}
</style>

<script>
function closeShop() {
    if (confirm('Are you sure you want to close your shop? This will make it inactive.')) {
        alert('Shop closure feature coming soon. Please contact admin.');
    }
}

function deleteShop() {
    if (confirm('⚠️ Are you sure you want to permanently delete your shop? This action cannot be undone!')) {
        alert('Shop deletion feature coming soon. Please contact admin.');
    }
}

console.log('✅ Shop settings page loaded successfully!');
</script>

<?php require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php'; ?>