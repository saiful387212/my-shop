<?php
// ============================================================
// FILE: public/admin/users.php
// PURPOSE: Admin user management with role management
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
// GET USERS
// ============================================

$users = [];
$error = '';

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    $stmt = $pdo->query("
        SELECT 
            id,
            name,
            email,
            is_admin,
            created_at,
            last_login
        FROM users
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Could not load users: ' . $e->getMessage();
    error_log('Admin users error: ' . $e->getMessage());
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
    <title>Manage Users - Admin</title>
    
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
            transition: all 0.3s;
        }
        
        .btn-sm {
            padding: 4px 10px;
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
        
        .btn-outline {
            background: transparent;
            color: #2C3E8F;
            border: 2px solid #2C3E8F;
        }
        
        .btn-outline:hover {
            background: #2C3E8F;
            color: white;
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
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2C3E8F;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        
        .role-badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-badge.admin {
            background: #cce5ff;
            color: #004085;
        }
        
        .role-badge.user {
            background: #d4edda;
            color: #155724;
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
        
        /* ===== MODALS ===== */
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
            margin-bottom: 16px;
        }
        
        .modal-content .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .modal-content select {
            width: 100%;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 14px;
            margin: 10px 0;
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
        <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
        <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
        <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
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
            <h1>Manage Users</h1>
            <small style="color:#6c757d;">Dashboard / Users</small>
        </div>
        <div>
            <span style="background:#2C3E8F;color:white;padding:6px 14px;border-radius:20px;font-size:13px;">
                <i class="fas fa-users"></i> <?php echo count($users); ?> Users
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
    
    <!-- Users Table -->
    <div class="table-container">
        
        <div class="table-header">
            <h3><i class="fas fa-users"></i> All Users</h3>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search users..." onkeyup="searchTable()">
            </div>
        </div>
        
        <?php if (!empty($users)): ?>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge <?php echo $user['is_admin'] == 1 ? 'admin' : 'user'; ?>">
                                    <?php echo $user['is_admin'] == 1 ? '👑 Admin' : '👤 Customer'; ?>
                                </span>
                            </td>
                            <td style="font-size:13px;color:#6c757d;">
                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td style="font-size:13px;color:#6c757d;">
                                <?php echo $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) : 'Never'; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                    <!-- Role Toggle Button -->
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-<?php echo $user['is_admin'] == 1 ? 'warning' : 'success'; ?> btn-sm toggle-role" 
                                                data-id="<?php echo $user['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                data-current="<?php echo $user['is_admin']; ?>">
                                            <?php if ($user['is_admin'] == 1): ?>
                                                <i class="fas fa-user"></i> Make Customer
                                            <?php else: ?>
                                                <i class="fas fa-user-crown"></i> Make Admin
                                            <?php endif; ?>
                                        </button>
                                    <?php else: ?>
                                        <span style="color:#6c757d;font-size:12px;">(You)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No Users Found</h3>
                <p style="color:#6c757d;">No users have registered yet.</p>
            </div>
        <?php endif; ?>
        
    </div>
    
</div>

<!-- ===== ROLE CHANGE MODAL ===== -->
<div class="modal-overlay" id="roleModal">
    <div class="modal-content">
        <div class="icon" style="color:#f39c12;">
            <i class="fas fa-user-crown"></i>
        </div>
        <h3>Change User Role</h3>
        <p>Change role for <strong id="roleUserName"></strong></p>
        
        <form method="POST" action="user-role.php">
            <input type="hidden" name="user_id" id="roleUserId">
            <input type="hidden" name="current_role" id="roleCurrent">
            
            <select name="new_role" id="roleSelect">
                <option value="0">👤 Customer</option>
                <option value="1">👑 Admin</option>
            </select>
            
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="closeRoleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Role
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
// ROLE CHANGE MODAL
// ============================================

const roleModal = document.getElementById('roleModal');
const roleUserId = document.getElementById('roleUserId');
const roleUserName = document.getElementById('roleUserName');
const roleCurrent = document.getElementById('roleCurrent');
const roleSelect = document.getElementById('roleSelect');

document.querySelectorAll('.toggle-role').forEach(btn => {
    btn.addEventListener('click', function() {
        roleUserId.value = this.dataset.id;
        roleUserName.textContent = this.dataset.name;
        roleCurrent.value = this.dataset.current;
        roleSelect.value = this.dataset.current;
        roleModal.classList.add('active');
    });
});

function closeRoleModal() {
    roleModal.classList.remove('active');
}

roleModal.addEventListener('click', function(e) {
    if (e.target === this) closeRoleModal();
});

console.log('✅ Admin users page loaded successfully!');
</script>

</body>
</html>