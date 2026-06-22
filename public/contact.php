<?php
// ============================================================
// FILE: public/contact.php
// PURPOSE: Contact Us page for My Shop
// ============================================================

// Define the absolute path
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

// Load configuration
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load database connection
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

// Load helper functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'functions.php';

// Load cart functions
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart_functions.php';

// Load Product model for categories
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Product.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get cart count
$cartCount = getCartTotalItems();

// Get categories for navigation
$categories = [];
try {
    $productModel = new Product();
    $categories = $productModel->getAllCategories();
} catch (Exception $e) {
    error_log('Contact page error: ' . $e->getMessage());
}

// ============================================
// PROCESS CONTACT FORM
// ============================================

$errors = [];
$success = false;
$formData = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $formData['name'] = sanitize($_POST['name'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['subject'] = sanitize($_POST['subject'] ?? '');
    $formData['message'] = sanitize($_POST['message'] ?? '');
    
    // Validation
    if (empty($formData['name'])) {
        $errors['name'] = 'Your name is required.';
    } elseif (strlen($formData['name']) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    if (empty($formData['subject'])) {
        $errors['subject'] = 'Subject is required.';
    } elseif (strlen($formData['subject']) < 3) {
        $errors['subject'] = 'Subject must be at least 3 characters.';
    }
    
    if (empty($formData['message'])) {
        $errors['message'] = 'Message is required.';
    } elseif (strlen($formData['message']) < 10) {
        $errors['message'] = 'Message must be at least 10 characters.';
    }
    
    // If no errors, show success (skip email for demo)
    if (empty($errors)) {
        $success = true;
        $formData = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    }
}

$pageTitle = 'Contact Us';

// Include header
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     CONTACT PAGE CONTENT
     ============================================ -->
<section class="contact-page">
    <div class="container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1>Contact <span class="highlight">Us</span></h1>
            <p>We'd love to hear from you</p>
        </div>
        
        <!-- ============================================
             SUCCESS MESSAGE
             ============================================ -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Thank you!</strong> Your message has been sent successfully. We'll get back to you soon.
                </div>
            </div>
        <?php endif; ?>
        
        <div class="contact-container">
            
            <!-- ============================================
                 CONTACT FORM
                 ============================================ -->
            <div class="contact-form-wrapper">
                <h2>Send Us a Message</h2>
                <p>Fill out the form below and we'll get back to you as soon as possible.</p>
                
                <form action="" method="POST" class="contact-form" id="contactForm">
                    
                    <!-- Name -->
                    <div class="form-group">
                        <label for="name">Your Name <span class="required">*</span></label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                               placeholder="John Doe"
                               value="<?php echo htmlspecialchars($formData['name']); ?>"
                               required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['name']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                               placeholder="john@example.com"
                               value="<?php echo htmlspecialchars($formData['email']); ?>"
                               required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Subject -->
                    <div class="form-group">
                        <label for="subject">Subject <span class="required">*</span></label>
                        <input type="text" 
                               id="subject" 
                               name="subject" 
                               class="form-control <?php echo isset($errors['subject']) ? 'is-invalid' : ''; ?>"
                               placeholder="Order Inquiry"
                               value="<?php echo htmlspecialchars($formData['subject']); ?>"
                               required>
                        <?php if (isset($errors['subject'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['subject']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Message -->
                    <div class="form-group">
                        <label for="message">Message <span class="required">*</span></label>
                        <textarea id="message" 
                                  name="message" 
                                  rows="5" 
                                  class="form-control <?php echo isset($errors['message']) ? 'is-invalid' : ''; ?>"
                                  placeholder="Write your message here..."
                                  required><?php echo htmlspecialchars($formData['message']); ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['message']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
            
            <!-- ============================================
                 CONTACT INFO
                 ============================================ -->
            <div class="contact-info-wrapper">
                <h2>Get in Touch</h2>
                <p>Here's how you can reach us:</p>
                
                <div class="contact-details">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Our Address</h4>
                            <p>123 Main Street, Suite 100<br>New York, NY 10001</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Email Us</h4>
                            <p><a href="mailto:info@myshop.com">info@myshop.com</a></p>
                            <p><a href="mailto:support@myshop.com">support@myshop.com</a></p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Call Us</h4>
                            <p><a href="tel:+15551234567">+1 (555) 123-4567</a></p>
                            <p><a href="tel:+15551234568">+1 (555) 123-4568</a></p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Working Hours</h4>
                            <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                            <p>Saturday: 10:00 AM - 4:00 PM</p>
                            <p>Sunday: Closed</p>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media -->
                <div class="social-media">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ============================================
             MAP SECTION
             ============================================ -->
        <div class="map-section">
            <h2>Find Us</h2>
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.2!2d-73.9878!3d40.7577!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25855c6480299%3A0x55194ec5a1ae072e!2sTimes%20Square!5e0!3m2!1sen!2sus!4v1700000000000!5m2!1sen!2sus" 
                    width="100%" 
                    height="350" 
                    style="border:0; border-radius:12px;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
        
    </div>
</section>

<!-- ============================================
     CONTACT PAGE STYLES
     ============================================ -->
<style>
/* ============================================
   CONTACT PAGE
   ============================================ */

.contact-page {
    padding: 40px 0 60px;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* ============================================
   PAGE HEADER
   ============================================ */

.page-header {
    text-align: center;
    margin-bottom: 50px;
}

.page-header h1 {
    font-size: 36px;
    font-weight: 800;
    color: #1a1a2e;
    font-family: 'Inter', sans-serif;
}

.page-header h1 .highlight {
    color: #2C3E8F;
}

.page-header h1::after {
    content: '';
    display: block;
    width: 60px;
    height: 4px;
    background: #2C3E8F;
    margin: 10px auto 0;
    border-radius: 2px;
}

.page-header p {
    color: #6c757d;
    font-size: 18px;
    margin-top: 8px;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   ALERT MESSAGES
   ============================================ */

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 14px;
    font-family: 'Inter', sans-serif;
    animation: slideDown 0.5s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-success i {
    font-size: 24px;
    color: #27ae60;
}

/* ============================================
   CONTACT CONTAINER
   ============================================ */

.contact-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 50px;
}

/* ============================================
   CONTACT FORM
   ============================================ */

.contact-form-wrapper {
    background: white;
    border-radius: 16px;
    padding: 36px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
}

.contact-form-wrapper h2 {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 8px;
    font-family: 'Inter', sans-serif;
}

.contact-form-wrapper p {
    color: #6c757d;
    margin-bottom: 24px;
    font-family: 'Inter', sans-serif;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    color: #1a1a2e;
    margin-bottom: 6px;
    font-family: 'Inter', sans-serif;
}

.form-group label .required {
    color: #e74c3c;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
    background: #f8fafc;
    box-sizing: border-box;
    color: #1a1a2e;
}

.form-control:focus {
    outline: none;
    border-color: #2C3E8F;
    background: white;
    box-shadow: 0 0 0 4px rgba(44, 62, 143, 0.1);
}

.form-control.is-invalid {
    border-color: #e74c3c;
    background: #fff5f5;
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.error-message {
    color: #e74c3c;
    font-size: 13px;
    font-weight: 500;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: 'Inter', sans-serif;
}

.btn-submit {
    width: 100%;
    padding: 14px;
    font-size: 16px;
    font-weight: 700;
    border-radius: 10px;
    background: #2C3E8F;
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-submit:hover {
    background: #1a2a6c;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(44, 62, 143, 0.3);
}

/* ============================================
   CONTACT INFO
   ============================================ */

.contact-info-wrapper {
    background: white;
    border-radius: 16px;
    padding: 36px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
}

.contact-info-wrapper h2 {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 8px;
    font-family: 'Inter', sans-serif;
}

.contact-info-wrapper p {
    color: #6c757d;
    margin-bottom: 24px;
    font-family: 'Inter', sans-serif;
}

.contact-details {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 30px;
}

.contact-item {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.contact-icon {
    width: 48px;
    height: 48px;
    min-width: 48px;
    background: #e8edf9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #2C3E8F;
}

.contact-text h4 {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 2px;
    font-family: 'Inter', sans-serif;
}

.contact-text p {
    font-size: 14px;
    color: #4a5568;
    margin: 0;
    line-height: 1.6;
    font-family: 'Inter', sans-serif;
}

.contact-text p a {
    color: #2C3E8F;
    text-decoration: none;
}

.contact-text p a:hover {
    text-decoration: underline;
}

/* ============================================
   SOCIAL MEDIA
   ============================================ */

.social-media h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 12px;
    font-family: 'Inter', sans-serif;
}

.social-media .social-links {
    display: flex;
    gap: 12px;
}

.social-media .social-links a {
    width: 44px;
    height: 44px;
    background: #f1f4f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1a1a2e;
    font-size: 18px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.social-media .social-links a:hover {
    background: #2C3E8F;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(44, 62, 143, 0.3);
}

/* ============================================
   MAP SECTION
   ============================================ */

.map-section {
    margin-top: 10px;
}

.map-section h2 {
    text-align: center;
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 24px;
    font-family: 'Inter', sans-serif;
}

.map-container {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
}

.map-container iframe {
    display: block;
    width: 100%;
    height: 350px;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1024px) {
    .contact-container {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}

@media (max-width: 768px) {
    .contact-page {
        padding: 30px 0 40px;
    }
    
    .contact-form-wrapper {
        padding: 24px;
    }
    
    .contact-info-wrapper {
        padding: 24px;
    }
    
    .page-header h1 {
        font-size: 28px;
    }
    
    .map-container iframe {
        height: 250px;
    }
}

@media (max-width: 480px) {
    .contact-form-wrapper {
        padding: 16px;
    }
    
    .contact-info-wrapper {
        padding: 16px;
    }
    
    .contact-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .page-header h1 {
        font-size: 24px;
    }
    
    .social-media .social-links {
        justify-content: center;
    }
}
</style>

<?php
// Include footer
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>