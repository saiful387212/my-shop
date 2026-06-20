// ============================================================
// FILE: public/assets/js/script.js
// PURPOSE: Main JavaScript functionality for My Shop
// ============================================================

/**
 * Wait for DOM to be fully loaded before executing
 * This ensures all HTML elements are available
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // 1. MOBILE MENU TOGGLE
    // ============================================
    
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navbar = document.querySelector('.navbar');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            // Toggle active class on button (changes to X icon)
            this.classList.toggle('active');
            // Toggle navbar visibility
            navbar.classList.toggle('active');
            // Update accessibility attribute
            const isExpanded = this.classList.contains('active');
            this.setAttribute('aria-expanded', isExpanded);
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const isClickInside = mobileToggle.contains(event.target) || 
                             (navbar && navbar.contains(event.target));
        
        if (!isClickInside && navbar && navbar.classList.contains('active')) {
            navbar.classList.remove('active');
            mobileToggle.classList.remove('active');
            mobileToggle.setAttribute('aria-expanded', 'false');
        }
    });
    
    // ============================================
    // 2. ADD TO CART FUNCTIONALITY
    // ============================================
    
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get product data from data attributes
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            const productPrice = parseFloat(this.getAttribute('data-price'));
            
            // Call the add to cart function
            addToCart(productId, productName, productPrice);
            
            // Visual feedback: change button text temporarily
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Added!';
            this.classList.add('btn-success');
            
            // Reset button after 2 seconds
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.remove('btn-success');
            }, 2000);
        });
    });
    
    /**
     * Add item to cart (AJAX)
     * @param {number} productId - ID of the product
     * @param {string} productName - Name of the product
     * @param {number} productPrice - Price of the product
     */
    function addToCart(productId, productName, productPrice) {
        // In a real application, you would send an AJAX request
        // to your server to add the item to the cart
        
        console.log('Added to cart:', {
            id: productId,
            name: productName,
            price: productPrice,
            quantity: 1
        });
        
        // Update cart count (demo purposes)
        const cartCount = document.getElementById('cartCount');
        if (cartCount) {
            let currentCount = parseInt(cartCount.textContent) || 0;
            currentCount += 1;
            cartCount.textContent = currentCount;
            
            // Add animation to cart icon
            const cartIcon = document.querySelector('.cart-icon');
            if (cartIcon) {
                cartIcon.classList.add('bounce');
                setTimeout(() => {
                    cartIcon.classList.remove('bounce');
                }, 500);
            }
        }
        
        // Show a toast notification (if you have a toast system)
        showNotification(`${productName} added to cart!`, 'success');
    }
    
    // ============================================
    // 3. QUICK VIEW BUTTONS
    // ============================================
    
    const quickViewButtons = document.querySelectorAll('.quick-view');
    
    quickViewButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            // In a real app, you'd open a modal with product details
            showNotification(`Quick view for product #${productId}`, 'info');
        });
    });
    
    // ============================================
    // 4. WISHLIST BUTTONS
    // ============================================
    
    const wishlistButtons = document.querySelectorAll('.add-to-wishlist');
    
    wishlistButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            
            // Toggle heart color
            const icon = this.querySelector('i');
            icon.classList.toggle('fas');
            icon.classList.toggle('far');
            icon.style.color = icon.classList.contains('fas') ? '#E74C3C' : '';
            
            // Show notification
            const action = icon.classList.contains('fas') ? 'added to' : 'removed from';
            showNotification(`Product ${action} wishlist`, 'info');
        });
    });
    
    // ============================================
    // 5. NOTIFICATION SYSTEM
    // ============================================
    
    /**
     * Show a notification toast
     * @param {string} message - The notification message
     * @param {string} type - 'success', 'error', 'info', 'warning'
     */
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span class="notification-icon">
                <i class="fas ${getIconForType(type)}"></i>
            </span>
            <span class="notification-message">${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        // Add to body
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', function() {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }
    
    /**
     * Get icon for notification type
     * @param {string} type - Notification type
     * @returns {string} - Font Awesome icon class
     */
    function getIconForType(type) {
        const icons = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        return icons[type] || 'fa-info-circle';
    }
    
    // ============================================
    // 6. SEARCH BAR ENHANCEMENT
    // ============================================
    
    const searchForm = document.querySelector('.search-bar form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const input = this.querySelector('input[type="text"]');
            const query = input.value.trim();
            
            if (query === '') {
                e.preventDefault();
                showNotification('Please enter a search term', 'warning');
                input.focus();
                input.style.borderColor = '#E74C3C';
                setTimeout(() => {
                    input.style.borderColor = '';
                }, 2000);
            }
        });
    }
    
    // ============================================
    // 7. SMOOTH SCROLL FOR ANCHOR LINKS
    // ============================================
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            
            // Skip if it's just "#" or empty
            if (targetId === '#' || targetId === '') return;
            
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // ============================================
    // 8. LAZY LOAD IMAGES (if not using native lazy)
    // ============================================
    
    // Native lazy loading is already in the HTML with loading="lazy"
    // This is a fallback for older browsers
    
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        
        const imageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.getAttribute('src');
                    img.classList.add('loaded');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    }
    
    // ============================================
    // 9. PRODUCT CARD HOVER EFFECTS (Enhanced)
    // ============================================
    
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            // Add a subtle glow effect
            this.style.boxShadow = 'var(--shadow-hover)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = 'var(--shadow-sm)';
        });
    });
    
    // ============================================
    // 10. CART COUNT UPDATE (Real-time)
    // ============================================
    
    /**
     * Fetch and update cart count from server
     * In a real app, you'd fetch this from your API
     */
    function updateCartCount() {
        // This is a placeholder - in a real app, you'd make an AJAX call
        // to get the current cart count from the server
        
        // Example of what the AJAX call would look like:
        /*
        fetch('api/cart/count.php')
            .then(response => response.json())
            .then(data => {
                const cartCount = document.getElementById('cartCount');
                if (cartCount) {
                    cartCount.textContent = data.count;
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
        */
    }
    
    // Update cart count every 30 seconds (for demo purposes)
    // In production, update when items are added/removed
    setInterval(updateCartCount, 30000);
    
    // ============================================
    // 11. KEYBOARD NAVIGATION SUPPORT
    // ============================================
    
    // Make the user dropdown accessible via keyboard
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        userMenu.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const dropdown = this.querySelector('.user-dropdown');
                if (dropdown) {
                    dropdown.style.opacity = '0';
                    dropdown.style.visibility = 'hidden';
                }
            }
        });
    }
    
    // ============================================
    // 12. CONSOLE WELCOME (For developers)
    // ============================================
    
    console.log('%c My Shop E-commerce ', 'background: #2C3E8F; color: white; font-size: 20px; font-weight: bold; padding: 10px; border-radius: 5px;');
    console.log('%c Built with ❤️ for developers ', 'background: #f0f0f0; color: #333; font-size: 14px; padding: 5px;');
    console.log('%c📦 Check out our products! ', 'color: #2C3E8F; font-size: 14px;');
    
    // ============================================
    // 13. ADD CSS FOR NOTIFICATIONS (Dynamic)
    // ============================================
    
    // Add notification styles dynamically
    const notificationStyles = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 99999;
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 400px;
            min-width: 300px;
            border-left: 4px solid #2C3E8F;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification-success {
            border-left-color: #27AE60;
        }
        
        .notification-error {
            border-left-color: #E74C3C;
        }
        
        .notification-warning {
            border-left-color: #F39C12;
        }
        
        .notification-info {
            border-left-color: #3498DB;
        }
        
        .notification-icon {
            font-size: 1.5rem;
            color: #2C3E8F;
        }
        
        .notification-success .notification-icon {
            color: #27AE60;
        }
        
        .notification-error .notification-icon {
            color: #E74C3C;
        }
        
        .notification-warning .notification-icon {
            color: #F39C12;
        }
        
        .notification-info .notification-icon {
            color: #3498DB;
        }
        
        .notification-message {
            flex: 1;
            font-size: 0.95rem;
            color: #333;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #999;
            padding: 0 4px;
            transition: color 0.2s;
        }
        
        .notification-close:hover {
            color: #333;
        }
        
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }
        
        .bounce {
            animation: bounce 0.3s ease;
        }
        
        .btn-success {
            background: #27AE60 !important;
            border-color: #27AE60 !important;
            color: white !important;
        }
        
        .btn-success:hover {
            background: #219A52 !important;
            border-color: #219A52 !important;
        }
        
        @media (max-width: 480px) {
            .notification {
                top: 10px;
                right: 10px;
                left: 10px;
                min-width: auto;
                max-width: none;
                padding: 14px 16px;
            }
        }
    `;
    
    // Add the styles to the page
    const styleSheet = document.createElement('style');
    styleSheet.textContent = notificationStyles;
    document.head.appendChild(styleSheet);
    
    console.log('✅ My Shop is ready!');
});