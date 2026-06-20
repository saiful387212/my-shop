<?php
// ============================================================
// FILE: app/models/Product.php
// PURPOSE: Product model for database operations
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class Product {
    
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
        if ($this->pdo === null) {
            throw new Exception('Database connection failed.');
        }
    }
    
    /**
     * Get all products with category names
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->query('
                SELECT 
                    p.*, 
                    c.name as category_name,
                    c.id as category_id
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.created_at DESC
            ');
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Product->getAll() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all products with category names (for admin)
     */
    public function getAllWithCategory() {
        try {
            $stmt = $this->pdo->query('
                SELECT 
                    p.*, 
                    c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.id DESC
            ');
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Product->getAllWithCategory() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get products by category
     */
    public function getByCategory($categoryId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    p.*, 
                    c.name as category_name,
                    c.id as category_id
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = :category_id
                ORDER BY p.name ASC
            ');
            $stmt->execute(['category_id' => $categoryId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Product->getByCategory() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a single product by ID
     */
    public function find($id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    p.*, 
                    c.name as category_name,
                    c.id as category_id
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id
            ');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Product->find() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search products by name, description, or category
     */
    public function search($keyword) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    p.*, 
                    c.name as category_name,
                    c.id as category_id
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.name LIKE :keyword 
                OR p.description LIKE :keyword
                OR c.name LIKE :keyword
                ORDER BY 
                    CASE 
                        WHEN p.name LIKE :keyword_exact THEN 1
                        WHEN p.name LIKE :keyword_start THEN 2
                        ELSE 3
                    END,
                    p.name ASC
            ');
            
            $keywordParam = '%' . $keyword . '%';
            $keywordExact = $keyword;
            $keywordStart = $keyword . '%';
            
            $stmt->execute([
                'keyword' => $keywordParam,
                'keyword_exact' => $keywordExact,
                'keyword_start' => $keywordStart
            ]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Product->search() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Advanced search with filters
     */
    public function searchAdvanced($keyword, $categoryId = 0, $sort = 'relevance') {
        try {
            $sql = '
                SELECT 
                    p.*, 
                    c.name as category_name,
                    c.id as category_id
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE 1=1
            ';
            
            $params = [];
            
            if (!empty($keyword)) {
                $sql .= ' AND (p.name LIKE :keyword 
                             OR p.description LIKE :keyword 
                             OR c.name LIKE :keyword)';
                $params['keyword'] = '%' . $keyword . '%';
            }
            
            if ($categoryId > 0) {
                $sql .= ' AND p.category_id = :category_id';
                $params['category_id'] = $categoryId;
            }
            
            switch ($sort) {
                case 'price_low':
                    $sql .= ' ORDER BY p.price ASC';
                    break;
                case 'price_high':
                    $sql .= ' ORDER BY p.price DESC';
                    break;
                case 'name':
                    $sql .= ' ORDER BY p.name ASC';
                    break;
                case 'newest':
                    $sql .= ' ORDER BY p.created_at DESC';
                    break;
                case 'relevance':
                default:
                    if (!empty($keyword)) {
                        $sql .= ' ORDER BY 
                            CASE 
                                WHEN p.name LIKE :keyword_exact THEN 1
                                WHEN p.name LIKE :keyword_start THEN 2
                                ELSE 3
                            END,
                            p.name ASC';
                        $params['keyword_exact'] = $keyword;
                        $params['keyword_start'] = $keyword . '%';
                    } else {
                        $sql .= ' ORDER BY p.created_at DESC';
                    }
                    break;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log('Product->searchAdvanced() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get search suggestions for autocomplete
     */
    public function getSuggestions($keyword, $limit = 10) {
        try {
            // Get matching products
            $stmt = $this->pdo->prepare('
                SELECT 
                    id,
                    name,
                    image_url,
                    price,
                    "product" as type
                FROM products 
                WHERE name LIKE :keyword 
                LIMIT :limit
            ');
            $stmt->execute([
                'keyword' => '%' . $keyword . '%',
                'limit' => $limit
            ]);
            $products = $stmt->fetchAll();
            
            // Get matching categories
            $stmt = $this->pdo->prepare('
                SELECT 
                    id,
                    name,
                    NULL as image_url,
                    NULL as price,
                    "category" as type
                FROM categories 
                WHERE name LIKE :keyword 
                LIMIT :limit
            ');
            $stmt->execute([
                'keyword' => '%' . $keyword . '%',
                'limit' => $limit
            ]);
            $categories = $stmt->fetchAll();
            
            return array_merge($products, $categories);
            
        } catch (PDOException $e) {
            error_log('Product->getSuggestions() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all categories
     */
    public function getAllCategories() {
        try {
            $stmt = $this->pdo->query('
                SELECT * FROM categories ORDER BY name ASC
            ');
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Product->getAllCategories() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create a new product
     */
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO products 
                (category_id, name, description, price, stock_quantity, image_url) 
                VALUES 
                (:category_id, :name, :description, :price, :stock_quantity, :image_url)
            ');
            return $stmt->execute([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => $data['price'],
                'stock_quantity' => $data['stock_quantity'],
                'image_url' => $data['image_url'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log('Product->create() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a product
     */
    public function update($id, $data) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE products 
                SET 
                    category_id = :category_id,
                    name = :name,
                    description = :description,
                    price = :price,
                    stock_quantity = :stock_quantity,
                    image_url = :image_url,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ');
            $data['id'] = $id;
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log('Product->update() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a product
     */
    public function delete($id) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log('Product->delete() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update stock quantity
     */
    public function updateStock($id, $quantity) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE products 
                SET stock_quantity = stock_quantity - :quantity 
                WHERE id = :id AND stock_quantity >= :quantity
            ');
            return $stmt->execute(['id' => $id, 'quantity' => $quantity]);
        } catch (PDOException $e) {
            error_log('Product->updateStock() Error: ' . $e->getMessage());
            return false;
        }
    }
}
?>