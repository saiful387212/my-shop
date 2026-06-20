<?php

// ============================================================
// FILE: my-shop/app/config/database.php
// PURPOSE: Database connection using PDO with best practices
// ============================================================

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

/**
 * Database Connection Class
 * 
 * Creates a secure PDO connection to MySQL with:
 * - Error handling
 * - Prepared statements support
 * - Character set UTF-8
 * - Fetch mode configuration
 */
class Database {
    /**
     * Database connection parameters
     * Using private properties to encapsulate sensitive data
     */
    private $host = 'localhost';
    private $db_name = 'my_shop';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    /**
     * PDO connection object
     * Stores the active database connection instance
     */
    private $conn = null;
    
    /**
     * Establish a database connection
     * 
     * @return PDO|null Returns PDO object on success, null on failure
     */
    public function connect() {
        // If we already have a connection, return it (reuse)
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            /**
             * Data Source Name (DSN)
             * Tells PDO which database driver to use and connection details
             * 
             * Format: driver:host=value;dbname=value;charset=value
             */
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            /**
             * PDO Options (critical for security and performance)
             * 
             * PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
             *   - Makes PDO throw exceptions instead of errors
             *   - Allows proper try/catch error handling
             * 
             * PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
             *   - Returns results as associative arrays
             *   - Easier to work with column names ($row['name'])
             * 
             * PDO::ATTR_EMULATE_PREPARES => false
             *   - Uses real prepared statements (not emulated)
             *   - Prevents SQL injection more effectively
             *   - Improves performance for complex queries
             */
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            /**
             * Create the PDO connection
             * 
             * Parameters:
             * 1. DSN (Data Source Name)
             * 2. Username
             * 3. Password
             * 4. Options array
             */
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Return the connection object
            return $this->conn;
            
        } catch (PDOException $e) {
            /**
             * Handle connection failure gracefully
             * 
             * NEVER expose database errors to users in production!
             * Log the error instead, and show a generic message
             */
            
            // Log the error (in production, use a proper logging system)
            error_log('Database Connection Error: ' . $e->getMessage());
            
            // Return null to indicate failure
            return null;
        }
    }
    
    /**
     * Close the database connection
     * 
     * Good practice to close connections when no longer needed
     */
    public function disconnect() {
        $this->conn = null;
    }
    
    /**
     * Get the current connection status
     * 
     * @return bool True if connected, false otherwise
     */
    public function isConnected() {
        return $this->conn !== null;
    }
}

/**
 * Create a singleton instance for easy global access
 * 
 * Instead of creating a new connection every time,
 * we reuse the same instance throughout the application
 */
function getDbConnection() {
    static $database = null;
    
    if ($database === null) {
        $database = new Database();
    }
    
    return $database->connect();
}

// ============================================================
// EXAMPLE USAGE (commented out for reference)
// ============================================================

/*
try {
    // Get the database connection
    $pdo = getDbConnection();
    
    if ($pdo === null) {
        throw new Exception('Could not connect to database');
    }
    
    // Prepare and execute a query using PDO
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => 'admin@myshop.com']);
    
    // Fetch results
    $user = $stmt->fetch();
    
    if ($user) {
        echo 'User found: ' . $user['name'];
    } else {
        echo 'User not found';
    }
    
} catch (PDOException $e) {
    // Handle database errors
    error_log('Query Error: ' . $e->getMessage());
    echo 'An error occurred while retrieving data.';
} catch (Exception $e) {
    // Handle general errors
    error_log('General Error: ' . $e->getMessage());
    echo 'An unexpected error occurred.';
}
*/

?>