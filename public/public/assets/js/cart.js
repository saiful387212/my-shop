// ============================================================
// FILE: public/assets/js/cart.js
// PURPOSE: Shopping cart functionality
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    console.log('✅ cart.js loaded successfully!');
    
    // ============================================
    // ADD TO CART FUNCTIONALITY
    // ============================================
    
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    console.log('Found ' + addToCartButtons.length + ' add to cart buttons');
    
    addToCartButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get product data from button attributes
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            const productPrice = this.getAttribute('data-price');
            const productImage = this.getAttribute('data-image') || '';
            
            console.log('Adding to cart:', {
                id: productId,
                name: productName,
                price: productPrice
            });
            
            // ============================================
            // SEND AJAX REQUEST TO ADD TO CART
            // ============================================
            const url = 'cart.php?action=add&id=' + productId + 
                       '&name=' + encodeURIComponent(productName) + 
                       '&price=' + productPrice + 
                       '&image=' + encodeURIComponent(productImage) + 
                       '&ajax=1';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data);
                    
                    if (data.success) {
                        // Update cart count in header
                        const cartCount = document.getElementById('cartCount');
                        if (cartCount) {
                            cartCount.textContent = data.cart_count;
                        }
                        
                        // Show success feedback on button
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check"></i> Added!';
                        this.style.background = '#27ae60';
                        this.style.color = 'white';
                        
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.style.background = '';
                            this.style.color = '';
                        }, 2000);
                        
                        // Show notification
                        showNotification('✅ ' + productName + ' added to cart!', 'success');
                    } else {
                        showNotification('❌ Error adding to cart', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('❌ Error adding to cart', 'error');
                });
        });
    });
    
    // ============================================
    // NOTIFICATION SYSTEM
    // ============================================
    
    function showNotification(message, type) {
        // Remove existing notifications
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
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
        
        notification.querySelector('.notification-close').addEventListener('click', function() {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }
    
    // ============================================
    // ADD NOTIFICATION STYLES
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
        }
        
        .notification-toast.show {
            transform: translateX(0);
        }
        
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
        
        .notification-success .notification-content {
            border-left-color: #27ae60;
        }
        
        .notification-error .notification-content {
            border-left-color: #e74c3c;
        }
        
        .notification-content span {
            flex: 1;
            font-size: 14px;
            font-weight: 500;
            color: #1a1a2e;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .notification-close:hover {
            color: #1a1a2e;
        }
    `;
    document.head.appendChild(style);
    
    console.log('✅ Cart functionality ready!');
});