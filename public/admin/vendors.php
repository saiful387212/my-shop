<?php
// ============================================================
// FILE: public/admin/vendors.php
// PURPOSE: Admin vendor management
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// ADMIN CHECK
// ============================================

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$shopModel = new Shop();

// ============================================
// GET SHOPS
// ============================================

$pendingShops = $shopModel->getPending();
$allShops = $shopModel->getAll();

// ============================================
// PROCESS ACTIONS
// ============================================

if (isset($_GET['action']) && isset($_GET['id'])) {
    $shopId = (int)$_GET['id'];
    
    if ($_GET['action'] == 'approve') {
        if ($shopModel->approve($shopId)) {
            $_SESSION['success_message'] = 'Shop approved successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to approve shop.';
        }
    }
    
    header('Location: vendors.php');
    exit;
}

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
    <title>Vendors - Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; min-height: 100vh; }
        
        .sidebar { width: 250px; background: #1a1a2e; color: white; min-height: 100vh; padding: 20px 0; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; }
        .sidebar-brand { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-brand h2 { font-size: 22px; }
        .sidebar-brand span { color: #4A6CCF; }
        .sidebar-menu { list-style: none; padding: 0 10px; }
        .sidebar-menu li a { display: block; padding: 12px 16px; color: rgba(255,255,255,0.7); text-decoration: none; border-radius: 8px; transition: all 0.3s; }
        .sidebar-menu li a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-menu li a.active { background: #2C3E8F; color: white; }
        .sidebar-menu li a i { margin-right: 10px; width: 20px; }
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 20px; }
        .sidebar-footer .admin-name { font-weight: bold; color: white; }
        .sidebar-footer .admin-email { font-size: 12px; color: rgba(255,255,255,0.5); }
        .sidebar-footer .logout-btn { display: block; margin-top: 10px; color: #e74c3c; text-decoration: none; }
        
        .main-content { margin-left: 250px; flex: 1; padding: 30px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #ddd; }
        .topbar h1 { font-size: 24px; }
        
        .btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block; font-weight: 600; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #219a52; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .table-container { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8f9fa; padding: 12px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: #6c757d; border-bottom: 2px solid #dee2e6; }
        table td { padding: 12px 16px; border-bottom: 1px solid #eee; vertical-align: middle; }
        table tr:hover td { background: #f8f9fa; }
        
        .status-badge { padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-badge.approved { background: #d4edda; color: #155724; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        
        .shop-name { font-weight: 600; }
        .shop-name i { color: #2C3E8F; margin-right: 6px; }
        
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 48px; color: #ccc; margin-bottom: 16px; }
        
        .section-title { font-size: 18px; font-weight: 700; margin: 30px 0 10px; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 16px; }
        }
    </style>
</head>
<body>

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
        <li><a href="vendors.php" class="active"><i class="fas fa-store"></i> Vendors</a></li>
        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
    </ul>
    <div class="sidebar-footer">
        <div class="admin-name"><?php echo htmlspecialchars($userName); ?></div>
        <div class="admin-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@myshop.com'); ?></div>
        <a href="<?php echo SITE_URL; ?>logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    
    <div class="topbar">
        <div>
            <h1>Vendor Management</h1>
            <small style="color:#6c757d;">Dashboard / Vendors</small>
        </div>
        <span style="background:#2C3E8F;color:white;padding:6px 14px;border-radius:20px;font-size:13px;">
            <i class="fas fa-store"></i> <?php echo count($allShops); ?> Shops
        </span>
    </div>
    
    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
    <?php endif; ?>
    
    <!-- Pending Shops -->
    <?php if (!empty($pendingShops)): ?>
        <div class="section-title"><i class="fas fa-clock" style="color:#f39c12;"></i> Pending Approval</div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Shop</th>
                        <th>Owner</th>
                        <th>Email</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingShops as $shop): ?>
                        <tr>
                            <td><span class="shop-name"><i class="fas fa-store"></i> <?php echo htmlspecialchars($shop['shop_name']); ?></span></td>
                            <td><?php echo htmlspecialchars($shop['owner_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($shop['email'] ?? ''); ?></td>
                            <td><?php echo date('M d, Y', strtotime($shop['created_at'])); ?></td>
                            <td><span class="status-badge pending">Pending</span></td>
                            <td>
                                <a href="?action=approve&id=<?php echo $shop['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve this shop?')">
                                    <i class="fas fa-check"></i> Approve
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-success" style="margin-top:20px;">
            <i class="fas fa-check-circle"></i> No pending shops to approve.
        </div>
    <?php endif; ?>
    
    <!-- All Shops -->
    <div class="section-title" style="margin-top:30px;"><i class="fas fa-store"></i> All Shops</div>
    <div class="table-container">
        <?php if (!empty($allShops)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Shop</th>
                        <th>Owner</th>
                        <th>Email</th>
                        <th>Created</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allShops as $shop): ?>
                        <tr>
                            <td><span class="shop-name"><i class="fas fa-store"></i> <?php echo htmlspecialchars($shop['shop_name']); ?></span></td>
                            <td><?php echo htmlspecialchars($shop['owner_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($shop['email'] ?? ''); ?></td>
                            <td><?php echo date('M d, Y', strtotime($shop['created_at'])); ?></td>
                            <td><span class="status-badge <?php echo $shop['is_approved'] ? 'approved' : 'pending'; ?>">
                                <?php echo $shop['is_approved'] ? 'Approved' : 'Pending'; ?>
                            </span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-store"></i>
                <p>No shops created yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
</div>

</body>
</html>