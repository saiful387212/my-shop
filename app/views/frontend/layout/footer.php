<?php
// ============================================================
// FILE: app/views/frontend/layout/footer.php
// PURPOSE: Site footer with working social buttons
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}
?>

    </main>
    <!-- ============================================
         MAIN CONTENT ENDS
         ============================================ -->
    
    <!-- ============================================
         FOOTER
         ============================================ -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                
                <!-- Company Info -->
                <div class="footer-col">
                    <div class="footer-logo">
                        <i class="fas fa-store"></i>
                        <span>My Shop</span>
                    </div>
                    <p class="footer-description">Your one-stop multi-vendor marketplace for quality products.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>products.php">Products</a></li>
                        <li><a href="<?php echo SITE_URL; ?>shops.php">Vendors</a></li>
                        <li><a href="<?php echo SITE_URL; ?>about.php">About</a></li>
                        <li><a href="<?php echo SITE_URL; ?>contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <!-- For Vendors -->
                <div class="footer-col">
                    <h4>For Vendors</h4>
                    <ul>
                        <?php if ($isLoggedIn && isset($userShop) && $userShop): ?>
                            <li><a href="<?php echo SITE_URL; ?>shop/manage.php">My Shop</a></li>
                        <?php elseif ($isLoggedIn): ?>
                            <li><a href="<?php echo SITE_URL; ?>shop/create.php">Open Shop</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo SITE_URL; ?>register.php">Register</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo SITE_URL; ?>terms.php">Terms</a></li>
                        <li><a href="<?php echo SITE_URL; ?>privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Main St</li>
                        <li><i class="fas fa-envelope"></i> info@myshop.com</li>
                        <li><i class="fas fa-phone-alt"></i> +1 (555) 123-4567</li>
                    </ul>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> My Shop. All rights reserved.</p>
                <div class="payment-methods">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-amex"></i>
                    <i class="fab fa-cc-paypal"></i>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- ============================================
         FOOTER FALLBACK STYLES
         ============================================ -->
    <style>
        .footer {
            background: #1a1a2e;
            color: rgba(255, 255, 255, 0.8);
            padding: 40px 0 20px;
            margin-top: 40px;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 12px;
        }
        
        .footer-description {
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.8;
            margin-bottom: 16px;
            max-width: 300px;
        }
        
        .social-links {
            display: flex;
            gap: 12px;
        }
        
        .social-links a {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 16px;
        }
        
        .social-links a:hover {
            background: #2C3E8F;
            transform: translateY(-3px);
        }
        
        .footer-col h4 {
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 8px;
        }
        
        .footer-col ul li a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .footer-col ul li a:hover {
            color: white;
            padding-left: 4px;
        }
        
        .contact-info li {
            display: flex;
            gap: 12px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .contact-info li i {
            color: #2C3E8F;
            margin-top: 4px;
            width: 18px;
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.4);
        }
        
        .payment-methods {
            display: flex;
            gap: 16px;
            font-size: 24px;
        }
        
        .payment-methods i {
            color: rgba(255, 255, 255, 0.4);
            transition: color 0.3s ease;
        }
        
        .payment-methods i:hover {
            color: white;
        }
        
        @media (max-width: 768px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }
            
            .social-links {
                justify-content: center;
            }
        }
    </style>
    
    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script src="<?php echo SITE_URL; ?>assets/js/cart.js"></script>
    
    <script>
        // Mobile Menu Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const mainNav = document.getElementById('mainNav');
            
            if (menuToggle && mainNav) {
                menuToggle.addEventListener('click', function() {
                    this.classList.toggle('active');
                    mainNav.classList.toggle('active');
                });
            }
            
            console.log('✅ Footer loaded successfully!');
            console.log('🛒 Cart count: <?php echo $cartCount ?? 0; ?>');
        });
    </script>
    
</body>
</html>