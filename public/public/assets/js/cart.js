// ============================================================
// FILE: public/assets/js/cart.js
// PURPOSE: Shopping cart functionality - COMPLETE FIX
// ============================================================

// ============================================
// PREVENT MULTIPLE INITIALIZATION
// ============================================

// Check if cart.js is already initialized
if (typeof window.cartInitialized === 'undefined') {
    window.cartInitialized = false;
}

if (!window.cartInitialized) {
    window.cartInitialized = true;

    document.addEventListener('DOMContentLoaded', function() {
        
        console.log('✅ cart.js loaded successfully!');
        
        // ============================================
        // TRACK PENDING REQUESTS
        // ============================================
        
        const pendingRequests = {};
        let isProcessing = false;
        
        // ============================================
        // ADD TO CART - SINGLE EVENT LISTENER
        // ============================================
        
        // Remove any existing listeners by using a single delegated listener
        document.removeEventListener('click', handleCartClick);
        document.addEventListener('click', handleCartClick);
        
        function handleCartClick(e) {
            // Find if clicked element or its parent has class 'add-to-cart'
            const button = e.target.closest('.add-to-cart');
            
            if (!button) return;
            
            // Prevent any default behavior
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // Prevent multiple clicks on same button
            if (button.dataset.processing === 'true') {
                console.log('⏳ Already processing this button');
                return;
            }
            
            const productId = button.getAttribute('data-id');
            
            // Check if already processing this product
            if (pendingRequests[productId]) {
                console.log('⏳ Already processing product ' + productId);
                return;
            }
            
            // Check if global processing lock
            if (isProcessing) {
                console.log('⏳ Another request is in progress');
                return;
            }
            
            console.log('🛒 Add to Cart clicked for product: ' + productId);
            
            // Lock processing
            isProcessing = true;
            button.dataset.processing = 'true';
            pendingRequests[productId] = true;
            
            // Call add to cart function
            addToCart(button, productId);
        }
        
        function addToCart(button, productId) {
            // Get product data
            const productName = button.getAttribute('data-name') || 'Product';
            const productPrice = button.getAttribute('data-price') || '0';
            const productImage = button.getAttribute('data-image') || '';
            
            console.log('📦 Adding product:', {
                id: productId,
                name: productName,
                price: productPrice
            });
            
            // Show loading state
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            button.disabled = true;
            
            // Build URL with proper encoding
            const url = 'cart.php?action=add&id=' + encodeURIComponent(productId) + 
                       '&name=' + encodeURIComponent(productName) + 
                       '&price=' + encodeURIComponent(productPrice) + 
                       '&image=' + encodeURIComponent(productImage) + 
                       '&ajax=1';
            
            console.log('📡 Sending request:', url);
            
            // Send AJAX request
            fetch(url, {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('📨 Response received:', data);
                
                if (data.success) {
                    // Update cart count
                    updateCartCount(data.cart_count);
                    
                    // Show success feedback
                    button.innerHTML = '<i class="fas fa-check"></i> Added!';
                    button.style.background = '#27ae60';
                    button.style.color = 'white';
                    button.style.borderColor = '#27ae60';
                    
                    // Show notification
                    showNotification('✅ ' + productName + ' added to cart!', 'success');
                    
                    // Animate cart icon
                    animateCartIcon();
                    
                    // Reset button after delay
                    setTimeout(function() {
                        button.innerHTML = originalHTML;
                        button.style.background = '';
                        button.style.color = '';
                        button.style.borderColor = '';
                        button.disabled = false;
                        button.dataset.processing = 'false';
                        delete pendingRequests[productId];
                        isProcessing = false;
                    }, 1500);
                    
                } else {
                    // Error response from server
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                    button.dataset.processing = 'false';
                    delete pendingRequests[productId];
                    isProcessing = false;
                    showNotification('❌ Error adding to cart: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('❌ Fetch error:', error);
                button.innerHTML = originalHTML;
                button.disabled = false;
                button.dataset.processing = 'false';
                delete pendingRequests[productId];
                isProcessing = false;
                showNotification('❌ Network error. Please try again.', 'error');
            });
        }
        
        // ============================================
        // UPDATE CART COUNT
        // ============================================
        
        function updateCartCount(count) {
            const cartCount = document.getElementById('cartCount');
            if (cartCount) {
                cartCount.textContent = count;
                console.log('📊 Cart count updated to:', count);
            } else {
                console.warn('⚠️ Cart count element not found!');
            }
        }
        
        // ============================================
        // ANIMATE CART ICON
        // ============================================
        
        function animateCartIcon() {
            const cartIcon = document.querySelector('.cart-icon');
            if (cartIcon) {
                cartIcon.classList.add('bounce');
                setTimeout(function() {
                    cartIcon.classList.remove('bounce');
                }, 500);
            }
        }
        
        // ============================================
        // NOTIFICATION SYSTEM
        // ============================================
        
        function showNotification(message, type) {
            // Remove existing notification
            const existing = document.querySelector('.notification-toast');
            if (existing) {
                existing.remove();
            }
            
            // Create notification
            const notification = document.createElement('div');
            notification.className = 'notification-toast notification-' + type;
            notification.innerHTML = `
                <div class="notification-content">
                    <span>${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Show with animation
            setTimeout(function() {
                notification.classList.add('show');
            }, 50);
            
            // Auto dismiss after 3 seconds
            setTimeout(function() {
                notification.classList.remove('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 3000);
            
            // Close button
            notification.querySelector('.notification-close').addEventListener('click', function() {
                notification.classList.remove('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            });
        }
        
        // ============================================
        // NOTIFICATION STYLES
        // ============================================
        
        const style = document.createElement('style');
        style.textContent = `
            .notification-toast {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 99999;
                transform: translateX(120%);
                transition: transform 0.4s ease;
                max-width: 400px;
                min-width: 280px;
            }
            .notification-toast.show { transform: translateX(0); }
            .notification-content {
                background: white;
                padding: 16px 20px;
                border-radius: 12px;
                box-shadow: 0 8px 30px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                gap: 12px;
                border-left: 4px solid #2C3E8F;
            }
            .notification-success .notification-content { border-left-color: #27ae60; }
            .notification-error .notification-content { border-left-color: #e74c3c; }
            .notification-content span { flex: 1; font-size: 14px; font-weight: 500; color: #1a1a2e; }
            .notification-close { background: none; border: none; font-size: 20px; cursor: pointer; color: #6c757d; }
            .notification-close:hover { color: #1a1a2e; }
            @keyframes bounce {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.2); }
            }
            .bounce { animation: bounce 0.4s ease; }
            @media (max-width: 480px) {
                .notification-toast { top: 10px; right: 10px; left: 10px; max-width: none; min-width: unset; }
            }
        `;
        document.head.appendChild(style);
        
        console.log('✅ Cart functionality ready!');
    });
}