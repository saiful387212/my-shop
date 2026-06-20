<?php
// ============================================================
// FILE: app/views/frontend/layout/footer.php
// ============================================================

if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}
?>

    </main>
    <!-- ============================================
         MAIN CONTENT ENDS HERE
         ============================================ -->
    
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>My Shop</h4>
                    <p>Your one-stop destination for quality products.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>shop.php">Shop</a></li>
                        <li><a href="<?php echo SITE_URL; ?>about.php">About</a></li>
                        <li><a href="<?php echo SITE_URL; ?>contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> My Shop. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo SITE_URL; ?>assets/js/script.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/login.js"></script>
</body>
</html>
<!-- In app/views/frontend/layout/footer.php -->
<script src="<?php echo SITE_URL; ?>assets/js/ui.js"></script>