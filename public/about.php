<?php
// ============================================================
// FILE: public/about.php
// PURPOSE: About Us page for My Shop
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

// Load Product model
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
    error_log('About page error: ' . $e->getMessage());
}

$pageTitle = 'About Us';

// ============================================
// USE THE SAME HEADER AS OTHER PAGES
// ============================================
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================
     ABOUT PAGE CONTENT
     ============================================ -->
<section class="about-page">
    <div class="container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1>About <span class="highlight">My Shop</span></h1>
            <p>Your trusted online shopping destination</p>
        </div>
        
        <!-- ============================================
             OUR STORY
             ============================================ -->
        <div class="about-story">
            <div class="story-content">
                <h2>Our Story</h2>
                <p>
                    Welcome to <strong>My Shop</strong> - your one-stop destination for quality products at affordable prices. 
                    We started with a simple mission: to make online shopping easy, secure, and enjoyable for everyone.
                </p>
                <p>
                    Founded in 2024, My Shop has grown from a small local store to a trusted online marketplace serving 
                    thousands of customers across the country. We believe in providing the best shopping experience 
                    with competitive prices, fast delivery, and exceptional customer service.
                </p>
                <p>
                    Whether you're looking for the latest electronics, fashion trends, home essentials, or unique gifts, 
                    we've got you covered. Our team carefully selects every product to ensure quality and value for our customers.
                </p>
                <div class="story-stats">
                    <div class="stat-item">
                        <span class="stat-number">10K+</span>
                        <span class="stat-label">Happy Customers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Products</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">4.9⭐</span>
                        <span class="stat-label">Average Rating</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">99.9%</span>
                        <span class="stat-label">On-Time Delivery</span>
                    </div>
                </div>
            </div>
            <div class="story-image">
                <div class="image-placeholder">
                    <i class="fas fa-store"></i>
                    <span>My Shop</span>
                </div>
            </div>
        </div>
        
        <!-- ============================================
             OUR MISSION & VALUES
             ============================================ -->
        <div class="about-mission">
            <h2>Our Mission &amp; Values</h2>
            <p class="mission-text">
                We're committed to providing an exceptional shopping experience through quality products, 
                secure transactions, and outstanding customer service.
            </p>
            
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Customer First</h3>
                    <p>Our customers are at the heart of everything we do. We listen, care, and always strive to exceed expectations.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Trust &amp; Security</h3>
                    <p>Your security matters. We use industry-standard encryption to protect your personal and payment information.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3>Quality Products</h3>
                    <p>We carefully curate our selection to ensure every product meets our high standards of quality and value.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Sustainability</h3>
                    <p>We're committed to sustainable practices and reducing our environmental impact through eco-friendly packaging.</p>
                </div>
            </div>
        </div>
        
        <!-- ============================================
             WHAT WE OFFER
             ============================================ -->
        <div class="about-offer">
            <h2>What We Offer</h2>
            
            <div class="offer-grid">
                <div class="offer-item">
                    <i class="fas fa-box"></i>
                    <h4>Wide Product Range</h4>
                    <p>From electronics to fashion, books to home essentials - we have it all.</p>
                </div>
                <div class="offer-item">
                    <i class="fas fa-truck"></i>
                    <h4>Fast Delivery</h4>
                    <p>Free shipping on orders over $50 with quick and reliable delivery.</p>
                </div>
                <div class="offer-item">
                    <i class="fas fa-undo-alt"></i>
                    <h4>Easy Returns</h4>
                    <p>30-day money-back guarantee with hassle-free return process.</p>
                </div>
                <div class="offer-item">
                    <i class="fas fa-headset"></i>
                    <h4>24/7 Support</h4>
                    <p>Our dedicated support team is always ready to help you.</p>
                </div>
                <div class="offer-item">
                    <i class="fas fa-lock"></i>
                    <h4>Secure Payment</h4>
                    <p>100% secure checkout with multiple payment options.</p>
                </div>
                <div class="offer-item">
                    <i class="fas fa-star"></i>
                    <h4>Best Prices</h4>
                    <p>Competitive prices with regular deals and exclusive discounts.</p>
                </div>
            </div>
        </div>
        
        <!-- ============================================
             TEAM SECTION
             ============================================ -->
        <div class="about-team">
            <h2>Meet Our Team</h2>
            <p class="team-subtitle">The passionate people behind My Shop</p>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h4>John Doe</h4>
                    <span class="member-role">Founder &amp; CEO</span>
                    <p>Visionary leader with 15+ years of experience in e-commerce.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h4>Jane Smith</h4>
                    <span class="member-role">Operations Manager</span>
                    <p>Ensures smooth operations and customer satisfaction.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h4>Mike Johnson</h4>
                    <span class="member-role">Head of Marketing</span>
                    <p>Creative strategist driving brand growth and engagement.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h4>Sarah Williams</h4>
                    <span class="member-role">Customer Support Lead</span>
                    <p>Passionate about delivering exceptional customer experiences.</p>
                </div>
            </div>
        </div>
        
        <!-- ============================================
             TESTIMONIALS
             ============================================ -->
        <div class="about-testimonials">
            <h2>What Our Customers Say</h2>
            
            <div class="testimonials-grid">
                <div class="testimonial">
                    <div class="testimonial-stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p>"Amazing shopping experience! The products are high quality and delivery was super fast. Highly recommend My Shop!"</p>
                    <div class="testimonial-author">
                        <strong>Priya Sharma</strong>
                        <span>Verified Buyer</span>
                    </div>
                </div>
                
                <div class="testimonial">
                    <div class="testimonial-stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p>"The best online store I've ever used. Great prices, excellent customer service, and easy returns. Will definitely shop again!"</p>
                    <div class="testimonial-author">
                        <strong>Ahmed Khan</strong>
                        <span>Verified Buyer</span>
                    </div>
                </div>
                
                <div class="testimonial">
                    <div class="testimonial-stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p>"Love the variety of products available on My Shop! The website is easy to use and the checkout process is seamless."</p>
                    <div class="testimonial-author">
                        <strong>Emily Davis</strong>
                        <span>Verified Buyer</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ============================================
             CALL TO ACTION
             ============================================ -->
        <div class="about-cta">
            <div class="cta-content">
                <h2>Ready to Start Shopping?</h2>
                <p>Join thousands of happy customers and discover amazing products today!</p>
                <a href="<?php echo SITE_URL; ?>products.php" class="btn btn-primary btn-large">
                    <i class="fas fa-shopping-bag"></i> Shop Now
                </a>
            </div>
        </div>
        
    </div>
</section>

<!-- ============================================
     ABOUT PAGE STYLES - ALL INCLUDED HERE
     ============================================ -->
<style>
/* ============================================
   ABOUT PAGE - COMPLETE STYLES
   ============================================ */

.about-page {
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
   OUR STORY
   ============================================ */

.about-story {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    margin-bottom: 50px;
    align-items: center;
}

.story-content h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
    font-family: 'Inter', sans-serif;
}

.story-content p {
    color: #4a5568;
    line-height: 1.8;
    margin-bottom: 16px;
    font-size: 15px;
    font-family: 'Inter', sans-serif;
}

.story-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #f0f0f0;
}

.stat-item {
    text-align: center;
}

.stat-item .stat-number {
    display: block;
    font-size: 24px;
    font-weight: 800;
    color: #2C3E8F;
    font-family: 'Inter', sans-serif;
}

.stat-item .stat-label {
    font-size: 13px;
    color: #6c757d;
    font-family: 'Inter', sans-serif;
}

.story-image .image-placeholder {
    background: linear-gradient(135deg, #2C3E8F, #4A6CCF);
    border-radius: 12px;
    padding: 60px 40px;
    text-align: center;
    color: white;
    min-height: 250px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.story-image .image-placeholder i {
    font-size: 64px;
    margin-bottom: 16px;
    color: white;
}

.story-image .image-placeholder span {
    font-size: 24px;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   MISSION & VALUES
   ============================================ */

.about-mission {
    margin-bottom: 50px;
    text-align: center;
}

.about-mission h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 12px;
    font-family: 'Inter', sans-serif;
}

.about-mission .mission-text {
    max-width: 700px;
    margin: 0 auto 40px;
    color: #6c757d;
    font-size: 18px;
    font-family: 'Inter', sans-serif;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.value-card {
    background: white;
    padding: 30px 20px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    transition: all 0.3s ease;
    text-align: center;
}

.value-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.1);
    border-color: #2C3E8F;
}

.value-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 12px;
    background: #e8edf9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #2C3E8F;
}

.value-card h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 8px;
    font-family: 'Inter', sans-serif;
}

.value-card p {
    font-size: 14px;
    color: #6c757d;
    line-height: 1.6;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   WHAT WE OFFER
   ============================================ */

.about-offer {
    margin-bottom: 50px;
    text-align: center;
}

.about-offer h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 30px;
    font-family: 'Inter', sans-serif;
}

.offer-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.offer-item {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    transition: all 0.3s ease;
    text-align: center;
}

.offer-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    border-color: #2C3E8F;
}

.offer-item i {
    font-size: 36px;
    color: #2C3E8F;
    margin-bottom: 12px;
}

.offer-item h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 6px;
    font-family: 'Inter', sans-serif;
}

.offer-item p {
    font-size: 14px;
    color: #6c757d;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   TEAM
   ============================================ */

.about-team {
    margin-bottom: 50px;
    text-align: center;
}

.about-team h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 4px;
    font-family: 'Inter', sans-serif;
}

.about-team .team-subtitle {
    color: #6c757d;
    margin-bottom: 30px;
    font-family: 'Inter', sans-serif;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.team-member {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    transition: all 0.3s ease;
    text-align: center;
}

.team-member:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    border-color: #2C3E8F;
}

.member-avatar i {
    font-size: 64px;
    color: #2C3E8F;
    margin-bottom: 8px;
}

.team-member h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 2px;
    font-family: 'Inter', sans-serif;
}

.team-member .member-role {
    font-size: 13px;
    color: #2C3E8F;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
}

.team-member p {
    font-size: 13px;
    color: #6c757d;
    margin-top: 8px;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   TESTIMONIALS
   ============================================ */

.about-testimonials {
    margin-bottom: 50px;
    text-align: center;
}

.about-testimonials h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 30px;
    font-family: 'Inter', sans-serif;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.testimonial {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #eef2f7;
    text-align: left;
}

.testimonial-stars {
    color: #f39c12;
    margin-bottom: 8px;
}

.testimonial-stars i {
    font-size: 14px;
}

.testimonial p {
    font-size: 14px;
    color: #4a5568;
    line-height: 1.6;
    margin-bottom: 12px;
    font-family: 'Inter', sans-serif;
}

.testimonial-author {
    border-top: 1px solid #f0f0f0;
    padding-top: 12px;
}

.testimonial-author strong {
    display: block;
    font-size: 14px;
    color: #1a1a2e;
    font-family: 'Inter', sans-serif;
}

.testimonial-author span {
    font-size: 12px;
    color: #6c757d;
    font-family: 'Inter', sans-serif;
}

/* ============================================
   CTA
   ============================================ */

.about-cta {
    background: linear-gradient(135deg, #2C3E8F, #4A6CCF);
    border-radius: 16px;
    padding: 60px 40px;
    text-align: center;
    color: white;
}

.cta-content h2 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    font-family: 'Inter', sans-serif;
}

.cta-content p {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 24px;
    font-family: 'Inter', sans-serif;
}

.about-cta .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: white;
    color: #2C3E8F;
    padding: 14px 36px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
}

.about-cta .btn:hover {
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
}

.about-cta .btn i {
    color: #2C3E8F;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1024px) {
    .values-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .offer-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .team-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .testimonials-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .about-story {
        grid-template-columns: 1fr;
        padding: 24px;
    }
    .story-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .values-grid {
        grid-template-columns: 1fr;
    }
    .offer-grid {
        grid-template-columns: 1fr;
    }
    .team-grid {
        grid-template-columns: 1fr 1fr;
    }
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
    .page-header h1 {
        font-size: 28px;
    }
    .story-image .image-placeholder {
        min-height: 180px;
        padding: 40px 20px;
    }
    .story-image .image-placeholder i {
        font-size: 48px;
    }
}

@media (max-width: 480px) {
    .team-grid {
        grid-template-columns: 1fr;
    }
    .story-stats {
        grid-template-columns: 1fr 1fr;
    }
    .about-cta {
        padding: 40px 20px;
    }
    .about-cta h2 {
        font-size: 22px;
    }
    .page-header h1 {
        font-size: 24px;
    }
}
</style>

<?php
// Include footer
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'footer.php';
?>