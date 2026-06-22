<?php
// ============================================================
// FILE: public/register.php
// PURPOSE: User registration with DU student fields
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load models
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Shop.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CHECK IF USER IS ALREADY LOGGED IN
// ============================================

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

// ============================================
// GET CATEGORIES FOR HEADER
// ============================================

$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Register categories error: ' . $e->getMessage());
}

// ============================================
// PROCESS REGISTRATION FORM
// ============================================

$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => '',
    'student_id' => '',
    'department' => '',
    'batch' => '',
    'hall' => ''
];

// Department options
$departments = [
    'Faculty of Arts',
    'Faculty of Science',
    'Faculty of Business Studies',
    'Faculty of Engineering and Technology',
    'Faculty of Law',
    'Faculty of Social Sciences',
    'Faculty of Biological Sciences',
    'Faculty of Pharmacy',
    'Faculty of Earth and Environmental Sciences',
    'Institute of Education and Research',
    'Institute of Business Administration (IBA)',
    'Other'
];

// Hall options
$halls = [
    'Curzon Hall',
    'Sir A F Rahman Hall',
    'Shaheedullah Hall',
    'Bijoy Ekattor Hall',
    'Kazi Nazrul Islam Hall',
    'Jagannath Hall',
    'Salimullah Muslim Hall',
    'Haji Muhammad Mohsin Hall',
    'Shahid Ziaur Rahman Hall',
    'Rokeya Hall (Women)',
    'Shamsun Nahar Hall (Women)',
    'Other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data
    $formData['full_name'] = sanitize($_POST['full_name'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['confirm_password'] = $_POST['confirm_password'] ?? '';
    $formData['student_id'] = sanitize($_POST['student_id'] ?? '');
    $formData['department'] = sanitize($_POST['department'] ?? '');
    $formData['batch'] = sanitize($_POST['batch'] ?? '');
    $formData['hall'] = sanitize($_POST['hall'] ?? '');
    
    // ============================================
    // VALIDATION
    // ============================================
    
    // Full Name
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($formData['full_name']) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters.';
    } elseif (strlen($formData['full_name']) > 100) {
        $errors['full_name'] = 'Full name cannot exceed 100 characters.';
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $formData['full_name'])) {
        $errors['full_name'] = 'Full name can only contain letters, spaces, hyphens, and apostrophes.';
    }
    
    // Email - with DU verification
    if (empty($formData['email'])) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (strlen($formData['email']) > 150) {
        $errors['email'] = 'Email cannot exceed 150 characters.';
    } elseif (!strpos($formData['email'], '@du.ac.bd')) {
        $errors['email'] = 'Only Dhaka University email addresses (@du.ac.bd) are allowed.';
    }
    
    // Password
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
    
    // Confirm Password
    if (empty($formData['confirm_password'])) {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($formData['password'] !== $formData['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    
    // Student ID
    if (empty($formData['student_id'])) {
        $errors['student_id'] = 'Student ID is required.';
    } elseif (!preg_match('/^[0-9\-]+$/', $formData['student_id'])) {
        $errors['student_id'] = 'Please enter a valid student ID (e.g., 2023-1-00-000).';
    }
    
    // Department
    if (empty($formData['department'])) {
        $errors['department'] = 'Please select your department.';
    }
    
    // Batch
    if (empty($formData['batch'])) {
        $errors['batch'] = 'Please enter your batch/year.';
    } elseif (!preg_match('/^[0-9]{4}$/', $formData['batch'])) {
        $errors['batch'] = 'Please enter a valid batch year (e.g., 2023).';
    }
    
    // Hall
    if (empty($formData['hall'])) {
        $errors['hall'] = 'Please select your hall of residence.';
    }
    
    // ============================================
    // CHECK FOR DUPLICATE EMAIL
    // ============================================
    
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            
            if ($pdo === null) {
                throw new Exception('Database connection failed.');
            }
            
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute(['email' => $formData['email']]);
            
            if ($stmt->fetch()) {
                $errors['email'] = 'This email is already registered. Please use a different email or login.';
            }
            
        } catch (PDOException $e) {
            error_log('Registration error (check email): ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
    
    // ============================================
    // IF NO ERRORS, CREATE USER
    // ============================================
    
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare('
                INSERT INTO users (
                    name, 
                    email, 
                    password, 
                    student_id, 
                    department, 
                    batch, 
                    hall, 
                    is_admin, 
                    is_verified,
                    created_at
                ) VALUES (
                    :name, 
                    :email, 
                    :password, 
                    :student_id, 
                    :department, 
                    :batch, 
                    :hall, 
                    0, 
                    0,
                    NOW()
                )
            ');
            
            $result = $stmt->execute([
                'name' => $formData['full_name'],
                'email' => $formData['email'],
                'password' => $hashedPassword,
                'student_id' => $formData['student_id'],
                'department' => $formData['department'],
                'batch' => $formData['batch'],
                'hall' => $formData['hall']
            ]);
            
            if ($result) {
                $userId = $pdo->lastInsertId();
                
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $formData['full_name'];
                $_SESSION['user_email'] = $formData['email'];
                $_SESSION['is_admin'] = 0;
                $_SESSION['department'] = $formData['department'];
                $_SESSION['batch'] = $formData['batch'];
                $_SESSION['hall'] = $formData['hall'];
                $_SESSION['student_id'] = $formData['student_id'];
                
                $_SESSION['success_message'] = 'Registration successful! Welcome to DU Marketplace!';
                
                // Check if user has a shop
                $shopModel = new Shop();
                $userShop = $shopModel->getByUser($userId);
                
                if ($userShop) {
                    header('Location: ' . SITE_URL . 'shop/manage.php');
                } else {
                    header('Location: ' . SITE_URL . 'index.php');
                }
                exit;
            } else {
                $errors['general'] = 'Failed to create account. Please try again.';
            }
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors['email'] = 'This email is already registered.';
            } else {
                error_log('Registration error (insert): ' . $e->getMessage());
                $errors['general'] = 'An error occurred while creating your account. Please try again.';
            }
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors['general'] = 'An unexpected error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Register';

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     REGISTRATION FORM
     ============================================ -->
<section class="register-section">
    <div class="container">
        <div class="register-wrapper">
            
            <!-- Page Header -->
            <div class="register-header">
                <h1>Join DU Marketplace</h1>
                <p>Create your <strong>Dhaka University</strong> student account</p>
            </div>
            
            <!-- DU Badge -->
            <div class="du-badge">
                <i class="fas fa-university"></i>
                <span>Dhaka University Student Portal</span>
            </div>
            
            <!-- General Error -->
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
                  method="POST" 
                  class="register-form" 
                  id="registerForm"
                  novalidate>
                
                <!-- Personal Information -->
                <div class="form-section">
                    <h3><i class="fas fa-user-circle"></i> Personal Information</h3>
                    
                    <!-- Full Name -->
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
                               required>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['full_name']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            DU Email Address
                            <span class="required">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                               placeholder="yourname@du.ac.bd"
                               value="<?php echo htmlspecialchars($formData['email']); ?>"
                               required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['email']); ?>
                            </div>
                        <?php endif; ?>
                        <small class="form-hint">Only @du.ac.bd email addresses are allowed.</small>
                    </div>
                </div>
                
                <!-- DU Student Information -->
                <div class="form-section du-section">
                    <h3><i class="fas fa-university"></i> DU Student Information</h3>
                    
                    <!-- Student ID -->
                    <div class="form-group">
                        <label for="student_id">
                            <i class="fas fa-id-card"></i>
                            Student ID
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="student_id" 
                               name="student_id" 
                               class="form-control <?php echo isset($errors['student_id']) ? 'is-invalid' : ''; ?>"
                               placeholder="e.g., 2023-1-00-000"
                               value="<?php echo htmlspecialchars($formData['student_id']); ?>"
                               required>
                        <?php if (isset($errors['student_id'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['student_id']); ?>
                            </div>
                        <?php endif; ?>
                        <small class="form-hint">Enter your valid DU student ID.</small>
                    </div>
                    
                    <!-- Department -->
                    <div class="form-group">
                        <label for="department">
                            <i class="fas fa-graduation-cap"></i>
                            Department
                            <span class="required">*</span>
                        </label>
                        <select id="department" 
                                name="department" 
                                class="form-control <?php echo isset($errors['department']) ? 'is-invalid' : ''; ?>" 
                                required>
                            <option value="">Select your department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>" 
                                    <?php echo ($formData['department'] == $dept) ? 'selected' : ''; ?>>
                                    <?php echo $dept; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['department'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['department']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Batch -->
                    <div class="form-group">
                        <label for="batch">
                            <i class="fas fa-calendar-alt"></i>
                            Batch / Session
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="batch" 
                               name="batch" 
                               class="form-control <?php echo isset($errors['batch']) ? 'is-invalid' : ''; ?>"
                               placeholder="e.g., 2023"
                               value="<?php echo htmlspecialchars($formData['batch']); ?>"
                               required>
                        <?php if (isset($errors['batch'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['batch']); ?>
                            </div>
                        <?php endif; ?>
                        <small class="form-hint">Enter your admission batch year (e.g., 2023).</small>
                    </div>
                    
                    <!-- Hall -->
                    <div class="form-group">
                        <label for="hall">
                            <i class="fas fa-building"></i>
                            Hall of Residence
                            <span class="required">*</span>
                        </label>
                        <select id="hall" 
                                name="hall" 
                                class="form-control <?php echo isset($errors['hall']) ? 'is-invalid' : ''; ?>" 
                                required>
                            <option value="">Select your hall</option>
                            <?php foreach ($halls as $hall): ?>
                                <option value="<?php echo $hall; ?>" 
                                    <?php echo ($formData['hall'] == $hall) ? 'selected' : ''; ?>>
                                    <?php echo $hall; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['hall'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['hall']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Password Information -->
                <div class="form-section">
                    <h3><i class="fas fa-lock"></i> Password</h3>
                    
                    <!-- Password -->
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
                            <ul class="requirements-list">
                                <li id="req-length" class="requirement"><i class="fas fa-circle"></i> At least 8 characters</li>
                                <li id="req-uppercase" class="requirement"><i class="fas fa-circle"></i> One uppercase letter</li>
                                <li id="req-lowercase" class="requirement"><i class="fas fa-circle"></i> One lowercase letter</li>
                                <li id="req-number" class="requirement"><i class="fas fa-circle"></i> One number</li>
                                <li id="req-special" class="requirement"><i class="fas fa-circle"></i> One special character</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
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
                </div>
                
                <!-- Terms -->
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="termsCheckbox" required>
                        <span class="checkmark"></span>
                        I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> 
                        and <a href="privacy.php" target="_blank">Privacy Policy</a>
                        <span class="required">*</span>
                    </label>
                    <div id="termsError" class="error-message" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i>
                        You must agree to the terms and conditions.
                    </div>
                </div>
                
                <!-- Submit -->
                <button type="submit" class="btn btn-primary btn-register">
                    <i class="fas fa-user-plus"></i>
                    Create DU Account
                </button>
                
                <!-- Login Link -->
                <div class="login-link">
                    Already have an account? <a href="<?php echo SITE_URL; ?>login.php">Login here</a>
                </div>
            </form>
            
            <!-- DU Benefits -->
            <div class="register-benefits">
                <h4>Why join DU Marketplace?</h4>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Connect with DU students</li>
                    <li><i class="fas fa-check-circle"></i> Buy and sell textbooks</li>
                    <li><i class="fas fa-check-circle"></i> Campus meetups</li>
                    <li><i class="fas fa-check-circle"></i> Trusted student community</li>
                    <li><i class="fas fa-check-circle"></i> Exclusive DU deals</li>
                </ul>
            </div>
            
        </div>
    </div>
</section>

<style>
/* ============================================
   REGISTRATION PAGE STYLES
   ============================================ */

.register-section {
    padding: 40px 0 60px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
}

.register-wrapper {
    max-width: 600px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    padding: 40px 45px;
    animation: slideUp 0.5s ease;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.register-header {
    text-align: center;
    margin-bottom: 20px;
}

.register-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: #1a1a2e;
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

/* DU Badge */
.du-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #f0f4ff;
    color: #2C3E8F;
    padding: 10px 20px;
    border-radius: 50px;
    margin-bottom: 24px;
    border: 1px solid #cce5ff;
    font-weight: 600;
    font-size: 14px;
}

.du-badge i {
    font-size: 18px;
}

/* Form Sections */
.form-section {
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 16px;
}

.form-section h3 {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
}

.form-section h3 i {
    color: #2C3E8F;
    margin-right: 8px;
}

.du-section {
    background: #f8faff;
    padding: 16px 20px;
    border-radius: 12px;
    border: 1px solid #e8edf9;
}

.du-section h3 i {
    color: #2C3E8F;
}

/* Form Elements */
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

.alert i {
    font-size: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
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

select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 40px;
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

.form-hint {
    color: #718096;
    font-size: 12px;
    margin-top: 2px;
}

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
}

.toggle-password:hover {
    color: #2C3E8F;
}

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
}

.requirement i {
    font-size: 8px;
    color: #a0aec0;
}

.requirement.met {
    color: #27ae60;
}

.requirement.met i {
    color: #27ae60;
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

/* Button */
.btn-register {
    width: 100%;
    padding: 14px 28px;
    font-size: 16px;
    font-weight: 700;
    margin-top: 8px;
    border-radius: 10px;
    background: #2C3E8F;
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-register:hover {
    background: #1a2a6c;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(44, 62, 143, 0.3);
}

.btn-register i {
    margin-right: 8px;
}

.login-link {
    text-align: center;
    font-size: 14px;
    color: #4a5568;
    margin-top: 16px;
}

.login-link a {
    color: #2C3E8F;
    font-weight: 600;
    text-decoration: none;
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
        margin: 0 16px;
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
    
    .du-section {
        padding: 12px 14px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle
    document.querySelectorAll('.toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
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
    
    console.log('✅ DU Registration page loaded!');
});
</script>

<?php require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php'; ?>