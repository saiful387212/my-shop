<?php
// ============================================================
// FILE: app/controllers/AuthController.php
// PURPOSE: Handle authentication requests
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

class AuthController {
    
    private $userModel;
    
    public function __construct() {
        require_once ABSPATH . 'app/models/User.php';
        $this->userModel = new User();
    }
    
    /**
     * Handle registration request
     */
    public function register() {
        // Check if POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // Get and validate data
        $name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation (same as in register.php)
        $errors = [];
        
        if (empty($name)) $errors['full_name'] = 'Full name is required.';
        if (empty($email)) $errors['email'] = 'Email is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';
        if (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
        if ($password !== $confirmPassword) $errors['confirm_password'] = 'Passwords do not match.';
        
        // Check if email exists
        if ($this->userModel->emailExists($email)) {
            $errors['email'] = 'Email already registered.';
        }
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        // Create user
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];
        
        $userId = $this->userModel->create($userData);
        
        if ($userId) {
            // Auto-login
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['is_admin'] = 0;
            
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful!',
                'redirect' => SITE_URL . 'index.php'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create account.']);
        }
    }
}