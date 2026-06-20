<?php
// ============================================================
// FILE: app/models/Order.php
// PURPOSE: Order model for database operations
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class Order {
    
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
        if ($this->pdo === null) {
            throw new Exception('Database connection failed.');
        }
    }
    
    /**
     * Get all orders with customer names
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->query('
                SELECT 
                    o.*,
                    u.name as customer_name,
                    u.email as customer_email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC
            ');
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Order->getAll() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get order by ID
     */
    public function find($id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    o.*,
                    u.name as customer_name,
                    u.email as customer_email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = :id
            ');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Order->find() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get order by order number
     */
    public function findByOrderNumber($orderNumber) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    o.*,
                    u.name as customer_name,
                    u.email as customer_email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.order_number = :order_number
            ');
            $stmt->execute(['order_number' => $orderNumber]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Order->findByOrderNumber() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get order items
     */
    public function getItems($orderId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM order_items 
                WHERE order_id = :order_id
                ORDER BY id ASC
            ');
            $stmt->execute(['order_id' => $orderId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Order->getItems() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update order status
     */
    public function updateStatus($id, $status) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE orders 
                SET status = :status, updated_at = NOW()
                WHERE id = :id
            ');
            return $stmt->execute(['id' => $id, 'status' => $status]);
        } catch (PDOException $e) {
            error_log('Order->updateStatus() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get orders by user
     */
    public function getByUser($userId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM orders 
                WHERE user_id = :user_id
                ORDER BY created_at DESC
            ');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Order->getByUser() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get order statistics
     */
    public function getStats() {
        try {
            $stmt = $this->pdo->query('
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as average_order_value,
                    COUNT(DISTINCT user_id) as unique_customers
                FROM orders
                WHERE status != "cancelled"
            ');
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Order->getStats() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new order
     */
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO orders (
                    user_id,
                    order_number,
                    total_amount,
                    shipping_address,
                    status
                ) VALUES (
                    :user_id,
                    :order_number,
                    :total_amount,
                    :shipping_address,
                    :status
                )
            ');
            
            $stmt->execute([
                'user_id' => $data['user_id'] ?? null,
                'order_number' => $data['order_number'],
                'total_amount' => $data['total_amount'],
                'shipping_address' => $data['shipping_address'],
                'status' => $data['status'] ?? 'pending'
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (PDOException $e) {
            error_log('Order->create() Error: ' . $e->getMessage());
            return false;
        }
    }
}
?>