<?php
// ============================================================
// FILE: public/register.php
// PURPOSE: User registration page - COMPLETE VERSION
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect to home
if (isLoggedIn()) {
    redirect(SITE_URL . 'index.php');
}

// Process registration form
$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $formData['full_name'] = sanitize($_POST['full_name'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['confirm_password'] = $_POST['confirm_password'] ?? '';
    
    // Validate Full Name
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($formData['full_name']) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters.';
    } elseif (strlen($formData['full_name']) > 100) {
        $errors['full_name'] = 'Full name cannot exceed 100 characters.';
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $formData['full_name'])) {
        $errors['full_name'] = 'Full name can only contain letters, spaces, hyphens, and apostrophes.';
    }
    
    // Validate Email
    if (empty($formData['email'])) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (strlen($formData['email']) > 150) {
        $errors['email'] = 'Email cannot exceed 150 characters.';
    }
    
    // Validate Password
    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($formData['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (strlen($formData['password']) > 255) {
        $errors['password'] = 'Password cannot exceed 255 characters.';
    } elseif (!preg_match('/[A-Z]/', $formData['password'])) {
        $errors['password'] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $formData['password'])) {
        $errors['password'] = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $formData['password'])) {
        $errors['password'] = 'Password must contain at least one number.';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $formData['password'])) {
        $errors['password'] = 'Password must contain at least one special character.';
    }
    
    // Validate Confirm Password
    if (empty($formData['confirm_password'])) {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($formData['password'] !== $formData['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    
    // Check for duplicate email
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute(['email' => $formData['email']]);
            
            if ($stmt->fetch()) {
                $errors['email'] = 'This email is already registered.';
            }
            
        } catch (PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare('
                INSERT INTO users (name, email, password, is_admin, created_at) 
                VALUES (:name, :email, :password, 0, NOW())
            ');
            
            $result = $stmt->execute([
                'name' => $formData['full_name'],
                'email' => $formData['email'],
                'password' => $hashedPassword
            ]);
            
            if ($result) {
                $userId = $pdo->lastInsertId();
                
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $formData['full_name'];
                $_SESSION['user_email'] = $formData['email'];
                $_SESSION['is_admin'] = 0;
                
                $_SESSION['success_message'] = 'Registration successful! Welcome to My Shop!';
                
                redirect(SITE_URL . 'index.php');
            } else {
                $errors['general'] = 'Failed to create account. Please try again.';
            }
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors['email'] = 'This email is already registered.';
            } else {
                error_log('Registration error: ' . $e->getMessage());
                $errors['general'] = 'An error occurred while creating your account.';
            }
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors['general'] = 'An unexpected error occurred.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - My Shop</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ============================================================
           ALL CSS IS EMBEDDED HERE TO ENSURE IT WORKS
           ============================================================ */
        
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-wrapper {
            max-width: 580px;
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 40px 45px;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Header */
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .register-header h1::before {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: #2C3E8F;
            margin: 0 auto 15px;
            border-radius: 2px;
        }
        
        .register-header p {
            color: #6c757d;
            font-size: 16px;
        }
        
        .register-header p strong {
            color: #2C3E8F;
        }
        
        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert i {
            font-size: 18px;
        }
        
        /* Form */
        .register-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .form-group label {
            font-weight: 600;
            font-size: 14px;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group label .required {
            color: #e74c3c;
            margin-left: 2px;
            font-weight: 700;
        }
        
        .form-group label i {
            color: #2C3E8F;
            font-size: 14px;
        }
        
        .form-control {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: #f8fafc;
            color: #1a1a2e;
            width: 100%;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2C3E8F;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(44, 62, 143, 0.1);
        }
        
        .form-control.is-invalid {
            border-color: #e74c3c;
            background: #fff5f5;
        }
        
        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1);
        }
        
        .form-control.is-valid {
            border-color: #27ae60;
            background: #f0fff4;
        }
        
        .form-control.is-valid:focus {
            box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.1);
        }
        
        .form-control::placeholder {
            color: #a0aec0;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
        }
        
        .error-message i {
            font-size: 14px;
        }
        
        .form-hint {
            color: #718096;
            font-size: 12px;
            margin-top: 2px;
        }
        
        /* Password Wrapper */
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-wrapper .form-control {
            padding-right: 50px;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #a0aec0;
            font-size: 18px;
            padding: 4px 8px;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: #2C3E8F;
        }
        
        .toggle-password:focus {
            outline: none;
        }
        
        /* Password Requirements */
        .password-requirements {
            margin-top: 8px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .password-requirements small {
            font-weight: 600;
            color: #4a5568;
            display: block;
            margin-bottom: 6px;
        }
        
        .requirements-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 16px;
        }
        
        .requirement {
            font-size: 12px;
            color: #718096;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }
        
        .requirement i {
            font-size: 8px;
            color: #a0aec0;
            transition: all 0.3s ease;
        }
        
        .requirement.met {
            color: #27ae60;
        }
        
        .requirement.met i {
            color: #27ae60;
        }
        
        .requirement.met i::before {
            content: '\f058';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }
        
        .requirement.unmet {
            color: #e74c3c;
        }
        
        .requirement.unmet i {
            color: #e74c3c;
        }
        
        .password-match {
            font-size: 13px;
            font-weight: 500;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .password-match.match {
            color: #27ae60;
        }
        
        .password-match.no-match {
            color: #e74c3c;
        }
        
        /* Checkbox */
        .checkbox-group {
            margin: 6px 0;
        }
        
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            font-weight: 400;
            font-size: 14px;
            color: #4a5568;
            line-height: 1.5;
        }
        
        .checkbox-label input[type="checkbox"] {
            display: none;
        }
        
        .checkmark {
            min-width: 20px;
            height: 20px;
            background: #f8fafc;
            border: 2px solid #cbd5e0;
            border-radius: 4px;
            display: inline-block;
            position: relative;
            flex-shrink: 0;
            transition: all 0.2s ease;
            margin-top: 2px;
        }
        
        .checkbox-label:hover .checkmark {
            border-color: #2C3E8F;
        }
        
        .checkbox-label input:checked + .checkmark {
            background: #2C3E8F;
            border-color: #2C3E8F;
        }
        
        .checkbox-label input:checked + .checkmark::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
        }
        
        .checkbox-label a {
            color: #2C3E8F;
            text-decoration: none;
            font-weight: 600;
        }
        
        .checkbox-label a:hover {
            text-decoration: underline;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: 2px solid transparent;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #2C3E8F;
            color: #ffffff;
            border-color: #2C3E8F;
        }
        
        .btn-primary:hover {
            background: #1a2a6c;
            border-color: #1a2a6c;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(44, 62, 143, 0.3);
        }
        
        .btn-register {
            width: 100%;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 700;
            margin-top: 8px;
        }
        
        .btn-register i {
            margin-right: 8px;
        }
        
        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .btn-register.loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-register.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
        
        /* Links */
        .login-link {
            text-align: center;
            font-size: 14px;
            color: #4a5568;
            margin-top: 4px;
        }
        
        .login-link a {
            color: #2C3E8F;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .login-link a:hover {
            color: #1a2a6c;
            text-decoration: underline;
        }
        
        /* Benefits */
        .register-benefits {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }
        
        .register-benefits h4 {
            font-size: 15px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 12px;
            text-align: center;
        }
        
        .register-benefits ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 20px;
        }
        
        .register-benefits ul li {
            font-size: 13px;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .register-benefits ul li i {
            color: #27ae60;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .register-wrapper {
                padding: 30px 24px;
            }
            
            .register-header h1 {
                font-size: 24px;
            }
            
            .requirements-list {
                grid-template-columns: 1fr;
            }
            
            .register-benefits ul {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .register-wrapper {
                padding: 24px 16px;
            }
            
            .register-header h1 {
                font-size: 20px;
            }
            
            .form-control {
                font-size: 14px;
                padding: 10px 14px;
            }
            
            .btn-register {
                font-size: 14px;
                padding: 12px 20px;
            }
        }
        
        /* Shake animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        .shake {
            animation: shake 0.4s ease;
        }
    </style>
</head>
<body>
    
    <div class="register-wrapper">
        
        <!-- Page Header -->
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Join <strong>My Shop</strong> and start shopping today!</p>
        </div>
        
        <!-- Display General Error -->
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Display Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Registration Form -->
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
              method="POST" 
              class="register-form" 
              id="registerForm"
              novalidate>
            
            <!-- Full Name Field -->
            <div class="form-group">
                <label for="fullName">
                    <i class="fas fa-user"></i>
                    Full Name
                    <span class="required">*</span>
                </label>
                <input type="text" 
                       id="fullName" 
                       name="full_name" 
                       class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                       placeholder="Enter your full name"
                       value="<?php echo htmlspecialchars($formData['full_name']); ?>"
                       required
                       autofocus>
                <?php if (isset($errors['full_name'])): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['full_name']); ?>
                    </div>
                <?php endif; ?>
                <small class="form-hint">Enter your full name as it appears on your ID.</small>
            </div>
            
            <!-- Email Field -->
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i>
                    Email Address
                    <span class="required">*</span>
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                       placeholder="Enter your email address"
                       value="<?php echo htmlspecialchars($formData['email']); ?>"
                       required>
                <?php if (isset($errors['email'])): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['email']); ?>
                    </div>
                <?php endif; ?>
                <small class="form-hint">We'll never share your email with anyone else.</small>
            </div>
            
            <!-- Password Field -->
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i>
                    Password
                    <span class="required">*</span>
                </label>
                <div class="password-wrapper">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                           placeholder="Create a password"
                           required>
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['password']); ?>
                    </div>
                <?php endif; ?>
                <div class="password-requirements">
                    <small>Password must contain:</small>
                    <ul class="requirements-list" id="passwordRequirements">
                        <li id="req-length" class="requirement">
                            <i class="fas fa-circle"></i>
                            At least 8 characters
                        </li>
                        <li id="req-uppercase" class="requirement">
                            <i class="fas fa-circle"></i>
                            One uppercase letter (A-Z)
                        </li>
                        <li id="req-lowercase" class="requirement">
                            <i class="fas fa-circle"></i>
                            One lowercase letter (a-z)
                        </li>
                        <li id="req-number" class="requirement">
                            <i class="fas fa-circle"></i>
                            One number (0-9)
                        </li>
                        <li id="req-special" class="requirement">
                            <i class="fas fa-circle"></i>
                            One special character (!@#$%^&*(),.?":{}|<>)
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Confirm Password Field -->
            <div class="form-group">
                <label for="confirmPassword">
                    <i class="fas fa-check-circle"></i>
                    Confirm Password
                    <span class="required">*</span>
                </label>
                <div class="password-wrapper">
                    <input type="password" 
                           id="confirmPassword" 
                           name="confirm_password" 
                           class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                           placeholder="Confirm your password"
                           required>
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['confirm_password']); ?>
                    </div>
                <?php endif; ?>
                <div id="passwordMatch" class="password-match"></div>
            </div>
            
            <!-- Terms and Conditions -->
            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="termsCheckbox" required>
                    <span class="checkmark"></span>
                    I agree to the <a href="terms.php" target="_blank">Terms &amp; Conditions</a> 
                    and <a href="privacy.php" target="_blank">Privacy Policy</a>
                    <span class="required">*</span>
                </label>
                <div id="termsError" class="error-message" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    You must agree to the terms and conditions.
                </div>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary btn-register">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>
            
            <!-- Login Link -->
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </form>
        
        <!-- Benefits Section -->
        <div class="register-benefits">
            <h4>Why join My Shop?</h4>
            <ul>
                <li><i class="fas fa-check-circle"></i> Access to exclusive deals</li>
                <li><i class="fas fa-check-circle"></i> Fast and secure checkout</li>
                <li><i class="fas fa-check-circle"></i> Order tracking</li>
                <li><i class="fas fa-check-circle"></i> Wishlist your favorite items</li>
                <li><i class="fas fa-check-circle"></i> 24/7 customer support</li>
            </ul>
        </div>
    </div>
    
    <!-- ============================================================
         JAVASCRIPT - Embedded to ensure it works
         ============================================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // DOM REFERENCES
            const form = document.getElementById('registerForm');
            const fullName = document.getElementById('fullName');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');
            const termsCheckbox = document.getElementById('termsCheckbox');
            const submitButton = form.querySelector('button[type="submit"]');
            
            // Password requirements elements
            const reqLength = document.getElementById('req-length');
            const reqUppercase = document.getElementById('req-uppercase');
            const reqLowercase = document.getElementById('req-lowercase');
            const reqNumber = document.getElementById('req-number');
            const reqSpecial = document.getElementById('req-special');
            
            // Password match indicator
            const passwordMatch = document.getElementById('passwordMatch');
            const termsError = document.getElementById('termsError');
            
            // PASSWORD TOGGLE VISIBILITY
            const toggleButtons = document.querySelectorAll('.toggle-password');
            
            toggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const input = this.closest('.password-wrapper').querySelector('input');
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // VALIDATION FUNCTIONS
            function validateFullName() {
                const value = fullName.value.trim();
                const errorElement = fullName.parentElement.querySelector('.error-message');
                
                if (errorElement && !errorElement.id) {
                    errorElement.remove();
                }
                
                fullName.classList.remove('is-invalid', 'is-valid');
                
                if (value.length === 0) {
                    fullName.classList.add('is-invalid');
                    showError(fullName, 'Full name is required.');
                    return false;
                }
                
                if (value.length < 2) {
                    fullName.classList.add('is-invalid');
                    showError(fullName, 'Full name must be at least 2 characters.');
                    return false;
                }
                
                if (value.length > 100) {
                    fullName.classList.add('is-invalid');
                    showError(fullName, 'Full name cannot exceed 100 characters.');
                    return false;
                }
                
                if (!/^[a-zA-Z\s'-]+$/.test(value)) {
                    fullName.classList.add('is-invalid');
                    showError(fullName, 'Full name can only contain letters, spaces, hyphens, and apostrophes.');
                    return false;
                }
                
                fullName.classList.add('is-valid');
                return true;
            }
            
            function validateEmail() {
                const value = email.value.trim();
                const errorElement = email.parentElement.querySelector('.error-message');
                
                if (errorElement && !errorElement.id) {
                    errorElement.remove();
                }
                
                email.classList.remove('is-invalid', 'is-valid');
                
                if (value.length === 0) {
                    email.classList.add('is-invalid');
                    showError(email, 'Email address is required.');
                    return false;
                }
                
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    email.classList.add('is-invalid');
                    showError(email, 'Please enter a valid email address.');
                    return false;
                }
                
                if (value.length > 150) {
                    email.classList.add('is-invalid');
                    showError(email, 'Email cannot exceed 150 characters.');
                    return false;
                }
                
                email.classList.add('is-valid');
                return true;
            }
            
            function validatePassword() {
                const value = password.value;
                
                password.classList.remove('is-invalid', 'is-valid');
                
                const hasLength = value.length >= 8;
                const hasUppercase = /[A-Z]/.test(value);
                const hasLowercase = /[a-z]/.test(value);
                const hasNumber = /[0-9]/.test(value);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(value);
                
                updateRequirement(reqLength, hasLength);
                updateRequirement(reqUppercase, hasUppercase);
                updateRequirement(reqLowercase, hasLowercase);
                updateRequirement(reqNumber, hasNumber);
                updateRequirement(reqSpecial, hasSpecial);
                
                const isValid = hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
                
                if (value.length > 0 && !isValid) {
                    password.classList.add('is-invalid');
                    showError(password, 'Password does not meet the requirements below.');
                } else if (value.length > 0 && isValid) {
                    password.classList.add('is-valid');
                }
                
                if (confirmPassword.value.length > 0) {
                    validateConfirmPassword();
                }
                
                return isValid;
            }
            
            function updateRequirement(element, isMet) {
                if (isMet) {
                    element.classList.remove('unmet');
                    element.classList.add('met');
                    element.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    element.classList.remove('met');
                    element.classList.add('unmet');
                    element.querySelector('i').className = 'fas fa-circle';
                }
            }
            
            function validateConfirmPassword() {
                const value = confirmPassword.value;
                
                confirmPassword.classList.remove('is-invalid', 'is-valid');
                
                if (value.length === 0) {
                    confirmPassword.classList.add('is-invalid');
                    showError(confirmPassword, 'Please confirm your password.');
                    passwordMatch.textContent = '';
                    passwordMatch.className = 'password-match';
                    return false;
                }
                
                if (value !== password.value) {
                    confirmPassword.classList.add('is-invalid');
                    showError(confirmPassword, 'Passwords do not match.');
                    passwordMatch.textContent = '✗ Passwords do not match';
                    passwordMatch.className = 'password-match no-match';
                    return false;
                }
                
                confirmPassword.classList.add('is-valid');
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.className = 'password-match match';
                return true;
            }
            
            function showError(input, message) {
                const parent = input.parentElement;
                let errorElement = parent.querySelector('.error-message');
                
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'error-message';
                    parent.appendChild(errorElement);
                }
                
                errorElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
            }
            
            // EVENT LISTENERS
            fullName.addEventListener('input', validateFullName);
            email.addEventListener('input', validateEmail);
            password.addEventListener('input', function() {
                validatePassword();
                if (confirmPassword.value.length > 0) {
                    validateConfirmPassword();
                }
            });
            confirmPassword.addEventListener('input', validateConfirmPassword);
            
            fullName.addEventListener('blur', validateFullName);
            email.addEventListener('blur', validateEmail);
            password.addEventListener('blur', function() {
                if (this.value.length > 0) {
                    validatePassword();
                }
            });
            confirmPassword.addEventListener('blur', function() {
                if (this.value.length > 0) {
                    validateConfirmPassword();
                }
            });
            
            // TERMS CHECKBOX
            termsCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    termsError.style.display = 'none';
                }
            });
            
            // FORM SUBMISSION
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const isNameValid = validateFullName();
                const isEmailValid = validateEmail();
                const isPasswordValid = validatePassword();
                const isConfirmValid = validateConfirmPassword();
                const isTermsChecked = termsCheckbox.checked;
                
                if (!isTermsChecked) {
                    termsError.style.display = 'flex';
                } else {
                    termsError.style.display = 'none';
                }
                
                if (isNameValid && isEmailValid && isPasswordValid && isConfirmValid && isTermsChecked) {
                    submitButton.disabled = true;
                    submitButton.classList.add('loading');
                    submitButton.innerHTML = 'Creating Account...';
                    form.submit();
                } else {
                    const firstError = form.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else if (!isTermsChecked) {
                        termsCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    
                    form.querySelectorAll('.is-invalid').forEach(function(field) {
                        field.classList.add('shake');
                        setTimeout(function() {
                            field.classList.remove('shake');
                        }, 500);
                    });
                }
            });
            
            console.log('✅ Registration form ready!');
        });
    </script>
    
</body>
</html>