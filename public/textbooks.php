<?php
// ============================================================
// FILE: public/textbooks.php
// PURPOSE: Dedicated textbook exchange for DU students
// ============================================================

define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

session_start();

// ============================================
// ADD Textbook-specific fields to products
// ============================================
// SQL to add textbook fields:
// ALTER TABLE products ADD COLUMN textbook_condition ENUM('new', 'good', 'fair', 'poor') DEFAULT 'good';
// ALTER TABLE products ADD COLUMN textbook_edition VARCHAR(20) NULL;
// ALTER TABLE products ADD COLUMN textbook_author VARCHAR(100) NULL;
// ALTER TABLE products ADD COLUMN textbook_publisher VARCHAR(100) NULL;
// ALTER TABLE products ADD COLUMN textbook_isbn VARCHAR(20) NULL;
// ALTER TABLE products ADD COLUMN is_textbook TINYINT DEFAULT 0;

$pageTitle = 'Textbook Exchange';

require_once ABSPATH . 'app/views/frontend/layout/header.php';
?>

<section class="textbooks-page">
    <div class="container">
        <div class="page-header">
            <h1>📚 Textbook <span class="highlight">Exchange</span></h1>
            <p>Buy and sell textbooks from fellow DU students</p>
        </div>
        
        <!-- Filter by Department -->
        <div class="filter-bar">
            <div class="filter-group">
                <div class="filter-item">
                    <label for="deptFilter">Department:</label>
                    <select id="deptFilter" class="filter-select">
                        <option value="">All Departments</option>
                        <option value="CSE">CSE</option>
                        <option value="EEE">EEE</option>
                        <option value="BBA">BBA</option>
                        <option value="English">English</option>
                        <option value="Economics">Economics</option>
                        <option value="Physics">Physics</option>
                        <option value="Chemistry">Chemistry</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="Law">Law</option>
                        <option value="Pharmacy">Pharmacy</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="courseFilter">Course:</label>
                    <input type="text" id="courseFilter" placeholder="Search course name...">
                </div>
                <div class="filter-item">
                    <label for="conditionFilter">Condition:</label>
                    <select id="conditionFilter" class="filter-select">
                        <option value="">Any</option>
                        <option value="new">Like New</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Results -->
        <div class="products-grid">
            <?php 
            // Sample textbook data - in real app, fetch from database
            $textbooks = [
                ['name' => 'Introduction to Algorithms', 'author' => 'Cormen', 'edition' => '3rd', 'price' => 1200, 'condition' => 'good'],
                ['name' => 'Data Structures and Algorithms', 'author' => 'Weiss', 'edition' => '2nd', 'price' => 800, 'condition' => 'fair'],
            ];
            ?>
            
            <?php foreach ($textbooks as $book): ?>
                <div class="product-card">
                    <div class="product-image">
                        <span class="badge textbook-badge">📖</span>
                        <img src="<?php echo SITE_URL; ?>assets/images/textbook-placeholder.jpg" alt="Textbook">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo $book['name']; ?></h3>
                        <p class="book-author"><i class="fas fa-user"></i> <?php echo $book['author']; ?></p>
                        <p class="book-edition"><i class="fas fa-book"></i> <?php echo $book['edition']; ?> Edition</p>
                        <span class="condition-badge <?php echo $book['condition']; ?>">
                            <?php echo ucfirst($book['condition']); ?>
                        </span>
                        <div class="product-price">৳<?php echo number_format($book['price'], 2); ?></div>
                        <button class="btn btn-primary add-to-cart">Add to Cart</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
.textbooks-page { padding: 40px 0 60px; background: #f8fafc; }
.page-header { text-align: center; margin-bottom: 40px; }
.page-header h1 { font-size: 32px; font-weight: 800; color: #1a1a2e; }
.page-header h1 .highlight { color: #2C3E8F; }
.page-header h1::after { content: ''; display: block; width: 60px; height: 4px; background: #2C3E8F; margin: 10px auto 0; border-radius: 2px; }
.page-header p { color: #6c757d; font-size: 16px; margin-top: 8px; }

.filter-bar { background: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #eef2f7; margin-bottom: 30px; }
.filter-group { display: flex; gap: 20px; flex-wrap: wrap; align-items: center; }
.filter-item { display: flex; align-items: center; gap: 8px; }
.filter-item label { font-size: 14px; font-weight: 600; color: #4a5568; }
.filter-select { padding: 8px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; background: white; min-width: 150px; }
.filter-select:focus { outline: none; border-color: #2C3E8F; }
.filter-item input[type="text"] { padding: 8px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; min-width: 150px; }
.filter-item input:focus { outline: none; border-color: #2C3E8F; }

.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
.product-card { background: white; border-radius: 16px; overflow: hidden; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #eef2f7; display: flex; flex-direction: column; }
.product-card:hover { transform: translateY(-6px); box-shadow: 0 12px 40px rgba(0,0,0,0.1); border-color: #2C3E8F; }
.product-image { position: relative; aspect-ratio: 1 / 1; background: #f8fafc; display: flex; align-items: center; justify-content: center; }
.product-image img { max-width: 60%; max-height: 60%; object-fit: contain; }
.textbook-badge { background: #FFD700; color: #1a1a2e; font-size: 24px; padding: 8px 12px; top: 12px; left: 12px; border-radius: 8px; }

.product-info { padding: 16px 20px 20px; flex: 1; display: flex; flex-direction: column; }
.product-name { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
.book-author { font-size: 13px; color: #6c757d; margin-bottom: 2px; }
.book-edition { font-size: 13px; color: #6c757d; margin-bottom: 8px; }

.condition-badge { padding: 2px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; width: fit-content; margin-bottom: 8px; }
.condition-badge.new { background: #d4edda; color: #155724; }
.condition-badge.good { background: #cce5ff; color: #004085; }
.condition-badge.fair { background: #fff3cd; color: #856404; }
.condition-badge.poor { background: #f8d7da; color: #721c24; }

.product-price { font-size: 20px; font-weight: 700; color: #2C3E8F; margin-bottom: 12px; }
.product-card .btn { width: 100%; justify-content: center; padding: 12px; border-radius: 8px; font-size: 14px; margin-top: auto; background: #2C3E8F; color: white; border: none; cursor: pointer; transition: all 0.3s ease; }
.product-card .btn:hover { background: #1a2a6c; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(44,62,143,0.3); }

@media (max-width: 768px) {
    .filter-group { flex-direction: column; align-items: stretch; }
    .filter-select, .filter-item input[type="text"] { width: 100%; min-width: unset; }
}
</style>

<?php require_once ABSPATH . 'app/views/frontend/layout/footer.php'; ?>