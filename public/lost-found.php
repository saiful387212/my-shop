<?php
// ============================================================
// FILE: public/lost-found.php
// PURPOSE: Lost & Found system for DU campus
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load required files
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// GET ACTION
// ============================================

$action = isset($_GET['action']) ? $_GET['action'] : '';
$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============================================
// PROCESS REPORT FORM
// ============================================

$errors = [];
$success = false;
$formData = [
    'title' => '',
    'description' => '',
    'location' => '',
    'category' => '',
    'contact_info' => ''
];

// ============================================
// HANDLE REPORT SUBMISSION
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report') {
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $_SESSION['error_message'] = 'Please login to report an item.';
        header('Location: ' . SITE_URL . 'login.php');
        exit;
    }
    
    // Get form data
    $formData['title'] = sanitize($_POST['title'] ?? '');
    $formData['description'] = sanitize($_POST['description'] ?? '');
    $formData['location'] = sanitize($_POST['location'] ?? '');
    $formData['category'] = sanitize($_POST['category'] ?? '');
    $formData['contact_info'] = sanitize($_POST['contact_info'] ?? '');
    
    // Validation
    if (empty($formData['title'])) {
        $errors['title'] = 'Title is required.';
    } elseif (strlen($formData['title']) < 3) {
        $errors['title'] = 'Title must be at least 3 characters.';
    }
    
    if (empty($formData['description'])) {
        $errors['description'] = 'Description is required.';
    } elseif (strlen($formData['description']) < 10) {
        $errors['description'] = 'Description must be at least 10 characters.';
    }
    
    if (empty($formData['location'])) {
        $errors['location'] = 'Location is required.';
    }
    
    if (empty($formData['category'])) {
        $errors['category'] = 'Please select Lost or Found.';
    }
    
    if (empty($formData['contact_info'])) {
        $errors['contact_info'] = 'Contact information is required.';
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
            $newFileName = 'lost_' . time() . '_' . uniqid() . '.' . $fileExt;
            $uploadPath = ABSPATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'lost-found' . DIRECTORY_SEPARATOR;
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath . $newFileName)) {
                $imageName = $newFileName;
            } else {
                $errors['image'] = 'Failed to upload image.';
            }
        }
    }
    
    // ============================================
    // SAVE TO DATABASE
    // ============================================
    
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO lost_items (
                    user_id,
                    title,
                    description,
                    location,
                    category,
                    contact_info,
                    image_url,
                    status
                ) VALUES (
                    :user_id,
                    :title,
                    :description,
                    :location,
                    :category,
                    :contact_info,
                    :image_url,
                    'open'
                )
            ");
            
            $result = $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':title' => $formData['title'],
                ':description' => $formData['description'],
                ':location' => $formData['location'],
                ':category' => $formData['category'],
                ':contact_info' => $formData['contact_info'],
                ':image_url' => $imageName
            ]);
            
            if ($result) {
                $success = true;
                $_SESSION['success_message'] = '✅ Item reported successfully!';
                
                // Redirect to clear form
                header('Location: ' . SITE_URL . 'lost-found.php');
                exit;
            } else {
                $errors['general'] = 'Failed to save item. Please try again.';
            }
            
        } catch (PDOException $e) {
            error_log('Lost item error: ' . $e->getMessage());
            $errors['general'] = 'Database error occurred.';
        } catch (Exception $e) {
            error_log('Lost item error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred.';
        }
    }
}

// ============================================
// HANDLE RESOLVE (Mark as resolved)
// ============================================

if ($action === 'resolve' && $itemId > 0) {
    try {
        $pdo = getDbConnection();
        
        if ($pdo === null) {
            throw new Exception('Database connection failed.');
        }
        
        $stmt = $pdo->prepare("
            UPDATE lost_items 
            SET status = 'resolved', updated_at = NOW() 
            WHERE id = :id
        ");
        $result = $stmt->execute([':id' => $itemId]);
        
        if ($result) {
            $_SESSION['success_message'] = '✅ Item marked as resolved!';
        } else {
            $_SESSION['error_message'] = '❌ Failed to resolve item.';
        }
        
    } catch (Exception $e) {
        error_log('Resolve item error: ' . $e->getMessage());
        $_SESSION['error_message'] = 'An error occurred.';
    }
    
    header('Location: ' . SITE_URL . 'lost-found.php');
    exit;
}

// ============================================
// FETCH ALL ITEMS
// ============================================

$items = [];
$filterCategory = isset($_GET['category']) ? $_GET['category'] : '';
$filterLocation = isset($_GET['location']) ? $_GET['location'] : '';

try {
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Database connection failed.');
    }
    
    $sql = "
        SELECT 
            li.*,
            u.name as reporter_name,
            u.email as reporter_email
        FROM lost_items li
        LEFT JOIN users u ON li.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($filterCategory)) {
        $sql .= " AND li.category = :category";
        $params[':category'] = $filterCategory;
    }
    
    if (!empty($filterLocation)) {
        $sql .= " AND li.location LIKE :location";
        $params[':location'] = '%' . $filterLocation . '%';
    }
    
    $sql .= " ORDER BY li.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Lost items fetch error: ' . $e->getMessage());
}

// ============================================
// LOCATIONS FOR FILTER
// ============================================

$locations = [
    'Curzon Hall',
    'TSC',
    'Central Library',
    'Administrative Building',
    'Science Faculty',
    'Arts Faculty',
    'Sir AF Rahman Hall',
    'Shaheedullah Hall',
    'Bijoy Ekattor Hall',
    'Kazi Nazrul Islam Hall',
    'Jagannath Hall',
    'Salimullah Muslim Hall',
    'Haji Muhammad Mohsin Hall',
    'Shahid Ziaur Rahman Hall',
    'Rokeya Hall',
    'Shamsun Nahar Hall',
    'Other'
];

// ============================================
// GET CATEGORIES FOR HEADER
// ============================================

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Lost found categories error: ' . $e->getMessage());
}

// ============================================
// MESSAGES
// ============================================

$successMsg = $_SESSION['success_message'] ?? '';
$errorMsg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$cartCount = getCartTotalItems();
$pageTitle = 'Lost & Found';

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     LOST & FOUND PAGE
     ============================================ -->
<section class="lost-found-page">
    <div class="container">
        
        <div class="page-header">
            <h1>🔍 Lost & <span class="highlight">Found</span></h1>
            <p>Report lost items or found items on DU campus</p>
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
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="lost-found.php?action=report" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Report Lost Item
            </a>
            <a href="lost-found.php?action=report&type=found" class="btn btn-success">
                <i class="fas fa-hand-holding-heart"></i> Report Found Item
            </a>
        </div>
        
        <!-- ============================================
             REPORT FORM
             ============================================ -->
        <?php if ($action === 'report'): ?>
            
            <div class="report-form-container">
                <div class="form-header">
                    <h2>
                        <?php 
                        $reportType = isset($_GET['type']) && $_GET['type'] === 'found' ? 'Found' : 'Lost';
                        echo $reportType; 
                    ?> Item Report
                    </h2>
                    <p>Help the DU community by reporting <?php echo strtolower($reportType); ?> items</p>
                </div>
                
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['general']); ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="report">
                    <input type="hidden" name="category" value="<?php echo strtolower($reportType); ?>">
                    
                    <!-- Title -->
                    <div class="form-group">
                        <label for="title">Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" 
                               class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>"
                               placeholder="What did you lose/find?"
                               value="<?php echo htmlspecialchars($formData['title']); ?>" required>
                        <?php if (isset($errors['title'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['title']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Description -->
                    <div class="form-group">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" rows="4" 
                                  class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>"
                                  placeholder="Describe the item in detail (color, brand, distinguishing features...)"
                                  required><?php echo htmlspecialchars($formData['description']); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['description']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Location -->
                    <div class="form-group">
                        <label for="location">Location <span class="required">*</span></label>
                        <select id="location" name="location" 
                                class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : ''; ?>" required>
                            <option value="">Select location</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc; ?>" 
                                    <?php echo ($formData['location'] == $loc) ? 'selected' : ''; ?>>
                                    <?php echo $loc; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['location'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['location']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="form-group">
                        <label for="contact_info">Contact Information <span class="required">*</span></label>
                        <input type="text" id="contact_info" name="contact_info" 
                               class="form-control <?php echo isset($errors['contact_info']) ? 'is-invalid' : ''; ?>"
                               placeholder="Phone number or email to contact"
                               value="<?php echo htmlspecialchars($formData['contact_info']); ?>" required>
                        <?php if (isset($errors['contact_info'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['contact_info']); ?></div>
                        <?php endif; ?>
                        <small class="form-hint">This will be visible to other students who want to help.</small>
                    </div>
                    
                    <!-- Image -->
                    <div class="form-group">
                        <label for="image">Image (Optional)</label>
                        <input type="file" id="image" name="image" accept="image/*" 
                               class="form-control" onchange="previewImage(this)">
                        <div class="form-hint">Upload an image of the item (Max: 5MB)</div>
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
                    
                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Report
                    </button>
                    
                    <a href="lost-found.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>
            </div>
            
        <?php else: ?>
            
            <!-- ============================================
                 FILTERS
                 ============================================ -->
            <div class="filter-bar">
                <div class="filter-group">
                    <div class="filter-item">
                        <label for="categoryFilter">Type:</label>
                        <select id="categoryFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All</option>
                            <option value="lost" <?php echo $filterCategory == 'lost' ? 'selected' : ''; ?>>Lost</option>
                            <option value="found" <?php echo $filterCategory == 'found' ? 'selected' : ''; ?>>Found</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="locationFilter">Location:</label>
                        <select id="locationFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc; ?>" 
                                    <?php echo $filterLocation == $loc ? 'selected' : ''; ?>>
                                    <?php echo $loc; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="statusFilter">Status:</label>
                        <select id="statusFilter" class="filter-select" onchange="applyFilters()">
                            <option value="">All</option>
                            <option value="open">Open</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- ============================================
                 ITEMS LIST
                 ============================================ -->
            <?php if (!empty($items)): ?>
                <div class="items-list">
                    <?php foreach ($items as $item): ?>
                        <div class="item-card <?php echo $item['category']; ?>">
                            <div class="item-badge <?php echo $item['category']; ?>-badge">
                                <?php echo strtoupper($item['category']); ?>
                            </div>
                            
                            <div class="item-content">
                                <?php if (!empty($item['image_url'])): ?>
                                    <div class="item-image">
                                        <img src="<?php echo SITE_URL; ?>uploads/lost-found/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                                             onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.png'">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="item-meta">
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo date('M d, Y \a\t g:i A', strtotime($item['created_at'])); ?></span>
                                    </p>
                                    <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <p class="item-reporter">
                                        <i class="fas fa-user"></i> Reported by: 
                                        <?php echo htmlspecialchars($item['reporter_name'] ?? 'Anonymous'); ?>
                                    </p>
                                    <div class="item-contact">
                                        <i class="fas fa-phone"></i> Contact: <?php echo htmlspecialchars($item['contact_info']); ?>
                                    </div>
                                    <div class="item-status <?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </div>
                                    
                                    <?php if ($item['status'] == 'open' && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['user_id']): ?>
                                        <a href="?action=resolve&id=<?php echo $item['id']; ?>" 
                                           class="btn btn-success btn-sm resolve-btn"
                                           onclick="return confirm('Mark this item as resolved?')">
                                            <i class="fas fa-check"></i> Mark Resolved
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No Items Found</h3>
                    <p>No lost or found items reported yet. Be the first to help!</p>
                    <div class="empty-actions">
                        <a href="?action=report" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Report Lost Item
                        </a>
                        <a href="?action=report&type=found" class="btn btn-success">
                            <i class="fas fa-hand-holding-heart"></i> Report Found Item
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
        
    </div>
</section>

<style>
/* ============================================
   LOST & FOUND STYLES
   ============================================ */

.lost-found-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

.container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px;
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

/* Quick Actions */
.quick-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-family: 'Inter', sans-serif;
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

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-success:hover {
    background: #219a52;
}

.btn-secondary {
    background: #e2e8f0;
    color: #1a1a2e;
}

.btn-secondary:hover {
    background: #cbd5e0;
}

.btn-sm {
    padding: 6px 14px;
    font-size: 12px;
}

/* Report Form */
.report-form-container {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    max-width: 700px;
    margin: 0 auto;
}

.form-header {
    text-align: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f0f0f0;
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

.form-group label .required {
    color: #e74c3c;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
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

.btn-submit {
    width: 100%;
    justify-content: center;
    padding: 14px;
    font-size: 16px;
    margin-top: 8px;
}

/* Filter Bar */
.filter-bar {
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    margin-bottom: 30px;
}

.filter-group {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-item label {
    font-size: 14px;
    font-weight: 600;
    color: #4a5568;
}

.filter-select {
    padding: 8px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    background: white;
    color: #1a1a2e;
    cursor: pointer;
    min-width: 150px;
}

.filter-select:focus {
    outline: none;
    border-color: #2C3E8F;
}

/* Items List */
.items-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.item-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    position: relative;
}

.item-card.lost {
    border-left: 5px solid #e74c3c;
}

.item-card.found {
    border-left: 5px solid #27ae60;
}

.item-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    z-index: 2;
}

.lost-badge {
    background: #fee2e2;
    color: #991b1b;
}

.found-badge {
    background: #d1fae5;
    color: #065f46;
}

.item-content {
    padding: 20px 24px;
    display: flex;
    gap: 20px;
}

.item-image {
    flex-shrink: 0;
    width: 120px;
    height: 120px;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex: 1;
}

.item-details h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 4px;
}

.item-meta {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: #6c757d;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.item-meta i {
    margin-right: 4px;
}

.item-description {
    color: #4a5568;
    margin-bottom: 8px;
    line-height: 1.6;
}

.item-reporter {
    font-size: 13px;
    color: #6c757d;
    margin-bottom: 4px;
}

.item-contact {
    font-size: 14px;
    font-weight: 600;
    color: #2C3E8F;
    margin-bottom: 8px;
}

.item-status {
    display: inline-block;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.item-status.open {
    background: #fff3cd;
    color: #856404;
}

.item-status.resolved {
    background: #d4edda;
    color: #155724;
}

.resolve-btn {
    margin-top: 8px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    border: 1px solid #eef2f7;
}

.empty-state i {
    font-size: 64px;
    color: #cbd5e0;
    margin-bottom: 16px;
}

.empty-state h3 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.empty-state p {
    color: #6c757d;
    margin-bottom: 24px;
}

.empty-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-select {
        width: 100%;
        min-width: unset;
    }
    
    .item-content {
        flex-direction: column;
    }
    
    .item-image {
        width: 100%;
        height: 200px;
    }
    
    .item-badge {
        position: relative;
        top: 0;
        right: 0;
        display: inline-block;
        margin-bottom: 8px;
    }
    
    .report-form-container {
        padding: 20px;
    }
    
    .quick-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .quick-actions .btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .item-meta {
        flex-direction: column;
        gap: 4px;
    }
    
    .empty-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .empty-actions .btn {
        width: 100%;
        max-width: 280px;
        justify-content: center;
    }
}
</style>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    
    if (input.files && input.files