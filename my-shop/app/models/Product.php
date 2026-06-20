<?php
// ============================================================
// FILE: my-shop/app/models/Product.php
// PURPOSE: Product model - handles all product database operations
// ============================================================

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

// Load database connection
require_once ABSPATH . 'app/config/database.php';

class Product {
    private $pdo;
    
    /**
     * Constructor - initialize database connection
     */
    public function __construct() {
        $this->pdo = getDbConnection();
        if ($this->pdo === null) {
            throw new Exception('Database connection failed');
        }
    }
    
    /**
     * Get all products with category names
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->query('
                SELECT p.*, c.name as category_name 
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
     * Get a single product by ID
     */
    public function find($id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT p.*, c.name as category_name 
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
     * Get products by category
     */
    public function getByCategory($categoryId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM products 
                WHERE category_id = :category_id 
                ORDER BY name ASC
            ');
            $stmt->execute(['category_id' => $categoryId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Product->getByCategory() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search products by name or description
     */
    public function search($keyword) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM products 
                WHERE name LIKE :keyword 
                OR description LIKE :keyword 
                ORDER BY name ASC
            ');
            $keyword = '%' . $keyword . '%';
            $stmt->execute(['keyword' => $keyword]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Product->search() Error: ' . $e->getMessage());
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
    
    /**
     * Get total number of products
     */
    public function count() {
        try {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM products');
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log('Product->count() Error: ' . $e->getMessage());
            return 0;
        }
    }
}
?>