<?php
// ============================================================
// FILE: app/config/database.php
// PURPOSE: Database connection using PDO
// ============================================================

// ============================================
// Security: Prevent direct access
// ============================================
if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

class Database {
    private $host = 'localhost';
    private $db_name = 'my_shop';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $conn = null;
    
    public function connect() {
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
            
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function disconnect() {
        $this->conn = null;
    }
    
    public function isConnected() {
        return $this->conn !== null;
    }
}

function getDbConnection() {
    static $database = null;
    
    if ($database === null) {
        $database = new Database();
    }
    
    return $database->connect();
}
?>