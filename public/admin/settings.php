<?php
// ============================================================
// FILE: public/admin/settings.php
// PURPOSE: Admin settings page
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// ADMIN CHECK
// ============================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

// ============================================
// PROCESS SETTINGS FORM
// ============================================

$settings = [
    'site_name' => 'My Shop',
    'site_email' => 'info@myshop.com',
    'site_phone' => '+1 (555) 123-4567',
    'site_address' => '123 Main Street, New York, NY 10001',
    'currency' => 'USD',
    'shipping_cost' => '5.00',
    'free_shipping_threshold' => '50.00',
    'tax_rate' => '0.00',
    'maintenance_mode' => '0'
];

$successMsg = '';
$errorMsg = '';

// Load settings from database if table exists
try {
    $pdo = getDbConnection();
    if ($pdo !== null) {
        // Check if settings table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT * FROM settings");
            $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if ($dbSettings) {
                foreach ($dbSettings as $key => $value) {
                    if (isset($settings[$key])) {
                        $settings[$key] = $value;
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    // Table doesn't exist, use default settings
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $settings['site_name'] = sanitize($_POST['site_name'] ?? 'My Shop');
    $settings['site_email'] = sanitize($_POST['site_email'] ?? 'info@myshop.com');
    $settings['site_phone'] = sanitize($_POST['site_phone'] ?? '+1 (555) 123-4567');
    $settings['site_address'] = sanitize($_POST['site_address'] ?? '123 Main Street, New York, NY 10001');
    $settings['currency'] = sanitize($_POST['currency'] ?? 'USD');
    $settings['shipping_cost'] = sanitize($_POST['shipping_cost'] ?? '5.00');
    $settings['free_shipping_threshold'] = sanitize($_POST['free_shipping_threshold'] ?? '50.00');
    $settings['tax_rate'] = sanitize($_POST['tax_rate'] ?? '0.00');
    $settings['maintenance_mode'] = isset($_POST['maintenance_mode']) ? '1' : '0';
    
    try {
        $pdo = getDbConnection();
        
        if ($pdo === null) {
            throw new Exception('Database connection failed.');
        }
        
        // Create settings table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Save settings
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value) 
                VALUES (:key, :value) 
                ON DUPLICATE KEY UPDATE setting_value = :value
            ");
            $stmt->execute([':key' => $key, ':value' => $value]);
        }
        
        $successMsg = '✅ Settings saved successfully!';
        
    } catch (Exception $e) {
        error_log('Settings error: ' . $e->getMessage());
        $errorMsg = '❌ Failed to save settings: ' . $e->getMessage();
    }
}

$userName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    
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
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
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
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* ===== ALERTS ===== */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        /* ===== SETTINGS ===== */
        .settings-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 800px;
        }
        
        .settings-container h2 {
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        
        .form-group .hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 2px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: Arial, sans-serif;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2C3E8F;
            background: white;
            box-shadow: 0 0 0 4px rgba(44,62,143,0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #2C3E8F;
        }
        
        .checkbox-group label {
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .settings-container {
                padding: 20px;
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
        <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
        <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
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
            <h1>Settings</h1>
            <small style="color:#6c757d;">Dashboard / Settings</small>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
    <?php endif; ?>
    
    <!-- Settings Form -->
    <div class="settings-container">
        <h2><i class="fas fa-cog" style="color:#2C3E8F;"></i> General Settings</h2>
        
        <form method="POST" action="">
            
            <!-- Site Name -->
            <div class="form-group">
                <label for="site_name">Site Name</label>
                <input type="text" id="site_name" name="site_name" 
                       class="form-control" value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                <div class="hint">Your store name displayed on the website.</div>
            </div>
            
            <!-- Site Email -->
            <div class="form-group">
                <label for="site_email">Site Email</label>
                <input type="email" id="site_email" name="site_email" 
                       class="form-control" value="<?php echo htmlspecialchars($settings['site_email']); ?>">
                <div class="hint">Email address for contact and order notifications.</div>
            </div>
            
            <!-- Site Phone -->
            <div class="form-group">
                <label for="site_phone">Phone Number</label>
                <input type="text" id="site_phone" name="site_phone" 
                       class="form-control" value="<?php echo htmlspecialchars($settings['site_phone']); ?>">
                <div class="hint">Contact phone number displayed on the website.</div>
            </div>
            
            <!-- Site Address -->
            <div class="form-group">
                <label for="site_address">Store Address</label>
                <input type="text" id="site_address" name="site_address" 
                       class="form-control" value="<?php echo htmlspecialchars($settings['site_address']); ?>">
                <div class="hint">Physical store address displayed on the website.</div>
            </div>
            
            <!-- Currency & Shipping Row -->
            <div class="form-row">
                <div class="form-group">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency" class="form-control">
                        <option value="USD" <?php echo $settings['currency'] == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                        <option value="EUR" <?php echo $settings['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                        <option value="GBP" <?php echo $settings['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                        <option value="BDT" <?php echo $settings['currency'] == 'BDT' ? 'selected' : ''; ?>>BDT (৳)</option>
                        <option value="INR" <?php echo $settings['currency'] == 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tax_rate">Tax Rate (%)</label>
                    <input type="number" id="tax_rate" name="tax_rate" 
                           class="form-control" value="<?php echo htmlspecialchars($settings['tax_rate']); ?>" step="0.01" min="0">
                    <div class="hint">Tax percentage applied to orders.</div>
                </div>
            </div>
            
            <!-- Shipping Row -->
            <div class="form-row">
                <div class="form-group">
                    <label for="shipping_cost">Shipping Cost ($)</label>
                    <input type="number" id="shipping_cost" name="shipping_cost" 
                           class="form-control" value="<?php echo htmlspecialchars($settings['shipping_cost']); ?>" step="0.01" min="0">
                    <div class="hint">Flat shipping cost for orders.</div>
                </div>
                
                <div class="form-group">
                    <label for="free_shipping_threshold">Free Shipping Threshold ($)</label>
                    <input type="number" id="free_shipping_threshold" name="free_shipping_threshold" 
                           class="form-control" value="<?php echo htmlspecialchars($settings['free_shipping_threshold']); ?>" step="0.01" min="0">
                    <div class="hint">Order amount for free shipping. Set 0 to disable.</div>
                </div>
            </div>
            
            <!-- Maintenance Mode -->
            <div class="checkbox-group">
                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" 
                       <?php echo $settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                <label for="maintenance_mode">Enable Maintenance Mode</label>
            </div>
            <div class="hint" style="margin-bottom:16px;color:#6c757d;font-size:13px;">
                When enabled, only admins can access the site. Customers will see a maintenance message.
            </div>
            
            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
            
        </form>
    </div>
    
</div>

<!-- ===== JAVASCRIPT ===== -->
<script>
console.log('✅ Admin settings page loaded successfully!');
</script>

</body>
</html>