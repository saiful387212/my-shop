<?php
// ============================================================
// FILE: public/admin/orders.php
// PURPOSE: Admin order management - FIXED VERSION
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
// GET ORDERS
// ============================================

$orders = [];
$error = '';

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    // Get all orders with customer names
    $stmt = $pdo->query("
        SELECT 
            o.*,
            u.name as customer_name,
            u.email as customer_email,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Could not load orders: ' . $e->getMessage();
    error_log('Admin orders error: ' . $e->getMessage());
}

// ============================================
// MESSAGES
// ============================================

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
    <title>Manage Orders - Admin</title>
    
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
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-primary {
            background: #2C3E8F;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a2a6c;
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
        
        .status-badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-badge.shipped {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .order-total {
            font-weight: 700;
            color: #2C3E8F;
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
            max-width: 420px;
            width: 90%;
        }
        
        .modal-content h3 {
            margin-bottom: 8px;
        }
        
        .modal-content p {
            color: #6c757d;
            margin-bottom: 16px;
        }
        
        .modal-content select {
            width: 100%;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 14px;
            margin-bottom: 16px;
        }
        
        .modal-content .btn-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
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
            
            .modal-content .btn-group {
                flex-direction: column;
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
        <li><a href="orders.php" class="active"><i class="fas fa-shopping-bag"></i> Orders</a></li>
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
            <h1>Manage Orders</h1>
            <small style="color:#6c757d;">Dashboard / Orders</small>
        </div>
        <div>
            <span style="background:#2C3E8F;color:white;padding:6px 14px;border-radius:20px;font-size:13px;">
                <i class="fas fa-shopping-bag"></i> <?php echo count($orders); ?> Orders
            </span>
        </div>
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
    
    <!-- Orders Table -->
    <div class="table-container">
        
        <div class="table-header">
            <h3><i class="fas fa-shopping-bag"></i> All Orders</h3>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search orders..." onkeyup="searchTable()">
            </div>
        </div>
        
        <?php if (!empty($orders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?>
                                <br>
                                <small style="color:#6c757d;"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></small>
                            </td>
                            <td><?php echo $order['item_count'] ?? 0; ?></td>
                            <td class="order-total">$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge <?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td style="font-size:13px;color:#6c757d;">
                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm update-status-btn" 
                                        data-id="<?php echo $order['id']; ?>"
                                        data-status="<?php echo $order['status']; ?>"
                                        data-order="<?php echo htmlspecialchars($order['order_number']); ?>">
                                    <i class="fas fa-edit"></i> Status
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>No Orders Found</h3>
                <p style="color:#6c757d;">No orders have been placed yet.</p>
            </div>
        <?php endif; ?>
        
    </div>
    
</div>

<!-- ===== STATUS UPDATE MODAL ===== -->
<div class="modal-overlay" id="statusModal">
    <div class="modal-content">
        <h3><i class="fas fa-edit"></i> Update Order Status</h3>
        <p>Order: <strong id="modalOrderNumber"></strong></p>
        
        <form method="POST" action="order-update.php" id="statusForm">
            <input type="hidden" name="order_id" id="modalOrderId">
            
            <label style="font-weight:600;display:block;margin-bottom:4px;">Select Status:</label>
            <select name="status" id="modalStatusSelect">
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
            
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== JAVASCRIPT ===== -->
<script>
// ============================================
// SEARCH FUNCTION
// ============================================

function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#tableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// ============================================
// STATUS MODAL - FIXED
// ============================================

const statusModal = document.getElementById('statusModal');
const modalOrderId = document.getElementById('modalOrderId');
const modalOrderNumber = document.getElementById('modalOrderNumber');
const modalStatusSelect = document.getElementById('modalStatusSelect');

// Add click event to all status buttons
document.querySelectorAll('.update-status-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Get data from button
        const orderId = this.dataset.id;
        const currentStatus = this.dataset.status;
        const orderNumber = this.dataset.order;
        
        // Set modal values
        modalOrderId.value = orderId;
        modalOrderNumber.textContent = orderNumber;
        modalStatusSelect.value = currentStatus;
        
        // Show modal
        statusModal.classList.add('active');
        
        console.log('📦 Order:', orderNumber, 'Current status:', currentStatus);
    });
});

// Close modal
function closeStatusModal() {
    statusModal.classList.remove('active');
}

// Close modal when clicking outside
statusModal.addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeStatusModal();
    }
});

// ============================================
// FORM SUBMIT - Show loading
// ============================================

document.getElementById('statusForm').addEventListener('submit', function() {
    const btn = this.querySelector('button[type="submit"]');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    btn.disabled = true;
});

console.log('✅ Admin orders page loaded successfully!');
</script>

</body>
</html>