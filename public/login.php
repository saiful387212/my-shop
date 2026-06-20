<?php
// ============================================================
// FILE: public/login.php
// PURPOSE: User login page with session management
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load User model
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'User.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
$currentPage = basename($_SERVER['PHP_SELF']);
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    if ($currentPage !== 'login.php') {
        redirect(SITE_URL . 'index.php');
    }
}

// Initialize variables
$errors = [];
$formData = [
    'email' => '',
    'password' => '',
    'remember' => false
];

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['remember'] = isset($_POST['remember']) ? true : false;
    
    // Validate Email
    if (empty($formData['email'])) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    // Validate Password
    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required.';
    }
    
    // If no validation errors, attempt login
    if (empty($errors)) {
        
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            // Query user by email
            $stmt = $pdo->prepare('
                SELECT id, name, email, password, is_admin 
                FROM users 
                WHERE email = :email
            ');
            $stmt->execute(['email' => $formData['email']]);
            $user = $stmt->fetch();
            
            // Verify password
            if ($user && password_verify($formData['password'], $user['password'])) {
                
                // Store user data in session
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = (int)$user['is_admin'];
                $_SESSION['logged_in_at'] = time();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                
                // Handle Remember Me
                if ($formData['remember']) {
                    $cookieParams = session_get_cookie_params();
                    setcookie(
                        session_name(),
                        session_id(),
                        time() + (30 * 24 * 60 * 60),
                        $cookieParams['path'],
                        $cookieParams['domain'],
                        $cookieParams['secure'],
                        $cookieParams['httponly']
                    );
                }
                
                // Update last login timestamp
                $updateStmt = $pdo->prepare('
                    UPDATE users 
                    SET last_login = NOW() 
                    WHERE id = :id
                ');
                $updateStmt->execute(['id' => $user['id']]);
                
                // Set success message
                $_SESSION['success_message'] = 'Welcome back, ' . $user['name'] . '!';
                
                // Redirect based on role
                if ($user['is_admin'] == 1) {
                    header('Location: ' . ADMIN_URL);
                } else {
                    header('Location: ' . SITE_URL . 'index.php');
                }
                exit;
                
            } else {
                $errors['general'] = 'Invalid email or password. Please try again.';
                error_log("Failed login attempt for email: " . $formData['email']);
            }
            
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
}

// Set page title
$pageTitle = 'Login';

// ============================================
// INCLUDE THE HEADER
// ============================================
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     LOGIN FORM - THIS IS THE MISSING PART!
     ============================================ -->
<section class="login-section">
    <div class="container">
        <div class="login-wrapper">
            
            <!-- Page Header -->
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Login to your <strong>My Shop</strong> account</p>
            </div>
            
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
            
            <!-- Display General Error -->
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>
            
            <!-- ============================================
                 LOGIN FORM - START
                 ============================================ -->
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
                  method="POST" 
                  class="login-form" 
                  id="loginForm"
                  novalidate>
                
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
                           required
                           autofocus>
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($errors['email']); ?>
                        </div>
                    <?php endif; ?>
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
                               placeholder="Enter your password"
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
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" <?php echo $formData['remember'] ? 'checked' : ''; ?>>
                        <span class="checkmark"></span>
                        Remember Me
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
                
                <!-- Register Link -->
                <div class="register-link">
                    Don't have an account? <a href="<?php echo SITE_URL; ?>register.php">Create one now</a>
                </div>
            </form>
            <!-- ============================================
                 LOGIN FORM - END
                 ============================================ -->
            
            <!-- Security Info -->
            <div class="login-security">
                <div class="security-badge">
                    <i class="fas fa-lock"></i>
                    <span>Secure Login</span>
                </div>
                <p>Your information is protected with industry-standard encryption.</p>
            </div>
        </div>
    </div>
</section>

<?php
// ============================================
// INCLUDE THE FOOTER
// ============================================
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>
<?php
// In public/login.php - after successful login

if ($user && password_verify($formData['password'], $user['password'])) {
    
    // ============================================
    // FIX: Set ALL session variables
    // ============================================
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin'] = (int)$user['is_admin'];  // ← THIS IS CRITICAL!
    $_SESSION['logged_in_at'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    // Debug: Log session data
    error_log('Login successful - Session data: ' . print_r($_SESSION, true));
    
    // Redirect based on role
    if ($user['is_admin'] == 1) {
        header('Location: ' . ADMIN_URL . 'dashboard.php');
    } else {
        header('Location: ' . SITE_URL . 'index.php');
    }
    exit;
}