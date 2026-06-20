// ============================================================
// FILE: public/assets/js/cart.js
// PURPOSE: Shopping cart interactivity
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // QUANTITY CONTROL
    // ============================================
    
    const quantityInputs = document.querySelectorAll('.qty-input');
    const decreaseBtns = document.querySelectorAll('.decrease-qty');
    const increaseBtns = document.querySelectorAll('.increase-qty');
    
    /**
     * Update cart item quantity
     */
    function updateCartQuantity(productId, quantity) {
        // Show loading state
        const item = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
        if (item) {
            item.style.opacity = '0.6';
        }
        
        // Send AJAX request
        fetch('cart.php?action=update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update subtotal for this item
                const subtotalElement = document.querySelector(`.item-subtotal[data-id="${productId}"]`);
                if (subtotalElement) {
                    subtotalElement.textContent = '$' + data.item_subtotal.toFixed(2);
                }
                
                // Update cart totals
                document.getElementById('subtotalDisplay').textContent = '$' + data.cart_subtotal.toFixed(2);
                
                // Update shipping
                updateShipping(data.cart_subtotal);
                
                // Update total
                updateTotal(data.cart_subtotal);
                
                // Update cart count in header (if exists)
                updateCartCount(data.cart_total);
            }
            
            // Remove loading state
            if (item) {
                item.style.opacity = '1';
            }
        })
        .catch(error => {
            console.error('Error updating cart:', error);
            if (item) {
                item.style.opacity = '1';
            }
        });
    }
    
    /**
     * Update shipping display
     */
    function updateShipping(subtotal) {
        const shippingDisplay = document.getElementById('shippingDisplay');
        const freeShippingThreshold = 50;
        
        if (subtotal >= freeShippingThreshold) {
            shippingDisplay.innerHTML = '<span class="free-shipping">FREE</span>';
            document.querySelector('.free-shipping-notice')?.remove();
        } else if (subtotal > 0) {
            const shipping = 5.00;
            shippingDisplay.textContent = '$' + shipping.toFixed(2);
            
            // Add free shipping notice if not exists
            if (!document.querySelector('.free-shipping-notice')) {
                const notice = document.createElement('div');
                notice.className = 'free-shipping-notice';
                notice.innerHTML = `
                    <i class="fas fa-truck"></i>
                    Add $${(freeShippingThreshold - subtotal).toFixed(2)} more for free shipping!
                `;
                document.querySelector('.summary-row.total').before(notice);
            } else {
                const notice = document.querySelector('.free-shipping-notice');
                notice.innerHTML = `
                    <i class="fas fa-truck"></i>
                    Add $${(freeShippingThreshold - subtotal).toFixed(2)} more for free shipping!
                `;
            }
        } else {
            shippingDisplay.textContent = '$0.00';
            document.querySelector('.free-shipping-notice')?.remove();
        }
    }
    
    /**
     * Update total
     */
    function updateTotal(subtotal) {
        const totalDisplay = document.getElementById('totalDisplay');
        const freeShippingThreshold = 50;
        let total = subtotal;
        
        if (subtotal > 0 && subtotal < freeShippingThreshold) {
            total += 5.00;
        }
        
        totalDisplay.textContent = '$' + total.toFixed(2);
    }
    
    /**
     * Update cart count in header
     */
    function updateCartCount(count) {
        const cartCount = document.getElementById('cartCount');
        if (cartCount) {
            cartCount.textContent = count;
        }
    }
    
    // Decrease quantity
    decreaseBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const input = document.querySelector(`.qty-input[data-id="${productId}"]`);
            if (input) {
                let currentValue = parseInt(input.value) || 1;
                if (currentValue > 1) {
                    currentValue--;
                    input.value = currentValue;
                    updateCartQuantity(productId, currentValue);
                }
            }
        });
    });
    
    // Increase quantity
    increaseBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const input = document.querySelector(`.qty-input[data-id="${productId}"]`);
            if (input) {
                let currentValue = parseInt(input.value) || 1;
                if (currentValue < 99) {
                    currentValue++;
                    input.value = currentValue;
                    updateCartQuantity(productId, currentValue);
                }
            }
        });
    });
    
    // Manual quantity input change
    quantityInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-id');
            let value = parseInt(this.value) || 1;
            
            if (value < 1) value = 1;
            if (value > 99) value = 99;
            
            this.value = value;
            updateCartQuantity(productId, value);
        });
        
        // Prevent invalid input
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                this.blur();
            }
        });
    });
    
    // ============================================
    // ADD TO CART (from product pages)
    // ============================================
    
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            const productPrice = this.getAttribute('data-price');
            const productImage = this.getAttribute('data-image') || '';
            
            // Add to cart via AJAX
            fetch(`cart.php?action=add&id=${productId}&name=${encodeURIComponent(productName)}&price=${productPrice}&image=${encodeURIComponent(productImage)}&ajax=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show notification
                        showNotification(data.message, 'success');
                        
                        // Update cart count
                        updateCartCount(data.cart_count);
                        
                        // Animate cart icon
                        const cartIcon = document.querySelector('.cart-icon');
                        if (cartIcon) {
                            cartIcon.classList.add('bounce');
                            setTimeout(() => {
                                cartIcon.classList.remove('bounce');
                            }, 500);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error adding to cart:', error);
                    showNotification('Error adding item to cart.', 'error');
                });
        });
    });
    
    // ============================================
    // NOTIFICATION SYSTEM
    // ============================================
    
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelector('.notification-toast');
        if (existing) {
            existing.remove();
        }
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `notification-toast notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Show with animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Auto dismiss
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
    
    // ============================================
    // NOTIFICATION STYLES (dynamic)
    // ============================================
    
    const notificationStyles = document.createElement('style');
    notificationStyles.textContent = `
        .notification-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 400px;
            min-width: 280px;
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
        
        .notification-success i {
            color: #27ae60;
        }
        
        .notification-error i {
            color: #e74c3c;
        }
        
        .notification-content i {
            font-size: 24px;
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
            padding: 0 4px;
        }
        
        .notification-close:hover {
            color: #1a1a2e;
        }
        
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .bounce {
            animation: bounce 0.4s ease;
        }
        
        @media (max-width: 480px) {
            .notification-toast {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
                min-width: unset;
            }
        }
    `;
    document.head.appendChild(notificationStyles);
    
    console.log('✅ Cart functionality loaded successfully!');
});