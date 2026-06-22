// ============================================================
// FILE: public/assets/js/cart.js
// PURPOSE: Shopping cart functionality - FIXED
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    console.log('✅ cart.js loaded successfully!');
    
    // ============================================
    // ADD TO CART - USING EVENT DELEGATION
    // ============================================
    
    // Track ongoing requests to prevent duplicates
    const pendingRequests = {};
    
    document.addEventListener('click', function(e) {
        const button = e.target.closest('.add-to-cart');
        
        if (button) {
            e.preventDefault();
            e.stopPropagation();
            
            // Check if already processing
            const productId = button.getAttribute('data-id');
            if (pendingRequests[productId]) {
                console.log('⏳ Already processing product ' + productId);
                return;
            }
            
            console.log('🛒 Add to Cart clicked!');
            handleAddToCart(button);
            return;
        }

        const decreaseButton = e.target.closest('.decrease-qty');
        if (decreaseButton) {
            e.preventDefault();
            const productId = decreaseButton.getAttribute('data-id');
            updateQuantity(productId, -1);
            return;
        }

        const increaseButton = e.target.closest('.increase-qty');
        if (increaseButton) {
            e.preventDefault();
            const productId = increaseButton.getAttribute('data-id');
            updateQuantity(productId, 1);
        }
    });

    document.addEventListener('change', function(e) {
        const input = e.target.closest('.qty-input');
        if (!input) return;

        const productId = input.getAttribute('data-id');
        const quantity = parseInt(input.value, 10);
        if (!productId || Number.isNaN(quantity) || quantity < 1) {
            return;
        }

        updateQuantity(productId, quantity, true);
    });
    
    function handleAddToCart(button) {
        // Get product data from button
        const productId = button.getAttribute('data-id');
        const productName = button.getAttribute('data-name');
        const productPrice = button.getAttribute('data-price');
        const productImage = button.getAttribute('data-image') || '';
        
        console.log('📦 Product:', { id: productId, name: productName, price: productPrice });
        
        if (!productId || !productName || !productPrice) {
            showNotification('❌ Error: Missing product information', 'error');
            return;
        }
        
        // Mark as pending
        pendingRequests[productId] = true;
        
        // Show loading state
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        button.disabled = true;
        
        // Send AJAX request
        const url = 'cart.php?action=add&id=' + productId + 
                   '&name=' + encodeURIComponent(productName) + 
                   '&price=' + productPrice + 
                   '&image=' + encodeURIComponent(productImage) + 
                   '&ajax=1';
        
        console.log('📡 Sending request to:', url);
        
        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                console.log('📨 Response:', data);
                
                if (data.success) {
                    updateCartCount(data.cart_count);
                    
                    button.innerHTML = '<i class="fas fa-check"></i> Added!';
                    button.style.background = '#27ae60';
                    button.style.color = 'white';
                    button.style.borderColor = '#27ae60';
                    
                    showNotification('✅ ' + productName + ' added to cart!', 'success');
                    animateCartIcon();
                    
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.style.background = '';
                        button.style.color = '';
                        button.style.borderColor = '';
                        button.disabled = false;
                        // Remove from pending
                        delete pendingRequests[productId];
                    }, 1500);
                } else {
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                    delete pendingRequests[productId];
                    showNotification('❌ Error adding to cart', 'error');
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                button.innerHTML = originalHTML;
                button.disabled = false;
                delete pendingRequests[productId];
                showNotification('❌ Error adding to cart', 'error');
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
        }
    }

    function updateQuantity(productId, deltaOrValue, isExact = false) {
        if (!productId) return;

        const input = document.querySelector(`.qty-input[data-id="${productId}"]`);
        if (!input) return;

        const currentValue = parseInt(input.value, 10) || 1;
        const nextQuantity = isExact
            ? parseInt(deltaOrValue, 10)
            : Math.max(0, currentValue + deltaOrValue);

        if (Number.isNaN(nextQuantity) || nextQuantity < 0) {
            return;
        }

        const body = new URLSearchParams();
        body.append('id', productId);
        body.append('quantity', nextQuantity);

        fetch('cart.php?action=update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: body.toString()
        })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                showNotification('❌ Unable to update quantity', 'error');
                return;
            }

            if (nextQuantity <= 0) {
                const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                if (cartItem) {
                    cartItem.remove();
                }
            } else {
                input.value = nextQuantity;
                const subtotal = document.querySelector(`.item-subtotal[data-id="${productId}"]`);
                if (subtotal && typeof data.item_subtotal !== 'undefined') {
                    subtotal.textContent = '$' + Number(data.item_subtotal).toFixed(2);
                }
            }

            updateCartCount(data.cart_total);
            updateCartSummary(data.cart_subtotal);
        })
        .catch(error => {
            console.error('❌ Error updating quantity:', error);
            showNotification('❌ Unable to update quantity', 'error');
        });
    }

    function updateCartSummary(subtotal) {
        const subtotalDisplay = document.getElementById('subtotalDisplay');
        const shippingDisplay = document.getElementById('shippingDisplay');
        const totalDisplay = document.getElementById('totalDisplay');
        const pageHeader = document.querySelector('.page-header p');

        if (subtotalDisplay) {
            subtotalDisplay.textContent = '$' + Number(subtotal || 0).toFixed(2);
        }

        const shipping = Number(subtotal || 0) > 0 && Number(subtotal || 0) < 50
            ? 5
            : 0;

        if (shippingDisplay) {
            if (shipping > 0) {
                shippingDisplay.textContent = '$' + shipping.toFixed(2);
            } else if (Number(subtotal || 0) > 0) {
                shippingDisplay.innerHTML = '<span class="free-shipping">FREE</span>';
            } else {
                shippingDisplay.textContent = '$0.00';
            }
        }

        if (totalDisplay) {
            totalDisplay.textContent = '$' + (Number(subtotal || 0) + shipping).toFixed(2);
        }

        if (pageHeader) {
            const items = document.querySelectorAll('.cart-item').length;
            pageHeader.textContent = items + ' items in your cart';
        }
    }
    
    // ============================================
    // ANIMATE CART ICON
    // ============================================
    
    function animateCartIcon() {
        const cartIcon = document.querySelector('.cart-icon');
        if (cartIcon) {
            cartIcon.classList.add('bounce');
            setTimeout(() => {
                cartIcon.classList.remove('bounce');
            }, 500);
        }
    }
    
    // ============================================
    // NOTIFICATION SYSTEM
    // ============================================
    
    function showNotification(message, type = 'info') {
        const existing = document.querySelector('.notification-toast');
        if (existing) existing.remove();
        
        const notification = document.createElement('div');
        notification.className = 'notification-toast notification-' + type;
        notification.innerHTML = `
            <div class="notification-content">
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
        
        notification.querySelector('.notification-close').addEventListener('click', function() {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
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