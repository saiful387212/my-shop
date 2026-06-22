<?php
// ============================================================
// FILE: app/models/Shop.php
// PURPOSE: Shop model for vendor management
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

class Shop {
    
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
        if ($this->pdo === null) {
            throw new Exception('Database connection failed.');
        }
    }
    
    /**
     * Create a new shop
     */
    public function create($userId, $data) {
        try {
            $shopSlug = $this->generateSlug($data['shop_name']);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO shops (user_id, shop_name, shop_slug, description, address, phone, email) 
                VALUES (:user_id, :shop_name, :shop_slug, :description, :address, :phone, :email)
            ");
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':shop_name' => $data['shop_name'],
                ':shop_slug' => $shopSlug,
                ':description' => $data['description'] ?? '',
                ':address' => $data['address'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':email' => $data['email'] ?? ''
            ]);
            
        } catch (PDOException $e) {
            error_log('Shop->create() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get shop by user ID
     */
    public function getByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM shops WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Shop->getByUser() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get shop by ID
     */
    public function getById($shopId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.name as owner_name, u.email as owner_email 
                FROM shops s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.id = :id
            ");
            $stmt->execute([':id' => $shopId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Shop->getById() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get shop by slug
     */
    public function getBySlug($slug) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.name as owner_name, u.email as owner_email 
                FROM shops s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.shop_slug = :slug
            ");
            $stmt->execute([':slug' => $slug]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Shop->getBySlug() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all shops
     */
    public function getAll($approved = false) {
        try {
            $sql = "SELECT s.*, u.name as owner_name FROM shops s LEFT JOIN users u ON s.user_id = u.id";
            if ($approved) {
                $sql .= " WHERE s.is_approved = 1 AND s.is_active = 1";
            }
            $sql .= " ORDER BY s.created_at DESC";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Shop->getAll() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get pending shops
     */
    public function getPending() {
        try {
            $stmt = $this->pdo->query("
                SELECT s.*, u.name as owner_name 
                FROM shops s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.is_approved = 0
                ORDER BY s.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Shop->getPending() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Approve shop
     */
    public function approve($shopId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE shops SET is_approved = 1 WHERE id = :id");
            return $stmt->execute([':id' => $shopId]);
        } catch (PDOException $e) {
            error_log('Shop->approve() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get shop products
     */
    public function getProducts($shopId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, c.name as category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.shop_id = :shop_id AND p.is_active = 1 AND p.status = 'approved'
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([':shop_id' => $shopId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Shop->getProducts() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all products with shop info
     */
    public function getAllProductsWithShop() {
        try {
            $stmt = $this->pdo->query("
                SELECT p.*, c.name as category_name, s.shop_name, s.shop_slug, u.name as vendor_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN shops s ON p.shop_id = s.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE p.is_active = 1 AND p.status = 'approved'
                ORDER BY p.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Shop->getAllProductsWithShop() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user has a shop
     */
    public function userHasShop($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM shops WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log('Shop->userHasShop() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get vendor orders
     */
    public function getVendorOrders($shopId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT vo.*, o.order_number, o.shipping_address, o.created_at as order_date,
                       u.name as customer_name, u.email as customer_email
                FROM vendor_orders vo
                LEFT JOIN orders o ON vo.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE vo.shop_id = :shop_id
                ORDER BY vo.created_at DESC
            ");
            $stmt->execute([':shop_id' => $shopId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Shop->getVendorOrders() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update vendor order status
     */
    public function updateVendorOrderStatus($vendorOrderId, $status) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE vendor_orders SET status = :status WHERE id = :id
            ");
            return $stmt->execute([':id' => $vendorOrderId, ':status' => $status]);
        } catch (PDOException $e) {
            error_log('Shop->updateVendorOrderStatus() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get shop stats
     */
    public function getStats($shopId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM products WHERE shop_id = :shop_id) as total_products,
                    (SELECT COUNT(*) FROM vendor_orders WHERE shop_id = :shop_id) as total_orders,
                    (SELECT SUM(total_amount) FROM vendor_orders WHERE shop_id = :shop_id) as total_revenue,
                    (SELECT COUNT(*) FROM vendor_orders WHERE shop_id = :shop_id AND status = 'pending') as pending_orders
            ");
            $stmt->execute([':shop_id' => $shopId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Shop->getStats() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate unique slug
     */
    private function generateSlug($name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $slug = trim($slug, '-');
        
        $stmt = $this->pdo->prepare("SELECT id FROM shops WHERE shop_slug = :slug");
        $stmt->execute([':slug' => $slug]);
        
        if ($stmt->fetch()) {
            $slug .= '-' . time();
        }
        
        return $slug;
    }
}
?>