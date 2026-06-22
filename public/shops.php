<?php
// ============================================================
// FILE: public/shops.php
// PURPOSE: View all vendor shops
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$shopModel = new Shop();
$shops = $shopModel->getAll(true);

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Shops categories error: ' . $e->getMessage());
}

$cartCount = getCartTotalItems();
$pageTitle = 'Vendors';

require_once ABSPATH . 'app/views/frontend/layout/header.php';
?>

<section class="shops-page">
    <div class="container">
        <div class="page-header">
            <h1>Our <span class="highlight">Vendors</span></h1>
            <p><?php echo count($shops); ?> trusted sellers</p>
        </div>
        
        <?php if (!empty($shops)): ?>
            <div class="shops-grid">
                <?php foreach ($shops as $shop): ?>
                    <a href="shop/view.php?slug=<?php echo $shop['shop_slug']; ?>" class="shop-card">
                        <div class="shop-logo">
                            <?php echo strtoupper(substr($shop['shop_name'], 0, 1)); ?>
                        </div>
                        <div class="shop-name"><?php echo htmlspecialchars($shop['shop_name']); ?></div>
                        <div class="shop-owner">by <?php echo htmlspecialchars($shop['owner_name'] ?? 'Vendor'); ?></div>
                        <span class="shop-badge">Verified Seller</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-store"></i>
                <h3>No Vendors Yet</h3>
                <p>Be the first to open a shop!</p>
                <a href="shop/create.php" class="btn btn-primary">Open a Shop</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.shops-page {
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

.shops-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 24px;
}

.shop-card {
    background: white;
    border-radius: 16px;
    padding: 30px 20px;
    text-align: center;
    text-decoration: none;
    color: #1a1a2e;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.shop-card:hover {
    border-color: #2C3E8F;
    transform: translateY(-6px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.shop-logo {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: #2C3E8F;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 700;
    margin: 0 auto 12px;
}

.shop-name {
    font-size: 16px;
    font-weight: 600;
}

.shop-owner {
    font-size: 13px;
    color: #6c757d;
}

.shop-badge {
    display: inline-block;
    background: #d4edda;
    color: #155724;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    margin-top: 6px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 48px;
    color: #cbd5e0;
    margin-bottom: 16px;
}

@media (max-width: 768px) {
    .shops-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .shops-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
}
</style>

<?php require_once ABSPATH . 'app/views/frontend/layout/footer.php'; ?>