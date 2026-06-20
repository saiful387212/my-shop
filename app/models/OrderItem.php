<?php
// ============================================================
// FILE: app/models/OrderItem.php
// PURPOSE: Order item model for database operations
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class OrderItem {
    
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
        if ($this->pdo === null) {
            throw new Exception('Database connection failed.');
        }
    }
    
    /**
     * Get all items for an order
     */
    public function getByOrderId($orderId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM order_items 
                WHERE order_id = :order_id
                ORDER BY id ASC
            ');
            $stmt->execute(['order_id' => $orderId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('OrderItem->getByOrderId() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create an order item
     */
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_name,
                    product_price,
                    quantity
                ) VALUES (
                    :order_id,
                    :product_id,
                    :product_name,
                    :product_price,
                    :quantity
                )
            ');
            
            return $stmt->execute([
                'order_id' => $data['order_id'],
                'product_id' => $data['product_id'],
                'product_name' => $data['product_name'],
                'product_price' => $data['product_price'],
                'quantity' => $data['quantity']
            ]);
            
        } catch (PDOException $e) {
            error_log('OrderItem->create() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete all items for an order
     */
    public function deleteByOrderId($orderId) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM order_items WHERE order_id = :order_id');
            return $stmt->execute(['order_id' => $orderId]);
        } catch (PDOException $e) {
            error_log('OrderItem->deleteByOrderId() Error: ' . $e->getMessage());
            return false;
        }
    }
}
?>