<?php
// ============================================================
// FILE: app/models/User.php
// PURPOSE: User model with database operations
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class User {
    
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
        if ($this->pdo === null) {
            throw new Exception('Database connection failed.');
        }
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT id, name, email, password, is_admin, created_at, last_login
                FROM users 
                WHERE email = :email
            ');
            $stmt->execute(['email' => $email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('User->findByEmail() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT id, name, email, is_admin, created_at, last_login
                FROM users 
                WHERE id = :id
            ');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('User->findById() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email) {
        try {
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute(['email' => $email]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log('User->emailExists() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO users (name, email, password, is_admin) 
                VALUES (:name, :email, :password, :is_admin)
            ');
            
            $result = $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_admin' => $data['is_admin'] ?? 0
            ]);
            
            if ($result) {
                return (int)$this->pdo->lastInsertId();
            }
            return false;
            
        } catch (PDOException $e) {
            error_log('User->create() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last login timestamp
     */
    public function updateLastLogin($userId) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE users 
                SET last_login = NOW() 
                WHERE id = :id
            ');
            return $stmt->execute(['id' => $userId]);
        } catch (PDOException $e) {
            error_log('User->updateLastLogin() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify user password
     */
    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
}
?>