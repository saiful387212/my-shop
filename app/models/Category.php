<?php
// ============================================================
// FILE: app/models/Category.php
// PURPOSE: Category model for database operations
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class Category {
    
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
        if ($this->pdo === null) {
            throw new Exception('Database connection failed.');
        }
    }
    
    /**
     * Get all categories
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->query('
                SELECT * FROM categories 
                ORDER BY name ASC
            ');
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Category->getAll() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all categories with product count
     */
    public function getAllWithProductCount() {
        try {
            $stmt = $this->pdo->query('
                SELECT 
                    c.*,
                    COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id
                GROUP BY c.id
                ORDER BY c.name ASC
            ');
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Category->getAllWithProductCount() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a single category by ID
     */
    public function find($id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM categories WHERE id = :id
            ');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Category->find() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get category by name
     */
    public function findByName($name) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM categories WHERE name = :name
            ');
            $stmt->execute(['name' => $name]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Category->findByName() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new category
     */
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO categories (name, description) 
                VALUES (:name, :description)
            ');
            return $stmt->execute([
                'name' => $data['name'],
                'description' => $data['description'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log('Category->create() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a category
     */
    public function update($id, $data) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE categories 
                SET name = :name, description = :description 
                WHERE id = :id
            ');
            return $stmt->execute([
                'id' => $id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log('Category->update() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a category
     */
    public function delete($id) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log('Category->delete() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if category has products
     */
    public function hasProducts($id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*) as count FROM products WHERE category_id = :category_id
            ');
            $stmt->execute(['category_id' => $id]);
            $result = $stmt->fetch();
            return ($result['count'] ?? 0) > 0;
        } catch (PDOException $e) {
            error_log('Category->hasProducts() Error: ' . $e->getMessage());
            return true; // Return true to prevent accidental deletion
        }
    }
    
    /**
     * Get product count for a category
     */
    public function getProductCount($id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*) as count FROM products WHERE category_id = :category_id
            ');
            $stmt->execute(['category_id' => $id]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Category->getProductCount() Error: ' . $e->getMessage());
            return 0;
        }
    }
}
?>